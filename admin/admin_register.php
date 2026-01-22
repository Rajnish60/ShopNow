<?php
include __DIR__ . "/../config/db.php";
$msg = "";

if (isset($_POST['register'])) {

    $name      = $_POST['name'];
    $shopname  = $_POST['shopname'];
    $address   = $_POST['address'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $pass      = $_POST['password'];
    $cpass     = $_POST['confirm_password'];

    if ($pass !== $cpass) {
        $msg = "<div class='msg error'>Passwords do not match</div>";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM admins WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $msg = "<div class='msg error'>Admin already exists</div>";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            mysqli_query(
                $conn,
                "INSERT INTO admins (name, shop_name, address, email, phone, password)
                 VALUES ('$name', '$shopname', '$address', '$email', '$phone', '$hash')"
            );

            $msg = "<div class='msg success'>
                        Admin registered successfully. 
                        <a href='admin_login.php'>Login</a>
                    </div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Register | ShopNow</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="auth-card">
    <h2>Admin Register</h2>

    <?= $msg ?>

    <form method="post">

        <div class="input-group">
            <input type="text" name="name" placeholder="Full Name" required>
        </div>

        <div class="input-group">
            <input type="text" name="shopname" placeholder="Shop Name" required>
        </div>

        <div class="input-group">
            <input type="text" name="address" placeholder="Shop Address" required>
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="input-group">
            <input type="text" name="phone" placeholder="Phone Number" required>
        </div>

        <div class="input-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <div class="input-group">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        </div>

        <button class="btn" name="register">Register</button>
    </form>

    <div class="links">
        Already an admin? <a href="admin_login.php">Login</a>
    </div>
</div>

</body>
</html>
