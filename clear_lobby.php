<?php
header('Content-Type: application/json');
require 'db.php';

$pdo->query("DELETE FROM games WHERE status IN ('waiting','running','finished')");
echo json_encode(['ok'=>true]);
