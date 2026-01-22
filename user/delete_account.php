<?php
session_start();
include __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['password'])) {
    header("Location: profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'];

// Fetch hashed password from DB
$query = mysqli_query($conn, "SELECT password FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($query);

// Verify password
if (!password_verify($password, $user['password'])) {
    header("Location: profile.php?error=1");
    exit;
}

// Delete user
mysqli_query($conn, "DELETE FROM users WHERE id='$user_id'");

// Destroy session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit;
