<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

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
$reason   = trim($_POST['reason']);

if ($reason === '') {
    header("Location: order_details.php?id=$order_id");
    exit;
}

/* VERIFY ITEM */
$itemQ = mysqli_query(
    $conn,
    "SELECT oi.*, o.delivered_at, o.user_id, p.admin_id, p.name AS product_name
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     WHERE oi.id = $item_id
       AND oi.order_id = $order_id
       AND o.user_id = $user_id
       AND o.status = 'Delivered'
       AND oi.return_status IS NULL"
);

if (mysqli_num_rows($itemQ) === 0) {
    header("Location: order_details.php?id=$order_id");
    exit;
}

$item = mysqli_fetch_assoc($itemQ);

/* 4 DAY RULE */
if ((time() - strtotime($item['delivered_at'])) > (4 * 86400)) {
    header("Location: order_details.php?id=$order_id");
    exit;
}

/* ================= SAVE RETURN (ITEM LEVEL) ================= */
mysqli_query(
    $conn,
    "UPDATE order_items
     SET return_status='Requested',
         return_reason='".mysqli_real_escape_string($conn,$reason)."',
         returned_at=NOW()
     WHERE id=$item_id"
);

/* ================= UPDATE ORDER FLAG ================= */
mysqli_query(
    $conn,
    "UPDATE orders
     SET return_requested = 1
     WHERE id = $order_id"
);

/* FETCH USER */
$userQ = mysqli_query(
    $conn,
    "SELECT name, email FROM users WHERE id=$user_id"
);
$user = mysqli_fetch_assoc($userQ);

/* FETCH ADMIN */
$adminQ = mysqli_query(
    $conn,
    "SELECT name, email FROM admins WHERE id={$item['admin_id']}"
);
$admin = mysqli_fetch_assoc($adminQ);

/* EMAIL BODY */
$subject = "Return Request | Order #$order_id";

$body = "
<h3>Return Request Submitted</h3>
<p><strong>Product:</strong> {$item['product_name']}</p>
<p><strong>Order ID:</strong> #$order_id</p>
<p><strong>Reason:</strong><br>$reason</p>
<p>Item will be pick up by delivery partner within 2 days.</p>
";

/* Send to User */
sendMail($user['email'], $subject, $body);

/* Send to Product Admin */
sendMail($admin['email'], $subject, $body);

header("Location: order_details.php?id=$order_id");
exit;
