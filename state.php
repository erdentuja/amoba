<?php
header('Content-Type: application/json');
require 'db.php';

$gameId    = (int)($_GET['game_id'] ?? 0);
$token     = $_GET['token'] ?? '';
$spectator = ($_GET['spectator'] ?? '0') === '1';

if (!$gameId) {
    echo json_encode(['error' => 'Hiányzó game_id']);
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

$yourSymbol = null;
$yourName = '';
$opponentName = '';

if (!$spectator) {
    if ($token === '') {
        echo json_encode(['error' => 'Hiányzó token']);
        exit;
    }

    if ($token === $game['player_x_token']) {
        $yourSymbol = 'X';
        $yourName = $game['player_x_name'] ?: 'X';
        $opponentName = $game['player_o_name'] ?: 'O';
    } elseif ($token === $game['player_o_token']) {
        $yourSymbol = 'O';
        $yourName = $game['player_o_name'] ?: 'O';
        $opponentName = $game['player_x_name'] ?: 'X';
    } else {
        echo json_encode(['error' => 'Ismeretlen játékos']);
        exit;
    }
} else {
    $yourSymbol   = null;
    $yourName     = '';
    $opponentName = '';
}

// 15x15 üres tábla
$board = [];
for ($y = 0; $y < $size; $y++) {
    $row = [];
    for ($x = 0; $x < $size; $x++) {
        $row[] = '';
    }
    $board[] = $row;
}

$m = $pdo->prepare("SELECT x, y, value FROM moves WHERE game_id = ?");
$m->execute([$gameId]);
while ($mv = $m->fetch()) {
    if ($mv['x'] >= 0 && $mv['x'] < $size && $mv['y'] >= 0 && $mv['y'] < $size) {
        $board[$mv['y']][$mv['x']] = $mv['value'];
    }
}

// utolsó lépés
$lastMove = null;
$lm = $pdo->prepare("SELECT x, y FROM moves WHERE game_id = ? ORDER BY id DESC LIMIT 1");
$lm->execute([$gameId]);
if ($row = $lm->fetch()) {
    $lastMove = ['x' => (int)$row['x'], 'y' => (int)$row['y']];
}

// győztes vonal keresése, ha vége és van győztes
$winLine = null;
if ($game['status'] === 'finished' && ($game['winner'] === 'X' || $game['winner'] === 'O')) {
    $winner = $game['winner'];
    $dirs = [
        [1,0], [0,1], [1,1], [1,-1]
    ];
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($board[$y][$x] !== $winner) continue;
            foreach ($dirs as $dir) {
                $dx = $dir[0];
                $dy = $dir[1];
                $coords = [[ $x, $y ]];
                $nx = $x + $dx;
                $ny = $y + $dy;
                while ($nx >= 0 && $nx < $size && $ny >= 0 && $ny < $size && $board[$ny][$nx] === $winner) {
                    $coords[] = [ $nx, $ny ];
                    $nx += $dx;
                    $ny += $dy;
                }
                if (count($coords) >= 5) {
                    $winLine = array_slice($coords, 0, 5);
                    break 2;
                }
            }
        }
    }
}

echo json_encode([
    'status'        => $game['status'],
    'winner'        => $game['winner'],
    'current_turn'  => $game['current_turn'],
    'your_symbol'   => $yourSymbol,
    'your_name'     => $yourName,
    'opponent_name' => $opponentName,
    'board'         => $board,
    'board_size'    => $size,
    'last_move'     => $lastMove,
    'win_line'      => $winLine,
    'player_x_name' => $game['player_x_name'],
    'player_o_name' => $game['player_o_name']
]);
