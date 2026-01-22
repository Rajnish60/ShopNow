<?php
session_start();
include __DIR__ . "/../config/db.php";

$msg = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    /* FETCH USER */
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($pass, $user['password'])) {

            /* SET SESSION */
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];

            /* LOAD CART FROM DATABASE */
            $_SESSION['cart'] = [];

            $cartQ = $conn->prepare(
                "SELECT product_id, variant_id, size, price, quantity 
                 FROM carts 
                 WHERE user_id = ?"
            );
            $cartQ->bind_param("i", $_SESSION['user_id']);
            $cartQ->execute();
            $cartResult = $cartQ->get_result();

            while ($row = $cartResult->fetch_assoc()) {
                $key = $row['product_id'] . "_" . $row['size'];

                $_SESSION['cart'][$key] = [
                    'product_id' => $row['product_id'],
                    'variant_id' => $row['variant_id'],
                    'size'       => $row['size'],
                    'price'      => $row['price'],
                    'quantity'   => $row['quantity']
                ];
            }

            /* SAFE REDIRECT */
            if (isset($_GET['redirect']) && $_GET['redirect'] !== '') {

                // prevent open redirect
                $redirect = $_GET['redirect'];

                if (strpos($redirect, 'http') === false) {
                    header("Location: $redirect");
                    exit;
                }
            }

            header("Location: index.php");
            exit;

        } else {
            $msg = "<div class='msg error'>Invalid password</div>";
        }

    } else {
        $msg = "<div class='msg error'>User not found</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Login | ShopNow</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<div class="card">
    <h2>User Login</h2>

    <?= $msg ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button name="login">Login</button>
    </form>

    <div class="link">
        <a href="forgot_password.php">Forgot Password?</a>
    </div>

    <div class="link">
        New user? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>
