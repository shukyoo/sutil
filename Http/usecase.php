<?php
$login_form = new LoginForm();

$article = new ArticleForm($_POST);
Article::save($article);

$article = new ArticleForm(1);
?>

<input type="text" name="xxxx" value="<?= $article->title ?>">