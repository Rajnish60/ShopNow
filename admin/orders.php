<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

/* ADMIN AUTH */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];

/* MARK DELIVERED */
if (isset($_GET['deliver'])) {

    $order_id = (int)$_GET['deliver'];

    /* VERIFY ORDER BELONGS TO THIS ADMIN */
    $checkQ = mysqli_query(
        $conn,
        "SELECT 1
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = $order_id
           AND p.admin_id = $admin_id
         LIMIT 1"
    );

    if (mysqli_num_rows($checkQ) === 0) {
        header("Location: orders.php");
        exit;
    }

    /* FETCH ADMIN */
    $adminQ = mysqli_query(
        $conn,
        "SELECT name, email FROM admins WHERE id = $admin_id"
    );
    $admin = mysqli_fetch_assoc($adminQ);

    /* MARK ORDER DELIVERED */
    mysqli_query(
        $conn,
        "UPDATE orders
         SET status = 'Delivered',
             delivered_at = NOW()
         WHERE id = $order_id
           AND status != 'Cancelled'"
    );

    /* FETCH ORDER + USER */
    $orderQ = mysqli_query(
        $conn,
        "SELECT o.id, u.name AS user_name, u.email AS user_email
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.id = $order_id"
    );
    $order = mysqli_fetch_assoc($orderQ);

    /* FETCH ADMIN ITEMS */
    $itemsQ = mysqli_query(
        $conn,
        "SELECT oi.*, p.name
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = $order_id
           AND p.admin_id = $admin_id"
    );

    $itemsHtml = '';
    while ($item = mysqli_fetch_assoc($itemsQ)) {
        $itemsHtml .= "
            <tr>
                <td>{$item['name']}</td>
                <td align='center'>{$item['size']}</td>
                <td align='center'>{$item['quantity']}</td>
                <td align='right'>₹".number_format($item['price'] * $item['quantity'])."</td>
            </tr>
        ";
    }

    /* EMAILS */
    sendMail(
        $order['user_email'],
        "Order Delivered | ShopNow",
        $itemsHtml
    );

    sendMail(
        $admin['email'],
        "Order Delivered Confirmation",
        $itemsHtml
    );

    header("Location: orders.php");
    exit;
}

/* FETCH ADMIN-SCOPED ORDERS */
$ordersQ = mysqli_query(
    $conn,
    "SELECT 
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        u.name,
        u.email,

        SUM(oi.return_status = 'Requested' AND p.admin_id = $admin_id) AS return_requested,
        SUM(oi.return_status = 'Approved'  AND p.admin_id = $admin_id) AS return_approved,
        SUM(oi.return_status = 'Rejected'  AND p.admin_id = $admin_id) AS return_rejected

     FROM orders o
     JOIN users u ON o.user_id = u.id
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     WHERE p.admin_id = $admin_id
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Orders | Admin</title>
<link rel="stylesheet" href="products.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>

<body>

<div class="admin-container">

<h2 class="page-title">Orders Management</h2>
<a href="dashboard.php" class="btn-home">← Back to Home</a>

<table class="admin-table">
<thead>
<tr>
    <th>Order ID</th>
    <th>Customer</th>
    <th>Email</th>
    <th>Total</th>
    <th>Status</th>
    <th>Placed On</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php while ($order = mysqli_fetch_assoc($ordersQ)): ?>

<?php
/* DISPLAY STATUS */
$displayStatus = $order['status'];

if ($order['return_requested'] > 0) {
    $displayStatus = 'Return Requested';
} elseif ($order['return_approved'] > 0) {
    $displayStatus = 'Return Approved';
} elseif (
    $order['return_rejected'] > 0 &&
    $order['return_requested'] == 0 &&
    $order['return_approved'] == 0
) {
    $displayStatus = 'Return Rejected';
}
?>

<tr>
<td>#<?= (int)$order['id'] ?></td>
<td><?= htmlspecialchars($order['name']) ?></td>
<td><?= htmlspecialchars($order['email']) ?></td>
<td>₹<?= number_format($order['total_amount']) ?></td>

<td>
    <span class="status <?= strtolower(str_replace(' ', '-', $displayStatus)) ?>">
        <?= htmlspecialchars($displayStatus) ?>
    </span>
</td>

<td><?= date("d M Y, h:i A", strtotime($order['created_at'])) ?></td>

<td class="actions">
<div class="action-buttons">

<a href="order_details.php?id=<?= (int)$order['id'] ?>" class="btn-view">
    View
</a>

<?php if (
    in_array($order['status'], ['Placed', 'Partially Cancelled']) &&
    $order['return_requested'] == 0
): ?>

<a href="orders.php?deliver=<?= (int)$order['id'] ?>"
   class="btn-deliver"
   onclick="return confirm('Mark this order as Delivered?');">
    Mark Delivered
</a>

<form method="post" action="cancel_order.php" class="inline-form"
      onsubmit="return confirm('Cancel this order?');">
    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
    <button type="submit" class="btn-cancel">Cancel Order</button>
</form>

<?php endif; ?>

</div>
</td>
</tr>

<?php endwhile; ?>
</tbody>
</table>

</div>
</body>
</html>
