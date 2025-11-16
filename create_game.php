<?php
header('Content-Type: application/json');
require 'db.php';

function random_code($length = 3) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i=0; $i<$length; $i++) {
        $out .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $out;
}

$playerName = trim($_POST['player_name'] ?? '');
$boardSize = (int)($_POST['board_size'] ?? 15);
if (!in_array($boardSize, [5,10,15], true)) { $boardSize = 15; }
if ($playerName === '') {
    echo json_encode(['error' => 'Adj meg egy nevet!']);
    exit;
}

$adminCode = $_POST['admin_code'] ?? '';

$isAdmin = ($playerName === 'András');

if ($isAdmin && $adminCode !== 'a123') {
    echo json_encode(['error' => 'Hibás admin kód András névhez.']);
    exit;
}

// Egy játékos csak egy várakozó mérkőzést indíthat
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM games WHERE status='waiting' AND player_x_name = ?");
$stmt->execute([$playerName]);
$c = (int)$stmt->fetch()['c'];
if ($c >= 1) {
    echo json_encode(['error' => 'Már indítottál egy várakozó mérkőzést. Várd meg, míg ahhoz csatlakoznak vagy fejeződjön be.']);
    exit;
}

try {
    $playerToken = bin2hex(random_bytes(16));
    $code = random_code(3);

    $stmt = $pdo->prepare("INSERT INTO games (code, player_x_token, player_x_name, board_size) VALUES (?, ?, ?, ?)");
    $stmt->execute([$code, $playerToken, $playerName, $boardSize]);
    $gameId = $pdo->lastInsertId();

    echo json_encode([
        'game_id' => (int)$gameId,
        'code' => $code,
        'token' => $playerToken,
        'symbol' => 'X',
        'your_name' => $playerName,
        'is_admin' => $isAdmin
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Hiba a játék létrehozásakor']);
}
