<?php
// ============================================================
//  StudySwap Hub — process-free-download.php
// ============================================================
require_once __DIR__ . '/db_connect.php';

if (!isLoggedIn()) {
    die("Access Denied.");
}

$bookId = (int)($_GET['id'] ?? 0);
$userId = currentUserId();

// 1. Check book status
$stmt = $pdo->prepare("SELECT title, pdf_file, user_id FROM books WHERE id = ? AND listing_type = 'free'");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book || empty($book['pdf_file'])) {
    die("Free file not found.");
}

// 2. Notification to Owner (Your generosity helped another learner ❤️)
sendNotification(
    $pdo, 
    (int)$book['user_id'], 
    'download', 
    "Great news! Someone just downloaded '" . $book['title'] . "'. Your generosity helped another learner! ❤️", 
    'my-books.php'
);

// 3. Redirect to secure download file
header("Location: download-book.php?id=" . $bookId);
exit;