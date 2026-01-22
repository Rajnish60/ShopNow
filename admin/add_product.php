<?php
session_start();
include __DIR__ . "/../config/db.php";

// Protect admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$msg = "";

if (isset($_POST['add_product'])) {

    $admin_id = $_SESSION['admin_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    /* IMAGE UPLOAD */
    $image = $_FILES['image'];
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $msg = "<div class='msg error'>Invalid image format</div>";
    } else {

        $new_name = uniqid("product_", true).".".$ext;
        move_uploaded_file($image['tmp_name'], "../uploads/products/".$new_name);

        // Insert product
        mysqli_query($conn,
            "INSERT INTO products (admin_id, name, description, image)
             VALUES ('$admin_id','$name','$desc','$new_name')"
        );

        $product_id = mysqli_insert_id($conn);

        // Insert size variants
        $sizes = $_POST['size'];
        $prices = $_POST['price'];
        $quantities = $_POST['quantity'];

        for ($i = 0; $i < count($sizes); $i++) {
            mysqli_query($conn,
                "INSERT INTO product_variants (product_id, size, price, quantity)
                 VALUES ('$product_id','{$sizes[$i]}','{$prices[$i]}','{$quantities[$i]}')"
            );
        }

        $msg = "<div class='msg success'>Product added successfully</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product | ShopNow Admin</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>

<body>

<div class="dashboard">

    <!-- NAVBAR -->
    <div class="card-navbar">
        <div class="logo">ShopNow Admin</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Manage Products</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="dashboard-content">
        <h2>Add Product</h2>

        <?= $msg ?>

        <form method="post" enctype="multipart/form-data">

            <div class="form-group">
                <input type="text" name="name" placeholder="Product Name" required>
            </div>

            <div class="form-group">
                <textarea name="description" placeholder="Product Description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <input type="file" name="image" required>
            </div>

            <h3>Size Variants</h3>

            <div id="variants">
                <div class="variant-row">
                    <input type="text" name="size[]" placeholder="Size (S/M/L/XL/XXL)" required>
                    <input type="number" name="price[]" placeholder="Price" required>
                    <input type="number" name="quantity[]" placeholder="Quantity" required>
                </div>
            </div>

            <button type="button" class="add-variant" onclick="addVariant()">+ Add Size</button>

            <br><br>
            <button class="btn" name="add_product">Add Product</button>

        </form>
    </div>
</div>

<script>
function addVariant() {
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.innerHTML = `
        <input type="text" name="size[]" placeholder="Size" required>
        <input type="number" name="price[]" placeholder="Price" required>
        <input type="number" name="quantity[]" placeholder="Quantity" required>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
    `;
    document.getElementById('variants').appendChild(row);
}
</script>

</body>
</html>
