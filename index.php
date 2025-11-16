<?php
// Amőba Online – teljes verzió v1.6.0 (admin András + max 1 várakozó játék)
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Amőba Online Lobby</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --cell-size: 34px;
            --cell-font: 20px;
        }
        @media (max-width: 768px) {
            :root {
                --cell-size: 40px;
                --cell-font: 22px;
            }
        }
        @media (max-width: 480px) {
            :root {
                --cell-size: 44px;
                --cell-font: 26px;
            }
        }
        body {
            font-family: Arial, sans-serif;
            padding: 10px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color:#f5f5f5;
        }
        h1 {
            font-size: 1.6rem;
            text-align:center;
            margin-bottom: 10px;
        }
        .app {
            max-width:1100px;
            margin:0 auto;
            display:grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.4fr);
            gap:12px;
        }
        @media (max-width: 900px) {
            .app {
                grid-template-columns: minmax(0,1fr);
            }
            /* Mobil nézetben a lobby kártya kerüljön a játék kártya fölé */
            .app > .card:first-of-type {
                order: 2;
            }
            .app > .card:last-of-type {
                order: 1;
            }
        }
        .card {
            background:rgba(255,255,255,0.08);
            border-radius:10px;
            padding:10px;
            box-shadow:0 4px 10px rgba(0,0,0,0.25);
            backdrop-filter: blur(6px);
        }
        #controls {
            margin-bottom: 8px;
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        #controls-row {
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            align-items:center;
        }
        input[type=text]{
            padding:6px 8px;
            font-size:0.9rem;
            border-radius:4px;
            border:1px solid rgba(255,255,255,0.4);
            background:rgba(0,0,0,0.2);
            color:#fff;
        }
        input[type=text]::placeholder {
            color:rgba(255,255,255,0.6);
        }
        button {
            padding:7px 10px;
            font-size:0.9rem;
            border-radius:4px;
            border:1px solid transparent;
            cursor:pointer;
            font-weight:bold;
        }
        button.primary {
            background:#ff9800;
            border-color:#ffb74d;
            color:#1b1b1b;
        }
        button.secondary {
            background:#26c6da;
            border-color:#4dd0e1;
            color:#00363a;
        }
        button.ghost {
            background:transparent;
            border-color:rgba(255,255,255,0.5);
            color:#fff;
        }
        button.danger {
            background:#e53935;
            border-color:#ef5350;
            color:#ffebee;
        }
        button:disabled {
            opacity:0.5;
            cursor:default;
        }
        #top-bar {
            display:flex;
            flex-direction:column;
            gap:4px;
            margin-bottom:4px;
        }
        #gameInfo {
            font-style: italic;
            font-size:0.9rem;
        }
        #scoreboard {
            margin-top:4px;
            font-weight:bold;
            font-size:0.95rem;
        }
        #status {
            margin-top:8px;
            font-size:0.95rem;
            min-height:1.2em;
        }
        #boardWrapper {
            margin-top: 10px;
            border-radius:12px;
            background:#0d1b2a url('board-bg.jpg') center/cover no-repeat;
            background-blend-mode: soft-light;
            padding:12px;
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
            position:relative;
        }
        #boardWrapper::before {
            content:'';
            position:absolute;
            inset:0;
            background:rgba(0,0,0,0.35);
            border-radius:12px;
            pointer-events:none;
        }
        #board {
            position:relative;
            display:grid;
            grid-template-columns: repeat(15, var(--cell-size));
            grid-template-rows: repeat(15, var(--cell-size));
            gap:1px;
            z-index:1;
        }
        .cell {
            width: var(--cell-size);
            height: var(--cell-size);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:var(--cell-font);
            cursor:pointer;
            user-select:none;
            background:rgba(0,0,0,0.25);
            border-radius:6px;
            transition:
                background 0.12s ease,
                box-shadow 0.12s ease,
                transform 0.08s ease;
        }
.cell:hover {
            background:rgba(38,198,218,0.22);
            box-shadow:0 0 6px rgba(38,198,218,0.7);
        }

        .cell.filled {
            animation: cell-pop 0.18s ease-out;
        }

        @keyframes cell-pop {
            0% {
                transform: scale(0.4);
                opacity:0;
            }
            100% {
                transform: scale(1);
                opacity:1;
            }
        }
        .cell:hover {
            background:#1b3358;
        }
        .cell.disabled {
            cursor:default;
            opacity:0.8;
        }
        .x-cell {
            color:#4fc3f7;
            text-shadow:0 0 8px rgba(79,195,247,0.8);
        }
        .o-cell {
            color:#ff8a80;
            text-shadow:0 0 8px rgba(255,138,128,0.8);
        }
        .last-move {
            box-shadow:0 0 0 3px #ffeb3b inset;
        }
        .win-cell {
            background:#263238 !important;
            animation: winPulse 0.5s ease-in-out infinite alternate;
        }
        @keyframes winPulse {
            from { transform: scale(1.0); box-shadow:0 0 12px rgba(255,215,64,0.5); }
            to   { transform: scale(1.07); box-shadow:0 0 18px rgba(255,235,59,0.9); }
        }
        #winLine {
            position:absolute;
            height:4px;
            background:linear-gradient(90deg,#ffeb3b,#ff9800);
            transform-origin:0 50%;
            pointer-events:none;
            display:none;
            box-shadow:0 0 10px rgba(255,193,7,0.8);
        }
        #lobby-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:6px;
            margin-bottom:4px;
        }
        #lobby-games, #ranking-list {
            margin-top:6px;
            font-size:0.9rem;
        }
        .lobby-item {
            display:flex;
            justify-content:space-between;
            align-items:stretch;
            gap:16px;
            padding:10px 14px;
            border-radius:10px;
            background:#161b22;
            border:1px solid #2388ff33;
            margin-bottom:10px;
        }
        .lobby-main {
            display:flex;
            flex-direction:column;
            gap:6px;
            flex:1;
        }
        .lobby-main-top {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
        }
        .lobby-main-bottom {
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:0.78rem;
            opacity:0.9;
        }
        .lobby-code {
            font-weight:bold;
            letter-spacing:0.08em;
            text-transform:uppercase;
            color:#58a6ff;
            font-size:0.85rem;
        }
        .lobby-status {
            display:flex;
            align-items:center;
            gap:6px;
            font-size:0.8rem;
        }

        .lobby-names {
            font-weight:700;
            font-size:1.05rem;
            margin:1px 0 2px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .lobby-player-x {
            color:#4fc3f7;
            text-shadow:0 0 6px rgba(79,195,247,0.7);
            display:inline-flex;
            align-items:center;
            gap:4px;
        }
        .lobby-player-x::before {
            content:'❌';
            font-size:0.9em;
            opacity:0.95;
        }
        .lobby-player-o {
            color:#ff8a80;
            text-shadow:0 0 6px rgba(255,138,128,0.7);
            display:inline-flex;
            align-items:center;
            gap:4px;
        }
        .lobby-player-o::before {
            content:'⭕';
            font-size:0.9em;
            opacity:0.95;
        }
        .lobby-vs {
            opacity:0.9;
            margin:0 4px;
        }
        #playerLine {
            font-size:1rem;
            font-weight:700;
            margin-bottom:2px;
        }
        #playerLine .player-name-x {
            color:#4fc3f7;
            text-shadow:0 0 6px rgba(79,195,247,0.7);
        }
        #playerLine .player-name-o {
            color:#ff8a80;
            text-shadow:0 0 6px rgba(255,138,128,0.7);
        }
        #playerLine .player-vs {
            opacity:0.8;
            margin:0 6px;
        }
        .pill {
            display:inline-block;
            padding:2px 6px;
            border-radius:999px;
            font-size:0.75rem;
            margin-left:4px;
        }
        .pill-waiting { background:#ffeb3b; color:#3e2723; }
        .pill-running { background:#4caf50; color:#e8f5e9; }
        .pill-finished { background:#9e9e9e; color:#212121; }
        .lobby-extra {
            font-size:0.78rem;
            opacity:0.85;
            margin-top:2px;
            text-align:right;
        }
        .lobby-timer {
            font-size:0.78rem;
            margin-top:2px;
            color:#ffcc80;
        }
        .ranking-header,
        .ranking-row {
            display:grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            column-gap:8px;
            align-items:center;
            padding:3px 0;
        }
        .ranking-header {
            font-size:0.8rem;
            text-transform:uppercase;
            letter-spacing:0.04em;
            border-bottom:1px solid rgba(255,255,255,0.25);
            margin-bottom:2px;
        }
        .ranking-header div {
            font-weight:600;
        }
        .ranking-row {
            border-bottom:1px solid rgba(255,255,255,0.1);
            font-size:0.85rem;
        }
        .ranking-row-name {
            font-weight:600;
        }
        .ranking-row-num {
            text-align:center;
        }
        #chat-card {
            margin-top:10px;
        }
        #chat-messages {
            margin-top:4px;
            background:rgba(0,0,0,0.35);
            border-radius:6px;
            padding:6px;
            max-height:180px;
            overflow-y:auto;
            font-size:0.85rem;
        }
        .chat-line {
            margin-bottom:3px;
        }
        .chat-time {
            opacity:0.7;
            font-size:0.75rem;
            margin-right:4px;
        }
        .chat-sender {
            font-weight:bold;
            margin-right:4px;
        }
        .chat-badge {
            display:inline-block;
            min-width:16px;
            padding:0 4px;
            text-align:center;
            border-radius:999px;
            font-size:0.7rem;
            margin-right:4px;
            color:#000;
            font-weight:bold;
        }
        .badge-x { background:#4fc3f7; color:#003c5f; }
        .badge-o { background:#ff8a80; color:#7f0000; }
        .badge-s { background:#cfd8dc; color:#263238; }
        #chat-input-row {
            margin-top:4px;
            display:flex;
            gap:4px;
        }
        #chatMessage {
            flex:1;
        }
        #version-badge {
            max-width:1100px;
            margin:8px auto 0;
            text-align:right;
            font-size:0.75rem;
            opacity:0.9;
        }
        #version-badge span {
            display:inline-block;
            padding:3px 8px;
            border-radius:999px;
            background:rgba(38,198,218,0.2);
            border:1px solid rgba(38,198,218,0.6);
            color:#e0f7fa;
            box-shadow:0 0 6px rgba(0,0,0,0.35);
        }
        /* Modal */
        #result-modal-overlay {
            position:fixed;
            inset:0;
            background:rgba(0,0,0,0.55);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:999;
        }
        #result-modal {
            position: relative;
            background:#0b1d33;
            padding:16px 18px;
            border-radius:16px;
            box-shadow:0 18px 40px rgba(0,0,0,0.75);
            max-width:300px;
            width:90%;
            transform:translateY(20px) scale(0.9);
            opacity:0;
            transition: all 0.2s ease-out;
            text-align:center;
            overflow: hidden;
        }

        /* finom fény a háttérben */
        #result-modal::before {
            content:'';
            position:absolute;
            inset:-40%;
            background:
                radial-gradient(circle at 0% 0%, rgba(255,255,255,0.04), transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(38,198,218,0.2), transparent 70%);
            opacity:0.8;
            filter: blur(18px);
            z-index:-1;
        }

        @keyframes modal-pop {
            0% {
                transform: translateY(20px) scale(0.85);
                opacity:0;
            }
            60% {
                transform: translateY(-4px) scale(1.03);
                opacity:1;
            }
            100% {
                transform: translateY(0) scale(1);
                opacity:1;
            }
        }

        #result-modal.show {
            transform:translateY(0) scale(1);
            opacity:1;
            animation: modal-pop 0.25s ease-out;
        }
        #result-modal-title {
            font-size:1.3rem;
            margin-bottom:6px;
        }
        #result-modal-text {
            font-size:0.95rem;
            margin-bottom:12px;
        }
        #result-modal-icon {
            font-size:2rem;
            margin-bottom:6px;
        }
    
        .hidden-join {
            display:none !important;
        }
        .admin-panel {
            margin-top:8px;
            padding:8px 10px;
            border-radius:8px;
            background:rgba(255,255,255,0.03);
            border:1px dashed rgba(255,255,255,0.18);
            font-size:0.8rem;
        }
        .admin-title {
            font-weight:bold;
            margin-bottom:4px;
            opacity:0.9;
        }
        .admin-buttons {
            display:flex;
            flex-wrap:wrap;
            gap:6px;
            margin-bottom:4px;
        }
        .admin-panel button {
            font-size:0.8rem;
            padding:4px 8px;
        }
        .admin-hint {
            opacity:0.7;
            font-size:0.75rem;
        }

    
        .name-row {
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:6px;
            margin-bottom:6px;
        }
        .name-row label {
            font-weight:bold;
            font-size:0.9rem;
        }
        .name-row input {
            max-width:160px;
        }
        .loggedin #welcomeText {
            font-weight:bold;
        }
        .admin-badge {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:2px 8px;
            border-radius:999px;
            font-size:0.75rem;
            background:linear-gradient(135deg,#ff1744,#ff9100);
            color:#fff;
            box-shadow:0 0 8px rgba(255,87,34,0.7);
        }
        .admin-panel {
            display:none;
            position:relative;
            margin-top:8px;
            padding:8px 10px;
            border-radius:8px;
            background:rgba(255,255,255,0.03);
            border:1px dashed rgba(255,255,255,0.4);
            font-size:0.8rem;
            box-shadow:0 0 16px rgba(255,138,101,0.35);
        }
        .admin-panel::before {
            content:'ADMIN';
            position:absolute;
            top:-10px;
            right:10px;
            font-size:0.65rem;
            letter-spacing:0.12em;
            background:#ff5722;
            color:#000;
            padding:2px 6px;
            border-radius:999px;
            box-shadow:0 0 8px rgba(255,87,34,0.8);
        }

    
        .admin-board {
            margin-top:6px;
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:6px;
        }
.admin-games {
            margin-top:8px;
            padding-top:6px;
            border-top:1px solid rgba(255,255,255,0.08);
            font-size:0.75rem;
        }
        .admin-games-title {
            opacity:0.8;
            margin-bottom:4px;
        }
        .admin-games-list {
            display:flex;
            flex-direction:column;
            gap:2px;
            max-height:140px;
            overflow-y:auto;
        }
        .admin-game-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:6px;
        }
        .admin-game-info {
            flex:1;
            opacity:0.9;
        }
        .admin-game-code {
            font-weight:600;
        }
        .admin-game-names {
            font-size:0.7rem;
            opacity:0.8;
        }
        .admin-game-delete {
            font-size:0.7rem;
            padding:2px 6px;
        }
        .admin-board-label {
            font-size:0.8rem;
            opacity:0.9;
        }
        .admin-board-buttons {
            display:flex;
            gap:4px;
        }
        .admin-players {
            margin-top:6px;
        }
        .admin-players-title {
            font-weight:600;
            margin-bottom:4px;
        }
        .admin-players-list {
            display:flex;
            flex-direction:column;
            gap:2px;
            max-height:140px;
            overflow-y:auto;
        }
        .admin-player-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:6px;
        }
        .admin-player-name {
            flex:1;
            opacity:0.9;
        }
        .admin-player-kick {
            font-size:0.7rem;
            padding:2px 6px;
        }
        .admin-games-subtitle {
            font-size:0.75rem;
            font-weight:600;
            opacity:0.8;
            margin-top:4px;
            margin-bottom:2px;
        }
        #result-modal-actions {
            display:flex;
            justify-content:center;
            gap:8px;
            margin-top:10px;
        }
            display:flex;
            gap:4px;
        }
        .size-btn {
            font-size:0.75rem;
            padding:3px 8px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,0.25);
            background:rgba(0,0,0,0.5);
        }
        .size-btn-active {
            background:linear-gradient(135deg,#26c6da,#00e676);
            color:#000;
            box-shadow:0 0 10px rgba(0,230,118,0.7);
            border-color:transparent;
        }

</style>
<link rel="stylesheet" href="modern.css">
</head>
<body>
<audio id="snd-move" src="sounds/move.mp3" preload="auto"></audio>
<audio id="snd-win" src="sounds/win.mp3" preload="auto"></audio>
<audio id="snd-exit" src="sounds/exit.mp3" preload="auto"></audio>

<h1>Amőba Online</h1>

<div class="app">
    <div class="card">
        <div id="controls">
            <div id="name-login-row" class="name-row">
                <label for="playerName">Add meg a neved:</label>
                <input type="text" id="playerName" placeholder="Pl. András">
                <button id="btnSetName" class="secondary">Belépés</button>
            </div>
            <div id="name-loggedin-row" class="name-row loggedin" style="display:none;">
                <span id="welcomeText">Üdvözöllek!</span>
                <span id="adminBadge" class="admin-badge" style="display:none;">ADMIN</span>
                <button id="btnLogout" class="ghost">Kijelentkezés</button>
            </div>
            <div id="controls-row">
                <button id="btnCreate" class="primary">Új játék indítása (X)</button>
                <span class="hidden-join">Csatlakozás kóddal:</span>
                <input type="text" id="joinCode" maxlength="3" placeholder="3 karakteres kód" class="hidden-join">
                <button id="btnJoin" class="secondary hidden-join">Csatlakozom (O)</button>
                <button id="btnRematch" class="ghost" disabled>Új meccs ugyanazzal a párral</button>
            </div>
        </div>
        <div id="admin-panel" class="admin-panel">
            <div class="admin-title">Admin panel (András)</div>
            <div class="admin-buttons">
                <button id="btnClearLobby" class="danger">Lobby törlése</button>
                <button id="btnClearStats" class="danger">Top játékosok törlése</button>
            </div>
            <div class="admin-board">
                <span class="admin-board-label">Játéktér mérete:</span>
                <div class="admin-board-buttons">
                    <button class="size-btn size-btn-active" data-size="15">15×15</button>
                    <button class="size-btn" data-size="10">10×10</button>
                    <button class="size-btn" data-size="5">5×5</button>
                </div>
            </div>
            <div id="admin-games" class="admin-games">
                <div class="admin-games-title">Aktív meccsek</div>
                <div class="admin-games-list"></div>
            </div>
            <div id="admin-players" class="admin-players">
                <div class="admin-players-title">Bejelentkezett játékosok</div>
                <div class="admin-players-list"></div>
            </div>
        </div>
        <div id="top-bar">
            <div id="playerLine"></div>
            <div id="gameInfo"></div>
            <div id="scoreboard">Pontszám (helyi): X: 0 | O: 0 | Döntetlen: 0</div>
        </div>
        <div id="status"></div>
        <div id="boardWrapper">
            <div id="board">
                <div id="winLine"></div>
            </div>
        </div>
        <div id="chat-card">
            <h3 style="margin:6px 0 2px;font-size:0.95rem;">Chat a játék alatt</h3>
            <div id="chat-messages"></div>
            <div id="chat-input-row">
                <input type="text" id="chatMessage" placeholder="Írj üzenetet...">
                <button id="btnChatSend" class="secondary">Küldés</button>
            </div>
        </div>
    </div>
    <div class="card">
        <div id="lobby-header">
            <h2 style="margin:0;font-size:1.1rem;">Lobby</h2>
        </div>
        <div style="font-size:0.8rem;opacity:0.85;margin-bottom:4px;">
            Maximum 3 várakozó és 3 futó meccs látszik egyszerre. Egy játékos csak egy mérkőzést indíthat.</div>
        <div id="lobby-games"></div>
        <h2 style="margin-top:10px;font-size:1.1rem;">Top játékosok</h2>
        <div id="ranking-list"></div>
    </div>
</div>

<div id="version-badge">
    <span>Amőba Online – v1.8.3</span>
</div>

<!-- Modal -->
<div id="result-modal-overlay">
    <div id="result-modal">
        <div id="result-modal-icon">🏆</div>
        <div id="result-modal-title">Győzelem!</div>
        <div id="result-modal-text">Szép játék volt!</div>
        <div id="result-modal-actions">
            <button id="result-modal-rematch" class="primary">Igen</button>
            <button id="result-modal-newgame" class="secondary">Új játék</button>
        </div>
    </div>
</div>

<script>
let gameId = null;
let token = null;
let mySymbol = null;
let myName = '';
let opponentName = '';
let pollTimer = null;
let lobbyTimer = null;
let chatTimer = null;
let lobbyCountdowns = {};

function clearLobbyCountdowns() {
    for (const id in lobbyCountdowns) {
        clearInterval(lobbyCountdowns[id]);
    }
    lobbyCountdowns = {};
}

function formatSecondsToMMSS(sec) {
    sec = Math.max(0, Math.floor(sec));
    const m = String(Math.floor(sec / 60)).padStart(2,'0');
    const s = String(sec % 60).padStart(2,'0');
    return m + ':' + s;
}

let spectatorPingTimer = null;
let lastChatId = 0;
let spectatorMode = false;
let spectatorToken = null;

let adminCode = '';
let isAdmin = false;

let scoreX = 0;
let scoreO = 0;
let scoreDraw = 0;
// Betöltéskor próbáljuk visszaállítani a korábbi belépést
try {
    const savedName = localStorage.getItem('amoba_name') || '';
    const savedIsAdmin = localStorage.getItem('amoba_is_admin') === '1';
    const savedAdminCode = localStorage.getItem('amoba_admin_code') || '';
    if (savedName) {
        myName = savedName;
        isAdmin = savedIsAdmin;
        adminCode = savedAdminCode;
        const nameInput = document.getElementById('playerName');
        if (nameInput) nameInput.value = savedName;
    }
} catch(e) {
    console.warn('LocalStorage nem elérhető:', e);
}

let lastWinnerShown = null;

let boardSize = 15;

let chatPlayerXName = '';
let chatPlayerOName = '';

let lastGameStatus = null;
let opponentJoinedNotified = false;

const boardDiv = document.getElementById('board');
const nameLoginRow = document.getElementById('name-login-row');
const nameLoggedInRow = document.getElementById('name-loggedin-row');
const welcomeText = document.getElementById('welcomeText');
const adminBadge = document.getElementById('adminBadge');
const adminPanel = document.getElementById('admin-panel');
const sizeButtons = document.querySelectorAll('.size-btn');
const adminGamesList = document.querySelector('#admin-games .admin-games-list');
const adminPlayersList = document.querySelector('#admin-players .admin-players-list');
const btnSetName = document.getElementById('btnSetName');
const btnLogout = document.getElementById('btnLogout');
const statusDiv = document.getElementById('status');
const infoDiv = document.getElementById('gameInfo');
const scoreDiv = document.getElementById('scoreboard');
const playerLineDiv = document.getElementById('playerLine');
const rematchBtn = document.getElementById('btnRematch');
const winLineDiv = document.getElementById('winLine');

let ggwpBadge = null;

function showWinEffects() {
    if (winLineDiv) {
        winLineDiv.classList.add('win-glow');
    }
    if (!ggwpBadge) {
        ggwpBadge = document.createElement('div');
        ggwpBadge.id = 'ggwp-badge';
        ggwpBadge.textContent = 'GG WP';
        const boardWrapper = document.getElementById('board-wrapper') || boardDiv.parentElement;
        if (boardWrapper) {
            boardWrapper.style.position = 'relative';
            boardWrapper.appendChild(ggwpBadge);
        }
        setTimeout(()=>{
            if (ggwpBadge && ggwpBadge.parentElement) {
                ggwpBadge.parentElement.removeChild(ggwpBadge);
                ggwpBadge = null;
            }
        }, 2200);
    }
}

const lobbyGamesDiv = document.getElementById('lobby-games');
const rankingDiv = document.getElementById('ranking-list');
const btnClearLobby = document.getElementById('btnClearLobby');

const chatMessagesDiv = document.getElementById('chat-messages');
const chatInput = document.getElementById('chatMessage');
const chatSendBtn = document.getElementById('btnChatSend');


// Név kezelés + admin mód


function applyLoginState() {
    const name = myName || '';
    if (!nameLoginRow || !nameLoggedInRow) return;
    if (!name) {
        nameLoginRow.style.display = 'flex';
        nameLoggedInRow.style.display = 'none';
        if (adminPanel) adminPanel.style.display = 'none';
        if (adminBadge) adminBadge.style.display = 'none';
        isAdmin = false;
        adminCode = '';
    } else {
        nameLoginRow.style.display = 'none';
        nameLoggedInRow.style.display = 'flex';
        if (welcomeText) welcomeText.textContent = `Üdvözöllek, ${name}!`;
        if (isAdmin) {
            if (adminPanel) adminPanel.style.display = 'block';
            if (adminBadge) adminBadge.style.display = 'inline-flex';
        } else {
            if (adminPanel) adminPanel.style.display = 'none';
            if (adminBadge) adminBadge.style.display = 'none';
        }
    }
}

if (btnSetName) {
    btnSetName.addEventListener('click', ()=>{
        const nameInput = document.getElementById('playerName');
        const name = (nameInput ? nameInput.value.trim() : '');
        if (!name) {
            alert('Adj meg egy nevet!');
            return;
        }
        // admin kód bekérése, ha András
        if (name === 'András') {
            const code = prompt('Admin kód (András):');
            if (code === null || !code) return;
            adminCode = code;
            isAdmin = true;
        } else {
            adminCode = '';
            isAdmin = false;
        }

        // név foglaltság ellenőrzése szerveren
        fetch('check_name.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({player_name:name})
        }).then(r=>r.json()).then(d=>{
            if (d.error) {
                alert(d.error);
                return;
            }
            if (d.in_use) {
                alert('Ezzel a névvel már játszik valaki. Válassz másik nevet!');
                return;
            }
            myName = name;
            // persist local session
            try {
                localStorage.setItem('amoba_name', myName);
                localStorage.setItem('amoba_is_admin', isAdmin ? '1' : '0');
                localStorage.setItem('amoba_admin_code', adminCode || '');
            } catch(e) {}
            applyLoginState();
        }).catch(()=>{
            alert('Név ellenőrzési hiba. Próbáld újra később.');
        });
    });
}
if (btnLogout) {
    btnLogout.addEventListener('click', ()=>{
        const nameToClear = myName || (document.getElementById('playerName') ? document.getElementById('playerName').value.trim() : '');
        // kérjük a szervert, hogy törölje az adott játékos várakozó / futó meccseit
        const doClientClear = ()=>{
            myName = '';
            adminCode = '';
            isAdmin = false;
            const nameInput = document.getElementById('playerName');
            if (nameInput) nameInput.value = '';
            try {
                localStorage.removeItem('amoba_name');
                localStorage.removeItem('amoba_is_admin');
                localStorage.removeItem('amoba_admin_code');
            } catch(e) {}
            applyLoginState();
            window.location.reload();
        };

        if (nameToClear) {
            fetch('delete_waiting_games.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:new URLSearchParams({player_name:nameToClear})
            }).finally(doClientClear);
        } else {
            doClientClear();
        }
    });
}

if (sizeButtons && sizeButtons.length) {
    sizeButtons.forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if (!isAdmin) {
                alert('A tábla méretét csak az admin állíthatja.');
                return;
            }
            

function refreshAdminGames(latestData) {
    if (!adminGamesList) return;
    if (!isAdmin) {
        adminGamesList.innerHTML = '';
        if (adminPlayersList) adminPlayersList.innerHTML = '';
        return;
    }
    const games = latestData && latestData.games ? latestData.games : null;
    if (!games) return;

    // --- Játékok listája: várakozó és futó külön csoportban ---
    adminGamesList.innerHTML = '';

    const waiting = games.filter(g => g.status === 'waiting');
    const running = games.filter(g => g.status === 'running');

    function addGameGroup(title, list) {
        if (!list.length) return;
        const subtitle = document.createElement('div');
        subtitle.className = 'admin-games-subtitle';
        subtitle.textContent = title;
        adminGamesList.appendChild(subtitle);

        list.forEach(g => {
            const row = document.createElement('div');
            row.className = 'admin-game-row';
            const info = document.createElement('div');
            info.className = 'admin-game-info';
            const codeSpan = document.createElement('div');
            codeSpan.className = 'admin-game-code';
            codeSpan.textContent = 'Kód: ' + g.code + ' (' + g.status + ')';
            const namesSpan = document.createElement('div');
            namesSpan.className = 'admin-game-names';
            const px = g.player_x_name || 'X';
            const po = g.player_o_name || (g.status === 'waiting' ? '...' : 'O');
            namesSpan.textContent = px + ' vs ' + po;
            info.appendChild(codeSpan);
            info.appendChild(namesSpan);

            const delBtn = document.createElement('button');
            delBtn.className = 'admin-game-delete danger';
            delBtn.textContent = 'Törlés';
            delBtn.addEventListener('click', () => {
                if (!confirm('Biztosan törlöd ezt a meccset (' + g.code + ')?')) return;
                fetch('admin_delete_game.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:new URLSearchParams({game_id:g.id})
                }).then(r=>r.json()).then(d=>{
                    if (d && d.ok) {
                        refreshLobby();
                    } else if (d && d.error) {
                        alert(d.error);
                    }
                }).catch(()=>{ alert('Hiba a meccs törlése közben.'); });
            });

            row.appendChild(info);
            row.appendChild(delBtn);
            adminGamesList.appendChild(row);
        });
    }

    addGameGroup('Várakozó meccsek', waiting);
    addGameGroup('Futó meccsek', running);

    // --- Bejelentkezett játékosok listája ---
    if (adminPlayersList) {
        const players = latestData && Array.isArray(latestData.players) ? latestData.players : [];
        adminPlayersList.innerHTML = '';
        players.forEach(name => {
            const row = document.createElement('div');
            row.className = 'admin-player-row';
            const nameDiv = document.createElement('div');
            nameDiv.className = 'admin-player-name';
            nameDiv.textContent = name;

            const kickBtn = document.createElement('button');
            kickBtn.className = 'admin-player-kick danger';
            kickBtn.textContent = 'Kirúgás';
            kickBtn.addEventListener('click', () => {
                if (!confirm('Biztosan kirúgod ezt a játékost: ' + name + '?')) return;
                fetch('admin_kick_player.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:new URLSearchParams({player_name:name})
                }).then(r=>r.json()).then(d=>{
                    if (d && d.ok) {
                        refreshLobby();
                    } else if (d && d.error) {
                        alert(d.error);
                    }
                }).catch(()=>{ alert('Hiba a játékos kirúgása közben.'); });
            });

            row.appendChild(nameDiv);
            row.appendChild(kickBtn);
            adminPlayersList.appendChild(row);
        });
    }
}
const val = parseInt(btn.getAttribute('data-size'),10);
            if (!val || [5,10,15].indexOf(val) === -1) return;
            boardSize = val;
            sizeButtons.forEach(b=>b.classList.remove('size-btn-active'));
            btn.classList.add('size-btn-active');
            // új tábla létrehozása vizuálisan
            createBoard();
        });
    });
}

// Modal elemek
const modalOverlay = document.getElementById('result-modal-overlay');
const modalBox = document.getElementById('result-modal');
const modalIcon = document.getElementById('result-modal-icon');
const modalTitle = document.getElementById('result-modal-title');
const modalText = document.getElementById('result-modal-text');
const modalRematchBtn = document.getElementById('result-modal-rematch');
const modalNewGameBtn = document.getElementById('result-modal-newgame');

const sndMove = document.getElementById('snd-move');
const sndWin = document.getElementById('snd-win');
const sndExit = document.getElementById('snd-exit');

function playSound(el) {
    if (!el) return;
    try {
        el.currentTime = 0;
        el.play();
    } catch(e) {
        // autoplay tiltás esetén csendben bukjon
        console.warn('Sound play blocked', e);
    }
}


function updateScoreboard() {
    scoreDiv.textContent = `Pontszám (helyi): X: ${scoreX} | O: ${scoreO} | Döntetlen: ${scoreDraw}`;
}

function createBoard() {
    const children = Array.from(boardDiv.children);
    for (const ch of children) {
        if (ch !== winLineDiv) boardDiv.removeChild(ch);
    }
    // rács méret beállítása
    boardDiv.style.gridTemplateColumns = `repeat(${boardSize}, var(--cell-size))`;
    boardDiv.style.gridTemplateRows = `repeat(${boardSize}, var(--cell-size))`;

    for (let y=0; y<boardSize; y++) {
        for (let x=0; x<boardSize; x++) {
            const cell = document.createElement('div');
            cell.className = 'cell';
            cell.dataset.x = x;
            cell.dataset.y = y;
            cell.addEventListener('click', onCellClick);
            boardDiv.appendChild(cell);
        }
    }
    winLineDiv.style.display = 'none';
}

function onCellClick(e) {
    if (!gameId || !token || !mySymbol || spectatorMode) return;
    const cell = e.currentTarget;
    const x = parseInt(cell.dataset.x);
    const y = parseInt(cell.dataset.y);

    fetch('move.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({
            game_id: gameId,
            token: token,
            x: x,
            y: y
        })
    }).then(r=>r.json()).then(d=>{
        if (d.error) {
            alert(d.error);
        } else {
            playSound(sndMove);
        }
    });
}

function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(loadState, 1500);
    loadState();

    if (chatTimer) clearInterval(chatTimer);
    lastChatId = 0;
    chatMessagesDiv.innerHTML = '';
    chatTimer = setInterval(loadChat, 1500);
    loadChat();

    if (spectatorPingTimer) clearInterval(spectatorPingTimer);
    if (spectatorMode && spectatorToken) {
        spectatorPingTimer = setInterval(()=>{
            fetch('spectator_ping.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:new URLSearchParams({
                    game_id: gameId,
                    token: spectatorToken
                })
            });
        }, 10000);
    }
}

function getCell(x, y) {
    return boardDiv.querySelector('.cell[data-x="' + x + '"][data-y="' + y + '"]');
}

function drawWinLine(line) {
    if (!Array.isArray(line) || line.length < 2) {
        winLineDiv.style.display = 'none';
        return;
    }
    const first = line[0];
    const last = line[line.length-1];
    const firstCell = getCell(first[0], first[1]);
    const lastCell  = getCell(last[0], last[1]);
    if (!firstCell || !lastCell) {
        winLineDiv.style.display = 'none';
        return;
    }
    const boardRect = boardDiv.getBoundingClientRect();
    const fr = firstCell.getBoundingClientRect();
    const lr = lastCell.getBoundingClientRect();

    const x1 = (fr.left + fr.width/2) - boardRect.left;
    const y1 = (fr.top + fr.height/2) - boardRect.top;
    const x2 = (lr.left + lr.width/2) - boardRect.left;
    const y2 = (lr.top + lr.height/2) - boardRect.top;

    const dx = x2 - x1;
    const dy = y2 - y1;
    const length = Math.sqrt(dx*dx + dy*dy);
    const angle = Math.atan2(dy, dx) * 180 / Math.PI;

    winLineDiv.style.width = length + 'px';
    winLineDiv.style.left = x1 + 'px';
    winLineDiv.style.top = y1 + 'px';
    winLineDiv.style.transform = 'rotate(' + angle + 'deg)';
    winLineDiv.style.display = 'block';
}


function scrollToBoard() {
    const wrapper = document.getElementById('boardWrapper');
    if (!wrapper) return;
    const rect = wrapper.getBoundingClientRect();
    const offset = window.pageYOffset || document.documentElement.scrollTop || 0;
    const top = rect.top + offset - 10;
    window.scrollTo({ top, behavior: 'smooth' });
}

function loadState() {
    if (!gameId) return;
    let url = 'state.php?game_id=' + encodeURIComponent(gameId);
    if (!spectatorMode && token) {
        url += '&token=' + encodeURIComponent(token);
    } else {
        url += '&spectator=1';
    }

    fetch(url)
        .then(r=>r.json())
        .then(d=>{
            if (d.error) {
                // Ha játék közben tűnik el a játék (pl. ellenfél kilép, admin törli),
                // jelezzük modalban, majd visszatérünk a főképernyőre.
                if (!spectatorMode && gameId && (d.error === 'Nincs ilyen játék' || d.error === 'Ismeretlen játékos')) {
                    // Biztos, ami biztos: régi győzelem/vereség modalt zárjuk be
                    hideResultModal();
                    modalIcon.textContent = '⚠️';
                    modalTitle.textContent = 'Az ellenfeled kilépett';
                    modalText.textContent = 'A játék megszakadt vagy törlésre került. Visszakerülsz a főképernyőre.';
                    modalOverlay.style.display = 'flex';
                    requestAnimationFrame(()=>{
                        modalBox.classList.add('show');
                    });
                    setTimeout(()=>{ window.location.reload(); }, 1800);
                } else {
                    statusDiv.textContent = d.error;
                }
                return;
            }

            // Üdvözlő popup: ha X vagy, eddig waiting volt, most running, és még nem jeleztünk
            if (!spectatorMode && mySymbol === 'X' && lastGameStatus === 'waiting' && d.status === 'running' && !opponentJoinedNotified) {
                opponentJoinedNotified = true;
                const joinerName = d.player_o_name || 'O játékos';
                modalIcon.textContent = '🎮';
                modalTitle.textContent = 'Ellenfél csatlakozott';
                modalText.textContent = joinerName + ' csatlakozott a játékhoz. Kezdődhet a parti!';

                // Itt csak egy gomb legyen: "Indulhat a játék"
                if (modalRematchBtn) {
                    modalRematchBtn.textContent = 'Indulhat a játék';
                    modalRematchBtn.style.display = 'inline-block';
                    modalRematchBtn.onclick = () => {
                        hideResultModal();
                    };
                }
                if (modalNewGameBtn) {
                    modalNewGameBtn.style.display = 'none';
                }

                modalOverlay.style.display = 'flex';
                requestAnimationFrame(()=>{
                    modalBox.classList.add('show');
                });
            }

            // Ha befejezett állapotból újra futóba váltottunk (pl. rematch),
            // akkor a győzelem/vereség modalt rejtsük el mindkét félnél.
            if (!spectatorMode && lastGameStatus === 'finished' && d.status === 'running') {
                hideResultModal();
                lastWinnerShown = null;
            }

            // Ha futó játékból visszaváltunk várakozásra, az ellenfél kilépett
            if (!spectatorMode && lastGameStatus === 'running' && d.status === 'waiting') {
                modalIcon.textContent = '⚠️';
                modalTitle.textContent = 'Az ellenfeled kilépett';
                modalText.textContent = 'A játékod megmaradt, de az ellenfeled kilépett. Most új ellenfélre vársz.';

                // Csak egy "Rendben" gomb legyen
                if (modalRematchBtn) {
                    modalRematchBtn.textContent = 'Rendben';
                    modalRematchBtn.style.display = 'inline-block';
                    modalRematchBtn.onclick = ()=> {
                        hideResultModal();
                    };
                }
                if (modalNewGameBtn) {
                    modalNewGameBtn.style.display = 'none';
                }

                modalOverlay.style.display = 'flex';
                requestAnimationFrame(()=>{
                    modalBox.classList.add('show');
                });
            }

            lastGameStatus = d.status;

                    if (Array.isArray(d.board)) {
            boardSize = d.board.length;
        }

        const cells = boardDiv.querySelectorAll('.cell');
        cells.forEach(c=>{
            c.textContent = '';
            c.classList.remove('last-move','win-cell','x-cell','o-cell','disabled','filled');
        });

        for (let y=0; y<boardSize; y++) {
            for (let x=0; x<boardSize; x++) {
                const val = d.board[y][x];
                const cell = getCell(x,y);
                if (!cell) continue;
                cell.textContent = val;
                if (val === 'X') {
                    cell.classList.add('x-cell','filled');
                } else if (val === 'O') {
                    cell.classList.add('o-cell','filled');
                }
            }
        }

            if (!spectatorMode) {
                myName = d.your_name || myName;
                applyLoginState();
                opponentName = d.opponent_name || opponentName;
            }

            chatPlayerXName = d.player_x_name || chatPlayerXName;
            if (playerLineDiv) {
                const px = d.player_x_name || 'X';
                const po = d.player_o_name || (d.status === 'waiting' ? '...' : 'O');
                playerLineDiv.innerHTML = `<span class="player-name-x">${px}</span><span class="player-vs">vs</span><span class="player-name-o">${po}</span>`;
            }


            chatPlayerOName = d.player_o_name || chatPlayerOName;

            let txt = '';
            if (spectatorMode) {
                txt = 'Néző módban vagy. Jelenlegi kör: ' + d.current_turn + '.';
            } else {
                txt = `Te (${mySymbol}): ${myName || mySymbol}. Ellenfél (${mySymbol === 'X' ? 'O' : 'X'}): ${opponentName || (mySymbol === 'X' ? 'O' : 'X')}. Kör: ${d.current_turn}.`;
            }
            if (d.status === 'waiting') txt += ' Várakozás második játékosra...';
            if (d.status === 'finished') {
                if (d.winner === 'draw') {
                    txt += ' Játék vége: döntetlen.';
                } else {
                    const winnerName = (d.winner === 'X' ? d.player_x_name : d.player_o_name) || d.winner;
                    txt += ' Játék vége, győztes: ' + d.winner + (winnerName ? ' (' + winnerName + ')' : '');
                }
            }
            statusDiv.textContent = txt;

            if (d.last_move && typeof d.last_move.x === 'number' && typeof d.last_move.y === 'number') {
                const lastCell = getCell(d.last_move.x, d.last_move.y);
                if (lastCell) lastCell.classList.add('last-move');
            }

            if (Array.isArray(d.win_line)) {
                d.win_line.forEach(pair=>{
                    const c = getCell(pair[0], pair[1]);
                    if (c) c.classList.add('win-cell');
                });
                drawWinLine(d.win_line);
            } else {
                winLineDiv.style.display = 'none';
            }

            if (!spectatorMode && d.status === 'finished' && d.winner && lastWinnerShown !== d.winner) {
                if (d.winner === 'X') scoreX++;
                else if (d.winner === 'O') scoreO++;
                else if (d.winner === 'draw') scoreDraw++;
                updateScoreboard();
                lastWinnerShown = d.winner;
                rematchBtn.disabled = false;
                showResultModal(d.winner);
            }

            if (d.status === 'finished') {
                if (!spectatorMode) rematchBtn.disabled = false;
                cells.forEach(c=> c.classList.add('disabled'));
            }
        });
}

/* Modal logika */

function showResultModal(winner) {
    let icon = '🏆';
    let title = 'Győzelem!';
    let text = 'Szép játék volt!';

    // győztes animációk / effektek
    if (winner && winner !== 'draw') {
        showWinEffects();
    }

    if (winner === 'draw') {
        icon = '🤝';
        title = 'Döntetlen';
        text = 'Senki sem nyert, de jól küzdöttetek.';
    } else if (mySymbol === winner) {
        icon = '🏆';
        title = 'Győzelem!';
        text = 'Megnyerted a partit!';
    } else {
        icon = '💀';
        title = 'Vereség';
        text = 'Most nem jött össze, de próbáld újra!';
    }

    // ikon + szöveg frissítése
    modalIcon.textContent = icon;
    modalTitle.textContent = title;
    modalText.textContent = text;

    // győzelem / vereség / döntetlen hang
    if (winner && winner !== 'draw') {
        playSound(sndWin);
    }

    // Győzelem / vereség esetén: két gomb
    // Bal: Új játék (rematch ugyanazzal a párossal)
    // Jobb: Kilépek (vissza a főoldalra)
    if (modalRematchBtn) {
        modalRematchBtn.textContent = 'Új játék';
        modalRematchBtn.style.display = 'inline-block';
        modalRematchBtn.onclick = () => {
            if (typeof doRematch === 'function') {
                doRematch();
            } else {
                hideResultModal();
            }
        };
    }

    if (modalNewGameBtn) {
        modalNewGameBtn.textContent = 'Kilépek';
        modalNewGameBtn.style.display = 'inline-block';
        modalNewGameBtn.onclick = () => {
            hideResultModal();
            playSound(sndExit);

            const name = myName || (document.getElementById('playerName') ? document.getElementById('playerName').value.trim() : '');
            const gid  = gameId;

            if (gid && name) {
                fetch('player_exit_game.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        game_id: gid,
                        player_name: name
                    })
                }).finally(()=>{
                    window.location.href = 'index.php';
                });
            } else {
                window.location.href = 'index.php';
            }
        };
    }

    modalOverlay.style.display = 'flex';
    requestAnimationFrame(()=>{
        modalBox.classList.add('show');
    });
}

function hideResultModal() {
    modalBox.classList.remove('show');
    setTimeout(()=>{
        modalOverlay.style.display = 'none';
    }, 150);
}

modalOverlay.addEventListener('click', (e)=>{
    if (e.target === modalOverlay) hideResultModal();
});

/* Lobby + ranglista */
function refreshLobby() {
    fetch('lobby.php')
        .then(r=>r.json())
        .then(d=>{
            renderLobby(d.games || []);
            renderRanking(d.stats || []);
            // admin nézetben aktív meccsek lista
            refreshAdminGames(d);
        });
}

function renderLobby(games) {
    lobbyGamesDiv.innerHTML = '';
    clearLobbyCountdowns();
    if (!games.length) {
        lobbyGamesDiv.textContent = 'Még nincs játék. Indíts egyet!';
        return;
    }
    games.forEach(g=>{
        const div = document.createElement('div');
        div.className = 'lobby-item';

        const main = document.createElement('div');
        main.className = 'lobby-main';

        const code = document.createElement('div');
        code.className = 'lobby-code';
        code.textContent = 'Kód: ' + g.code;

        const names = document.createElement('div');
        names.className = 'lobby-names';
        const pxName = (g.player_x_name || 'X');
        const poName = (g.player_o_name || (g.status === 'waiting' ? '...' : 'O'));
        names.innerHTML = `<span class="lobby-player-x">${pxName}</span><span class="lobby-vs">vs</span><span class="lobby-player-o">${poName}</span>`;

        const status = document.createElement('div');
        status.className = 'lobby-status';
        let pillText = '';
        let pillClass = '';
        if (g.status === 'waiting') { pillText='Várakozik'; pillClass='pill pill-waiting'; }
        else if (g.status === 'running') { pillText='Fut'; pillClass='pill pill-running'; }
        else if (g.status === 'finished') { pillText='Vége'; pillClass='pill pill-finished'; }
        const pill = document.createElement('span');
        pill.className = pillClass;
        pill.textContent = pillText;

        status.textContent = 'Státusz: ';
        status.appendChild(pill);

        const extra = document.createElement('div');
        extra.className = 'lobby-extra';
        const sizeLabel = (g.board_size || 15) + '×' + (g.board_size || 15);
        extra.textContent = 'Tábla: ' + sizeLabel + ' • Nézők: ' + (g.spectators || 0);

        // Várakozó játékokra 10:00 visszaszámláló
        if (g.status === 'waiting') {
            const timerSpan = document.createElement('div');
            timerSpan.className = 'lobby-timer';

            let remaining = (typeof g.remaining_seconds === 'number')
                ? g.remaining_seconds
                : (g.remaining_seconds ? parseInt(g.remaining_seconds,10) : 600);

            if (isNaN(remaining)) remaining = 600;

            timerSpan.textContent = 'Hátralévő idő: ' + formatSecondsToMMSS(remaining);
            extra.appendChild(document.createElement('br'));
            extra.appendChild(timerSpan);

            if (g.id) {
                const gameId = g.id;
                lobbyCountdowns[gameId] = setInterval(()=>{
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(lobbyCountdowns[gameId]);
                        timerSpan.textContent = 'Hátralévő idő: 00:00';
                    } else {
                        timerSpan.textContent = 'Hátralévő idő: ' + formatSecondsToMMSS(remaining);
                    }
                }, 1000);
            }
        }

        // új layout: felső sor kód + játékosok, alsó sor státusz + extra
        const headerRow = document.createElement('div');
        headerRow.className = 'lobby-main-top';
        headerRow.appendChild(code);
        headerRow.appendChild(names);

        const bottomRow = document.createElement('div');
        bottomRow.className = 'lobby-main-bottom';
        bottomRow.appendChild(status);
        bottomRow.appendChild(extra);

        main.appendChild(headerRow);
        main.appendChild(bottomRow);

        const actions = document.createElement('div');
        actions.style.display='flex';
        actions.style.flexDirection='column';
        actions.style.gap='3px';

        // Várakozó játék: csak csatlakozás, nézés nem kell
        if (g.status === 'waiting') {
            const currentName = myName || (document.getElementById('playerName') ? document.getElementById('playerName').value.trim() : '');
            const px = (g.player_x_name || '').trim();
            const isOwner = currentName && currentName === px;

            // Ellenőrizzük, hogy a játékos már csatlakozott-e másik meccshez O-ként
            const alreadyJoinedAsO = games.some(gg => (gg.player_o_name || '').trim() === currentName);

            if (!isOwner && !alreadyJoinedAsO) {
                const joinBtn = document.createElement('button');
                joinBtn.className = 'secondary';
                joinBtn.textContent = 'Csatlakozom';
                joinBtn.onclick = ()=>{
                    document.getElementById('joinCode').value = g.code;
                    document.getElementById('btnJoin').click();
                };
                actions.appendChild(joinBtn);
            }
        }

        // Futó játék: Nézem gomb csak akkor, ha nem én vagyok az egyik játékos
        if (g.status === 'running') {
            const currentName = myName || (document.getElementById('playerName') ? document.getElementById('playerName').value.trim() : '');
            const px = (g.player_x_name || '').trim();
            const po = (g.player_o_name || '').trim();
            const isPlayer = currentName && (currentName === px || currentName === po);

            if (!isPlayer) {
                const watchBtn = document.createElement('button');
                watchBtn.className = 'ghost';
                watchBtn.textContent = 'Nézem';
                watchBtn.onclick = ()=>{
                    const name = currentName || 'Néző';
                    myName = name;
                    spectatorMode = true;
                    gameId = g.id;
                    token = null;
                    mySymbol = null;
                    lastWinnerShown = null;
                    lastGameStatus = null;
                    opponentJoinedNotified = false;
                    rematchBtn.disabled = true;
                    infoDiv.textContent = `Néző módban figyeled ezt a játékot. Kód: ${g.code}`;

                    fetch('spectator_join.php',{
                        method:'POST',
                        headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:new URLSearchParams({game_id:g.id, name:myName})
                    }).then(r=>r.json()).then(d=>{
                        if (d.error) { alert(d.error); return; }
                        spectatorToken = d.token;
                        createBoard();
                        hideResultModal();
                        startPolling();
                        scrollToBoard();
                    });
                };
                actions.appendChild(watchBtn);
            }
        }

        div.appendChild(main);
        div.appendChild(actions);
        lobbyGamesDiv.appendChild(div);
    });
}


function renderRanking(stats) {
    rankingDiv.innerHTML = '';
    if (!stats.length) {
        rankingDiv.textContent = 'Még nincs elég adat.';
        return;
    }

    // Fejléc: Játékos, Győzelem, Vereség, Döntetlen
    const header = document.createElement('div');
    header.className = 'ranking-header';
    ['Játékos','Győzelem','Vereség','Döntetlen'].forEach(label=>{
        const cell = document.createElement('div');
        cell.textContent = label;
        header.appendChild(cell);
    });
    rankingDiv.appendChild(header);

    stats.forEach(s=>{
        const row = document.createElement('div');
        row.className = 'ranking-row';

        const nameCell = document.createElement('div');
        nameCell.className = 'ranking-row-name';
        nameCell.textContent = s.player_name;

        const winCell = document.createElement('div');
        winCell.className = 'ranking-row-num';
        winCell.textContent = s.wins;

        const lossCell = document.createElement('div');
        lossCell.className = 'ranking-row-num';
        lossCell.textContent = s.losses;

        const drawCell = document.createElement('div');
        drawCell.className = 'ranking-row-num';
        drawCell.textContent = s.draws;

        row.appendChild(nameCell);
        row.appendChild(winCell);
        row.appendChild(lossCell);
        row.appendChild(drawCell);

        rankingDiv.appendChild(row);
    });
}

function startLobbyRefresh() {
    if (lobbyTimer) clearInterval(lobbyTimer);
    lobbyTimer = setInterval(refreshLobby, 5000);
    refreshLobby();
}

/* Chat */
function appendChatMessages(msgs) {
    if (!msgs || !msgs.length) return;
    msgs.forEach(m=>{
        const line = document.createElement('div');
        line.className = 'chat-line';

        const t = document.createElement('span');
        t.className = 'chat-time';
        t.textContent = '[' + m.time + ']';

        const badge = document.createElement('span');
        badge.className = 'chat-badge badge-s';
        let badgeText = 'S';
        if (chatPlayerXName && m.sender === chatPlayerXName) {
            badge.className = 'chat-badge badge-x';
            badgeText = 'X';
        } else if (chatPlayerOName && m.sender === chatPlayerOName) {
            badge.className = 'chat-badge badge-o';
            badgeText = 'O';
        }
        badge.textContent = badgeText;

        const s = document.createElement('span');
        s.className = 'chat-sender';
        s.textContent = m.sender + ':';

        const txt = document.createElement('span');
        txt.textContent = ' ' + m.message;

        line.appendChild(t);
        line.appendChild(badge);
        line.appendChild(s);
        line.appendChild(txt);
        chatMessagesDiv.appendChild(line);

        lastChatId = Math.max(lastChatId, m.id);
    });
    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
}

function loadChat() {
    if (!gameId) return;
    fetch('get_chat.php?game_id=' + encodeURIComponent(gameId) + '&last_id=' + encodeURIComponent(lastChatId))
        .then(r=>r.json())
        .then(d=>{
            if (d.error) return;
            chatPlayerXName = d.player_x_name || chatPlayerXName;
            if (playerLineDiv) {
                const px = d.player_x_name || 'X';
                const po = d.player_o_name || (d.status === 'waiting' ? '...' : 'O');
                playerLineDiv.innerHTML = `<span class="player-name-x">${px}</span><span class="player-vs">vs</span><span class="player-name-o">${po}</span>`;
            }


            chatPlayerOName = d.player_o_name || chatPlayerOName;
            appendChatMessages(d.messages || []);
        });
}

function sendChat() {
    if (!gameId) return;
    const msg = chatInput.value.trim();
    if (!msg) return;
    const senderName = myName || (mySymbol ? ('Játékos ' + mySymbol) : 'Néző');
    fetch('send_chat.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({
            game_id: gameId,
            sender: senderName,
            message: msg
        })
    }).then(r=>r.json()).then(d=>{
        if (d.error) { alert(d.error); return; }
        chatInput.value = '';
        loadChat();
    });
}

chatSendBtn.addEventListener('click', sendChat);
chatInput.addEventListener('keydown', e=>{
    if (e.key === 'Enter') {
        e.preventDefault();
        sendChat();
    }
});

/* Gombok */
document.getElementById('btnCreate').addEventListener('click', ()=>{
    const name = myName || document.getElementById('playerName').value.trim();
    if (!name) {
        alert('Adj meg egy nevet, mielőtt játékot indítasz!');
        return;
    }
    if (name === 'András' && !adminCode) {
        alert('Először add meg az admin kódot a Belépés gombnál.');
        return;
    }
    spectatorMode = false;
    spectatorToken = null;
    lastGameStatus = null;
    opponentJoinedNotified = false;
    fetch('create_game.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({player_name:name, admin_code:adminCode, board_size:boardSize})
    }).then(r=>r.json()).then(d=>{
        if (d.error) { alert(d.error); return; }
        gameId = d.game_id;
        token = d.token;
        mySymbol = d.symbol;
        myName = d.your_name || name;
        opponentName = '';
        lastWinnerShown = null;
        rematchBtn.disabled = true;
        infoDiv.textContent = 'Várakozol az ellenfélre. A másik játékos a lobbyból csatlakozhat.';
        createBoard();
        hideResultModal();
        startPolling();
        scrollToBoard();
    });
});

document.getElementById('btnJoin').addEventListener('click', ()=>{
    const code = document.getElementById('joinCode').value.trim().toUpperCase();
    const name = myName || document.getElementById('playerName').value.trim();
    if (!name) {
        alert('Adj meg egy nevet, mielőtt csatlakozol!');
        return;
    }
    if (!code) {
        alert('Írd be a játék kódját!');
        return;
    }
    if (name === 'András' && !adminCode) {
        alert('Először add meg az admin kódot a Belépés gombnál.');
        return;
    }
    spectatorMode = false;
    spectatorToken = null;
    lastGameStatus = null;
    opponentJoinedNotified = false;
    fetch('join_game.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({code:code, player_name:name, admin_code:adminCode})
    }).then(r=>r.json()).then(d=>{
        if (d.error) { alert(d.error); return; }
        gameId = d.game_id;
        token = d.token;
        mySymbol = d.symbol;
        myName = d.your_name || name;
        opponentName = '';
        lastWinnerShown = null;
        rematchBtn.disabled = true;
        infoDiv.textContent = 'Csatlakoztál a játékhoz a lobbyból.';
        createBoard();
        hideResultModal();
        startPolling();
        scrollToBoard();
    });
});

function doRematch() {
    if (!gameId || !token || spectatorMode) { hideResultModal(); return; }
    fetch('reset_game.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({game_id:gameId, token:token})
    }).then(r=>r.json()).then(d=>{
        if (d.error) { alert(d.error); return; }
        lastWinnerShown = null;
        rematchBtn.disabled = true;
        statusDiv.textContent = 'Új meccs indult, X kezd.';
        createBoard();
        chatMessagesDiv.innerHTML = '';
        lastChatId = 0;
        hideResultModal();
        loadState();
    });
}

rematchBtn.addEventListener('click', doRematch);
modalRematchBtn.addEventListener('click', doRematch);
if (modalNewGameBtn) {
    modalNewGameBtn.addEventListener('click', ()=>{
        hideResultModal();
        // Új játék: vissza a lobby nézethez
        window.location.href = 'index.php';
    });
}

// lobby törlése kóddal
btnClearLobby.addEventListener('click', ()=>{
    if (!isAdmin) {
        alert('Ezt a gombot csak András használhatja.');
        return;
    }
    fetch('clear_lobby.php', {
        method:'POST'
    }).then(r=>r.json()).then(d=>{
        if (d.error) { alert(d.error); return; }
        refreshLobby();
        alert('Lobby törlése kész (futó + befejezett meccsek).');
    });
});

const btnClearStats = document.getElementById('btnClearStats');
if (btnClearStats) {
    btnClearStats.addEventListener('click', ()=>{
        if (!isAdmin) {
            alert('Ezt a gombot csak András használhatja.');
            return;
        }
        fetch('clear_stats.php', {
            method:'POST'
        }).then(r=>r.json()).then(d=>{
            if (d.error) { alert(d.error); return; }
            alert('Top játékosok törölve.');
            refreshLobby();
        });
    });
}

// induláskor
applyLoginState();
createBoard();
updateScoreboard();
startLobbyRefresh();
</script>
<script src="modern.js"></script></body>
</html>
