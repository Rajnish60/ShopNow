<?php
session_start();
include __DIR__ . "/../config/db.php";

/* ADMIN PROTECTION */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];

/* DELETE PRODUCT (ADMIN SAFE) */
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];

    /* Verify product belongs to this admin */
    $checkQ = mysqli_query(
        $conn,
        "SELECT id FROM products 
         WHERE id = $product_id AND admin_id = $admin_id"
    );

    if (mysqli_num_rows($checkQ) === 1) {

        mysqli_query(
            $conn,
            "DELETE FROM product_variants 
             WHERE product_id = $product_id"
        );

        mysqli_query(
            $conn,
            "DELETE FROM products 
             WHERE id = $product_id AND admin_id = $admin_id"
        );
    }

    header("Location: products.php");
    exit;
}

/* FETCH ONLY ADMIN PRODUCTS */
$productsQ = mysqli_query(
    $conn,
    "SELECT * FROM products 
     WHERE admin_id = $admin_id
     ORDER BY id DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Products | Admin</title>
<link rel="stylesheet" href="products.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>

<body>

<div class="admin-container">

<h2 class="page-title">Manage Products</h2>

<div class="product-actions">
    <a href="dashboard.php" class="btn-home">← Back to Home</a>
    <a href="add_product.php" class="btn-add">+ Add New Product</a>
</div>

<table class="admin-table">
<thead>
<tr>
    <th>Image</th>
    <th>Product</th>
    <th>Variants (Size / Price / Stock)</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php while ($product = mysqli_fetch_assoc($productsQ)): ?>

<?php
$variantsQ = mysqli_query(
    $conn,
    "SELECT * FROM product_variants 
     WHERE product_id = {$product['id']}"
);
?>

<tr>
<td>
    <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" class="product-img">
</td>

<td>
    <strong><?= htmlspecialchars($product['name']) ?></strong><br>
    <small><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</small>
</td>

<td>
<?php if (mysqli_num_rows($variantsQ) > 0): ?>
    <ul class="variant-list">
        <?php while ($v = mysqli_fetch_assoc($variantsQ)): ?>
            <li>
                <strong><?= htmlspecialchars($v['size']) ?></strong> |
                ₹<?= number_format($v['price']) ?> |
                Stock: <?= $v['quantity'] ?>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <em>No variants</em>
<?php endif; ?>
</td>

<td>
    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-edit">
        Edit
    </a>

    <a href="products.php?delete=<?= $product['id'] ?>"
       class="btn-delete"
       onclick="return confirm('Delete this product permanently?');">
        Delete
    </a>
</td>
</tr>

<?php endwhile; ?>
</tbody>
</table>

</div>

</body>
</html>
