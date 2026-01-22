<?php
include __DIR__ . "/../config/db.php";
$msg = "";

if(!isset($_GET['token'])){
    die("Invalid request");
}

$token = $_GET['token'];

$q = mysqli_query($conn,
    "SELECT * FROM admins 
     WHERE reset_token='$token' 
     AND token_expiry > NOW()"
);

if(mysqli_num_rows($q)!=1){
    die("Invalid or expired token");
}

$user = mysqli_fetch_assoc($q);

if(isset($_POST['reset'])){
    $pass = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    if($pass !== $cpass){
        $msg = "<div class='msg error'>Passwords do not match</div>";
    } else {
        $hash = password_hash($pass,PASSWORD_DEFAULT);

        mysqli_query($conn,
            "UPDATE admins 
             SET password='$hash', reset_token=NULL, token_expiry=NULL
             WHERE id='{$user['id']}'"
        );

        $msg = "<div class='msg success'>
                Password updated successfully. 
                <a href='admin_login.php'>Login</a>
               </div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link rel="stylesheet" href="admin_style.css">
</head>
<body>

<div class="auth-card">
<h2>Reset Password</h2>

<?= $msg ?>

<form method="post">
    <div class="input-group">
        <input type="password" name="password" placeholder="New Password" required>
    </div>
    <div class="input-group">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    </div>
<button class="btn" name="reset">Reset Password</button>
</form>
</div>

</body>
</html>
