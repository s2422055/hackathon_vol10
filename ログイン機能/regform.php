<?php
require_once 'db.php';

$pdo = connectDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $field = trim($_POST['field'] ?? ''); // 興味のある分野

    // 空チェック
    if (empty($username) || empty($password1) || empty($password2) || empty($field)) {
        echo "<p>すべてのフィールドを入力してください。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // 長さチェック（20文字以内）
    if (
        mb_strlen($username) > 20 ||
        mb_strlen($password1) > 20 ||
        mb_strlen($password2) > 20 ||
        mb_strlen($field) > 100 // 任意の制限、適宜変更可
    ) {
        echo "<p>各入力欄は適切な長さで入力してください（例: ユーザー名とパスワードは20文字以内）。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // パスワード一致チェック
    if ($password1 !== $password2) {
        echo "<p>パスワードが一致しません。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // ユーザー名の重複チェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group2_users WHERE username = :username");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $userExists = $stmt->fetchColumn();

    if ($userExists > 0) {
        echo "<p>このユーザー名はすでに使われています。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

    // パスワードをハッシュ化
    $hashedPassword = password_hash($password1, PASSWORD_DEFAULT);

    // ユーザー登録
    $stmt = $pdo->prepare("
        INSERT INTO group2_users (username, password, field, points)
        VALUES (:username, :password, :field, 0)
    ");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
    $stmt->bindValue(':field', $field, PDO::PARAM_STR);

    if ($stmt->execute()) {
        // affiliation にも登録する
        $stmt2 = $pdo->prepare("
            INSERT INTO affiliation (username)
            VALUES (:username)
        ");
        $stmt2->bindValue(':username', $username, PDO::PARAM_STR);
        if ($stmt2->execute()) {
            header('Location: login.html');
            exit();
        } else {
            echo "<p>affiliationテーブルへの登録に失敗しました。</p>";
            echo "<a href=\"./regform.html\">戻る</a>";
            exit();
        }
    } else {
        echo "<p>group2_usersへの登録に失敗しました。</p>";
        echo "<a href=\"./regform.html\">戻る</a>";
        exit();
    }

} else {
    header('Location: register.html');
    exit();
}
?>
