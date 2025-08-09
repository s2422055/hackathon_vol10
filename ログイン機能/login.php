<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'function.php';


$pdo = connectDatabase();
// // ログインチェック
// checkLogin();
// ログイン処理

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header('Location: login.html?error=1');
        exit();
    }

    $stmt = $pdo->prepare("SELECT password FROM hackathon10_users WHERE username = :username");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // セキュリティ対策
        $_SESSION['username'] = $username;
        header('Location: homepage.php');
        exit();
    } else {
        header('Location: login.html?error=1');
        exit();
    }
} else {
    header('Location: login.html');
    exit();
}
?>
