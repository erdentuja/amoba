<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$sender = trim($_POST['sender'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($gameId <= 0 || $sender === '' || $message === '') {
    echo json_encode(['error' => 'Hiányzó adat']);
    exit;
}

if (mb_strlen($message) > 500) {
    $message = mb_substr($message, 0, 500);
}

$stmt = $pdo->prepare("INSERT INTO chat_messages (game_id, sender, message) VALUES (?, ?, ?)");
$stmt->execute([$gameId, $sender, $message]);

echo json_encode(['ok' => true]);
