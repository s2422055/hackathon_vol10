<?php
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
/* 省略：前回提示のCSSをここに入れてください */
body { font-family: Arial, sans-serif; background: #f9f9f9; margin:0; padding:0; }
header { background: #4CAF50; color: white; padding: 15px; text-align: center; }
main { max-width: 800px; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; }
.animal-list { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
.animal-card {
  border: 1px solid #ccc; border-radius: 8px; padding: 15px; width: 180px;
  box-shadow: 0 0 5px rgba(0,0,0,0.1); background: #fff;
  cursor: pointer;
  transition: box-shadow 0.3s ease;
  text-align: center;
}
.animal-card:hover {
  box-shadow: 0 0 15px #4CAF50;
}
.animal-name { font-weight: bold; font-size: 1.2em; margin-bottom: 8px; }
.animal-desc { font-size: 0.9em; color: #555; min-height: 50px; }
.logout-btn {
  background-color: #e74c3c; color: white; padding: 8px 15px; border: none;
  border-radius: 5px; cursor: pointer; float: right; font-weight: bold;
}
.logout-btn:hover {
  background-color: #c0392b;
}
</style>
<script>
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
<header>
  <div>
    ようこそ、<?php echo htmlspecialchars($username); ?> さん
    <form method="POST" action="logout.php" style="display:inline;">
      <button type="submit" class="logout-btn">ログアウト</button>
    </form>
  </div>
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
</body>
</html>
