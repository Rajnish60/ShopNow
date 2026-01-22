<?php
session_start();
include __DIR__ . "/../config/db.php";

/* ADMIN CHECK */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

/* GET PRODUCT */
$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

/* FETCH PRODUCT */
$productQ = mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id");
if (mysqli_num_rows($productQ) === 0) {
    header("Location: manage_products.php");
    exit;
}
$product = mysqli_fetch_assoc($productQ);

/* FETCH VARIANTS */
$variantsQ = mysqli_query(
    $conn,
    "SELECT * FROM product_variants WHERE product_id=$product_id ORDER BY size"
);

/* UPDATE PRODUCT */
if (isset($_POST['update_product'])) {

    mysqli_begin_transaction($conn);

try {

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);

    /* UPDATE PRODUCT */
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid image format");
        }

        $newName = uniqid("prod_") . "." . $ext;
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "../uploads/products/" . $newName
        );

        mysqli_query(
            $conn,
            "UPDATE products
             SET name='$name', description='$desc', image='$newName'
             WHERE id=$product_id"
        );
    } else {
        mysqli_query(
            $conn,
            "UPDATE products
             SET name='$name', description='$desc'
             WHERE id=$product_id"
        );
    }

    /* UPDATE EXISTING VARIANTS */
    if (!empty($_POST['variant_id'])) {
        foreach ($_POST['variant_id'] as $i => $vid) {
            $price = (int)$_POST['price'][$i];
            $qty   = (int)$_POST['quantity'][$i];

            mysqli_query(
                $conn,
                "UPDATE product_variants
                 SET price=$price, quantity=$qty
                 WHERE id=".(int)$vid
            );
        }
    }

    /* ADD NEW VARIANTS */
    if (!empty($_POST['new_size'])) {
        foreach ($_POST['new_size'] as $i => $size) {
            $size  = trim($size);
            $price = (int)$_POST['new_price'][$i];
            $qty   = (int)$_POST['new_quantity'][$i];

            if ($size !== "") {
                mysqli_query(
                    $conn,
                    "INSERT INTO product_variants
                     (product_id, size, price, quantity)
                     VALUES ($product_id, '$size', $price, $qty)"
                );
            }
        }
    }

    mysqli_commit($conn);

    header("Location: products.php?updated=1");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    die("Update failed: " . $e->getMessage());
}

}

/* DELETE VARIANT */
if (isset($_GET['delete_variant'])) {
    $vid = (int)$_GET['delete_variant'];
    mysqli_query($conn, "DELETE FROM product_variants WHERE id=$vid");
    header("Location: edit_product.php?id=$product_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Product | Admin</title>
<link rel="stylesheet" href="products.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>

<body>

<div class="admin-container">

<h2>Edit Product</h2>

<form method="post" enctype="multipart/form-data">

<label>Product Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

<label>Description</label>
<textarea name="description" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>

<label>Product Image</label>
<img src="../uploads/products/<?= $product['image'] ?>" width="120">
<input type="file" name="image">

<hr>

<h3>Variants (Size / Price / Stock)</h3>

<?php while($v = mysqli_fetch_assoc($variantsQ)): ?>
<div class="variant-row">
    <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">

    <input type="text" value="<?= $v['size'] ?>" readonly>
    <input type="number" name="price[]" value="<?= $v['price'] ?>" required>
    <input type="number" name="quantity[]" value="<?= $v['quantity'] ?>" required>

    <a href="edit_product.php?id=<?= $product_id ?>&delete_variant=<?= $v['id'] ?>"
       onclick="return confirm('Delete this variant?');"
       class="btn-delete">✕</a>
</div>
<?php endwhile; ?>

<hr>

<h3>Add New Variants</h3>

<div id="newVariants"></div>

<button type="button" onclick="addVariant()">+ Add Size</button>

<br><br>
<button name="update_product" class="btn-save">Save Changes</button>
<a href="products.php" class="btn-back">Cancel</a>

</form>

</div>

<script>
function addVariant(){
    const div = document.createElement("div");
    div.className = "variant-row";
    div.innerHTML = `
        <input type="text" name="new_size[]" placeholder="Size (S/M/L)">
        <input type="number" name="new_price[]" placeholder="Price">
        <input type="number" name="new_quantity[]" placeholder="Stock">
    `;
    document.getElementById("newVariants").appendChild(div);
}
</script>

</body>
</html>
