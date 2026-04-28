<?php
// ============================================================
//  StudySwap Hub — wishlist-toggle.php
//  POST handler: adds or removes a book from wishlist
// ============================================================
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');

$bookId   = (int)($_POST['book_id'] ?? 0);
$redirect = $_POST['redirect'] ?? 'browse.php';

if ($bookId > 0) {
    // Check if already in wishlist
    $check = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?');
    $check->execute([currentUserId(), $bookId]);

    if ($check->fetch()) {
        // Remove
        $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND book_id = ?')
            ->execute([currentUserId(), $bookId]);
    } else {
        // Add (ignore if book doesn't exist)
        try {
            $pdo->prepare('INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)')
                ->execute([currentUserId(), $bookId]);
        } catch (PDOException $e) {
            // Silently ignore duplicate/FK error
        }
    }
}

header('Location: ' . $redirect);
exit;
