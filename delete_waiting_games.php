<?php
header('Content-Type: application/json');
require 'db.php';

$name = trim($_POST['player_name'] ?? '');
if ($name === '') { echo json_encode(['error'=>'Név hiányzik']); exit; }

// 1) Törlés saját WAITING meccsek
$stmt = $pdo->prepare("DELETE FROM games WHERE status='waiting' AND player_x_name=?");
$stmt->execute([$name]);

// 2/a) Ha X kilép: az O-ból lesz új X, a játék waiting-re áll
$stmt = $pdo->prepare("SELECT id, player_o_name, player_o_token FROM games WHERE status='running' AND player_x_name=?");
$stmt->execute([$name]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $g) {
    $upd = $pdo->prepare("UPDATE games SET
        player_x_name = :nx,
        player_x_token = :nt,
        player_o_name = NULL,
        player_o_token = NULL,
        status='waiting'
        WHERE id=:id");
    $upd->execute([
        ':nx'=>$g['player_o_name'],
        ':nt'=>$g['player_o_token'],
        ':id'=>$g['id']
    ]);
}

// 2/b) Ha O kilép: csak az O törlődik, játék waiting-re áll
$stmt = $pdo->prepare("UPDATE games SET
    player_o_name=NULL,
    player_o_token=NULL,
    status='waiting'
    WHERE status='running' AND player_o_name=?");
$stmt->execute([$name]);

echo json_encode(['ok'=>true]);
