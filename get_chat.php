<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_GET['game_id'] ?? 0);
$lastId = (int)($_GET['last_id'] ?? 0);

if ($gameId <= 0) {
    echo json_encode(['error' => 'Hiányzó game_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, sender, message, DATE_FORMAT(created_at, '%H:%i:%s') AS time
                       FROM chat_messages
                       WHERE game_id = ? AND id > ?
                       ORDER BY id ASC");
$stmt->execute([$gameId, $lastId]);
$rows = $stmt->fetchAll();

$gstmt = $pdo->prepare("SELECT player_x_name, player_o_name FROM games WHERE id = ?");
$gstmt->execute([$gameId]);
$game = $gstmt->fetch();

echo json_encode([
    'messages'      => $rows,
    'player_x_name' => $game['player_x_name'] ?? null,
    'player_o_name' => $game['player_o_name'] ?? null
]);
