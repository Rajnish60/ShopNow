<?php
session_start();
include __DIR__ . "/../config/db.php";
$msg = "";

if(isset($_POST['register'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    if($pass !== $cpass){
        $msg = "<div class='msg error'>Passwords do not match</div>";
    } else {
        $check = mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");
        if(mysqli_num_rows($check)>0){
            $msg = "<div class='msg error'>Email already exists</div>";
        } else {
            $hash = password_hash($pass,PASSWORD_DEFAULT);
            mysqli_query($conn,
                "INSERT INTO users(name,phone,email,password)
                 VALUES('$name','$phone','$email','$hash')");
            $msg = "<div class='msg success'>Registration successful. <a href='login.php'>Login</a></div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="card">
<h2>Create Account</h2>

<?= $msg ?>

<form method="post">
<input name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email address" required>
<input type="phone" name="phone" placeholder="Phone Number" required>
<input type="password" name="password" placeholder="Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>
<button name="register">Register</button>
</form>

<div class="link">
Already have an account? <a href="login.php">Login</a>
</div>
</div>

</body>
</html>
