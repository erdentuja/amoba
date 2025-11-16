<?php
header('Content-Type: application/json');
require 'db.php';

$name = trim($_POST['player_name'] ?? '');
if ($name === '') { echo json_encode(['error'=>'Név hiányzik']); exit; }

// Admin András mindig beléphessen, akkor is, ha régi meccsben még benne ragadt a neve
if ($name === 'András') {
    echo json_encode(['in_use'=> false]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM games WHERE status='running' AND (player_x_name=? OR player_o_name=?)");
$stmt->execute([$name,$name]);
$c = (int)$stmt->fetch()['c'];

echo json_encode(['in_use'=> $c>0]);
