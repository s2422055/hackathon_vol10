<?php
require_once 'db.php';

// --- ログインチェック ---
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$pdo = connectDatabase();

// usernameからuser_idを取得
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM hackathon10_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}
$userId = $user['id'];

// --- 動物選択＋性格設定のPOST受信処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['animal_id'], $_POST['custom_setting']) && !isset($_POST['message'])) {
    $animal_id = intval($_POST['animal_id']);
    $custom_setting = trim($_POST['custom_setting']);

    if ($animal_id <= 0 || $custom_setting === '') {
        $error = "動物と性格を正しく選択してください。";
    } else {
        $_SESSION['animal_id'] = $animal_id;
        $_SESSION['animal_setting'] = $custom_setting;

        // POSTリロード防止のためリダイレクト
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// --- 動物IDと性格設定のセッションチェック ---
if (!isset($_SESSION['animal_id'], $_SESSION['animal_setting'])) {
    // 動物リスト取得（動物選択フォーム表示用）
    $stmt = $pdo->query("SELECT animal_id, name FROM hackathon10_animals ORDER BY animal_id ASC");
    $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 選択画面表示
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8" />
        <title>動物と性格を選択</title>
        <style>
            body { font-family: 'M PLUS Rounded 1c', sans-serif; background: #fff8e1; padding: 20px; text-align: center; }
            .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            select, textarea, input[type="submit"] { width: 100%; padding: 12px; margin-top: 10px; border-radius: 10px; border: 1px solid #ccc; font-size: 1em; }
            input[type="submit"] { background: #ffb300; color: white; border: none; cursor: pointer; }
            input[type="submit"]:hover { background: #ffa000; }
            .error { color: red; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>動物と性格を選んでね 🐾</h2>
            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="animal_id">動物</label>
                <select id="animal_id" name="animal_id" required>
                    <option value="">-- 選択してください --</option>
                    <?php foreach ($animals as $animal): ?>
                        <option value="<?= htmlspecialchars($animal['animal_id']) ?>">
                            <?= htmlspecialchars($animal['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="custom_setting">性格や話し方</label>
                <textarea id="custom_setting" name="custom_setting" placeholder="例: 優しくて、語尾に『にゃ』をつける" rows="3" required></textarea>

                <input type="submit" value="チャットを開始">
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- チャットメッセージ送信処理 ---
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    if ($userMessage === '') {
        $error = "メッセージが空です。";
    } else {
        $animalId = $_SESSION['animal_id'];
        $animalSetting = $_SESSION['animal_setting'];

        // 動物名取得
        $stmt = $pdo->prepare("SELECT name FROM hackathon10_animals WHERE animal_id = ?");
        $stmt->execute([$animalId]);
        $animalName = $stmt->fetchColumn();

        if (!$animalName) {
            die("動物が見つかりません。");
        }

        // 過去のチャット履歴（最新5件）を取得
        $stmt = $pdo->prepare("SELECT role, message FROM hackathon10_logs WHERE user_id = ? AND animal_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId, $animalId]);
        $pastLogs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        // 動物として振る舞う設定を先頭にuserロールで追加
        array_unshift($pastLogs, ['role' => 'user', 'message' => "あなたは{$animalName}です。{$animalSetting}"]);

        // ユーザーメッセージをDB保存
        $stmt = $pdo->prepare("INSERT INTO hackathon10_logs (user_id, animal_id, role, message) VALUES (?, ?, 'user', ?)");
        $stmt->execute([$userId, $animalId, $userMessage]);

        // APIリクエストデータ作成（systemロールは入れない）
        $contents = [];
        foreach ($pastLogs as $log) {
            $contents[] = ['role' => $log['role'], 'parts' => [['text' => $log['message']]]];
        }
        // 今回のメッセージも最後に追加
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $apiKey = "your_api_key"; // ←ここにAPIキー
        $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key={$apiKey}";

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

        // アシスタント応答をDB保存
        $stmt = $pdo->prepare("INSERT INTO hackathon10_logs (user_id, animal_id, role, message) VALUES (?, ?, 'assistant', ?)");
        $stmt->execute([$userId, $animalId, $assistantReply]);

        // 古いログを6件までに制限して削除
        $stmt = $pdo->prepare("
            DELETE FROM hackathon10_logs
            WHERE log_id NOT IN (
                SELECT log_id FROM hackathon10_logs
                WHERE user_id = ? AND animal_id = ?
                ORDER BY created_at DESC
                LIMIT 6
            ) AND user_id = ? AND animal_id = ?
        ");
        $stmt->execute([$userId, $animalId, $userId, $animalId]);
    }
}

// --- チャット画面表示のためログを取得 ---
$stmt = $pdo->prepare("SELECT role, message FROM hackathon10_logs WHERE user_id = ? AND animal_id = ? ORDER BY created_at ASC LIMIT 6");
$stmt->execute([$userId, $_SESSION['animal_id']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>動物チャット</title>
<style>
body { font-family: 'M PLUS Rounded 1c', sans-serif; background: #fff8e1; padding: 20px; }
.chat-container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.message { margin: 10px 0; padding: 12px 18px; border-radius: 15px; max-width: 80%; }
.user { background: #ffe082; text-align: right; margin-left: auto; }
.assistant { background: #ffd54f; text-align: left; margin-right: auto; }
form { display: flex; gap: 10px; margin-top: 15px; }
input[type="text"] { flex-grow: 1; padding: 12px; border-radius: 10px; border: 1px solid #ccc; }
input[type="submit"] { background: #ffb300; border: none; padding: 12px 24px; border-radius: 10px; color: white; cursor: pointer; }
.error-message { color: red; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="chat-container">
  <h2>動物チャット</h2>

  <?php if (!empty($error)): ?>
    <div class="error-message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php foreach ($logs as $log): ?>
    <div class="message <?= htmlspecialchars($log['role']) ?>">
      <?= nl2br(htmlspecialchars($log['message'])) ?>
    </div>
  <?php endforeach; ?>

  <form method="POST" action="">
    <input type="text" name="message" autocomplete="off" placeholder="メッセージを入力…" required>
    <input type="submit" value="送信">
  </form>
</div>
</body>
</html>
