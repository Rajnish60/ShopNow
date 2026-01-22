<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* FETCH ORDERS */
$ordersQ = mysqli_query(
    $conn,
    "SELECT *
     FROM orders
     WHERE user_id = $user_id
     ORDER BY id DESC"
);

$orderCount = mysqli_num_rows($ordersQ);
?>

<!DOCTYPE html>
<html>
<head>
<title>My Orders | ShopNow</title>
<link rel="stylesheet" href="orders.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="orders-page">

<h2 class="page-title">My Orders</h2>

<!-- ================= TOP ACTION ================= -->
<div class="order-action">
<?php if ($orderCount > 0): ?>
    <a href="index.php" class="btn-home">Continue Shopping</a>
<?php else: ?>
    <p class="empty-orders">You have not placed any orders yet.</p>
    <a href="index.php" class="btn-home">Continue Shopping</a>
<?php endif; ?>
</div>

<?php while ($order = mysqli_fetch_assoc($ordersQ)): ?>

<?php
/* ================= RETURN STATUS OVERRIDE ================= */
$returnStatuses = [];

$rs = mysqli_query(
    $conn,
    "SELECT DISTINCT return_status
     FROM order_items
     WHERE order_id = {$order['id']}
       AND return_status IS NOT NULL"
);

while ($r = mysqli_fetch_assoc($rs)) {
    $returnStatuses[] = $r['return_status'];
}

$displayStatus = $order['status'];

if (in_array('Requested', $returnStatuses, true)) {
    $displayStatus = 'Return Requested';
} elseif (in_array('Approved', $returnStatuses, true)) {
    $displayStatus = 'Return Approved';
} elseif (!empty($returnStatuses)
    && count(array_unique($returnStatuses)) === 1
    && $returnStatuses[0] === 'Rejected'
) {
    $displayStatus = 'Return Rejected';
}
?>

<div class="order-card">

<!-- ================= ORDER HEADER ================= -->
<div class="order-header">
    <div>
        <h4>Order #<?= $order['id'] ?></h4>
        <span><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></span>
    </div>

    <div class="order-meta">
        <span class="status <?= strtolower(str_replace(' ', '-', $displayStatus)) ?>">
            <?= htmlspecialchars($displayStatus) ?>
        </span>
        <strong>₹<?= number_format($order['total_amount']) ?></strong>
    </div>
</div>

<!-- ================= ORDER ITEMS ================= -->
<div class="order-items">

<?php
$itemsQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name, p.image
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = {$order['id']}"
);

while ($item = mysqli_fetch_assoc($itemsQ)):
?>

<div class="order-item">
    <img src="../uploads/products/<?= htmlspecialchars($item['image']) ?>">

    <div class="item-info">
        <h5><?= htmlspecialchars($item['name']) ?></h5>
        <p>Size: <?= htmlspecialchars($item['size']) ?> • Qty: <?= (int)$item['quantity'] ?></p>
    </div>

    <div class="item-price">
        ₹<?= number_format($item['price'] * $item['quantity']) ?>
    </div>
</div>

<?php endwhile; ?>
</div>

<!-- ================= ACTIONS ================= -->
<div class="order-actions">
    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn-view">
        View Details
    </a>
</div>

</div>

<?php endwhile; ?>

</div>

</body>
</html>
