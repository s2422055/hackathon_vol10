<?php
// ▼▼▼ この3行を追加してください ▼▼▼
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ▲▲▲ ここまでを追加 ▲▲▲

require_once 'db.php';

// データベースに接続
$pdo = connectDatabase();

// フォームがPOSTメソッドで送信されたかを確認
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 送信されたデータを取得
    $username = trim($_POST['username'] ?? '');
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // 入力が空でないかチェック
    if (empty($username) || empty($password1) || empty($password2)) {
        echo "<p>すべてのフィールドを入力してください。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // パスワードが一致するかチェック
    if ($password1 !== $password2) {
        echo "<p>パスワードが一致しません。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // --- ここからがご要望の核心部分です ---

    // ▼ 要望2: ユーザー名の重複チェック
    // データベースに同じユーザー名が存在しないか確認する
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hackathon10_users WHERE username = :username");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $userExists = $stmt->fetchColumn();

    // ユーザー名が既に存在する場合 (カウントが0より大きい場合)
    if ($userExists > 0) {
        // メッセージを表示して処理を終了する
        echo "<p>このユーザー名はすでに使われています。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // --- ここまでがご要望の核心部分です ---


    // パスワードを安全な形式にハッシュ化
    $hashedPassword = password_hash($password1, PASSWORD_DEFAULT);

    // データベースに新しいユーザーを登録
    $stmt = $pdo->prepare("
        INSERT INTO hackathon10_users (username, password)
        VALUES (:username, :password)
    ");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);

    // 登録処理を実行
    if ($stmt->execute()) {
        // ▼ 要望1: 登録成功時にログインページへ移動
        // header()関数を使って、ユーザーのブラウザを login.html にリダイレクトさせる
        header('Location: login.html');
        exit(); // リダイレクト後は必ず exit() を実行する
    } else {
        // データベースへの登録が失敗した場合
        echo "<p>ユーザー登録に失敗しました。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

} else {
    // POST以外の方法でアクセスされた場合は、登録フォームにリダイレクト
    header('Location: regform.html');
    exit();
}
?>