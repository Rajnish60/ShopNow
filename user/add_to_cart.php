<?php
session_start();
include __DIR__ . "/../config/db.php";

/* LOGIN CHECK */
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['HTTP_REFERER'] ?? 'index.php');
    header("Location: login.php?redirect=$redirect");
    exit;
}

/* REQUEST CHECK */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* INPUT */
$product_id = (int)($_POST['product_id'] ?? 0);
$size       = $_POST['size'] ?? 'S';

if ($product_id <= 0) {
    header("Location: index.php");
    exit;
}

/* FETCH VARIANT */
$stmt = $conn->prepare(
    "SELECT id, price, quantity 
     FROM product_variants 
     WHERE product_id = ? AND size = ? 
     LIMIT 1"
);
$stmt->bind_param("is", $product_id, $size);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$variant = $result->fetch_assoc();

/* INIT CART */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* CART KEY */
$key = $product_id . "_" . $size;

/* STOCK CHECK */
$currentQty = $_SESSION['cart'][$key]['quantity'] ?? 0;

if ($currentQty >= $variant['quantity']) {
    header("Location: cart.php?error=out_of_stock");
    exit;
}

/* ADD / UPDATE SESSION CART */
if (isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['quantity']++;
} else {
    $_SESSION['cart'][$key] = [
        'product_id' => $product_id,
        'variant_id' => $variant['id'],
        'size'       => $size,
        'price'      => (int)$variant['price'],
        'quantity'   => 1
    ];
}

/* SAVE CART TO DATABASE */
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO carts (user_id, product_id, variant_id, size, price, quantity)
    VALUES (?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE quantity = quantity + 1
");
$stmt->bind_param(
    "iiisi",
    $user_id,
    $product_id,
    $variant['id'],
    $size,
    $variant['price']
);
$stmt->execute();

/* REDIRECT */
header("Location: cart.php");
exit;

