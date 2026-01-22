<?php
session_start();
include __DIR__ . "/../config/db.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// PRODUCT QUERY (CORRECT & OPTIMIZED)
if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $products = mysqli_query(
        $conn,
        "SELECT p.*,
                MIN(v.price) AS min_price,
                SUM(v.quantity) AS total_stock
         FROM products p
         JOIN product_variants v ON p.id = v.product_id
         WHERE p.name LIKE '%$search%'
            OR p.description LIKE '%$search%'
         GROUP BY p.id
         ORDER BY p.id DESC"
    );
} else {
    $products = mysqli_query(
        $conn,
        "SELECT p.*,
                MIN(v.price) AS min_price,
                SUM(v.quantity) AS total_stock
         FROM products p
         JOIN product_variants v ON p.id = v.product_id
         GROUP BY p.id
         ORDER BY p.id DESC"
    );
}

if (isset($_GET['clear_buy_now'])) {
    unset($_SESSION['buy_now']);
}

$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopNow – Online Shopping</title>
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="logo">ShopNow</div>

    <div class="search-box">
        <form method="GET">
            <input type="text" name="search" placeholder="Search for products..."
                   value="<?= htmlspecialchars($search) ?>">
            <button>Search</button>
        </form>
    </div>

    <div class="nav-links">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="profile-menu">
                <div class="profile-icon">👤 <?= htmlspecialchars($_SESSION['name']) ?></div>
                <div class="dropdown">
                    <a href="profile.php">My Profile</a>
                    <a href="orders.php">Orders</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>

            <div class="cart">
                <a href="cart.php">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php if($cartCount>0): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?>
                </a>
            </div>
            <a href="../admin/admin_login.php" class="btn">Seller</a>
        <?php else: ?>
            <a href="login.php" class="btn">Login / Register</a>
            <a href="../admin/admin_login.php" class="btn">Seller</a>
        <?php endif; ?>
    </div>
</header>

<!-- PRODUCTS -->
<section class="products">

<?php if(!empty($search)): ?>
<p class="search-info">Showing results for "<strong><?= htmlspecialchars($search) ?></strong>"</p>
<?php endif; ?>

<h2>Featured Products</h2>

<div class="product-grid">

<?php if(mysqli_num_rows($products)>0): ?>
<?php while($row=mysqli_fetch_assoc($products)): ?>

<div class="product-card">
    <img src="../uploads/products/<?= htmlspecialchars($row['image']) ?>">

    <h3><?= htmlspecialchars($row['name']) ?></h3>
    <p class="price">From ₹<?= number_format($row['min_price']) ?></p>

    <p class="desc">
        <?= strlen($row['description'])>80
            ? substr(htmlspecialchars($row['description']),0,80).'...'
            : htmlspecialchars($row['description']); ?>
    </p>

    <div class="card-actions">
        <a href="product_details.php?id=<?= $row['id'] ?>" class="btn-view">
            View Details
        </a>

        <?php if($row['total_stock'] > 0): ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="product_details.php?id=<?= $row['id'] ?>" class="btn-cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to Cart
                </a>
            <?php else: ?>
                <a href="login.php?redirect=product_details.php?id=<?= $row['id'] ?>" class="btn-cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Add to Cart
                </a>
            <?php endif; ?>

        <?php else: ?>
            <button class="btn-disabled" disabled>Out of Stock</button>
        <?php endif; ?>
    </div>

</div>

<?php endwhile; ?>
<?php else: ?>
<p>No products found.</p>
<?php endif; ?>

</div>
</section>

<footer class="footer">
<p>© 2026 ShopNow. All Rights Reserved.</p>
<p>Made with ❤️ for learning Full Stack Development</p>
</footer>

<script>
document.addEventListener("click", e=>{
    const profile=document.querySelector(".profile-menu");
    const dropdown=document.querySelector(".dropdown");
    if(profile && profile.contains(e.target)){
        dropdown.style.display = dropdown.style.display==="block"?"none":"block";
    } else if(dropdown){
        dropdown.style.display="none";
    }
});
</script>

</body>
</html>
