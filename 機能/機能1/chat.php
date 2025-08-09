<?php
// chat.php
session_start();
require_once 'function.php';

// ログインチェック
checkLogin();

// 動物選択チェック
if (empty($_SESSION['animal'])) {
    header('Location: homepage.php');
    exit();
}

$animal = $_SESSION['animal'];
$username = $_SESSION['username'];

// ==== Gemini API 設定 ====
// Google AI Studioで取得したAPIキー
$apiKey = 'your_api_key'; // ← APIキーを入れる
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}";

// 会話履歴（セッション内で保持）
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// ユーザーが送信したとき
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $userMessage = trim($_POST['message']);

    // 会話履歴にユーザーの発言を追加
    $_SESSION['chat_history'][] = ["role" => "user", "content" => $userMessage];

    // Geminiへのプロンプト作成
    $systemPrompt = "あなたは{$animal}です。以下のルールで会話してください。
- {$animal}らしい口調で話す
- 感情表現や鳴き声を交える
- ユーザーの質問に親しみを持って答える";

    // 会話履歴をまとめる（Geminiは会話履歴を一つのテキストにまとめて渡す方が簡単）
    $conversation = $systemPrompt . "\n\n";
    foreach ($_SESSION['chat_history'] as $msg) {
        if ($msg['role'] === 'user') {
            $conversation .= "ユーザー: {$msg['content']}\n";
        } else {
            $conversation .= "{$animal}: {$msg['content']}\n";
        }
    }

    // ==== Gemini API 呼び出し ====
    $postData = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $conversation]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $aiReply = $result['candidates'][0]['content']['parts'][0]['text'];
        $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $aiReply];
    } else {
        $_SESSION['chat_history'][] = ["role" => "assistant", "content" => "エラーが発生しました。"];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($animal); ?>とおしゃべり</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
            max-width: 70%;
        }
        .user {
            background-color: #d1ecf1;
            align-self: flex-end;
        }
        .assistant {
            background-color: #f8d7da;
            align-self: flex-start;
        }
        form {
            display: flex;
            padding: 10px;
            background: white;
            border-top: 1px solid #ccc;
        }
        input[type="text"] {
            flex: 1;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-right: 10px;
        }
        button {
            padding: 10px 15px;
            font-size: 16px;
            background-color: #28a745;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">
    <?php echo htmlspecialchars($animal); ?>とおしゃべり
</h2>

<div class="chat-container">
    <?php foreach ($_SESSION['chat_history'] as $chat): ?>
        <div class="message <?php echo $chat['role']; ?>">
            <?php echo nl2br(htmlspecialchars($chat['content'])); ?>
        </div>
    <?php endforeach; ?>
</div>

<form method="POST">
    <input type="text" name="message" placeholder="メッセージを入力..." required>
    <button type="submit">送信</button>
</form>

</body>
</html>
