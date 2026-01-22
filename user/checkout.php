<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// If Buy Now exists → use it
if (isset($_SESSION['buy_now'])) {
    $cartItems = [ $_SESSION['buy_now'] ];
    $isBuyNow = true;
} else {
    $cartItems = $_SESSION['cart'] ?? [];
    $isBuyNow = false;
}

if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

// If user comes from cart, clear buy_now
if (isset($_GET['from']) && $_GET['from'] === 'cart') {
    unset($_SESSION['buy_now']);
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout | ShopNow</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="checkout.css">
</head>
<body>

<div class="checkout-card">

<h2>Checkout</h2>

<div class="checkout-grid">

<!-- DELIVERY FORM -->
<div class="checkout-form">
<h3>Delivery Address</h3>

<form method="post" action="place_order.php">

<input type="text" name="name" placeholder="Full Name" required>

<div class="two-col">
    <input type="text" name="phone" placeholder="Phone Number" required>
    <input type="text" name="alt_phone" placeholder="Alternate Phone (Optional)">
</div>

<div class="two-col">
    <input type="text" name="pincode" placeholder="Pincode" required>
    <input type="text" name="district" placeholder="District" required>
</div>

<div class="two-col">
    <input type="text" name="state" placeholder="State" required>
    <input type="text" name="locality" placeholder="Locality / Area" required>
</div>

<textarea name="address" placeholder="Full Address (House no, Street, etc.)" required></textarea>
<input type="text" name="landmark" placeholder="Landmark (Optional)">
<h4>Your order will be delivered within 7 days</h4>

<!-- PAYMENT METHOD -->
<div class="payment-box">
    <h3>Payment Method</h3>

    <!-- COD -->
    <label class="payment-option active">
        <input type="radio" name="payment_method" value="COD" checked>
        <div class="payment-content">
            <strong>Cash on Delivery</strong>
            <span>Pay when your order is delivered</span>
        </div>
    </label>

    <!-- Online Payment (Disabled) -->
    <label class="payment-option disabled">
        <input type="radio" disabled>
        <div class="payment-content">
            <strong>Online Payment</strong>
            <span>Coming Soon</span>
        </div>
    </label>
</div>


<button class="place-order">Place Order</button>
</form>

<?php if ($isBuyNow): ?>
    <a href="index.php?clear_buy_now=1" class="back-cart">← Back to Home</a>
<?php else: ?>
    <a href="cart.php" class="back-cart">← Back to Cart</a>
<?php endif; ?>


</div>

<!-- ORDER SUMMARY -->
<div class="order-summary">
<h3>Order Summary</h3>

<?php
$total = 0;
foreach ($cartItems as $item):

$pq = mysqli_query($conn,"SELECT name,image FROM products WHERE id=".$item['product_id']);
$product = mysqli_fetch_assoc($pq);

$subtotal = $item['price'] * $item['quantity'];
$total += $subtotal;
?>

<div class="summary-item">
<img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>">

<div class="summary-details">
    <h4><?= htmlspecialchars($product['name']) ?></h4>
    <p>Size: <?= $item['size'] ?></p>
    <p>Qty: <?= $item['quantity'] ?></p>
</div>

<div class="summary-price">₹<?= number_format($subtotal) ?></div>
</div>

<?php endforeach; ?>

<div class="summary-total">
<span>Total</span>
<strong>₹<?= number_format($total) ?></strong>
</div>

</div>

</div>
</div>

</body>
</html>
