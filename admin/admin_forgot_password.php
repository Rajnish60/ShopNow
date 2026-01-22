<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

date_default_timezone_set('Asia/Kolkata');

$BASE_URL = "http://localhost/RRV";
$msg = "";

if (isset($_POST['submit'])) {

    $email = trim($_POST['email']);

    // Check admin email
    $check = mysqli_query(
        $conn,
        "SELECT id FROM admins 
         WHERE email = '".mysqli_real_escape_string($conn, $email)."'"
    );

    if (mysqli_num_rows($check) === 1) {

        // Generate token + expiry
        $token  = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Save token
        mysqli_query(
            $conn,
            "UPDATE admins 
             SET reset_token = '$token',
                 token_expiry = '$expiry'
             WHERE email = '".mysqli_real_escape_string($conn, $email)."'"
        );

        // Reset link
        $link = $BASE_URL . "/admin/admin_reset_password.php?token=" . $token;

        // Email body
        $body = "
            <h3>Admin Password Reset</h3>
            <p>Click the link below to reset your admin password:</p>
            <p><a href='$link'>$link</a></p>
            <p><strong>This link expires in 5 minutes.</strong></p>
        ";

        // Send email
        if (sendMail($email, "ShopNow Admin Password Reset", $body)) {
            $msg = "<div class='msg success'>Password reset link sent to your email</div>";
        } else {
            $msg = "<div class='msg error'>Email could not be sent.</div>";
        }

    } else {
        $msg = "<div class='msg error'>Admin email not found</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Forgot Password | ShopNow</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="auth-card">
    <h2>Forgot Password</h2>

    <?= $msg ?>

    <form method="post">
        <div class="input-group">
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button class="btn" name="submit">Send Reset Link</button>
    </form>

    <div class="links">
        <a href="admin_login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
