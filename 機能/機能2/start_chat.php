<?php
require_once 'db.php';

// ログインチェック
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$pdo = connectDatabase();

// username から user_id を取得
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

// POSTデータ取得・バリデーション
$animal_id = intval($_POST['animal_id'] ?? 0);
$custom_setting = trim($_POST['custom_setting'] ?? '');

if ($animal_id <= 0) {
    die('動物が選択されていません。');
}
if ($custom_setting === '') {
    // 任意項目なので空の場合は半角スペース１つに
    $custom_setting = ' ';
}

// セッションに保存
$_SESSION['animal_id'] = $animal_id;
$_SESSION['animal_setting'] = $custom_setting;
$_SESSION['user_id'] = $userId;

// 動物の説明・性格設定をDBに保存または更新
// 既に登録があれば更新、なければ挿入
$stmt = $pdo->prepare("SELECT animal_description_id FROM hackathon10_animal_description WHERE animal_id = ? AND user_id = ?");
$stmt->execute([$animal_id, $userId]);
$existing = $stmt->fetchColumn();

if ($existing) {
    // 更新
    $stmt = $pdo->prepare("UPDATE hackathon10_animal_description SET description = ?, created_at = CURRENT_TIMESTAMP WHERE animal_description_id = ?");
    $stmt->execute([$custom_setting, $existing]);
} else {
    // 挿入
    $stmt = $pdo->prepare("INSERT INTO hackathon10_animal_description (animal_id, user_id, description) VALUES (?, ?, ?)");
    $stmt->execute([$animal_id, $userId, $custom_setting]);
}

// チャット画面へリダイレクト
header('Location: chat.php');
exit();
