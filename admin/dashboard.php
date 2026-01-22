<?php
session_start();
include __DIR__ . "/../config/db.php";

/* AUTH */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

/* ADMIN INFO */
$query = mysqli_query(
    $conn,
    "SELECT name, shop_name, address, email, phone 
     FROM admins 
     WHERE id = $admin_id"
);
$admin = mysqli_fetch_assoc($query);

/* STATS */

/* Total Products */
$productQ = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total_products
     FROM products
     WHERE admin_id = $admin_id"
);
$totalProducts = mysqli_fetch_assoc($productQ)['total_products'];

/* Total Orders (unique orders having this admin's products) */
$orderQ = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT oi.order_id) AS total_orders
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE p.admin_id = $admin_id"
);
$totalOrders = mysqli_fetch_assoc($orderQ)['total_orders'];

/* Pending Orders */
$pendingQ = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT oi.order_id) AS pending_orders
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN orders o ON oi.order_id = o.id
     WHERE p.admin_id = $admin_id
       AND (
            o.status IN ('Placed','Partially Cancelled')
            OR oi.return_status = 'Requested'
       )"
);
$pendingOrders = mysqli_fetch_assoc($pendingQ)['pending_orders'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard | ShopNow</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="dashboard">

    <!-- NAVBAR INSIDE CARD -->
    <div class="card-navbar">
        <div class="logo">ShopNow Admin</div>

        <div class="nav-links">
            <a href="add_product.php">Add Product</a>
            <a href="products.php">Manage Products</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="dashboard-content">

        <div class="dash-header">
            <h2>Welcome, <?= htmlspecialchars($admin['name']) ?> 👋</h2>
        </div>

        <!-- SHOP INFO -->
        <div class="info-grid">
            <div class="info-card">
                <h4>Shop Name</h4>
                <p><?= htmlspecialchars($admin['shop_name']) ?></p>
            </div>

            <div class="info-card">
                <h4>Email</h4>
                <p><?= htmlspecialchars($admin['email']) ?></p>
            </div>

            <div class="info-card">
                <h4>Phone</h4>
                <p><?= htmlspecialchars($admin['phone']) ?></p>
            </div>

            <div class="info-card">
                <h4>Address</h4>
                <p><?= htmlspecialchars($admin['address']) ?></p>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <h3><?= $totalProducts ?></h3>
                <span>Total Products</span>
            </div>

            <div class="stat-card">
                <h3><?= $totalOrders ?></h3>
                <span>Total Orders</span>
            </div>

            <div class="stat-card">
                <h3><?= $pendingOrders ?></h3>
                <span>Pending Orders</span>
            </div>
        </div>

    </div>
</div>


</body>
</html>
