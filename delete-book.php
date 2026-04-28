<?php
// delete-book.php
// Securely deletes a book listing belonging to the current user
require_once __DIR__ . '/db_connect.php';
requireLogin('login.php');

$bookId = (int)($_GET['id'] ?? 0);
$uid    = currentUserId();

if ($bookId > 0) {
    // Only delete if the book belongs to this user
    $stmt = $pdo->prepare(
        'SELECT id, image FROM books WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$bookId, $uid]);
    $book = $stmt->fetch();

    if ($book) {
        // Delete the book image from disk if it exists
        if ($book['image']) {
            $imgPath = __DIR__ . '/uploads/books/' . $book['image'];
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        // Delete from DB (cascades to requests, wishlist, swap_history via FK)
        $pdo->prepare('DELETE FROM books WHERE id = ? AND user_id = ?')
            ->execute([$bookId, $uid]);

        redirectWith('my-books.php', 'flash_success', 'Book listing removed successfully.');
    }
}

// If book not found or doesn't belong to user, redirect silently
header('Location: my-books.php');
exit;
