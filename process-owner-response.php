<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action']; // 'accepted' or 'rejected'
    $ownerId = currentUserId();

    // Verify owner owns this request
    $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ? AND owner_id = ?");
    $stmt->execute([$action, $requestId, $ownerId]);

    header("Location: requests.php?msg=Status updated successfully");
    exit;
}