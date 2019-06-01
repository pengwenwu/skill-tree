<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/17
 * Time: 21:39
 */

class ArticleVote
{
    const ONE_WEEK_IN_SECONDS = 7 * 86400;
    const VOTE_SCORE = 432;
    const ARTICLE_PER_PAGE = 20;

    private $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    /**
     * 用户投票
     * @param $user
     * @param $article
     */
    public function articleVote(string $user/**user:123*/, string $article/**article:123*/)
    {
        // 获取文章投票截取时间（一星期内）
        $cutoff_time = time() - self::ONE_WEEK_IN_SECONDS;

        // 检查文章是否可以进行投票
        if ($this->redis->zScore('time:', $article) < $cutoff_time) {
            return;
        }

        $tmp = explode($article, ':');
        $article_id = end($tmp);
        if ($this->redis->sAdd('voted:' . $article_id, $user)) {
            // 第一次投票成功的后续处理：票数增加；评分增加
            // TODO: 事务处理
            $this->redis->zIncrBy('score:', $article, self::VOTE_SCORE);
            $this->redis->hIncrBy($article, 'votes', 1);
        }
    }

    /**
     * 发布新文章
     * @param string $user
     * @param string $title
     * @return string
     */
    public function postArticle(string $user/**user:123*/, string $title): string
    {
        $article_id = (string)$this->redis->incr('article:');

        $voted = 'voted:' . $article_id;
        $this->redis->sAdd($voted, $user);
        $this->redis->expire($voted, self::ONE_WEEK_IN_SECONDS);

        $article = 'article:' . $article_id;
        $now = microtime(true);
        $this->redis->hMSet($article, [
            'title' => $title,
            'poster' => $user,
            'time' => microtime(true),
            'votes' => 1,
        ]);
        $this->redis->zAdd('score:', $now + self::VOTE_SCORE, $article);
        $this->redis->zAdd('time:', $now, $article);

        return $article_id;
    }

    /**
     * 获取文章列表
     * @param int $page
     * @param string $order
     * @return array
     */
    public function getArticles(int $page, string $order = 'score:'): array
    {
        $start = ($page - 1) * self::ARTICLE_PER_PAGE;
        $end = $start + self::ARTICLE_PER_PAGE - 1;

        $ids = $this->redis->zRevRange($order, $start, $end);

        $articles = [];
        foreach ($ids as $id) {
            $article_data = $this->redis->hGetAll($id);
            $article_data['id'] = $id;

            $articles[] = $article_data;
        }

        return $articles;
    }
}
