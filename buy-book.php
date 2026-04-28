<?php
require_once __DIR__ . '/db_connect.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$bookId = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND listing_type = 'sale'");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) { die("Invalid Book or not for sale."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout — StudySwap Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/includes/navbar.php'; ?>
    <div class="container" style="max-width:500px; margin-top:50px;">
        <div class="card" style="padding:20px;">
            <h2 style="color:var(--brown-700);">Checkout</h2>
            <p>Buying: <strong><?= h($book['title']) ?></strong></p>
            <p style="font-size:1.2rem; font-weight:bold;">Price: Rs. <?= number_format($book['price']) ?></p>
            
            <form action="process-payment.php" method="POST" style="margin-top:20px;">
                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" class="form-control" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" class="form-control" required placeholder="1234 5678 9101 1121">
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Expiry</label>
                        <input type="text" class="form-control" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>CVV</label>
                        <input type="password" class="form-control" placeholder="***" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">
                    <i class="fas fa-lock"></i> Pay Now & Unlock PDF
                </button>
            </form>
        </div>
    </div>
</body>
</html>