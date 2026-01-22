<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];
$item_id  = (int)$_POST['item_id'];

mysqli_begin_transaction($conn);

/* VERIFY ITEM */
$itemQ = mysqli_query(
    $conn,
    "SELECT oi.*, o.created_at, o.user_id
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     WHERE oi.id = $item_id
       AND oi.order_id = $order_id
       AND o.user_id = $user_id
       AND (oi.status IS NULL OR oi.status = 'Placed')"
);

if (mysqli_num_rows($itemQ) === 0) {
    mysqli_rollback($conn);
    header("Location: order_details.php?id=$order_id");
    exit;
}

$item = mysqli_fetch_assoc($itemQ);

/* 24 HOUR RULE */
if ((time() - strtotime($item['created_at'])) > 86400) {
    mysqli_rollback($conn);
    header("Location: order_details.php?id=$order_id");
    exit;
}

/* RESTORE STOCK */
mysqli_query(
    $conn,
    "UPDATE product_variants
     SET quantity = quantity + {$item['quantity']}
     WHERE product_id = {$item['product_id']}
       AND size = '{$item['size']}'"
);

/* CANCEL ITEM */
mysqli_query(
    $conn,
    "UPDATE order_items
     SET status = 'Cancelled',
         cancelled_at = NOW()
     WHERE id = $item_id"
);

/* RECALCULATE ORDER TOTAL */
$totalQ = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(price * quantity), 0) AS new_total
     FROM order_items
     WHERE order_id = $order_id
       AND (status IS NULL OR status = 'Placed')"
);

$row = mysqli_fetch_assoc($totalQ);
$newTotal = (int)$row['new_total'];

/* UPDATE ORDER */
if ($newTotal == 0) {
    mysqli_query(
        $conn,
        "UPDATE orders
         SET total_amount = 0,
             status = 'Cancelled',
             cancelled_at = NOW()
         WHERE id = $order_id"
    );
} else {
    mysqli_query(
        $conn,
        "UPDATE orders
         SET total_amount = $newTotal,
             status = 'Partially Cancelled'
         WHERE id = $order_id"
    );
}

mysqli_commit($conn);

/* EMAIL */
require "../mail_config.php";

/* Fetch user */
$userQ = mysqli_query(
    $conn,
    "SELECT u.name, u.email
     FROM users u
     JOIN orders o ON o.user_id = u.id
     WHERE o.id = $order_id"
);
$user = mysqli_fetch_assoc($userQ);

/* Product */
$productQ = mysqli_query(
    $conn,
    "SELECT name FROM products WHERE id = {$item['product_id']}"
);
$product = mysqli_fetch_assoc($productQ);

$subtotal = $item['price'] * $item['quantity'];

$body = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial;background:#f4f6f8;padding:20px;">
<div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

<h2>Hello '.htmlspecialchars($user['name']).',</h2>

<p>The following item from your order <strong>#'.$order_id.'</strong> has been cancelled:</p>

<table width="100%" cellpadding="10" style="border-collapse:collapse;">
<tr style="background:#fdecea;">
<th align="left">Product</th>
<th>Size</th>
<th>Qty</th>
<th align="right">Subtotal</th>
</tr>
<tr>
<td>'.htmlspecialchars($product['name']).'</td>
<td align="center">'.$item['size'].'</td>
<td align="center">'.$item['quantity'].'</td>
<td align="right">₹'.number_format($subtotal).'</td>
</tr>
</table>

<p style="margin-top:15px;">Remaining items (if any) will continue.</p>

<p style="font-size:13px;color:#777;">— Team <strong>ShopNow</strong></p>

</div>
</body>
</html>';

sendMail($user['email'], "Item Cancelled | ShopNow", $body);

/* ADMIN EMAIL (ITEM CANCELLED) */

// Fetch admin who owns this product
$adminQ = mysqli_query(
    $conn,
    "SELECT a.name, a.email
     FROM products p
     JOIN admins a ON p.admin_id = a.id
     WHERE p.id = {$item['product_id']}
     LIMIT 1"
);

if ($admin = mysqli_fetch_assoc($adminQ)) {

    $adminSubtotal = $item['price'] * $item['quantity'];

    $adminBody = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Arial;background:#f4f6f8;padding:20px;">
    <div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

    <h2>Hello '.htmlspecialchars($admin['name'], ENT_QUOTES, "UTF-8").',</h2>

    <p>
    An item from order <strong>#'.$order_id.'</strong> has been
    <span style="color:#d32f2f;font-weight:600;">cancelled by the customer</span>.
    </p>

    <table width="100%" cellpadding="10" style="border-collapse:collapse;">
        <tr style="background:#fdecea;">
            <th align="left">Product</th>
            <th>Size</th>
            <th>Qty</th>
            <th align="right">Subtotal</th>
        </tr>
        <tr>
            <td>'.htmlspecialchars($product['name'], ENT_QUOTES, "UTF-8").'</td>
            <td align="center">'.$item['size'].'</td>
            <td align="center">'.$item['quantity'].'</td>
            <td align="right">₹'.number_format($adminSubtotal).'</td>
        </tr>
    </table>

    <p style="margin-top:15px;">
    Stock has been automatically restored.
    </p>

    <p style="font-size:13px;color:#777;">
    — Team <strong>ShopNow</strong>
    </p>

    </div>
    </body>
    </html>
    ';

    sendMail($admin['email'], "Item Cancelled | Order #$order_id", $adminBody);
}

/* REDIRECT */
header("Location: order_details.php?id=$order_id");
exit;
