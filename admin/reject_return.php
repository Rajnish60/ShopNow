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
$item_id  = (int)$_POST['item_id'];
$reason   = trim($_POST['reason']);

if ($reason === '') {
    header("Location: order_details.php?id=$order_id");
    exit;
}

mysqli_begin_transaction($conn);

/* FETCH ADMIN */
$adminQ = mysqli_query(
    $conn,
    "SELECT name, email FROM admins WHERE id = $admin_id"
);
$admin = mysqli_fetch_assoc($adminQ);

/* FETCH ITEM + USER */
$itemQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name AS product_name, u.name AS user_name, u.email AS user_email
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN orders o ON oi.order_id = o.id
     JOIN users u ON o.user_id = u.id
     WHERE oi.id = $item_id
       AND oi.order_id = $order_id
       AND oi.return_status = 'Requested'"
);

if (mysqli_num_rows($itemQ) === 0) {
    mysqli_rollback($conn);
    header("Location: orders.php");
    exit;
}

$item = mysqli_fetch_assoc($itemQ);

/* REJECT RETURN */
mysqli_query(
    $conn,
    "UPDATE order_items
     SET return_status = 'Rejected',
         return_reject_reason = '".mysqli_real_escape_string($conn, $reason)."',
         return_rejected_at = NOW()
     WHERE id = $item_id"
);

mysqli_commit($conn);

/* EMAIL TO USER */
$userBody = "
<h2>Hello {$item['user_name']},</h2>

<p>Your return request for the following item has been
<strong style='color:red;'>rejected</strong>:</p>

<table width='100%' cellpadding='8' cellspacing='0' border='1'>
<tr style='background:#f1f3f6;'>
    <th align='left'>Product</th>
    <th>Size</th>
    <th>Qty</th>
    <th align='right'>Price</th>
</tr>
<tr>
    <td>{$item['product_name']}</td>
    <td align='center'>{$item['size']}</td>
    <td align='center'>{$item['quantity']}</td>
    <td align='right'>₹".number_format($item['price'])."</td>
</tr>
</table>

<p><strong>Rejection Reason:</strong></p>
<p style='background:#f8f9fa;padding:10px;border-left:4px solid red;'>
{$reason}
</p>

<p>If you have questions, please contact support.</p>

<p>— Team <strong>ShopNow</strong></p>
";

sendMail(
    $item['user_email'],
    "Return Rejected | ShopNow",
    $userBody
);

/* EMAIL TO ADMIN */
$adminBody = "
<h2>Hello {$admin['name']},</h2>

<p>You have <strong style='color:red;'>rejected</strong> a return request.</p>

<p>
<strong>Order ID:</strong> #{$order_id}<br>
<strong>Customer:</strong> {$item['user_name']} ({$item['user_email']})
</p>

<table width='100%' cellpadding='8' cellspacing='0' border='1'>
<tr style='background:#f1f3f6;'>
    <th align='left'>Product</th>
    <th>Size</th>
    <th>Qty</th>
    <th align='right'>Price</th>
</tr>
<tr>
    <td>{$item['product_name']}</td>
    <td align='center'>{$item['size']}</td>
    <td align='center'>{$item['quantity']}</td>
    <td align='right'>₹".number_format($item['price'])."</td>
</tr>
</table>

<p><strong>Rejection Reason:</strong></p>
<p style='background:#f8f9fa;padding:10px;border-left:4px solid red;'>
{$reason}
</p>

<p>— ShopNow System</p>
";


header("Location: order_details.php?id=$order_id");
exit;
