<?php
require_once 'db.php';

$pdo = connectDatabase();
$stmt = $pdo->query("SELECT animal_id, name FROM hackathon10_animals ORDER BY animal_id ASC");
$animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id = intval($_POST['animal_id'] ?? 0);
    $custom_setting = trim($_POST['custom_setting'] ?? '');

    if ($animal_id > 0 && $custom_setting !== '') {
        $_SESSION['animal_id'] = $animal_id;
        $_SESSION['animal_setting'] = $custom_setting;

        // チャット画面へリダイレクト
        header('Location: chat.php');
        exit();
    } else {
        $error = '動物と性格を正しく選択してください。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
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
    <?php if ($error): ?>
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
