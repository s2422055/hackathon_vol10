<?php
require_once 'db.php';

$pdo = connectDatabase();

$error = '';
$success = '';

// 動物リスト取得（追加もここで）
$stmt = $pdo->query("SELECT animal_id, name FROM hackathon10_animals ORDER BY animal_id ASC");
$animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 動物追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_animal') {
    $newAnimal = trim($_POST['new_animal_name'] ?? '');
    if ($newAnimal === '') {
        $error = "追加する動物名を入力してください。";
    } else {
        // 重複チェック
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM hackathon10_animals WHERE name = ?");
        $stmt->execute([$newAnimal]);
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $error = "その動物はすでに登録されています。";
        } else {
            // 動物追加
            $stmt = $pdo->prepare("INSERT INTO hackathon10_animals (name) VALUES (?)");
            if ($stmt->execute([$newAnimal])) {
                $success = "動物「" . htmlspecialchars($newAnimal) . "」を追加しました。";
                // 動物リストを更新
                $stmt = $pdo->query("SELECT animal_id, name FROM hackathon10_animals ORDER BY animal_id ASC");
                $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = "動物の追加に失敗しました。";
            }
        }
    }
}

// 動物選択＋性格設定（任意）のPOST受信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'select_animal') {
    $animal_id = intval($_POST['animal_id'] ?? 0);
    $custom_setting = trim($_POST['custom_setting'] ?? '');

    if ($animal_id <= 0) {
        $error = '動物を正しく選択してください。';
    }
    if ($custom_setting === '') {
        $custom_setting = ' ';  // 空なら半角スペースをセット
    }

    if (empty($error)) {
        $_SESSION['animal_id'] = $animal_id;
        $_SESSION['animal_setting'] = $custom_setting;

        // チャット画面へリダイレクト
        header('Location: chat.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>動物と性格を選択</title>
<style>
body { font-family: 'M PLUS Rounded 1c', sans-serif; background: #fff8e1; padding: 20px; text-align: center; }
.container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
select, textarea, input[type="submit"], input[type="text"] { width: 100%; padding: 12px; margin-top: 10px; border-radius: 10px; border: 1px solid #ccc; font-size: 1em; box-sizing: border-box; }
input[type="submit"] { background: #ffb300; color: white; border: none; cursor: pointer; }
input[type="submit"]:hover { background: #ffa000; }
.error { color: red; margin-top: 10px; }
.success { color: green; margin-top: 10px; }
hr { margin: 30px 0; }
</style>
</head>
<body>
<div class="container">
    <h2>動物と性格を選んでね 🐾</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <!-- 動物追加フォーム -->
    <form method="POST" action="">
        <input type="hidden" name="form_type" value="add_animal">
        <label for="new_animal_name">新しい動物を追加する</label>
        <input type="text" id="new_animal_name" name="new_animal_name" placeholder="例：フクロウ" autocomplete="off">
        <input type="submit" value="動物を追加">
    </form>

    <hr>

    <!-- 動物選択＋性格設定フォーム -->
    <form method="POST" action="">
        <input type="hidden" name="form_type" value="select_animal">
        <label for="animal_id">動物 <span style="color:red;">*</span></label>
        <select id="animal_id" name="animal_id" required>
            <option value="">-- 選択してください --</option>
            <?php foreach ($animals as $animal): ?>
                <option value="<?= htmlspecialchars($animal['animal_id']) ?>">
                    <?= htmlspecialchars($animal['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="custom_setting">性格や話し方（任意）</label>
        <textarea id="custom_setting" name="custom_setting" placeholder="例: 優しくて、語尾に『にゃ』をつける" rows="3"></textarea>

        <input type="submit" value="チャットを開始">
    </form>
</div>
</body>
</html>
