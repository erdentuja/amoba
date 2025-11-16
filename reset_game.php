<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$token  = $_POST['token'] ?? '';

if ($gameId <= 0 || $token === '') {
    echo json_encode(['error' => 'Hiányzó adatok']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    echo json_encode(['error' => 'Nincs ilyen játék']);
    exit;
}

// Csak befejezett meccset lehessen újraindítani
if ($game['status'] !== 'finished') {
    echo json_encode(['error' => 'Csak befejezett meccs indítható újra.']);
    exit;
}

// Mindkét játékosnak bent kell lennie
if (empty($game['player_x_name']) || empty($game['player_o_name'])) {
    echo json_encode(['error' => 'Az ellenfeled kilépett, ez a meccs már nem indítható újra.']);
    exit;
}

if ($token !== $game['player_x_token'] && $token !== $game['player_o_token']) {
    echo json_encode(['error' => 'Nincs jogosultság ehhez a játékhoz']);
    exit;
}

// Lépések törlése
$del = $pdo->prepare("DELETE FROM moves WHERE game_id = ?");
$del->execute([$gameId]);

// Játék visszaállítása
$upd = $pdo->prepare("UPDATE games SET status='running', winner=NULL, current_turn='X' WHERE id=?");
$upd->execute([$gameId]);

echo json_encode(['ok' => true]);
