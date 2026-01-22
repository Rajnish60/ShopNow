<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

/* ADMIN AUTH */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];
$order_id = (int)$_POST['order_id'];

mysqli_begin_transaction($conn);

/* FETCH ADMIN */
$adminQ = mysqli_query(
    $conn,
    "SELECT name, email FROM admins WHERE id = $admin_id"
);
$admin = mysqli_fetch_assoc($adminQ);

/* VERIFY ORDER */
$orderQ = mysqli_query(
    $conn,
    "SELECT o.*, u.name AS user_name, u.email AS user_email
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = $order_id
       AND o.status IN ('Placed','Partially Cancelled')"
);

if (mysqli_num_rows($orderQ) === 0) {
    mysqli_rollback($conn);
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($orderQ);

/* FETCH ACTIVE ITEMS */
$itemsQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $order_id
       AND (oi.status IS NULL OR oi.status='Placed')"
);

if (mysqli_num_rows($itemsQ) === 0) {
    mysqli_rollback($conn);
    header("Location: orders.php");
    exit;
}

$userItemsHtml = "";
$totalCancelled = 0;

/* CANCEL ITEMS */
while ($item = mysqli_fetch_assoc($itemsQ)) {

    // Restore stock
    mysqli_query(
        $conn,
        "UPDATE product_variants
         SET quantity = quantity + {$item['quantity']}
         WHERE id = {$item['variant_id']}"
    );

    // Cancel item
    mysqli_query(
        $conn,
        "UPDATE order_items
         SET status='Cancelled',
             cancelled_at=NOW()
         WHERE id={$item['id']}"
    );

    $subtotal = $item['price'] * $item['quantity'];
    $totalCancelled += $subtotal;

    $userItemsHtml .= "
        <tr>
            <td>".htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8')."</td>
            <td align='center'>{$item['size']}</td>
            <td align='center'>{$item['quantity']}</td>
            <td align='right'>₹".number_format($subtotal)."</td>
        </tr>
    ";
}

/* UPDATE ORDER */
mysqli_query(
    $conn,
    "UPDATE orders
     SET status='Cancelled',
         total_amount=0,
         cancelled_at=NOW()
     WHERE id=$order_id"
);

mysqli_commit($conn);

/* USER EMAIL */
$userBody = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial;background:#f4f6f8;padding:20px;">
<div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

<h2>Hello '.htmlspecialchars($order['user_name'], ENT_QUOTES, "UTF-8").',</h2>

<p>Your order <strong>#'.$order_id.'</strong> has been cancelled by the admin.</p>

<table width="100%" cellpadding="10" style="border-collapse:collapse;">
<tr style="background:#fdecea;">
<th align="left">Product</th>
<th>Size</th>
<th>Qty</th>
<th align="right">Subtotal</th>
</tr>
'.$userItemsHtml.'
</table>

<h3 style="text-align:right;margin-top:15px;">
Total Cancelled Amount: ₹'.number_format($totalCancelled).'
</h3>

<p style="margin-top:10px;color:#555;">
Reason: You are not available on delivery date.
</p>

<p style="font-size:13px;color:#777;">
— Team <strong>ShopNow</strong>
</p>

</div>
</body>
</html>';

sendMail($order['user_email'], "Order Cancelled | ShopNow", $userBody);

/* ADMIN EMAIL */
$adminBody = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial;background:#f4f6f8;padding:20px;">
<div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

<h2>Order Cancelled Successfully</h2>

<p>
<strong>Order ID:</strong> #'.$order_id.'<br>
<strong>Customer:</strong> '.htmlspecialchars($order['user_name']).'<br>
<strong>Total Cancelled:</strong> ₹'.number_format($totalCancelled).'
</p>

<p>You cancelled this order successfully.</p>

<p style="font-size:13px;color:#777;">
— ShopNow System
</p>

</div>
</body>
</html>';

sendMail($admin['email'], "Order Cancelled | Order #$order_id", $adminBody);

/* REDIRECT */
header("Location: orders.php");
exit;
