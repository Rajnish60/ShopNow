<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

$BASE_URL = "http://localhost/RRV";
$msg = "";

if (isset($_POST['submit'])) {

    $email = trim($_POST['email']);

    // Check if email exists
    $check = mysqli_query(
        $conn,
        "SELECT id FROM users WHERE email = '".mysqli_real_escape_string($conn, $email)."'"
    );

    if (mysqli_num_rows($check) === 1) {

        // Generate token + expiry
        $token  = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        mysqli_query(
            $conn,
            "UPDATE users 
             SET reset_token = '$token',
                 token_expiry = '$expiry'
             WHERE email = '".mysqli_real_escape_string($conn, $email)."'"
        );

        // Reset link
        $link = $BASE_URL . "/user/reset_password.php?token=" . $token;

        // Email body
        $body = "
            <h3>Password Reset</h3>
            <p>Click the link below to reset your password:</p>
            <p><a href='$link'>$link</a></p>
            <p><strong>This link expires in 5 minutes.</strong></p>
        ";

        // Send mail
        if (sendMail($email, "Password Reset Request | ShopNow", $body)) {
            $msg = "<div class='msg success'>Password reset link sent to your email</div>";
        } else {
            $msg = "<div class='msg error'>Email could not be sent. Please try again.</div>";
        }

    } else {
        $msg = "<div class='msg error'>Email not found</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h2>Forgot Password</h2>

    <?= $msg ?>

    <form method="post">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button name="submit">Send Reset Link</button>
    </form>

    <div class="link">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
