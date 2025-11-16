<?php
header('Content-Type: application/json');
require 'db.php';

$gameId = (int)($_POST['game_id'] ?? 0);
$name   = trim($_POST['player_name'] ?? '');

if ($gameId <= 0 || $name === '') {
    echo json_encode(['error' => 'Hiányzó adatok']);
    exit;
}

try {
    // Meccs betöltése
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo json_encode(['ok' => true]); // már nincs ilyen meccs
        exit;
    }

    $px = trim($game['player_x_name'] ?? '');
    $po = trim($game['player_o_name'] ?? '');

    if ($px !== $name && $po !== $name) {
        echo json_encode(['error' => 'Nem vagy résztvevője ennek a meccsnek']);
        exit;
    }

    // Ha csak egy játékos volt, akkor egyszerűen töröljük a meccset
    if ($px && !$po && $px === $name) {
        $delMoves = $pdo->prepare("DELETE FROM moves WHERE game_id = ?");
        $delMoves->execute([$gameId]);
        $delSpecs = $pdo->prepare("DELETE FROM spectators WHERE game_id = ?");
        $delSpecs->execute([$gameId]);
        $delGame = $pdo->prepare("DELETE FROM games WHERE id = ?");
        $delGame->execute([$gameId]);
        echo json_encode(['ok' => true]);
        exit;
    }
    if ($po && !$px && $po === $name) {
        $delMoves = $pdo->prepare("DELETE FROM moves WHERE game_id = ?");
        $delMoves->execute([$gameId]);
        $delSpecs = $pdo->prepare("DELETE FROM spectators WHERE game_id = ?");
        $delSpecs->execute([$gameId]);
        $delGame = $pdo->prepare("DELETE FROM games WHERE id = ?");
        $delGame->execute([$gameId]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Ha mindkét játékos megvolt, akkor a kilépő helyett maradjon egy "várakozó" meccs
    // Az, aki bent marad, X-ként folytathatja, új ellenfél tud csatlakozni.

    $newPxName   = $px;
    $newPxToken  = $game['player_x_token'] ?? null;
    $newPoName   = null;
    $newPoToken  = null;

    if ($px === $name && $po !== '') {
        // X lép ki, O marad -> O lesz az új X
        $newPxName  = $po;
        $newPxToken = $game['player_o_token'] ?? null;
    } elseif ($po === $name && $px !== '') {
        // O lép ki, X marad -> X marad X
        $newPxName  = $px;
        $newPxToken = $game['player_x_token'] ?? null;
    }

    // Lépések és nézők törlése – új parti indul majd
    $delMoves = $pdo->prepare("DELETE FROM moves WHERE game_id = ?");
    $delMoves->execute([$gameId]);

    $delSpecs = $pdo->prepare("DELETE FROM spectators WHERE game_id = ?");
    $delSpecs->execute([$gameId]);

    // Meccs visszaállítása várakozó állapotba
    $upd = $pdo->prepare("
        UPDATE games
        SET status      = 'waiting',
            winner      = NULL,
            current_turn= 'X',
            player_x_name  = ?,
            player_x_token = ?,
            player_o_name  = NULL,
            player_o_token = NULL,
            created_at     = NOW()
        WHERE id = ?
    ");
    $upd->execute([$newPxName, $newPxToken, $gameId]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Hiba a kilépés közben']);
}
