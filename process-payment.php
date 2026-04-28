<?php
// ============================================================
//  StudySwap Hub — process-payment.php
// ============================================================
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $bookId = (int)$_POST['book_id'];
    $userId = currentUserId();
    
    // 1. Check karein ke book exist karti hai aur sale ke liye hai
    $stmt = $pdo->prepare("SELECT title, price, user_id FROM books WHERE id = ? AND listing_type = 'sale' AND is_available = 1");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if (!$book) {
        die("Invalid request or book no longer available.");
    }

    // 2. Dummy Payment Success Logic 
    // Real world mein yahan Stripe/JazzCash ki API call hoti hai
    $paymentSuccess = true; 

    if ($paymentSuccess) {
        // A. Requests table mein entry karein (status = 'accepted')
        // Isse download-book.php ko signal mil jayega ke access allowed hai
        $ins = $pdo->prepare(
            "INSERT INTO requests (book_id, requester_id, owner_id, status, message) 
             VALUES (?, ?, ?, 'accepted', 'Paid Purchase')"
        );
        $ins->execute([$bookId, $userId, $book['user_id']]);

        // B. Notification bhejein owner ko
        sendNotification($pdo, (int)$book['user_id'], 'payment', 
            "Someone bought your book: " . $book['title'], 
            'dashboard.php');

        // Redirect to success page
        header("Location: payment-success.php?id=" . $bookId);
        exit;
    } else {
        header("Location: payment-failed.php");
        exit;
    }
} else {
    header("Location: browse.php");
    exit;
}