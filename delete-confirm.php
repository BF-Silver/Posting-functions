<?php
/**
* セッション開始
* セッションの保存期間を600秒に指定　※任意の秒数へ変更可能
* かつ、確実に破棄する
*/
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.gc_divisor', 1);
session_start();
/**
* DB接続情報
*/
const DB_HOST = 'mysql:host=mysql1.php.starfree.ne.jp;dbname=bfsilver21_db;charset=utf8';
const DB_USER = 'bfsilver21_user';
const DB_PASSWORD = 'hiro0701';
/**
* 削除ボタンで遷移してきたときの処理
*/
if (isset($_POST['delete_btn'])) {
/**
* 編集対象の投稿情報を取得
*/
if (isset($_POST['post_id']) && $_POST['post_id'] != '') {
// セッションに投稿IDを保持
$_SESSION['id'] = $_POST['post_id'];
try {
/**
* DB接続処理
*/
$pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // 例外が発生した際にスローする
]);
/**
* 投稿内容登録処理
*/
$sql = ('
SELECT id, title, comment
FROM board_info 
WHERE id = :ID
');
$stmt = $pdo->prepare($sql);
// プレースホルダーに値をセット
$stmt->bindValue(':ID', $_SESSION['id'], PDO::PARAM_INT);
// SQL実行
$stmt->execute();
// 投稿情報の取得
$post_info = $stmt->fetch();
$_SESSION['title'] = $post_info['title'];
$_SESSION['comment'] = $post_info['comment'];
} catch (PDOException $e) {
echo '接続失敗' . $e->getMessage();
exit();
}
// DBとの接続を切る
$pdo = null;
$stmt = null;
}
}
/**
* 削除ボタンが押下されたときの処理
*/
if (isset($_POST['delete_submit_btn'])) {
try {
/**
* DB接続処理
*/
$pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // 例外が発生した際にスローする
]);
/**
* 投稿内容削除処理
*/
$sql = ('
DELETE FROM board_info 
WHERE id = :ID
');
$stmt = $pdo->prepare($sql);
// プレースホルダーに値をセット
$stmt->bindValue(':ID', $_SESSION['id'], PDO::PARAM_INT);
// SQL実行
$stmt->execute();
// 削除に成功したらセッション変数を破棄
unset($_SESSION['id']);
unset($_SESSION['title']);
unset($_SESSION['comment']);
// 削除成功画面へ遷移
header('Location: delete-success.php');
exit();
} catch (PDOException $e) {
echo '接続失敗' . $e->getMessage();
exit();
}
// DBとの接続を切る
$pdo = null;
$stmt = null;
}
/**
* キャンセルボタンが押下されたら
* セッション情報を破棄して
* 掲示板一覧画面へ戻る
*/
if (isset($_POST['cancel_btn'])) {
unset($_SESSION['id']);
unset($_SESSION['title']);
unset($_SESSION['comment']);
header('Location: index.php');
return;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>掲示板</title>
<link rel="stylesheet" href="./style.css">
</head>
<body>
<h1>削除</h1>
<p class="delete-confirm-msg">以下の投稿を削除します</p>
<!-- 削除確認画面 -->
<section class="post-form">
<form action="#" method="post">
<div class="post-form__flex">
<div>
<p>投稿者</p>
<p><?php if (isset($_SESSION['title'])) echo $_SESSION['title']; ?></p>
</div>
<div>
<p>投稿内容</p>
<p class="p-pre"><?php if (isset($_SESSION['comment']))  echo $_SESSION['comment']; ?></p>
</div>
</div>
<div class="btn-flex">
<button type="submit" name="delete_submit_btn" value="delete_submit_btn">削除</button>
<button type="submit" name="cancel_btn" value="cancel_btn">キャンセル</button>
</div>
</form>
</section>
</body>
</html>