<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* REQUEST CHECK */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* GET ITEMS */
if (isset($_SESSION['buy_now'])) {
    $items = [ $_SESSION['buy_now'] ];
    $isBuyNow = true;
} else {
    $items = $_SESSION['cart'] ?? [];
    $isBuyNow = false;
}

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

/* VALIDATE ADDRESS */
$required = ['name','phone','pincode','district','state','locality','address'];
foreach ($required as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        header("Location: checkout.php?error=address");
        exit;
    }
}

$user_id = $_SESSION['user_id'];

/* Build address string */
$address = "
Name: {$_POST['name']}
Phone: {$_POST['phone']}
Alt Phone: ".($_POST['alt_phone'] ?? '')."
Address: {$_POST['address']}
Locality: {$_POST['locality']}
District: {$_POST['district']}
State: {$_POST['state']}
Pincode: {$_POST['pincode']}
Landmark: ".($_POST['landmark'] ?? '')."
";

/* PAYMENT */
$payment_method = $_POST['payment_method'] ?? 'COD';
if ($payment_method !== 'COD') {
    header("Location: checkout.php?error=payment");
    exit;
}

/* CALCULATE TOTAL */
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

/* CREATE ORDER */
$stmt = $conn->prepare(
    "INSERT INTO orders (user_id, total_amount, address, status)
     VALUES (?, ?, ?, 'Placed')"
);
$stmt->bind_param("ids", $user_id, $total, $address);
$stmt->execute();

$order_id = $stmt->insert_id;
if ($order_id <= 0) {
    header("Location: checkout.php?error=order");
    exit;
}

/* ORDER ITEMS + STOCK UPDATE */
foreach ($items as $item) {

    // lock variant
    $vstmt = $conn->prepare(
        "SELECT id, quantity FROM product_variants WHERE id=? FOR UPDATE"
    );
    $vstmt->bind_param("i", $item['variant_id']);
    $vstmt->execute();
    $vres = $vstmt->get_result();
    $variant = $vres->fetch_assoc();

    if (!$variant || $variant['quantity'] < $item['quantity']) {
        header("Location: checkout.php?error=stock");
        exit;
    }

    // insert order item
    $istmt = $conn->prepare(
        "INSERT INTO order_items 
        (order_id, product_id, variant_id, size, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?)"
    );
    $istmt->bind_param(
        "iiisii",
        $order_id,
        $item['product_id'],
        $item['variant_id'],
        $item['size'],
        $item['price'],
        $item['quantity']
    );
    $istmt->execute();

    // reduce stock
    $ustmt = $conn->prepare(
        "UPDATE product_variants 
         SET quantity = quantity - ? 
         WHERE id = ?"
    );
    $ustmt->bind_param("ii", $item['quantity'], $item['variant_id']);
    $ustmt->execute();
}

/* CLEAR CART TABLE (DB) */
if (!$isBuyNow) {
    $stmt = $conn->prepare(
        "DELETE FROM carts WHERE user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}


/* CLEAR SESSION */
if ($isBuyNow) {
    unset($_SESSION['buy_now']);
} else {
    unset($_SESSION['cart']);
}


/* Fetch user */
$userQ = mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id");
$user  = mysqli_fetch_assoc($userQ);

/* Fetch ordered items */
$itemsQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name 
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $order_id"
);

$itemsHtml   = "";
$grandTotal  = 0;

while ($item = mysqli_fetch_assoc($itemsQ)) {

    $productName = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
    $size        = htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8');
    $qty         = (int)$item['quantity'];
    $price       = (int)$item['price'];

    $subtotal = $price * $qty;
    $grandTotal += $subtotal;

    $itemsHtml .= "
        <tr>
            <td>{$productName}</td>
            <td align='center'>{$size}</td>
            <td align='center'>{$qty}</td>
            <td align='right'>₹" . number_format($price) . "</td>
            <td align='right'>₹" . number_format($subtotal) . "</td>
        </tr>
    ";
}

$subject = "Order Placed Successfully | ShopNow";

/* HTML EMAIL BODY (UTF-8 SAFE) */
$body = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Confirmation | ShopNow</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; background:#f4f6f8; padding:20px;">

<div style="max-width:700px; margin:auto; background:#ffffff; padding:25px; border-radius:8px;">

<h2 style="color:#333;">
Hello '.htmlspecialchars($user['name'], ENT_QUOTES, "UTF-8").',
</h2>

<p style="font-size:15px;color:#555;">
Your order <strong>#'.$order_id.'</strong> has been placed successfully.
</p>

<table width="100%" cellpadding="10" cellspacing="0"
       style="border-collapse:collapse;font-size:14px;margin-top:15px;">

<thead>
<tr style="background:#f1f3f6;">
<th align="left">Product</th>
<th align="center">Size</th>
<th align="center">Qty</th>
<th align="right">Price</th>
<th align="right">Subtotal</th>
</tr>
</thead>

<tbody>
'.$itemsHtml.'
</tbody>

</table>

<h3 style="text-align:right;margin-top:20px;color:#000;">
Total Amount: ₹'.number_format($grandTotal).'
</h3>

<p style="margin-top:10px;font-size:14px;">
<strong>Payment Method:</strong> Cash on Delivery
</p>

<p style="font-size:14px;color:#555;">
Your order will be delivered within 7 days.
You can cancel your order within 24 hours.
</p>

<hr style="margin:25px 0;border:none;border-top:1px solid #eee;">

<p style="font-size:13px;color:#777;">
Thank you for shopping with <strong>ShopNow</strong>
</p>

</div>
</body>
</html>
';

/* SEND EMAIL USING PHPMailer */
sendMail($user['email'], $subject, $body);

/* ADMIN EMAILS */

// Fetch admins whose products are in this order
$adminQ = mysqli_query(
    $conn,
    "SELECT DISTINCT a.id, a.name, a.email
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN admins a ON p.admin_id = a.id
     WHERE oi.order_id = $order_id"
);

while ($admin = mysqli_fetch_assoc($adminQ)) {

    // Fetch only this admin's items
    $adminItemsQ = mysqli_query(
        $conn,
        "SELECT oi.*, p.name
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = $order_id
           AND p.admin_id = {$admin['id']}"
    );

    $itemsHtml  = '';
    $adminTotal = 0;

    while ($item = mysqli_fetch_assoc($adminItemsQ)) {
        $subtotal = $item['price'] * $item['quantity'];
        $adminTotal += $subtotal;

        $itemsHtml .= "
            <tr>
                <td>".htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8')."</td>
                <td align='center'>{$item['size']}</td>
                <td align='center'>{$item['quantity']}</td>
                <td align='right'>₹".number_format($subtotal)."</td>
            </tr>
        ";
    }

    $adminSubject = "New Order #$order_id | ShopNow";

    $adminBody = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Arial;background:#f4f6f8;padding:20px;">
    <div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

    <h2>Hello '.htmlspecialchars($admin['name'], ENT_QUOTES, "UTF-8").',</h2>

    <p>A new order <strong>#'.$order_id.'</strong> contains your product(s).</p>

    <table width="100%" cellpadding="10" style="border-collapse:collapse;">
        <tr style="background:#f1f3f6;">
            <th align="left">Product</th>
            <th>Size</th>
            <th>Qty</th>
            <th align="right">Subtotal</th>
        </tr>
        '.$itemsHtml.'
    </table>

    <h3 style="text-align:right;margin-top:15px;">
        Total (Your Items): ₹'.number_format($adminTotal).'
    </h3>

    <p>Please process this order from your admin panel.</p>

    <p style="font-size:13px;color:#777;">
        — Team <strong>ShopNow</strong>
    </p>

    </div>
    </body>
    </html>
    ';

    sendMail($admin['email'], $adminSubject, $adminBody);
}


/* SUCCESS */
header("Location: order_success.php?order_id=$order_id");
exit;
