<?php
session_start();
include __DIR__ . "/../config/db.php";

/* LOGIN CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* VALID ORDER ID */
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id  = $_SESSION['user_id'];

/* FETCH ORDER */
$orderQ = mysqli_query(
    $conn,
    "SELECT * FROM orders 
     WHERE id = $order_id AND user_id = $user_id"
);

if (mysqli_num_rows($orderQ) === 0) {
    header("Location: index.php");
    exit;
}

$order = mysqli_fetch_assoc($orderQ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Successful | ShopNow</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="order_success.css">

<style>
</style>
</head>

<body>

<div class="success-card">

    <div class="success-icon">✔</div>

    <h2>Order Placed Successfully!</h2>
    <p>Thank you for shopping with <strong>ShopNow</strong></p>

    <div class="order-info">
        <div><strong>Order ID:</strong> #<?= $order['id'] ?></div>
        <div><strong>Total Amount:</strong> ₹<?= number_format($order['total_amount']) ?></div>
        <div><strong>Payment Method:</strong> Cash on Delivery</div>
        <div><strong>Status:</strong> <?= htmlspecialchars($order['status'] ?: 'Placed') ?></div>
        <div><strong>Order Date:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></div>
        <div><h4> You can cancel your order within 24hours</h4></div>
        <div><h4> Your order will be delivered within 7 days</h4></div>
    </div>

    <div class="btn-group">
        <a href="index.php" class="btn btn-home">Continue Shopping</a>
        <a href="orders.php" class="btn btn-orders">View Orders</a>
    </div>

</div>

</body>
</html>
