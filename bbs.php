<?php
$dbh = new PDO('mysql:host=mysql;dbname=BBS_2021', 'root', '');

if (isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;
  if(isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // アップロードされた画像がある場合
    if(preg_match('/^image\//', mime_content_type($_FILES['image']['tmp_name'])) !== 1) {
      // アップロードされたものが画像ではなかった場合
      header("HTTP/1.1 302 Found");
      header("Location: ./bbs.php");
    }

    // 元のファイル名から拡張子を取得
    $pathinfo = pathinfo($_FILES['image']['name']);
    $extension = $pathinfo['extension'];
    // 新しいファイル名を決める。他の投稿の画像ファイルと重複しないように時間＋乱数で決める。
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath = '/var/www/public/image/' . $image_filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
      ':body' => $_POST['body'],
      ':image_filename' => $image_filename,
  ]);

  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
  header("HTTP/1.1 302 Found");
  header("Location: ./bbs.php");
  return;
}

$select_sth = null;
if(isset($_GET['search'])){
  // 絞り込み
  $select_sth = $dbh->prepare('SELECT * FROM bbs_entries WHERE body LIKE :search ORDER BY created_at DESC');
  $select_sth->execute([
    'search' => '%' . $_GET['search'] . '%',
  ]);
} else {
  // 全件取得
  $select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
  $select_sth->execute();
}
?>

<h1>掲示板</h1>

<!-- フォームのPOST先はこのファイル自身にする -->
<form method="POST" action="./bbs.php" enctype="multipart/form-data">
  <textarea name="body"></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image" id="imageInput">
  </div>
  <button type="submit">送信</button>
</form>

<hr>

<form method="GET" action="./bbs.php">
  <input type="text" name="search" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
  <button type="submit">検索</button>
  <?php if(!empty($_GET['search'])): ?>
  <a href="?search=">絞り込み解除</a>
  <?php endif; ?>
</form>

<hr>

<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID:  <?= $entry['id'] ?>  日時 <?= $entry['created_at'] ?></dt>
    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) // 必ず htmlspecialchars() すること ?>
      <?php if(!empty($entry['image_filename'])): ?>
      <div>
        <img src="/image/<?= $entry['image_filename'] ?>" style="max-height: 10em;">
      </div>
      <?php endif; ?>
      </dd>
      <!--
        編集フォームへのリンク
        URLクエリパメータ edit_entry_id として投稿テーブル(bbs_entriesテーブル)の主キー(idカラム)を編集フォームに渡す。
        編集フォームでは主キーを元に編集対象の投稿を取得したり更新したりする。
      -->
      <a href="./editform.php?edit_entry_id=<?= $entry['id'] ?>">編集する</a>
  </dl>
<?php endforeach ?>

<script>
documet.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  imageInput.addEventListener("change", () => {
    if (imageInput.files.length < 1) {
      // 未選択の場合
      return;
    }
    if (imageInput.files[0].size > 5* 1024 * 1024) {
      // ファイルが5MBより大きい場合
      alart("5MB以下のファイルを選択してください。");
      imageInput.value = "";
    }
  });
});
</script>