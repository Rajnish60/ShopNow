<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* FETCH ORDER */
$orderQ = mysqli_query(
    $conn,
    "SELECT * FROM orders
     WHERE id = $order_id AND user_id = $user_id"
);

if (mysqli_num_rows($orderQ) === 0) {
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($orderQ);

/* FETCH RETURN STATUSES */
$returnQ = mysqli_query(
    $conn,
    "SELECT DISTINCT return_status
     FROM order_items
     WHERE order_id = $order_id
       AND return_status IS NOT NULL"
);

$returnStatuses = [];
while ($r = mysqli_fetch_assoc($returnQ)) {
    $returnStatuses[] = $r['return_status'];
}

/* DETERMINE DISPLAY STATUS */
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

/* CANCEL LOGIC */
$orderPlacedTime = strtotime($order['created_at']);
$canCancelOrder = (
    in_array($order['status'], ['Placed', 'Partially Cancelled']) &&
    (time() - $orderPlacedTime) <= 86400
);

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
<html>
<head>
<title>Order #<?= $order_id ?> | ShopNow</title>
<link rel="stylesheet" href="order_details.css">
</head>
<body>

<div class="orders-page">
<h2 class="page-title">Order Details</h2>

<div class="order-card">

<!-- ORDER HEADER -->
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

<!-- ADDRESS -->
<div class="order-items">
    <h4>Delivery Address</h4>
    <p><?= nl2br(htmlspecialchars($order['address'])) ?></p>
</div>

<!-- ITEMS -->
<div class="order-items">
<h4>Items</h4>

<?php while ($item = mysqli_fetch_assoc($itemsQ)): ?>
<?php
$canReturn = false;
$remainingHours = 0;

if (
    $order['status'] === 'Delivered' &&
    !empty($order['delivered_at']) &&
    empty($item['return_status']) &&
    $item['status'] !== 'Cancelled'
) {
    $deliveryTime = strtotime($order['delivered_at']);
    $returnExpiry = $deliveryTime + (4 * 86400);
    $remainingSeconds = $returnExpiry - time();

    if ($remainingSeconds > 0) {
        $canReturn = true;
        $remainingHours = ceil($remainingSeconds / 3600);
    }
}
?>

<div class="order-item-row">

    <div class="item-img">
        <img src="../uploads/products/<?= htmlspecialchars($item['image']) ?>" alt="">
    </div>

    <div class="item-info">
        <h5><?= htmlspecialchars($item['name']) ?></h5>
        <p>Size: <?= $item['size'] ?> | Qty: <?= $item['quantity'] ?></p>

        <?php if ($item['status'] === 'Cancelled'): ?>
            <span class="badge cancelled">
                Cancelled on <?= date("d M Y, h:i A", strtotime($item['cancelled_at'])) ?>
            </span>
        <?php endif; ?>

        <?php if ($item['return_status'] === 'Requested' && !empty($item['returned_at'])): ?>
            <span class="badge return">
                Return Requested on
                <?= date("d M Y, h:i A", strtotime($item['returned_at'])) ?>
            </span>

        <?php elseif ($item['return_status'] === 'Approved' && !empty($item['return_approved_at'])): ?>
            <span class="badge approved">
                Return Approved on
                <?= date("d M Y, h:i A", strtotime($item['return_approved_at'])) ?>
            </span>

        <?php elseif ($item['return_status'] === 'Rejected' && !empty($item['return_rejected_at'])): ?>
            <span class="badge rejected">
                Return Rejected on
                <?= date("d M Y, h:i A", strtotime($item['return_rejected_at'])) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="item-price">
        ₹<?= number_format($item['price'] * $item['quantity']) ?>
    </div>

    <div class="item-action">

        <?php if ($item['status'] === 'Placed' && $canCancelOrder): ?>
            <form method="post" action="cancel_item.php">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <button class="btn-cancel">Cancel</button>
            </form>
        <?php endif; ?>

        <?php if ($canReturn): ?>
            <button class="btn-return"
                onclick="openReturnModal(<?= $item['id'] ?>)">
                Return
            </button>
            <small class="return-timer">
                You can return within <?= $remainingHours ?> hrs
            </small>
        <?php endif; ?>

    </div>
</div>
<?php endwhile; ?>
</div>

<!-- TIMELINE -->
<div class="order-timeline">
<?php if (!empty($order['cancelled_at'])): ?>
    <p class="text-cancelled">
        <strong>Order Cancelled:</strong>
        <?= date("d M Y, h:i A", strtotime($order['cancelled_at'])) ?>
    </p>
<?php endif; ?>

<?php if (!empty($order['delivered_at'])): ?>
    <p class="text-delivered">
        <strong>Order Delivered:</strong>
        <?= date("d M Y, h:i A", strtotime($order['delivered_at'])) ?>
    </p>
<?php endif; ?>
</div>

<!-- ACTIONS -->
<div class="order-actions-bar">
<a href="orders.php" class="btn-back">← Back to Orders</a>

<?php if (($order['status'] === 'Placed' || $order['status'] === 'Partially Cancelled') && $canCancelOrder): ?>
<form method="post" action="cancel_order_all.php"
      onsubmit="return confirm('Cancel entire order?');">
    <input type="hidden" name="order_id" value="<?= $order_id ?>">
    <button type="submit" class="btn-danger">Cancel Order</button>
</form>
<?php endif; ?>
</div>

</div>
</div>

<!-- RETURN MODAL -->
<div id="returnModal" class="return-modal">
<div class="return-modal-content">
<h3>Return Item</h3>

<form method="post" action="return_item.php">
    <input type="hidden" name="order_id" value="<?= $order_id ?>">
    <input type="hidden" name="item_id" id="return_item_id">

    <textarea name="reason"
        placeholder="Please explain the reason for return..."
        required></textarea>

    <div class="return-actions">
        <button type="submit" class="btn-return">Submit Return</button>
        <button type="button" class="btn-cancel"
                onclick="closeReturnModal()">Cancel</button>
    </div>
</form>
</div>
</div>

<script>
function openReturnModal(id) {
    document.getElementById('return_item_id').value = id;
    document.getElementById('returnModal').classList.add('show');
}
function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
}
</script>

</body>
</html>
