<?php
session_start();
include __DIR__ . "/../config/db.php";

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$query = mysqli_query($conn, "SELECT name, email, phone FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile | ShopNow</title>
    <link rel="stylesheet" href="profile_style.css">
</head>
<body>

<div class="profile-container">
    <h2>My Profile</h2>

    <div class="profile-field">
        <label>Name</label>
        <p><?= htmlspecialchars($user['name']) ?></p>
    </div>

    <div class="profile-field">
        <label>Email</label>
        <p><?= htmlspecialchars($user['email']) ?></p>
    </div>

    <div class="profile-field">
        <label>Phone</label>
        <p><?= htmlspecialchars($user['phone']) ?></p>
    </div>

    <a href="index.php" class="back-home-btn">
        ← Back to Home
    </a>

    <form method="post" action="delete_account.php" id="deleteForm"
        onsubmit="return confirm('Are you sure you want to permanently delete your account?');">

        <!-- Password field (hidden initially) -->
        <div class="profile-field" id="passwordBox" style="display:none;">
            <label>Confirm Password</label>
            <input 
                type="password" 
                name="password" 
                placeholder="Enter your password"
                required
                style="width:100%; padding:10px; margin-top:6px;"
            >
        </div>

        <!-- Initial Delete button -->
        <button type="button" class="delete-btn" id="deleteBtn">
            Delete Account
        </button>

        <!-- Confirm + Cancel buttons -->
        <div id="confirmActions" style="display:none; margin-top:10px;">
            <button type="submit" class="delete-btn"
                    style="background:#ff1f1f; margin-bottom:8px;">
                Confirm Delete
            </button>

            <button type="button" class="delete-btn"
                    id="cancelDeleteBtn"
                    style="background:#999;">
                Cancel
            </button>
        </div>
    </form>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red; margin-top:10px;">
            Incorrect password. Account not deleted.
        </p>
    <?php endif; ?>

    <script>
        const deleteBtn = document.getElementById('deleteBtn');
        const passwordBox = document.getElementById('passwordBox');
        const confirmActions = document.getElementById('confirmActions');
        const cancelBtn = document.getElementById('cancelDeleteBtn');

        deleteBtn.addEventListener('click', () => {
            passwordBox.style.display = 'block';
            confirmActions.style.display = 'block';
            deleteBtn.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            passwordBox.style.display = 'none';
            confirmActions.style.display = 'none';
            deleteBtn.style.display = 'block';

            // Clear password field
            passwordBox.querySelector('input').value = '';
        });
    </script>
</div>

</body>
</html>
