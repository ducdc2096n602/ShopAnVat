<?php 
require_once('helpers/startSession.php');
startRoleSession('customer');
require "layout/header.php"; 
require_once('database/config.php');
require_once('database/dbhelper.php');
require_once('utils/utility.php');

if (isset($_GET['news_ID'])) {
    $id = intval($_GET['news_ID']);
    $sql = 'SELECT * FROM news WHERE news_ID = ' . $id;
    $news = executeSingleResult($sql);

    if ($news == null) {
        header('Location: index.php');
        die();
    }
} else {
    header('Location: index.php');
    die();
}
?>

<style>
.container-news {
    max-width: 1000px;
    margin: 60px auto;
    padding: 0 20px;
    font-family: Arial, sans-serif;
}

.news-title {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 20px;
    text-align: center;
    color: #333;
}

.news-thumbnail {
    display: block;
    max-width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 10px;
    margin: 0 auto 25px;
}

.news-content {
    font-size: 17px;
    line-height: 1.8;
    color: #444;
    text-align: justify;
    margin-bottom: 60px;
}

.news-content img {
    display: block;
    max-width: 100%;
    height: auto;
    margin: 20px auto;
    border-radius: 10px;
}

.news-content h1,
.news-content h2,
.news-content h3 {
    color: #333;
    margin-top: 30px;
}

.news-content p {
    margin-bottom: 16px;
}

.other-news {
    margin-top: 30px;
}

.other-news h4 {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}

.other-news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.other-news-item {
    text-decoration: none;
    color: #222;
    border: 1px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    transition: box-shadow 0.3s;
    background: #fff;
}

.other-news-item:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.other-news-item img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.other-news-item .text {
    padding: 10px 15px;
    font-size: 16px;
}
</style>

<div class="container-news">
    <div class="news-title"><?= $news['title'] ?></div>
    <img src="images/uploads/newsupload/<?= $news['thumbnail'] ?>" class="news-thumbnail" alt="Thumbnail">

    <div class="news-content">
        <?= $news['content'] ?>
    </div>

    <div class="other-news">
        <h4>Các bài viết khác:</h4>
        <div class="other-news-grid">
            <?php
            $sql = 'SELECT news_ID, title, thumbnail FROM news WHERE news_ID != ' . $id . ' ORDER BY RAND() LIMIT 4';
            $newsList = executeResult($sql);
            foreach ($newsList as $item) {
                echo '
                <a href="newsdetails.php?news_ID=' . $item['news_ID'] . '" class="other-news-item">
                    <img src="images/uploads/newsupload/' . $item['thumbnail'] . '" alt="Thumbnail">
                    <div class="text">' . $item['title'] . '</div>
                </a>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>