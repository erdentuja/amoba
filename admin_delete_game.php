<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
if ($gameId <= 0) {
    echo json_encode(['error' => 'Érvénytelen meccs azonosító']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Hiba a meccs törlése közben']);
}
