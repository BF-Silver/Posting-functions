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
* 投稿者ID（20桁）を生成
*/
if (isset($_SESSION['cont_id'])) {
$cont_id = $_SESSION['cont_id'];
} else {
$_SESSION['cont_id'] = 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) .
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . chr(mt_rand(65, 90)) . 
chr(mt_rand(65, 90)) . chr(mt_rand(65, 90));
$cont_id = $_SESSION['cont_id'];
}
/**
* DB接続情報
*/
const DB_HOST = 'mysql:host=mysql1.php.starfree.ne.jp;dbname=bfsilver21_db;charset=utf8';
const DB_USER = 'bfsilver21_user';
const DB_PASSWORD = 'hiro0701';
/**
* 投稿ボタンが押下されたときの処理
*/
if (isset($_POST['post_btn'])) {
// 更新操作用の処理
unset($_SESSION['id']);
/**
* セッション変数に情報を保存して
* タイトルまたは投稿内容の片方だけが
* 入力されていた場合、
* 入力フォームに内容を保持する
*/
if (isset($_POST['post_title']) && $_POST['post_title'] != '') {
$_SESSION['title'] = $_POST['post_title'];
} else {
unset($_SESSION['title']);
}
if (isset($_POST['post_comment']) && $_POST['post_comment'] != '') {
$_SESSION['comment'] = $_POST['post_comment'];
} else {
unset($_SESSION['comment']);
}
/**
* エラーメッセージ格納
*/
if ($_POST['post_title'] == '') $err_msg_title  = '※投稿者を入力して下さい';
if ($_POST['post_comment'] == '') $err_msg_comment  = '※投稿内容を入力して下さい';
/**
* 必要項目がすべて入力されてたら投稿処理を実行
*/
if (
isset($_POST['post_title']) && $_POST['post_title'] != '' &&
isset($_POST['post_comment']) && $_POST['post_comment'] != ''
) {
$title = $_POST['post_title'];
$comment = $_POST['post_comment'];
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
INSERT INTO
board_info (title, comment, contributor_id)
VALUES
(:TITLE, :COMMENT, :CONTRIBUTOR_ID)
');
$stmt = $pdo->prepare($sql);
// プレースホルダーに値をセット
$stmt->bindValue(':TITLE', $title, PDO::PARAM_STR);
$stmt->bindValue(':COMMENT', $comment, PDO::PARAM_STR);
$stmt->bindValue(':CONTRIBUTOR_ID', $cont_id, PDO::PARAM_STR);
// SQL実行
$stmt->execute();
// 投稿に成功したらセッション変数を破棄
unset($_SESSION['title']);
unset($_SESSION['comment']);
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
* 投稿一覧取得処理
*/
try {
/**
* DB接続処理
*/
$pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // データをカラム名をキーとする連想配列で取得する
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // 例外が発生した際にスローする
]);
$sql = ('
SELECT * 
FROM board_info 
ORDER BY id DESC
');
$stmt = $pdo->prepare($sql);
// SQL実行
$stmt->execute();
// 投稿情報を辞書形式ですべて取得
$post_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
echo '接続失敗' . $e->getMessage();
exit();
}


// 1ページに表示する件数を定義
$limit = 10;

// 現在のページ番号を取得（デフォルトは1）
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = (int)$_GET['page'];
} else {
    $current_page = 1;
}

// オフセットの計算
$offset = ($current_page - 1) * $limit;

/**
 * DB接続処理（投稿一覧取得）
 */
try {
    // DB接続
    $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // 全体の投稿数を取得
    $sql_count = 'SELECT COUNT(*) AS total FROM board_info';
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute();
    $total_posts = $stmt_count->fetch()['total'];
    
    // 投稿一覧をページ単位で取得
    $sql = 'SELECT * FROM board_info ORDER BY id DESC LIMIT :LIMIT OFFSET :OFFSET';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':LIMIT', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':OFFSET', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $post_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '接続失敗' . $e->getMessage();
    exit();
}

// 総ページ数の計算
$total_pages = ceil($total_posts / $limit);



if (isset($_POST['comment_btn'])) {
    // 投稿IDとコメント内容を取得
    $post_id = $_POST['post_id'];
    $comment_text = $_POST['comment_text'];
    
    // エラーチェック
    if ($comment_text == '') {
        $err_msg_comment = '※コメント内容を入力してください';
    } else {
        try {
            // DB接続
            $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 例外が発生した際にスローする
            ]);

            // コメントの挿入SQL
            $sql = '
                INSERT INTO comments (post_id, contributor_id, comment)
                VALUES (:POST_ID, :CONTRIBUTOR_ID, :COMMENT)
            ';

            // SQL準備
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':POST_ID', $post_id, PDO::PARAM_INT);
            $stmt->bindValue(':CONTRIBUTOR_ID', $cont_id, PDO::PARAM_STR);
            $stmt->bindValue(':COMMENT', $comment_text, PDO::PARAM_STR);

            // SQL実行
            $stmt->execute();
        } catch (PDOException $e) {
            echo '接続失敗' . $e->getMessage();
            exit();
        }
    }
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
<h1>掲示板</h1>
<!-- 投稿フォーム -->
<section class="post-form">
<form action="#" method="post">
<div class="post-form__flex">
<div>
<label>
<p>投稿者</p>
<input type="text" name="post_title" value="<?php if (isset($_SESSION['title'])) echo $_SESSION['title']; ?>">
<!-- エラーメッセージ -->
<?php if (isset($err_msg_title)) {
echo "<p class='err'>{$err_msg_title}</p>";
} ?>
</label>
</div>
<div>
<label>
<p>投稿内容</p>
<textarea name="post_comment" cols="50" rows="10"><?php if (isset($_SESSION['comment'])) echo $_SESSION['comment']; ?></textarea>
<!-- エラーメッセージ -->
<?php if (isset($err_msg_comment)) {
echo "<p class='err'>{$err_msg_comment}</p>";
} ?>
</label>
</div>
</div>
<button class="btn--mg-c" type="submit" name="post_btn" value="post_btn">投稿</button>
</form>
</section>
<hr>
<!-- 投稿一覧 -->
<section class="post-list">
<?php if (count($post_list) === 0) : ?>
<!-- 投稿が無いときはメッセージを表示する -->
<p class="no-post-msg">投稿後に表示されます</p>
<?php else : ?>
<ul>
<!-- 投稿情報の出力 -->
<?php foreach ($post_list as $post_item) : ?>
<li>
<form action="" method="post">
<!-- 投稿ID -->
<span>ID：<?php echo $post_item['id']; ?>　</span>
<!-- 投稿タイトル -->
<span><?php echo $post_item['title']; ?></span>
<!-- 投稿者ID -->
<span>／投稿者：<?php echo $post_item['contributor_id']; ?></span>
<!-- 投稿内容 -->
<p class="p-pre"><?php echo $post_item['comment']; ?></p>
<!-- 投稿日時 -->
<span class="post-datetime">投稿日時：<?php echo $post_item['created_at']; ?></span>
<!-- 過去に更新されていたら更新日時も表示 -->
<?php if ($post_item['created_at'] < $post_item['updated_at']) : ?>
<span class="post-datetime post-datetime__updated">更新日時：<?php echo $post_item['updated_at']; ?></span>
<?php endif; ?>
</form>
<!-- 自分の投稿内容かつセッションが有効な間は編集・削除が可能 -->
<?php if ($post_item['contributor_id'] === $cont_id) : ?>
<div class="btn-flex">
<form action="update-edit.php" method="post">
<button type="submit" name="update_btn">編集</button>
<input type="hidden" name="post_id" value="<?php echo $post_item['id']; ?>">
</form>
<form action="delete-confirm.php" method="post">
<button type="submit" name="delete_btn">削除</button>
<input type="hidden" name="post_id" value="<?php echo $post_item['id']; ?>">
</form>
</div>
<?php endif; ?>
<?php if (isset($_SESSION['id']) && ($_SESSION['id'] == $post_item['id'])): ?>
<p class='updated-post'>更新しました</p>
<?php endif; ?>



    <!-- コメント表示を追加 -->
    <?php
    try {
        // DB接続
        $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // コメントの取得SQL
        $sql = 'SELECT * FROM comments WHERE post_id = :POST_ID ORDER BY id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':POST_ID', $post_item['id'], PDO::PARAM_INT);
        $stmt->execute();

        // コメント一覧を取得
        $comments = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo '接続失敗' . $e->getMessage();
        exit();
    }
    ?>

    <?php if (!empty($comments)) : ?>
        <section class="comments">
            <h3>コメント一覧</h3>
            <ul>
                <?php foreach ($comments as $comment) : ?>
                    <li>
                        <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                        <span>投稿者ID: <?php echo $comment['contributor_id']; ?></span>
                        <span>投稿日時: <?php echo $comment['created_at']; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>


<!-- コメントフォーム -->
<section class="comment-form">
    <h4>コメントする</h4>
    <form action="#" method="post">
        <input type="hidden" name="post_id" value="<?php echo $post_item['id']; ?>">
        <textarea name="comment_text" rows="5" cols="50"></textarea>
        <button type="submit" name="comment_btn" value="comment_btn">コメント</button>
    </form>
</section>



</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>




<!-- ページネーション -->
<?php if ($total_pages > 1) : ?>
    <nav class="pagination">
        <ul>
            <?php if ($current_page > 1) : ?>
                <li><a href="?page=<?php echo $current_page - 1; ?>">前のページ</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                <li><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages) : ?>
                <li><a href="?page=<?php echo $current_page + 1; ?>">次のページ</a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

</body>
</html>