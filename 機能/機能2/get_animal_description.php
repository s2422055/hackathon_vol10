<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'ログインしてください']);
    exit();
}

$pdo = connectDatabase();

// username から user_id を取得
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM hackathon10_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'ユーザーが見つかりません']);
    exit();
}
$userId = $user['id'];

// GETパラメータ取得
$animal_id = intval($_GET['animal_id'] ?? 0);
if ($animal_id <= 0) {
    echo json_encode(['success' => false, 'message' => '動物IDが不正です']);
    exit();
}

// 性格設定を取得
$stmt = $pdo->prepare("SELECT description FROM hackathon10_animal_description WHERE animal_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$animal_id, $userId]);
$description = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'description' => $description ?: ''
]);
