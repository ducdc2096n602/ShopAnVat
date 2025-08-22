<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
require "layout/header.php";
require_once('database/config.php');
require_once('database/dbhelper.php');

// Lấy danh mục nếu có
$cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$where = '';
if ($cat_id) {
    $where = "WHERE news.CategoryNews_ID = $cat_id";
}
$sql = "SELECT news.*, categorynews.name AS category_name 
        FROM news 
        LEFT JOIN categorynews ON news.CategoryNews_ID = categorynews.CategoryNews_ID 
        $where
        ORDER BY news.created_at DESC";
$newsList = executeResult($sql);
?>

<style>
.news-item-link {
    text-decoration: none;
    color: inherit;
    display: block;
    margin-bottom: 30px;
}
.news-item {
    display: flex;
    gap: 20px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 20px;
    transition: background-color 0.3s;
}
.news-item:hover {
    background-color: #f9f9f9;
}
.news-thumb {
    width: 220px;
    height: 140px;
    object-fit: cover;
    border-radius: 10px;
    flex-shrink: 0;
}
.news-info h5 {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 5px;
}
.news-info small {
    color: #888;
}
.news-info p {
    margin-top: 10px;
    color: #444;
    font-size: 15px;
}
</style>

<main style="margin-top: 2rem;">
    <section class="main-layout main-layout-2col">
        <div class="row-2col">
            <aside class="sidebar">
                <h3>Danh mục tin tức</h3>
                <ul class="category-list">
                    <?php
                    $categories = executeResult("SELECT * FROM categorynews");
                    foreach ($categories as $cat) {
                        $active = ($cat_id == $cat['CategoryNews_ID']) ? 'style="font-weight:bold;color:#d00;"' : '';
                        echo '<li><a href="news.php?cat=' . $cat['CategoryNews_ID'] . '" ' . $active . '>' . htmlspecialchars($cat['name']) . '</a></li>';
                    }
                    ?>
                </ul>
            </aside>

            <!-- Nội dung tin tức -->
            <div class="product-area">
                <h2 style="margin-bottom: 20px;">
                    <?= $cat_id ? 'Danh mục: ' . htmlspecialchars($categories[array_search($cat_id, array_column($categories, 'CategoryNews_ID'))]['name']) : 'Tin tức mới nhất' ?>
                </h2>

                <?php
                if (empty($newsList)) {
                    echo "<p>Không có bài viết nào.</p>";
                } else {
                    foreach ($newsList as $news) {
                        echo '
                        <a class="news-item-link" href="newsdetails.php?news_ID=' . $news['news_ID'] . '">
                            <div class="news-item">
                                <img class="news-thumb" src="images/uploads/newsupload/' . $news['thumbnail'] . '" alt="Thumbnail">
                                <div class="news-info">
                                    <h5>' . $news['title'] . '</h5>
                                    <small>Danh mục: ' . $news['category_name'] . ' | Ngày đăng: ' . date('d/m/Y H:i', strtotime($news['created_at'])) . '</small>
                                    <p>' . mb_substr(strip_tags($news['content']), 0, 150, 'UTF-8') . '...</p>
                                </div>
                            </div>
                        </a>';
                    }
                }
                ?>
            </div>
        </div>
    </section>
</main>

<?php require_once('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>
