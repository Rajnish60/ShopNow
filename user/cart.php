<?php
session_start();
include __DIR__ . "/../config/db.php";

/* PROTECT CART */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* REMOVE ITEM */
if (isset($_GET['remove'])) {

    $key = $_GET['remove'];

    if (isset($_SESSION['cart'][$key])) {

        $variant_id = $_SESSION['cart'][$key]['variant_id'];
        $user_id    = $_SESSION['user_id'];

        // Remove from SESSION
        unset($_SESSION['cart'][$key]);

        // Remove from DATABASE
        mysqli_query(
            $conn,
            "DELETE FROM carts 
             WHERE user_id = $user_id 
             AND variant_id = $variant_id"
        );
    }

    header("Location: cart.php");
    exit;
}

/* INCREASE QUANTITY (STOCK SAFE) */
if (isset($_GET['inc'])) {
    $key = $_GET['inc'];

    if (isset($_SESSION['cart'][$key])) {
        $variant_id = $_SESSION['cart'][$key]['variant_id'];
        $currentQty = $_SESSION['cart'][$key]['quantity'];

        $stockQ = mysqli_query(
            $conn,
            "SELECT quantity FROM product_variants WHERE id = $variant_id"
        );
        $variant = mysqli_fetch_assoc($stockQ);

        if ($variant && $currentQty < $variant['quantity']) {
            $_SESSION['cart'][$key]['quantity']++;
        }
    }

    header("Location: cart.php");
    exit;
}

/* DECREASE QUANTITY */
if (isset($_GET['dec'])) {
    $key = $_GET['dec'];

    if (isset($_SESSION['cart'][$key]) && $_SESSION['cart'][$key]['quantity'] > 1) {
        $_SESSION['cart'][$key]['quantity']--;
    }

    header("Location: cart.php");
    exit;
}

/* CART INIT */
$cart = $_SESSION['cart'] ?? [];

/* STOCK VALIDATION */
$hasOutOfStock = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart | ShopNow</title>
<link rel="stylesheet" href="cart.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

</head>

<body>

<section class="products">
<div class="cart-container">

<h2>Your Shopping Cart</h2>

<?php if (empty($cart)): ?>

<p>Your cart is empty.</p>
<div class="cart-actions">
<a href="index.php" class="btn-view">Continue Shopping</a>
</div>
<?php else: ?>

<table class="cart-table">
<tr>
    <th>Product</th>
    <th>Size</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Subtotal</th>
    <th>Action</th>
</tr>

<?php
$total = 0;

foreach ($cart as $key => $item):

$productQ = mysqli_query(
    $conn,
    "SELECT name, image FROM products WHERE id = ".$item['product_id']
);
$product = mysqli_fetch_assoc($productQ);

$variantQ = mysqli_query(
    $conn,
    "SELECT quantity FROM product_variants WHERE id = ".$item['variant_id']
);
$variant = mysqli_fetch_assoc($variantQ);

$currentStock = (int)($variant['quantity'] ?? 0);
$isOutOfStock = $currentStock < $item['quantity'];

if ($isOutOfStock) {
    $hasOutOfStock = true;
}

$subtotal = $item['price'] * $item['quantity'];
$total += $subtotal;
?>

<tr>
<td>
    <div class="cart-product">
    <div class="cart-img-wrapper">
        <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="">
        <?php if ($isOutOfStock): ?>
            <div class="stock-overlay">OUT OF STOCK</div>
        <?php endif; ?>
    </div>

    <div class="cart-product-name">
        <?= htmlspecialchars($product['name']) ?>
    </div>
</div>

</td>

<td><?= htmlspecialchars($item['size']) ?></td>

<td>₹<?= number_format($item['price']) ?></td>

<td class="cart-qty">
    <a href="cart.php?dec=<?= $key ?>" class="qty-btn">➖</a>
    <span class="qty-number"><?= $item['quantity'] ?></span>

    <?php if (!$isOutOfStock): ?>
        <a href="cart.php?inc=<?= $key ?>" class="qty-btn">➕</a>
    <?php else: ?>
        <span class="qty-btn disabled">➕</span>
    <?php endif; ?>
</td>

<td>₹<?= number_format($subtotal) ?></td>

<td>
    <a href="cart.php?remove=<?= $key ?>" class="cart-remove-btn">
        Remove
    </a>
</td>
</tr>

<?php endforeach; ?>

<tr>
<td colspan="4" class="cart-total-label">Total</td>
<td colspan="2" class="cart-total">₹<?= number_format($total) ?></td>
</tr>

</table>

<h4> Products will be delivered within 7days </h4>
<div class="cart-actions">
<a href="index.php" class="btn-view">Continue Shopping</a>

<?php if ($hasOutOfStock): ?>
    <button class="btn-cart disabled" onclick="stockAlert()">Proceed to Checkout</button>
<?php else: ?>
    <a href="checkout.php?from=cart" class="btn-cart">Proceed to Checkout</a>
<?php endif; ?>

</div>

<?php endif; ?>

</div>
</section>

<script>
function stockAlert(){
    alert("Some items in your cart are out of stock. Please remove them to continue.");
}
</script>

</body>
</html>
