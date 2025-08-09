<?php
// PHPの部分は変更なし
session_start();
require_once 'function.php';
require_once 'db.php';

checkLogin();

$pdo = connectDatabase();
$username = $_SESSION['username'] ?? '';

// 動物リスト取得
$stmt = $pdo->query("SELECT animal_id, name, description FROM hackathon10_animals ORDER BY animal_id");
$animals = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<title>動物チャット - ホーム</title>
<style>
/* ▼▼▼ CSSはここから ▼▼▼ */
/* Google Fontsからフォントを読み込み */
@import url('https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700&display=swap');

/* カラーパレットを定義 */
:root {
    --bg-gradient-start: #e0f2f1;
    --bg-gradient-end: #fce4ec;
    --content-bg: rgba(255, 255, 255, 0.6);
    --primary-color: #00796b;
    --secondary-color: #d81b60;
    --font-color: #263238;
    --card-border: rgba(255, 255, 255, 0.8);
    --card-shadow: rgba(0, 0, 0, 0.1);
    /* 肉球の色を追加 */
    --paw-color: rgba(255, 255, 255, 0.7);
}

@keyframes gradient-animation {
    0% { background-position: 0% 50%; }
    25% { background-position: 50% 0%; }
    50% { background-position: 100% 50%; }
    75% { background-position: 50% 100%; }
    100% { background-position: 0% 50%; }
}

/* ★★★ 肉球のフェードイン・アウト用アニメーションを追加 ★★★ */
@keyframes fade-in-out {
    0%, 100% {
        opacity: 0;
        transform: scale(0.9);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

body {
    font-family: 'M PLUS Rounded 1c', sans-serif;
    margin: 0;
    padding: 0;
    color: var(--font-color);
    background: linear-gradient(-45deg, var(--bg-gradient-start), var(--bg-gradient-end), #e1f5fe, #c8e6c9);
    background-size: 400% 400%;
    animation: gradient-animation 15s ease infinite;
    overflow: hidden; /* 肉球がはみ出ないように */
    min-height: 100vh;
}

/* ★★★ 肉球を配置するコンテナ用のスタイル ★★★ */
#paws-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1; /* 背景グラデーションの真上に配置 */
    pointer-events: none; /* クリックを邪魔しないように */
}

/* ★★★ 肉球自体のスタイル ★★★ */
.paw {
position: absolute;
width: 50px; /* サイズはJSで調整 */
height: 50px;
background-image: url("paw.png"); /* ★ ここを変更 ★ */
background-size: contain;
background-repeat: no-repeat;
opacity: 0; /* 最初は見えない状態 */
animation-name: fade-in-out;
animation-timing-function: ease-in-out;
animation-iteration-count: 1;
}


/* ヘッダー以降のデザインは変更なし */
header {
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: var(--primary-color);
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.5);
}

header > div { font-weight: 700; font-size: 1.1em; }

main {
    max-width: 900px;
    margin: 40px auto;
    background: var(--content-bg);
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 32px 0 var(--card-shadow);
}

h1 { text-align: center; color: var(--primary-color); margin-bottom: 40px; font-weight: 700; }

.animal-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; }

.animal-card {
    background: var(--content-bg);
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 15px 0 var(--card-shadow);
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.animal-card:hover { transform: translateY(-8px) scale(1.03); box-shadow: 0 10px 25px 0 var(--card-shadow); }
.animal-name { font-weight: 700; font-size: 1.4em; color: var(--primary-color); margin-bottom: 12px; }
.animal-desc { font-size: 0.95em; color: #37474f; line-height: 1.6; min-height: 60px; }

.logout-btn {
    background-color: var(--secondary-color); color: white; padding: 10px 20px; border: none;
    border-radius: 10px; cursor: pointer; font-weight: bold; font-family: inherit;
    transition: background-color 0.3s ease, transform 0.2s ease;
}
.logout-btn:hover { background-color: #c2185b; transform: scale(1.05); }

header form { margin: 0; }

/* ▲▲▲ CSSはここまで ▲▲▲ */
</style>
<script>
// selectAnimal関数は変更なし
function selectAnimal(id, name) {
  if (confirm(name + ' と話しますか？')) {
    document.getElementById('animal_id').value = id;
    document.getElementById('animal_name').value = name;
    document.getElementById('animalForm').submit();
  }
}
</script>
</head>
<body>

<div id="paws-container"></div>

<header>
  <div>ようこそ、<?php echo htmlspecialchars($username); ?> さん</div>
  <form method="POST" action="logout.php">
    <button type="submit" class="logout-btn">ログアウト</button>
  </form>
</header>

<main>
  <h1>話したい動物を選んでください</h1>
  <?php if (empty($animals)): ?>
    <p>現在、話せる動物が登録されていません。</p>
  <?php else: ?>
    <div class="animal-list">
      <?php foreach ($animals as $animal): ?>
        <div class="animal-card" onclick="selectAnimal(<?php echo $animal['animal_id']; ?>, '<?php echo htmlspecialchars($animal['name']); ?>')">
          <div class="animal-name"><?php echo htmlspecialchars($animal['name']); ?></div>
          <div class="animal-desc"><?php echo nl2br(htmlspecialchars($animal['description'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="POST" action="chat.php" id="animalForm" style="display:none;">
    <input type="hidden" name="animal_id" id="animal_id" value="" />
    <input type="hidden" name="animal_name" id="animal_name" value="" />
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('paws-container');
    const creationInterval = 500; // 新しい肉球が生まれる間隔（ミリ秒）。500 = 0.5秒

    // 新しい肉球を1つ生成する関数
    function createPaw() {
        const paw = document.createElement('div');
        paw.classList.add('paw');

        // 出現範囲を画面の外側まで広げる
        paw.style.top = (Math.random() * 120 - 10) + 'vh';
        paw.style.left = (Math.random() * 120 - 10) + 'vw';
        
        // サイズと回転角度をランダムに
        const randomSize = Math.random() * 40 + 20;
        paw.style.width = randomSize + 'px';
        paw.style.height = randomSize + 'px';
        paw.style.transform = `rotate(${Math.random() * 360}deg)`;
        
        // アニメーションの時間をランダムに
        paw.style.animationDuration = (Math.random() * 5 + 5) + 's';

        // ★重要：アニメーションが終わったら、その肉球をページから削除する
        paw.addEventListener('animationend', function() {
            paw.remove();
        });

        // コンテナに肉球を追加
        container.appendChild(paw);
    }

    // ★重要：指定した間隔で、新しい肉球を生成し続ける
    setInterval(createPaw, creationInterval);
});
</script>

</body>
</html>