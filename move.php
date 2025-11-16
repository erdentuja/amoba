<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$token  = $_POST['token'] ?? '';
$x      = (int)($_POST['x'] ?? -1);
$y      = (int)($_POST['y'] ?? -1);

if ($gameId <= 0 || $token === '' || $x < 0 || $y < 0) {
    echo json_encode(['error' => 'Rosszul megadott adatok']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();

$size = 15;
if ($game && isset($game['board_size'])) {
    $size = (int)$game['board_size'];
}
if ($size < 5) $size = 5;
if ($size > 15) $size = 15;


if (!$game) {
    echo json_encode(['error' => 'Nincs ilyen játék']);
    exit;
}

if ($game['status'] !== 'running') {
    echo json_encode(['error' => 'A játék nem fut']);
    exit;
}

$symbol = null;
if ($token === $game['player_x_token']) $symbol = 'X';
if ($token === $game['player_o_token']) $symbol = 'O';
if ($symbol === null) {
    echo json_encode(['error' => 'Ismeretlen játékos']);
    exit;
}

if ($symbol !== $game['current_turn']) {
    echo json_encode(['error' => 'Nem a te köröd']);
    exit;
}

// Már foglalt?
$chk = $pdo->prepare("SELECT id FROM moves WHERE game_id = ? AND x = ? AND y = ?");
$chk->execute([$gameId, $x, $y]);
if ($chk->fetch()) {
    echo json_encode(['error' => 'Már foglalt mező']);
    exit;
}

// Lépés mentése
$ins = $pdo->prepare("INSERT INTO moves (game_id, x, y, value) VALUES (?, ?, ?, ?)");
$ins->execute([$gameId, $x, $y, $symbol]);

// Tábla újjáépítése
$board = [];
for ($yy = 0; $yy < $size; $yy++) {
    $row = [];
    for ($xx = 0; $xx < $size; $xx++) {
        $row[] = '';
    }
    $board[] = $row;
}

$m = $pdo->prepare("SELECT x, y, value FROM moves WHERE game_id = ?");
$m->execute([$gameId]);
while ($mv = $m->fetch()) {
    $board[$mv['y']][$mv['x']] = $mv['value'];
}

function checkWinner($board, $lastX, $lastY, $symbol, $size) {
    $dirs = [
        [1,0], [0,1], [1,1], [1,-1]
    ];
    foreach ($dirs as $dir) {
        $dx = $dir[0];
        $dy = $dir[1];
        $count = 1;

        $x = $lastX + $dx;
        $y = $lastY + $dy;
        while ($x >= 0 && $x < $size && $y >= 0 && $y < $size && $board[$y][$x] === $symbol) {
            $count++;
            $x += $dx;
            $y += $dy;
        }

        $x = $lastX - $dx;
        $y = $lastY - $dy;
        while ($x >= 0 && $x < $size && $y >= 0 && $y < $size && $board[$y][$x] === $symbol) {
            $count++;
            $x -= $dx;
            $y -= $dy;
        }

        if ($count >= 5) {
            return true;
        }
    }
    return false;
}

$winner = null;
if (checkWinner($board, $x, $y, $symbol, $size)) {
    $winner = $symbol;
    $upd = $pdo->prepare("UPDATE games SET status='finished', winner=? WHERE id=?");
    $upd->execute([$winner, $gameId]);

    // ranglista frissítése
    $gx = $game['player_x_name'] ?: 'X';
    $go = $game['player_o_name'] ?: 'O';

    $ensure = $pdo->prepare("INSERT IGNORE INTO stats (player_name) VALUES (?), (?)");
    $ensure->execute([$gx, $go]);

    if ($winner === 'X') {
        $updS = $pdo->prepare("UPDATE stats SET wins = wins + 1 WHERE player_name = ?");
        $updS->execute([$gx]);
        $updS = $pdo->prepare("UPDATE stats SET losses = losses + 1 WHERE player_name = ?");
        $updS->execute([$go]);
    } else {
        $updS = $pdo->prepare("UPDATE stats SET wins = wins + 1 WHERE player_name = ?");
        $updS->execute([$go]);
        $updS = $pdo->prepare("UPDATE stats SET losses = losses + 1 WHERE player_name = ?");
        $updS->execute([$gx]);
    }

} else {
    // Tele a tábla?
    $cnt = $pdo->prepare("SELECT COUNT(*) AS c FROM moves WHERE game_id = ?");
    $cnt->execute([$gameId]);
    $c = (int)$cnt->fetch()['c'];
    if ($c >= 15*15) {
        $winner = 'draw';
        $upd = $pdo->prepare("UPDATE games SET status='finished', winner='draw' WHERE id=?");
        $upd->execute([$gameId]);

        $gx = $game['player_x_name'] ?: 'X';
        $go = $game['player_o_name'] ?: 'O';
        $ensure = $pdo->prepare("INSERT IGNORE INTO stats (player_name) VALUES (?), (?)");
        $ensure->execute([$gx, $go]);

        $updS = $pdo->prepare("UPDATE stats SET draws = draws + 1 WHERE player_name IN (?, ?)");
        $updS->execute([$gx, $go]);
    } else {
        // Körváltás
        $next = ($game['current_turn'] === 'X') ? 'O' : 'X';
        $upd = $pdo->prepare("UPDATE games SET current_turn=? WHERE id=?");
        $upd->execute([$next, $gameId]);
    }
}

echo json_encode([
    'ok' => true,
    'winner' => $winner
]);
