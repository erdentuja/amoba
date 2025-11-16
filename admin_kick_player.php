<?php
header('Content-Type: application/json');
require 'db.php';

$name = trim($_POST['player_name'] ?? '');
if ($name === '') {
    echo json_encode(['error' => 'Hiányzó játékosnév']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM games WHERE player_x_name = ? OR player_o_name = ?");
    $stmt->execute([$name, $name]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Hiba a játékos kirúgása közben']);
}
