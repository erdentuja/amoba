<?php
header('Content-Type: application/json');
require 'db.php';

$code = $_POST['code'] ?? '';
$code = strtoupper(trim($code));
$playerName = trim($_POST['player_name'] ?? '');
$adminCode = $_POST['admin_code'] ?? '';

if ($playerName === '') {
    echo json_encode(['error' => 'Adj meg egy nevet!']);
    exit;
}

$isAdmin = ($playerName === 'András');
if ($isAdmin && $adminCode !== 'a123') {
    echo json_encode(['error' => 'Hibás admin kód András névhez.']);
    exit;
}

if ($code === '') {
    echo json_encode(['error' => 'Hiányzó kód']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE code = ?");
$stmt->execute([$code]);
$game = $stmt->fetch();

if (!$game) {
    echo json_encode(['error' => 'Nincs ilyen kód']);
    exit;
}

if (trim($game['player_x_name'] ?? '') === $playerName) {
    echo json_encode(['error' => 'Nem csatlakozhatsz a saját indított játékodhoz.']);
    exit;
}

// Ellenőrizzük, hogy a játékos nem csatlakozott-e már másik meccshez O-ként
$check = $pdo->prepare("SELECT COUNT(*) AS c FROM games WHERE status IN ('waiting','running') AND player_o_name = ?");
$check->execute([$playerName]);
$row = $check->fetch();
if ($row && (int)$row['c'] >= 1) {
    echo json_encode(['error' => 'Már csatlakoztál egy másik mérkőzéshez O játékosként. Várd meg, míg befejeződik.']);
    exit;
}

if ($game['status'] !== 'waiting') {
    echo json_encode(['error' => 'Már fut vagy befejeződött']);
    exit;
}

$playerToken = bin2hex(random_bytes(16));

$upd = $pdo->prepare("UPDATE games SET player_o_token = ?, player_o_name = ?, status = 'running' WHERE id = ?");
$upd->execute([$playerToken, $playerName, $game['id']]);

echo json_encode([
    'game_id' => (int)$game['id'],
    'code' => $game['code'],
    'token' => $playerToken,
    'symbol' => 'O',
    'your_name' => $playerName,
    'is_admin' => $isAdmin
]);
