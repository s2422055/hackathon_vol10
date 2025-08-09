<?php
session_start();

// ✅ **データベース接続関数**
function connectDatabase() {
    $host = 'localhost'; 
    $dbname = 'your_database_name'; // データベース名を指定
    $username = 'your_username'; // ユーザー名を指定
    $password = 'your_password'; // パスワードを指定

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}
?>