<?php
header('Content-Type: application/json');
require 'db.php';

// Limit: max 3 waiting + 3 running
$limitWaiting = 3;
$limitRunning = 3;

// 10 percnél régebbi várakozó meccsek törlése
$pdo->exec("DELETE FROM games WHERE status='waiting' AND created_at < (NOW() - INTERVAL 10 MINUTE)");

// waiting takarítás
$stmt = $pdo->query("SELECT id FROM games WHERE status='waiting' ORDER BY created_at DESC");
$waiting = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (count($waiting) > $limitWaiting) {
    $delIds = array_slice($waiting, $limitWaiting);
    if (!empty($delIds)) {
        $in = implode(',', array_fill(0, count($delIds), '?'));
        $pdo->prepare("DELETE FROM games WHERE id IN ($in)")->execute($delIds);
    }
}

// running takarítás
$stmt = $pdo->query("SELECT id FROM games WHERE status='running' ORDER BY created_at DESC");
$running = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (count($running) > $limitRunning) {
    $delIds = array_slice($running, $limitRunning);
    if (!empty($delIds)) {
        $in = implode(',', array_fill(0, count($delIds), '?'));
        $pdo->prepare("DELETE FROM games WHERE id IN ($in)")->execute($delIds);
    }
}

// lobby lekérdezés nézőszámmal
$gamesStmt = $pdo->query("
    SELECT g.id, g.code, g.status, g.player_x_name, g.player_o_name, g.winner, g.created_at,
           g.board_size,
           TIMESTAMPDIFF(SECOND, g.created_at, NOW()) AS age_seconds,
           IFNULL(s.spec_count, 0) AS spectators
    FROM games g
    LEFT JOIN (
        SELECT game_id, COUNT(*) AS spec_count
        FROM spectators
        WHERE last_seen > (NOW() - INTERVAL 30 SECOND)
        GROUP BY game_id
    ) s ON s.game_id = g.id
    ORDER BY (g.status='waiting') DESC, g.created_at DESC
");
$games = $gamesStmt->fetchAll();

// remaining_seconds a várakozó meccsekhez (10 perc = 600 mp)
foreach ($games as &$g) {
    if ($g['status'] === 'waiting') {
        if (isset($g['age_seconds'])) {
            $age = (int)$g['age_seconds'];
            $left = 600 - $age;
            if ($left < 0) $left = 0;
            $g['remaining_seconds'] = $left;
        } else {
            $g['remaining_seconds'] = 600;
        }
    }
}
unset($g);

// bejelentkezett játékosok (várakozó + futó meccsekből)
$playerSet = [];
foreach ($games as $g) {
    if (!empty($g['player_x_name'])) {
        $playerSet[$g['player_x_name']] = true;
    }
    if (!empty($g['player_o_name'])) {
        $playerSet[$g['player_o_name']] = true;
    }
}
$players = array_keys($playerSet);
natcasesort($players);
$players = array_values($players);

// ranglista
$statsStmt = $pdo->query("SELECT player_name, wins, losses, draws,
                          (wins+losses+draws) AS games
                          FROM stats
                          ORDER BY wins DESC, games DESC, player_name ASC
                          LIMIT 10");
$stats = $statsStmt->fetchAll();

echo json_encode([
    'games' => $games,
    'stats' => $stats,
    'players' => $players
]);
