<?php
session_start();
include __DIR__ . "/../config/db.php";
require __DIR__ . "/../config/mail_config.php";

/* AUTH */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$order_id = (int)$_POST['order_id'];

mysqli_begin_transaction($conn);

/* VERIFY ORDER */
$orderQ = mysqli_query(
    $conn,
    "SELECT * FROM orders
     WHERE id = $order_id
       AND user_id = $user_id
       AND status IN ('Placed','Partially Cancelled')"
);

if (mysqli_num_rows($orderQ) === 0) {
    mysqli_rollback($conn);
    header("Location: orders.php");
    exit;
}

$order = mysqli_fetch_assoc($orderQ);

/* 24 HOUR RULE */
if ((time() - strtotime($order['created_at'])) > 86400) {
    mysqli_rollback($conn);
    header("Location: order_details.php?id=$order_id");
    exit;
}

/* FETCH ACTIVE ITEMS */
$itemsQ = mysqli_query(
    $conn,
    "SELECT oi.*, p.name, p.admin_id
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $order_id
       AND (oi.status IS NULL OR oi.status = 'Placed')"
);

if (mysqli_num_rows($itemsQ) === 0) {
    mysqli_rollback($conn);
    header("Location: order_details.php?id=$order_id");
    exit;
}

/* PREPARE DATA */
$userItemsHtml  = "";
$grandTotal     = 0;
$adminItems     = []; // admin_id => items[]

/* CANCEL ITEMS */
while ($item = mysqli_fetch_assoc($itemsQ)) {

    // restore stock
    mysqli_query(
        $conn,
        "UPDATE product_variants
         SET quantity = quantity + {$item['quantity']}
         WHERE product_id = {$item['product_id']}
           AND size = '{$item['size']}'"
    );

    // cancel item
    mysqli_query(
        $conn,
        "UPDATE order_items
         SET status='Cancelled',
             cancelled_at=NOW()
         WHERE id={$item['id']}"
    );

    $subtotal = $item['price'] * $item['quantity'];
    $grandTotal += $subtotal;

    /* USER EMAIL HTML */
    $userItemsHtml .= "
        <tr>
            <td>".htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8')."</td>
            <td align='center'>{$item['size']}</td>
            <td align='center'>{$item['quantity']}</td>
            <td align='right'>₹".number_format($subtotal)."</td>
        </tr>
    ";

    /* GROUP ITEMS BY ADMIN */
    $adminItems[$item['admin_id']][] = [
        'name'     => $item['name'],
        'size'     => $item['size'],
        'qty'      => $item['quantity'],
        'subtotal' => $subtotal
    ];
}

/* UPDATE ORDER */
mysqli_query(
    $conn,
    "UPDATE orders
     SET status='Cancelled',
         total_amount=0,
         cancelled_at=NOW()
     WHERE id=$order_id"
);

mysqli_commit($conn);

/* USER EMAIL */
$userQ = mysqli_query(
    $conn,
    "SELECT name, email FROM users WHERE id=$user_id"
);
$user = mysqli_fetch_assoc($userQ);

$userBody = '
<!DOCTYPE html>
<html><body style="font-family:Arial;background:#f4f6f8;padding:20px;">
<div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

<h2>Hello '.htmlspecialchars($user['name'], ENT_QUOTES, "UTF-8").',</h2>

<p>Your order <strong>#'.$order_id.'</strong> has been completely cancelled.</p>

<table width="100%" cellpadding="10" style="border-collapse:collapse;">
<tr style="background:#fdecea;">
<th align="left">Product</th><th>Size</th><th>Qty</th><th align="right">Subtotal</th>
</tr>
'.$userItemsHtml.'
</table>

<h3 style="text-align:right;margin-top:15px;">
Total Cancelled Amount: ₹'.number_format($grandTotal).'
</h3>

<p style="font-size:13px;color:#777;">— Team <strong>ShopNow</strong></p>

</div></body></html>';

sendMail($user['email'], "Order Cancelled | ShopNow", $userBody);

/* ADMIN EMAILS */
foreach ($adminItems as $admin_id => $items) {

    $adminQ = mysqli_query(
        $conn,
        "SELECT name, email FROM admins WHERE id=$admin_id"
    );
    if (!$admin = mysqli_fetch_assoc($adminQ)) continue;

    $adminRows = "";
    foreach ($items as $it) {
        $adminRows .= "
            <tr>
                <td>{$it['name']}</td>
                <td align='center'>{$it['size']}</td>
                <td align='center'>{$it['qty']}</td>
                <td align='right'>₹".number_format($it['subtotal'])."</td>
            </tr>
        ";
    }

    $adminBody = '
    <html><body style="font-family:Arial;background:#f4f6f8;padding:20px;">
    <div style="max-width:650px;margin:auto;background:#fff;padding:25px;border-radius:8px;">

    <h2>Hello '.htmlspecialchars($admin['name'], ENT_QUOTES, "UTF-8").',</h2>

    <p>Order <strong>#'.$order_id.'</strong> has been cancelled by the customer.</p>

    <table width="100%" cellpadding="10" style="border-collapse:collapse;">
    <tr style="background:#fdecea;">
    <th align="left">Product</th><th>Size</th><th>Qty</th><th align="right">Subtotal</th>
    </tr>
    '.$adminRows.'
    </table>

    <p style="font-size:13px;color:#777;">— Team <strong>ShopNow</strong></p>

    </div></body></html>';

    sendMail($admin['email'], "Order Cancelled | Order #$order_id", $adminBody);
}

/* REDIRECT */
header("Location: order_details.php?id=$order_id");
exit;
