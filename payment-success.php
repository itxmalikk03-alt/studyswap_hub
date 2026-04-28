<?php
require_once __DIR__ . '/db_connect.php';
$bookId = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Success — StudySwap Hub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
    <?php require __DIR__ . '/includes/navbar.php'; ?>
    <div class="container" style="text-align:center; padding:100px 20px;">
        <div class="card" style="max-width:500px; margin:0 auto; padding:40px;">
            <i class="fas fa-check-circle" style="font-size:4rem; color:#27ae60; margin-bottom:20px;"></i>
            <h2 style="color:var(--brown-700);">Payment Successful!</h2>
            <p style="margin:15px 0; color:var(--muted);">Shukriya! Aapka transaction complete ho chuka hai. Ab aap is book ka PDF download kar sakte hain.</p>
            
            <div style="display:flex; gap:10px; margin-top:30px;">
                <a href="download-book.php?id=<?= $bookId ?>" class="btn btn-primary" style="flex:1; justify-content:center;">
                    <i class="fas fa-download"></i> Download PDF Now
                </a>
                <a href="dashboard.php" class="btn btn-outline" style="flex:1; justify-content:center;">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>