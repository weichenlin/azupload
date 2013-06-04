<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
</head>
<body>
    <form action="/?act=upload" method="post" enctype="multipart/form-data">
        <label for="file">choose file:</label>
        <input type="file" name="file"><br />
        <input type="submit" value="上傳" />
    </form>
    <?php foreach($this->histories as $data): ?>
        <a href="<?=$data[ 'url' ]"><?=$data[ 'file' ]?></a>
    <?php endforeach; ?>
</body>
</html>