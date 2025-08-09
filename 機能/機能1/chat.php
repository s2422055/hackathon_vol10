<?php
session_start();
require_once 'function.php';
require_once 'db.php';

checkLogin();

$pdo = connectDatabase();
$username = $_SESSION['username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['animal_id'], $_POST['animal_name'])) {
    $_SESSION['animal_id'] = (int)$_POST['animal_id'];
    $_SESSION['animal'] = $_POST['animal_name'];
}

if (!isset($_SESSION['animal_id'], $_SESSION['animal'])) {
    header('Location: homepage.php');
    exit();
}

$animal_id = $_SESSION['animal_id'];
$animal = $_SESSION['animal'];

// ユーザーID取得
$stmt = $pdo->prepare("SELECT id FROM hackathon10_users WHERE username = :username");
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();
if (!$user) {
    die("ユーザーが存在しません。");
}
$user_id = $user['id'];

// 会話履歴取得（最新20件、時系列順）
$stmt = $pdo->prepare("
    SELECT role, message
    FROM (
        SELECT role, message, created_at
        FROM hackathon10_logs
        WHERE user_id = :user_id
          AND animal_id = :animal_id
        ORDER BY created_at DESC
        LIMIT 20
    ) sub
    ORDER BY created_at ASC
");
$stmt->execute([
    ':user_id' => $user_id,
    ':animal_id' => $animal_id,
]);
$chat_history = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $user_message = trim($_POST['message']);

    $insertStmt = $pdo->prepare("INSERT INTO hackathon10_logs (user_id, animal_id, role, message) VALUES (:user_id, :animal_id, :role, :message)");

    // ユーザーメッセージをDBに保存
    $insertStmt->execute([
        ':user_id' => $user_id,
        ':animal_id' => $animal_id,
        ':role' => 'user',
        ':message' => $user_message,
    ]);

    // 古いログ削除（20件超過分を削除）
    $delStmt = $pdo->prepare("
        DELETE FROM hackathon10_logs
        WHERE log_id IN (
            SELECT log_id FROM hackathon10_logs
            WHERE user_id = :user_id AND animal_id = :animal_id
            ORDER BY created_at ASC
            OFFSET 20
        )
    ");
    $delStmt->execute([
        ':user_id' => $user_id,
        ':animal_id' => $animal_id,
    ]);

    // 履歴を再取得
    $stmt->execute([
        ':user_id' => $user_id,
        ':animal_id' => $animal_id,
    ]);
    $chat_history = $stmt->fetchAll();

    // Google AI Studioで試したプロンプト例
    $systemPrompt = "あなたは「{$animal}」という動物です。親しみやすく、{$animal}らしい口調で話してください。";

    // 会話履歴をプロンプト形式に整形
    $conversationText = $systemPrompt . "\n";
    foreach ($chat_history as $chat) {
        if ($chat['role'] === 'user') {
            $conversationText .= "ユーザー: {$chat['message']}\n";
        } else {
            $conversationText .= "{$animal}: {$chat['message']}\n";
        }
    }
    $conversationText .= "{$animal}:";  // ここにAIが返答を続ける

    // Gemini APIの呼び出し設定
    $GEMINI_API_KEY = '**';  // 取得したAPIキーに置き換える
    $GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateText?key={$GEMINI_API_KEY}";

    $postData = [
        "prompt" => [
            "text" => $conversationText
        ],
        "temperature" => 0.7,
        "maxOutputTokens" => 256,
        "candidateCount" => 1,
        "topP" => 0.8,
        "topK" => 40
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GEMINI_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $ai_reply = $data['candidates'][0]['output'] ?? '（返信が取得できませんでした）';

    // AI返信をDBに保存
    $insertStmt->execute([
        ':user_id' => $user_id,
        ':animal_id' => $animal_id,
        ':role' => 'assistant',
        ':message' => $ai_reply,
    ]);

    // リロード防止
    header("Location: chat.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title><?php echo htmlspecialchars($animal); ?> と会話</title>
<style>
/* 簡単なチャットUI */
body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
.chat-container { max-width: 700px; margin: 40px auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px #ccc; }
h2 { text-align: center; margin-bottom: 30px; }
.message { max-width: 70%; margin-bottom: 15px; padding: 10px 15px; border-radius: 12px; line-height: 1.4; word-wrap: break-word; }
.user { background-color: #d1ecf1; margin-left: auto; text-align: right; }
.assistant { background-color: #f8d7da; margin-right: auto; text-align: left; }
form { display: flex; margin-top: 25px; }
input[type="text"] { flex: 1; padding: 10px 15px; font-size: 16px; border-radius: 10px 0 0 10px; border: 1px solid #ccc; outline: none; }
button { padding: 10px 25px; font-size: 16px; border-radius: 0 10px 10px 0; border: none; background-color: #28a745; color: white; cursor: pointer; }
button:hover { background-color: #218838; }
</style>
</head>
<body>
<div class="chat-container">
<h2><?php echo htmlspecialchars($animal); ?> とおしゃべり</h2>

<div class="chat-history">
    <?php foreach ($chat_history as $chat): ?>
        <div class="message <?php echo htmlspecialchars($chat['role']); ?>">
            <?php echo nl2br(htmlspecialchars($chat['message'])); ?>
        </div>
    <?php endforeach; ?>
</div>

<form method="POST" autocomplete="off">
    <input type="text" name="message" placeholder="メッセージを入力してください..." required />
    <button type="submit">送信</button>
</form>
</div>
</body>
</html>
