<?php
require_once __DIR__ . '/db_connect.php';

if (!isLoggedIn()) {
    die("Access Denied. Please login first.");
}

$bookId = (int)($_GET['id'] ?? 0);
$userId = currentUserId();

// Fetch book and check status
$stmt = $pdo->prepare("SELECT pdf_file, listing_type, user_id FROM books WHERE id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book || empty($book['pdf_file'])) {
    die("File not found.");
}

$canDownload = false;

// Rule 1: Agar book free hai
if ($book['listing_type'] === 'free') {
    $canDownload = true;
} 
// Rule 2: Agar user khud owner hai
elseif ((int)$book['user_id'] === $userId) {
    $canDownload = true;
}
// Rule 3: Check swap/payment status
else {
    $st = $pdo->prepare("SELECT status FROM requests WHERE book_id = ? AND requester_id = ? AND status = 'accepted' LIMIT 1");
    $st->execute([$bookId, $userId]);
    if ($st->fetch()) {
        $canDownload = true;
    }
}

if ($canDownload) {
    $filePath = 'uploads/pdfs/' . $book['pdf_file'];
    if (file_exists($filePath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath);
        exit;
    } else {
        die("File does not exist on server.");
    }
} else {
    echo "<script>alert('Access Locked! You need an approved swap or payment to download this.'); window.location.href='book-detail.php?id=$bookId';</script>";
}