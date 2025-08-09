<?php
// session_start();

// ✅ DB接続（外部ファイルを使ってるならここで読み込む）
require_once 'db.php';

// ✅ ログインチェック関数（ログインしていないと login.html にリダイレクト）
function checkLogin() {
    if (!isset($_SESSION['username'])) {
        header('Location: login.html');
        exit();
    }
}

// ✅ ログイン処理関数（login.phpなどから呼び出し）
function loginUser($username, $password) {
    $pdo = connectDatabase();

    $stmt = $pdo->prepare("SELECT * FROM hackathon10_users WHERE username = :username");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ログイン成功
        $_SESSION['username'] = $user['username'];
        return true;
    } else {
        // ログイン失敗
        return false;
    }
}

// ✅ ログアウト処理関数（logout.phpなどで呼び出す）
function logoutUser() {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit();
}
?>
