<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = (int)$_GET['id'];

/* FETCH PRODUCT */
$productQ = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
$product  = mysqli_fetch_assoc($productQ);

if (!$product) {
    header("Location: index.php");
    exit;
}

/* FETCH VARIANTS */
$variantsQ = mysqli_query(
    $conn,
    "SELECT * FROM product_variants WHERE product_id = $product_id"
);

/* FIND FIRST AVAILABLE SIZE */
$defaultSize  = "";
$defaultPrice = "";

mysqli_data_seek($variantsQ, 0);
while ($v = mysqli_fetch_assoc($variantsQ)) {
    if ($v['quantity'] > 0) {
        $defaultSize  = $v['size'];
        $defaultPrice = $v['price'];
        break;
    }
}
mysqli_data_seek($variantsQ, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name']) ?> | ShopNow</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<div class="details-wrapper">
<div class="details-container">

<!-- LEFT -->
<div>
    <div class="image-zoom">
        <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="">
    </div>
    <div class="order-actions">
        <a href="index.php" class="btn-home">← Back to Home</a>
    </div>
</div>

<!-- RIGHT -->
<div class="details-info">

<h2><?= htmlspecialchars($product['name']) ?></h2>

<div class="price" id="priceText">
    <?= $defaultPrice !== "" ? "₹".number_format($defaultPrice) : "Out of Stock" ?>
</div>

<p class="product-desc">
    <?= nl2br(htmlspecialchars($product['description'])) ?>
</p>

<div class="sizes">
<label>Select Size</label><br>

<select id="sizeSelect" <?= $defaultSize === "" ? 'disabled' : '' ?>>
<?php while ($v = mysqli_fetch_assoc($variantsQ)): ?>
    <option
        value="<?= htmlspecialchars($v['size']) ?>"
        data-price="<?= $v['price'] ?>"
        <?= ($v['size'] === $defaultSize) ? 'selected' : '' ?>
        <?= $v['quantity'] == 0 ? 'disabled' : '' ?>
    >
        <?= htmlspecialchars($v['size']) ?>
        <?= $v['quantity'] == 0 ? '(Out)' : '' ?>
    </option>
<?php endwhile; ?>
</select>
</div>

<?php if (isset($_SESSION['user_id']) && $defaultSize !== ""): ?>

<form method="post" action="add_to_cart.php">
    <input type="hidden" name="product_id" value="<?= $product_id ?>">
    <input type="hidden" name="size" id="selectedSize" value="<?= htmlspecialchars($defaultSize) ?>">
    <input type="hidden" name="price" id="selectedPrice" value="<?= $defaultPrice ?>">

    <div class="btn-group">
        <button type="submit" class="btn add-cart">
            <i class="fa-solid fa-cart-shopping"></i> Add to Cart
        </button>

        <button type="submit" formaction="buy_now.php" class="btn buy-now">
            Buy Now
        </button>
    </div>
</form>

<?php elseif (!isset($_SESSION['user_id'])): ?>

<a href="login.php?redirect=product_details.php?id=<?= $product_id ?>"
   class="btn buy-now">
   Login to Continue
</a>

<?php else: ?>

<button class="btn buy-now disabled" disabled>Out of Stock</button>

<?php endif; ?>

</div>
</div>
</div>

<script>
const sizeSelect    = document.getElementById("sizeSelect");
const priceText     = document.getElementById("priceText");
const selectedSize  = document.getElementById("selectedSize");
const selectedPrice = document.getElementById("selectedPrice");

if (sizeSelect) {
    sizeSelect.addEventListener("change", () => {
        const opt = sizeSelect.options[sizeSelect.selectedIndex];
        priceText.innerText = "₹" + opt.dataset.price;
        selectedSize.value  = opt.value;
        selectedPrice.value = opt.dataset.price;
    });
}
</script>

</body>
</html>
