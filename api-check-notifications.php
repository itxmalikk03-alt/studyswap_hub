<?php
// ============================================================
//  StudySwap Hub — api-check-notifications.php
// ============================================================
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['new' => false]);
    exit;
}

$userId = currentUserId();

// Sirf wo notifications uthayein jo 'is_read = 0' hain aur pichle 10 seconds mein aaye hain
$stmt = $pdo->prepare("SELECT id, message FROM notifications WHERE user_id = ? AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND) LIMIT 1");
$stmt->execute([$userId]);
$notif = $stmt->fetch();

if ($notif) {
    // Notification ko read mark kar dein taaki dobara pop-up na aaye
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$notif['id']]);
    echo json_encode(['new' => true, 'message' => $notif['message']]);
} else {
    echo json_encode(['new' => false]);
}