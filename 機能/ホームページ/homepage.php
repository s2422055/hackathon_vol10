<?php
require_once 'db.php';

$pdo = connectDatabase();

$error = '';
$success = '';
// usernameからuser_idを取得
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM hackathon10_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userId = $user['id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit();
}

// 動物追加処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_animal_name'])) {
    $newAnimal = trim($_POST['new_animal_name']);
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

$selectedAnimalId = $_SESSION['animal_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>動物と性格を選択</title>
<style>
body { font-family: 'M PLUS Rounded 1c', sans-serif; background: #fff8e1; padding: 20px; text-align: center; }
.container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
select, textarea, input[type="submit"], input[type="text"] { width: 100%; padding: 12px; margin-top: 10px; border-radius: 10px; border: 1px solid #ccc; font-size: 1em; }
input[type="submit"] { background: #ffb300; color: white; border: none; cursor: pointer; }
input[type="submit"]:hover { background: #ffa000; }
.error { color: red; margin-top: 10px; }
.success { color: green; margin-top: 10px; }
.logout-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    background-color: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.logout-btn:hover {
    background-color: #c82333;
}
</style>
</head>
<body>
<a href="logout.php" class="logout-btn">ログアウト</a>
<div class="container">
    <h2>動物と性格を選んでね 🐾</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <!-- 動物追加フォーム -->
    <form method="POST" action="">
        <label for="new_animal_name">新しい動物を追加する</label>
        <input type="text" id="new_animal_name" name="new_animal_name" placeholder="例：フクロウ" autocomplete="off">
        <input type="submit" value="動物を追加">
    </form>

    <hr style="margin: 30px 0;">

    <!-- 動物選択＋性格設定フォーム -->
    <form id="animalForm" method="POST" action="start_chat.php">
        <label for="animal_id">動物 <span style="color:red;">*</span></label>
        <select id="animal_id" name="animal_id" required>
            <option value="">-- 選択してください --</option>
            <?php foreach ($animals as $animal): ?>
                <option value="<?= htmlspecialchars($animal['animal_id']) ?>" <?= ($animal['animal_id'] == $selectedAnimalId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($animal['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="custom_setting">性格や話し方（任意）</label>
        <textarea id="custom_setting" name="custom_setting" rows="3" placeholder="例: 優しくて、語尾に『にゃ』をつける"></textarea>

        <input type="submit" value="チャットを開始">
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const animalSelect = document.getElementById('animal_id');
    const customSetting = document.getElementById('custom_setting');

    function fetchDescription(animalId) {
        if (!animalId) {
            customSetting.value = '';
            return;
        }
        fetch('get_animal_description.php?animal_id=' + animalId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    customSetting.value = data.description || '';
                } else {
                    customSetting.value = '';
                }
            })
            .catch(() => {
                customSetting.value = '';
            });
    }

    if (animalSelect.value) {
        fetchDescription(animalSelect.value);
    }

    animalSelect.addEventListener('change', () => {
        fetchDescription(animalSelect.value);
    });
});
</script>
</body>
</html>
