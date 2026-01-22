<?php
session_start();
include __DIR__ . "/../config/db.php";

/* ADMIN AUTH CHECK */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$variant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($variant_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

/* CHECK IF VARIANT USED IN ORDERS */
$checkQ = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS used_count
     FROM order_items
     WHERE variant_id = $variant_id"
);

$check = mysqli_fetch_assoc($checkQ);

if ($check['used_count'] > 0) {
    header("Location: edit_product.php?error=variant_used");
    exit;
}

/* SAFE TO DELETE */
mysqli_query(
    $conn,
    "DELETE FROM product_variants WHERE id = $variant_id"
);

header("Location: edit_product.php?deleted=1");
exit;
