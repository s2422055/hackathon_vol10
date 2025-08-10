<?php

// エラー表示設定（開発用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// --- ログインチェック ---
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}

$pdo = connectDatabase();
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM hackathon10_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.html');
    exit();
}
$userId = $user['id'];

// --- 動物IDと性格設定のセッションチェック ---
if (!isset($_SESSION['animal_id'])) {
    header('Location: homepage.php');
    exit();
}


// ★★★ APIリクエスト（JavaScriptからのfetch）の処理 ★★★
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $json_data = json_decode(file_get_contents("php://input"), true);
    $userMessage = trim($json_data['message'] ?? '');

    if ($userMessage === '') {
        echo json_encode(['reply' => 'メッセージが空っぽです。']);
        exit();
    }

    $animalId = $_SESSION['animal_id'];
    $animalSetting = $_SESSION['animal_setting'] ?? '';

    // --- ここから下のAI通信ロジックは元のまま ---
    $stmt = $pdo->prepare("SELECT name FROM hackathon10_animals WHERE animal_id = ?");
    $stmt->execute([$animalId]);
    $animalName = $stmt->fetchColumn();
    if (!$animalName) { die("動物が見つかりません。"); }

    $stmt = $pdo->prepare("SELECT role, message FROM hackathon10_logs WHERE user_id = ? AND animal_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId, $animalId]);
    $pastLogs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $systemPrompt = "あなたは{$animalName}です。";
    if (!empty($animalSetting) && trim($animalSetting) !== '') {
        $systemPrompt .= $animalSetting;
    }
    array_unshift($pastLogs, ['role' => 'user', 'message' => $systemPrompt]);

    $stmt = $pdo->prepare("INSERT INTO hackathon10_logs (user_id, animal_id, role, message) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$userId, $animalId, $userMessage]);
    
    $contents = [];
    foreach ($pastLogs as $log) {
        $contents[] = ['role' => ($log['role'] === 'assistant' ? 'model' : 'user'), 'parts' => [['text' => $log['message']]]];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

    $apiKey = "自分のAPIキーをここに入力"; // 自分のAPIキーを設定してください
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
    $payload = json_encode(['contents' => $contents], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $apiResponse = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($apiResponse, true);
    $assistantReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '（応答エラー）';

    $stmt = $pdo->prepare("INSERT INTO hackathon10_logs (user_id, animal_id, role, message) VALUES (?, ?, 'assistant', ?)");
    $stmt->execute([$userId, $animalId, $assistantReply]);
    
    // ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
    // ★★★ ここにログ削除機能を追加しました ★★★
    // ★★★ 最新8件のログだけを残し、それより古いものを削除します ★★★
    // ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
    $stmt = $pdo->prepare("
        DELETE FROM hackathon10_logs
        WHERE log_id NOT IN (
            SELECT log_id FROM (
                SELECT log_id FROM hackathon10_logs
                WHERE user_id = ? AND animal_id = ?
                ORDER BY created_at DESC
                LIMIT 8
            ) AS subquery
        ) AND user_id = ? AND animal_id = ?
    ");
    $stmt->execute([$userId, $animalId, $userId, $animalId]);
    // ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★


    // JSON形式で返答を返し、処理を終了
    echo json_encode(['reply' => $assistantReply], JSON_UNESCAPED_UNICODE);
    exit();
}


// ★★★ 通常のページアクセスの処理（チャット画面表示） ★★★
$stmt = $pdo->prepare("SELECT role, message FROM hackathon10_logs WHERE user_id = ? AND animal_id = ? ORDER BY created_at ASC");
$stmt->execute([$userId, $_SESSION['animal_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name FROM hackathon10_animals WHERE animal_id = ?");
$stmt->execute([$_SESSION['animal_id']]);
$currentAnimalName = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($currentAnimalName ?? '動物') ?>とのチャット</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap');
:root {
    --bg-color: #fdf8f5; --header-bg: #ffffff; --bubble-user-bg: #e1f0e8;
    --bubble-cat-bg: #ffffff; --accent-color: #8d6e63; --accent-hover-color: #795548;
    --text-color: #424242; --border-color: #eeeeee; --focus-ring-color: #a1887f;
}
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'M PLUS Rounded 1c', sans-serif; background-color: var(--bg-color);
    color: var(--text-color); margin: 0; display: flex; flex-direction: column; height: 100vh;
}
.visually-hidden {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}
header {
    background-color: var(--header-bg); padding: 10px 20px; border-bottom: 1px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.header-title { font-size: 1.2em; font-weight: 700; color: var(--accent-color); }
.nav-buttons { display: flex; align-items: center; gap: 15px; }
.nav-btn {
    display: inline-block; padding: 8px 16px; background-color: var(--accent-color);
    color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none;
    font-size: 0.9em; font-weight: 700; transition: background-color 0.2s, box-shadow 0.2s;
}
.nav-btn:hover { background-color: var(--accent-hover-color); }
.logout-btn { background-color: #9e9e9e; }
.logout-btn:hover { background-color: #757575; }
main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.chat-box { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
.message {
    max-width: 75%; padding: 10px 15px; border-radius: 18px; line-height: 1.5;
    word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: relative;
}
.message::before {
    content: ''; position: absolute; bottom: 0; width: 0; height: 0; border: 10px solid transparent;
}
.user {
    background-color: var(--bubble-user-bg); align-self: flex-end; border-bottom-right-radius: 4px;
}
.user::before { right: -10px; border-left-color: var(--bubble-user-bg); border-bottom-color: var(--bubble-user-bg); }
.cat {
    background-color: var(--bubble-cat-bg); align-self: flex-start; border-bottom-left-radius: 4px;
}
.cat::before { left: -10px; border-right-color: var(--bubble-cat-bg); border-bottom-color: var(--bubble-cat-bg); }
.input-area {
    display: flex; padding: 15px; border-top: 1px solid var(--border-color);
    background-color: var(--header-bg); gap: 10px;
}
#message {
    flex: 1; padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 20px;
    font-size: 1em; font-family: inherit; transition: border-color 0.2s, box-shadow 0.2s;
}
.send-btn {
    padding: 10px 20px; background-color: var(--accent-color); color: white; border: none;
    border-radius: 20px; cursor: pointer; font-weight: 700; font-family: inherit;
    transition: background-color 0.2s, box-shadow 0.2s;
}
.send-btn:hover { background-color: var(--accent-hover-color); }
:is(a, button, input):focus-visible {
    outline: none; box-shadow: 0 0 0 3px var(--bg-color), 0 0 0 5px var(--focus-ring-color);
}
</style>
</head>
<body>
<header>
    <span class="header-title">🐾 <?= htmlspecialchars($currentAnimalName ?? '動物') ?>とお話し中</span>
    <nav aria-label="メインナビゲーション">
        <div class="nav-buttons">
            <a href="homepage.php" class="nav-btn">動物選択に戻る</a>
            <form action="logout.php" method="POST" style="margin: 0;">
                <button type="submit" class="nav-btn logout-btn">ログアウト</button>
            </form>
        </div>
    </nav>
</header>
<main>
    <div class="chat-box" id="chatBox" role="log" aria-live="polite">
        <?php foreach ($logs as $log): ?>
            <div class="message <?= htmlspecialchars($log['role'] === 'assistant' ? 'cat' : 'user') ?>">
                <?= nl2br(htmlspecialchars($log['message'])) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <form class="input-area" id="chatForm">
        <label for="message" class="visually-hidden">メッセージ入力</label>
        <input type="text" id="message" placeholder="メッセージを入力…" autofocus>
        <button type="submit" class="send-btn" aria-label="メッセージを送信">送信</button>
    </form>
</main>
<script>
const chatBoxOnload = document.getElementById('chatBox');
if (chatBoxOnload) { chatBoxOnload.scrollTop = chatBoxOnload.scrollHeight; }

const chatForm = document.getElementById('chatForm');
if (chatForm) {
    chatForm.addEventListener('submit', function(event) {
        event.preventDefault();
        sendMessage();
    });
}
async function sendMessage() {
    const input = document.getElementById('message');
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    input.focus();

    try {
        const res = await fetch('chat.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message})
        });
        if (!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();
        addMessage(data.reply || "（エラー）", 'cat');
    } catch (error) {
        console.error('Fetch error:', error);
        addMessage("ごめんなさい、通信に失敗しました…", 'cat');
    }
}
function addMessage(text, type) {
    const chatBox = document.getElementById('chatBox');
    const msg = document.createElement('div');
    const className = (type === 'assistant') ? 'cat' : type;
    msg.className = `message ${className}`;
    
    msg.innerHTML = text.replace(/\n/g, '<br>');

    chatBox.appendChild(msg);
    chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
}
</script>
</body>
</html>