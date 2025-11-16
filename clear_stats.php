<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $pdo->query("DELETE FROM stats");
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Hiba a top játékosok törlése közben']);
}
