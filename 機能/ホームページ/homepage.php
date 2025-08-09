<?php
// homepage.php
session_start();
require_once 'function.php';

// ログインしていない場合はログインページへ
checkLogin();

// 動物選択後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['animal'])) {
        $_SESSION['animal'] = $_POST['animal'];
        header('Location: chat.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>動物とおしゃべり</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        h1 {
            margin-top: 30px;
            color: #333;
        }
        .animal-list {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 40px;
        }
        .animal-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            width: 150px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .animal-card:hover {
            transform: scale(1.05);
        }
        .animal-card img {
            width: 100px;
            height: 100px;
        }
        button {
            margin-top: 40px;
            padding: 10px 20px;
            font-size: 18px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        input[type="radio"] {
            display: none;
        }
        label {
            display: block;
        }
    </style>
</head>
<body>

    <h1>どの動物とおしゃべりしますか？</h1>
    <p>こんにちは、<?php echo htmlspecialchars($_SESSION['username']); ?> さん！</p>

    <form method="POST">
        <div class="animal-list">
            <label class="animal-card">
                <input type="radio" name="animal" value="犬" required>
                <img src="images/dog.png" alt="犬">
                <div>犬</div>
            </label>
            <label class="animal-card">
                <input type="radio" name="animal" value="猫">
                <img src="images/cat.png" alt="猫">
                <div>猫</div>
            </label>
            <label class="animal-card">
                <input type="radio" name="animal" value="鳥">
                <img src="images/bird.png" alt="鳥">
                <div>鳥</div>
            </label>
            <label class="animal-card">
                <input type="radio" name="animal" value="イルカ">
                <img src="images/dolphin.png" alt="イルカ">
                <div>イルカ</div>
            </label>
        </div>
        <button type="submit">次へ</button>
    </form>

</body>
</html>
