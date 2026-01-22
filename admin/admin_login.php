<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

$msg = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $q = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email'");

    if (mysqli_num_rows($q) == 1) {
        $admin = mysqli_fetch_assoc($q);

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: dashboard.php");
            exit;
        } else {
            $msg = "<div class='msg error'>Invalid password</div>";
        }
    } else {
        $msg = "<div class='msg error'>Admin not found</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login | ShopNow</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="auth-card">
    <h2>Admin Login</h2>

    <?= $msg ?>

    <form method="post">
        <div class="input-group">
            <input type="email" name="email" placeholder="Admin Email" required>
        </div>

        <div class="input-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <button class="btn" name="login">Login</button>
    </form>

    <div class="links">
        <a href="admin_forgot_password.php">Forgot Password?</a>
    </div>

    <div class="links">
        New admin? <a href="admin_register.php">Register</a>
    </div>
</div>

</body>
</html>
