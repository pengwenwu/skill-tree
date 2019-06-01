<?php
require './ArticleVote.php';
$article_vote = new ArticleVote();
$article_vote->postArticle('user:3', '这是第三篇文章');

$article_vote->articleVote('user:4', 'article:2');
$articles = $article_vote->getArticles(1);

var_dump($articles);
