<?php
session_start();
require_once 'db.php';

$pdo = connectDatabase();

$error = '';
$success = '';

// 動物追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_animal_name'])) {
    $newAnimal = trim($_POST['new_animal_name']);
    if ($newAnimal === '') {
        $error = "追加する動物名を入力してください。";
    } else {
        // 重複チェック（同名の動物が既にあるか）
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM hackathon10_animals WHERE name = ?");
        $stmt->execute([$newAnimal]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $error = "その動物はすでに登録されています。";
        } else {
            // 動物名を追加
            $stmt = $pdo->prepare("INSERT INTO hackathon10_animals (name) VALUES (?)");
            if ($stmt->execute([$newAnimal])) {
                $success = "動物「" . htmlspecialchars($newAnimal) . "」を追加しました。";
            } else {
                $error = "動物の追加に失敗しました。";
            }
        }
    }
}

// 動物リストを取得（追加後のリストも含む）
$stmt = $pdo->query("SELECT animal_id, name FROM hackathon10_animals ORDER BY animal_id ASC");
$animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 以降は既存の選択フォームとメッセージ表示を組み合わせてください
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>動物と性格を選択・追加</title>
<style>
body { font-family: 'M PLUS Rounded 1c', sans-serif; background: #fff8e1; padding: 20px; text-align: center; }
.container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
select, textarea, input[type="text"], input[type="submit"] { width: 100%; padding: 12px; margin-top: 10px; border-radius: 10px; border: 1px solid #ccc; font-size: 1em; }
input[type="submit"] { background: #ffb300; color: white; border: none; cursor: pointer; }
input[type="submit"]:hover { background: #ffa000; }
.message { margin-top: 10px; font-weight: bold; }
.error { color: red; }
.success { color: green; }
</style>
</head>
<body>
<div class="container">
    <h2>動物と性格を選んでね 🐾</h2>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>

    <!-- 動物追加フォーム -->
    <form method="POST" action="">
        <label for="new_animal_name">新しい動物を追加する</label>
        <input type="text" id="new_animal_name" name="new_animal_name" placeholder="例：フクロウ" autocomplete="off">
        <input type="submit" value="動物を追加">
    </form>

    <hr style="margin: 30px 0;">

    <!-- 動物選択フォーム -->
    <form method="POST" action="chat.php">
        <label for="animal_id">動物を選択してください</label>
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
