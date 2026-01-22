<?php
session_start();
include __DIR__ . "/../config/db.php";

/* ADMIN AUTH */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* FETCH ORDER */
$orderQ = mysqli_query(
    $conn,
    "SELECT o.*, u.name, u.email
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = $order_id"
);

if (mysqli_num_rows($orderQ) === 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($orderQ);

/* FETCH RETURN STATUSES */
$returnStatuses = [];
$rs = mysqli_query(
    $conn,
    "SELECT DISTINCT return_status
     FROM order_items
     WHERE order_id = $order_id
       AND return_status IS NOT NULL"
);
while ($r = mysqli_fetch_assoc($rs)) {
    $returnStatuses[] = $r['return_status'];
}

/* DISPLAY STATUS */
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

/* FETCH ITEMS */
$itemsQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name, p.image
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $order_id"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order #<?= $order_id ?> | Admin</title>
<link rel="stylesheet" href="order_details.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>

<body>

<div class="admin-container">

<h2 class="page-title">Order Details</h2>
<a href="orders.php" class="btn-home">← Back to Orders</a>

<div class="order-card">

<!-- ORDER HEADER -->
<div class="order-header">
    <div>
        <h3>Order #<?= $order['id'] ?></h3>
        <p><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></p>
    </div>

    <div>
        <span class="status <?= strtolower(str_replace(' ', '-', $displayStatus)) ?>">
            <?= htmlspecialchars($displayStatus) ?>
        </span>
        <h3>₹<?= number_format($order['total_amount']) ?></h3>
    </div>
</div>

<!-- CUSTOMER -->
<div class="order-section">
    <h4>Customer</h4>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
</div>

<!-- ADDRESS -->
<div class="order-section">
    <h4>Delivery Address</h4>
    <p style="white-space:pre-line;">
        <?= htmlspecialchars($order['address']) ?>
    </p>
</div>

<!-- ITEMS -->
<div class="order-section">
<h4>Items</h4>

<?php while ($item = mysqli_fetch_assoc($itemsQ)): ?>

<div class="order-item-admin">

    <img src="../uploads/products/<?= htmlspecialchars($item['image']) ?>">

    <div class="item-info">
        <strong><?= htmlspecialchars($item['name']) ?></strong>
        <p>Size: <?= $item['size'] ?> | Qty: <?= $item['quantity'] ?></p>

        <?php if ($item['status'] === 'Cancelled'): ?>
            <span class="badge cancelled">
                Cancelled on <?= date("d M Y, h:i A", strtotime($item['cancelled_at'])) ?>
            </span>
        <?php endif; ?>

        <?php if ($item['return_status'] === 'Requested' && !empty($item['returned_at'])): ?>
            <span class="badge return">
                Return Requested on <?= date("d M Y, h:i A", strtotime($item['returned_at'])) ?>
            </span>
            <p class="return-reason">
                <strong>Reason:</strong><br>
                <?= nl2br(htmlspecialchars($item['return_reason'])) ?>
            </p>
        <?php elseif ($item['return_status'] === 'Approved' && !empty($item['return_approved_at'])): ?>
            <span class="badge approved">
                Return Approved on <?= date("d M Y, h:i A", strtotime($item['return_approved_at'])) ?>
            </span>
        <?php elseif ($item['return_status'] === 'Rejected' && !empty($item['return_rejected_at'])): ?>
            <span class="badge rejected">
                Return Rejected on <?= date("d M Y, h:i A", strtotime($item['return_rejected_at'])) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="item-price">
        ₹<?= number_format($item['price'] * $item['quantity']) ?>
    </div>

    <!-- RETURN ACTIONS -->
    <div class="item-action">

    <?php if ($item['return_status'] === 'Requested'): ?>

        <form method="post"
              action="approve_return.php"
              class="inline-form"
              onsubmit="return confirm('Approve this return?');">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <button class="btn-approve">Approve</button>
        </form>

        <button class="btn-reject"
                onclick="openRejectModal(<?= $item['id'] ?>)">
            Reject
        </button>

    <?php endif; ?>

    </div>

</div>

<?php endwhile; ?>
</div>

<!-- TIMELINE -->
<div class="order-section">
<h4>Order Timeline</h4>

<p><strong>Placed:</strong>
<?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></p>

<?php if (!empty($order['cancelled_at'])): ?>
<p class="text-cancelled">
<strong>Cancelled:</strong>
<?= date("d M Y, h:i A", strtotime($order['cancelled_at'])) ?>
</p>
<?php endif; ?>

<?php if (!empty($order['delivered_at'])): ?>
<p class="text-delivered">
<strong>Delivered:</strong>
<?= date("d M Y, h:i A", strtotime($order['delivered_at'])) ?>
</p>
<?php endif; ?>
</div>

</div>
</div>

<!-- REJECT RETURN MODAL -->
<div id="rejectModal" class="return-modal">
<div class="return-modal-content">

<h3>Reject Return</h3>

<form method="post" action="reject_return.php">
    <input type="hidden" name="order_id" value="<?= $order_id ?>">
    <input type="hidden" name="item_id" id="reject_item_id">

    <textarea name="reason" required
        placeholder="Reason for rejecting return..."></textarea>

    <div class="return-actions">
        <button type="submit" class="btn-reject">Reject</button>
        <button type="button" class="btn-cancel"
                onclick="closeRejectModal()">Cancel</button>
    </div>
</form>

</div>
</div>

<script>
function openRejectModal(itemId) {
    document.getElementById('reject_item_id').value = itemId;
    document.getElementById('rejectModal').classList.add('show');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('show');
}
</script>

</body>
</html>
