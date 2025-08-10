<?php
// ★★★ 機能（ロジック）は元のコードのままです ★★★

require_once 'db.php';

$pdo = connectDatabase();

$error = '';
$success = '';

// usernameからuser_idを取得
$username = $_SESSION['username'] ?? 'ゲスト';
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ▼▼▼ UI（見た目）は美しいアニメーション版のものです ▼▼▼ */
@import url('https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap');
:root {
    --bg-gradient-start: #e0f2f1; --bg-gradient-end: #fce4ec;
    --content-bg: rgba(255, 255, 255, 0.7); --primary-color: #00796b;
    --secondary-color: #d81b60; --font-color: #263238;
    --card-border: rgba(255, 255, 255, 0.8); --card-shadow: rgba(0, 0, 0, 0.1);
}
@keyframes gradient-animation {
    0% { background-position: 0% 50%; } 25% { background-position: 50% 0%; }
    50% { background-position: 100% 50%; } 75% { background-position: 50% 100%; }
    100% { background-position: 0% 50%; }
}
@keyframes fade-in-out {
    0%, 100% { opacity: 0; } 50% { opacity: 1; }
}
body {
    font-family: 'M PLUS Rounded 1c', sans-serif; margin: 0; padding: 0; color: var(--font-color);
    background: linear-gradient(-45deg, var(--bg-gradient-start), var(--bg-gradient-end), #e1f5fe, #c8e6c9);
    background-size: 400% 400%; animation: gradient-animation 15s ease infinite;
    overflow-x: hidden; min-height: 100vh;
}
#paws-container {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    z-index: -1; pointer-events: none;
}
.paw {
    position: absolute; width: 50px; height: 50px;
    background-image: url("paw.png"); background-size: contain;
    background-repeat: no-repeat; opacity: 0; animation-name: fade-in-out;
    animation-timing-function: ease-in-out; animation-iteration-count: 1;
}
header {
    background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); color: var(--primary-color);
    padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}
header .welcome-msg { font-weight: 700; font-size: 1.1em; }
main {
    max-width: 600px; width: 90%; margin: 40px auto; background: var(--content-bg);
    border: 1px solid var(--card-border); backdrop-filter: blur(10px);
    padding: 30px; border-radius: 20px; box-shadow: 0 8px 32px 0 var(--card-shadow);
    text-align: left;
}
h2 { text-align: center; color: var(--primary-color); margin-top:0; }
label { font-weight: bold; margin-top: 15px; display: block; }
select, textarea, input[type="submit"], input[type="text"] {
    width: 100%; padding: 12px; margin-top: 8px; border-radius: 10px;
    border: 1px solid #ccc; font-size: 1em; font-family: 'M PLUS Rounded 1c', sans-serif;
    box-sizing: border-box;
}
input[type="submit"] {
    background: var(--secondary-color); color: white; border: none; cursor: pointer;
    font-weight: bold; margin-top: 20px; transition: background-color 0.2s;
}
input[type="submit"]:hover { background: #c2185b; }
.error { color: #d32f2f; background-color: #ffcdd2; padding: 10px; border-radius: 8px; text-align: center; font-weight: bold;}
.success { color: #2e7d32; background-color: #c8e6c9; padding: 10px; border-radius: 8px; text-align: center; font-weight: bold;}
hr { border: 0; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0)); margin: 30px 0; }
.logout-btn {
    padding: 8px 16px; background-color: #6c757d; color: white; text-decoration: none;
    border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: background-color 0.2s;
}
.logout-btn:hover { background-color: #5a6268; }
</style>
</head>
<body>

<div id="paws-container"></div>

<header>
    <div class="welcome-msg">ようこそ、<?= htmlspecialchars($username) ?> さん</div>
    <a href="logout.php" class="logout-btn">ログアウト</a>
</header>

<main>
    <h2>動物と性格を選んでね 🐾</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="homepage.php">
        <label for="new_animal_name">新しい動物を追加する</label>
        <input type="text" id="new_animal_name" name="new_animal_name" placeholder="例：フクロウ" autocomplete="off">
        <input type="submit" value="動物を追加">
    </form>

    <hr>

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
</main>

<script>
// ★★★ ここから下のJavaScriptも元のコードのままです ★★★

// 肉球アニメーション用のJavaScript
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('paws-container');
    if (container) {
        const creationInterval = 500;
        function createPaw() {
            const paw = document.createElement('div');
            paw.classList.add('paw');
            paw.style.top = (Math.random() * 120 - 10) + 'vh';
            paw.style.left = (Math.random() * 120 - 10) + 'vw';
            const randomSize = Math.random() * 40 + 20;
            paw.style.width = randomSize + 'px';
            paw.style.height = randomSize + 'px';
            paw.style.transform = `rotate(${Math.random() * 360}deg)`;
            paw.style.animationDuration = (Math.random() * 5 + 5) + 's';
            paw.addEventListener('animationend', function() { paw.remove(); });
            container.appendChild(paw);
        }
        setInterval(createPaw, creationInterval);
    }
});

// 動物の説明を動的に取得するJavaScript
document.addEventListener('DOMContentLoaded', () => {
    const animalSelect = document.getElementById('animal_id');
    const customSetting = document.getElementById('custom_setting');

    if (!animalSelect || !customSetting) return;

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