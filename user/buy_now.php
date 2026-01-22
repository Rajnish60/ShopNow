<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$product_id = (int)$_POST['product_id'];
$size = $_POST['size'] ?? 'S';

/* Fetch variant */
$stmt = $conn->prepare(
    "SELECT id, price, quantity 
     FROM product_variants 
     WHERE product_id = ? AND size = ? 
     LIMIT 1"
);
$stmt->bind_param("is", $product_id, $size);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$variant = $res->fetch_assoc();

/* Store BUY NOW separately */
$_SESSION['buy_now'] = [
    'product_id' => $product_id,
    'variant_id'=> $variant['id'],
    'size'       => $size,
    'price'      => (int)$variant['price'],
    'quantity'   => 1
];

header("Location: checkout.php");
exit;
