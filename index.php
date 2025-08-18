<?php
require_once('helpers/startSession.php');
startRoleSession('customer'); // Ép rõ session khách
require "layout/header.php";
require_once('database/config.php');
require_once('database/dbhelper.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_ID = isset($_GET['category_ID']) ? intval($_GET['category_ID']) : null;
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

<style>
button.listening i {
    color: red;
    animation: pulse 1s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* --- BẮT ĐẦU CSS PHÂN TRANG ĐẸP --- */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
    margin-top: 24px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.pagination a, .pagination span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    font-size: 1.1rem;
    border-radius: 50%;
    border: 1px solid #e0e0e0;
    background: #fff;
    color: #007bff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.2s;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
}
.pagination a:hover {
    background: #007bff;
    color: #fff;
    box-shadow: 0 4px 16px rgba(0,123,255,0.12);
}
.pagination span.current-page {
    background: linear-gradient(90deg,#007bff 60%,#00c6ff 100%);
    color: #fff;
    border: none;
    font-weight: bold;
    box-shadow: 0 4px 16px rgba(0,123,255,0.18);
    cursor: default;
}
.pagination .arrow {
    font-size: 1.3rem;
    padding: 0 6px;
    color: #007bff;
    background: none;
    border: none;
    box-shadow: none;
}
.pagination a.arrow:hover {
    color: #fff;
    background: #007bff;
}
@media (max-width: 600px) {
    .pagination a, .pagination span {
        min-width: 30px;
        height: 30px;
        font-size: 0.95rem;
    }
}
/* --- KẾT THÚC CSS PHÂN TRANG ĐẸP --- */
</style>

<main>
    <div class="search-wrapper">
        <form action="thucdon.php" method="GET" class="search-form">
            <input name="search" id="searchInput" type="text" placeholder="Nhập từ khóa sản phẩm" value="<?= htmlspecialchars($search) ?>">
            <button type="button" onclick="startVoiceSearch()" title="Tìm kiếm bằng giọng nói">
                <i class="fas fa-microphone"></i>
            </button>
        </form>
    </div>

    <!-- Banner Swiper -->
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide"><img src="images/uploads/banner/chuchu_banner1.jpeg" alt="Banner 1"></div>
            <div class="swiper-slide"><img src="images/uploads/banner/chuchu_banner2.png" alt="Banner 2"></div>
            <div class="swiper-slide"><img src="images/uploads/banner/chuchu_banner3.png" alt="Banner 3"></div>
            <div class="swiper-slide"><img src="images/uploads/banner/chuchu_banner4.jpeg" alt="Banner 4"></div>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>

    <!-- Danh mục + sản phẩm -->
    <section class="main-layout main-layout-2col">
        <div class="row-2col">
            <aside class="sidebar">
                <h3>Danh mục</h3>
                <ul class="category-list">
                    <?php
                    $categories = executeResult("SELECT * FROM category WHERE is_deleted = 0");
                    foreach ($categories as $cat) {
                        echo '<li><a href="thucdon.php?category_ID=' . $cat['category_ID'] . '">' . htmlspecialchars($cat['category_name']) . '</a></li>';
                    }
                    ?>
                </ul>
            </aside>

            <div class="product-area">
                <?php
                // Xây dựng câu truy vấn sản phẩm
                if (!empty($search)) {
                    echo '<h2>Kết quả tìm kiếm: "' . htmlspecialchars($search) . '"</h2>';
                    $where = "p.is_deleted = 0 AND p.product_name LIKE '%" . addslashes($search) . "%'";
                } else if ($category_ID !== null) {
                    $where = "p.is_deleted = 0 AND p.category_ID = $category_ID";
                } else {
                    $where = "p.is_deleted = 0";
                }

// --- BẮT ĐẦU PHÂN TRANG ---
$limit = 20; // Số sản phẩm mỗi trang
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $limit;

// Đếm tổng số sản phẩm phù hợp
$count_sql = "SELECT COUNT(p.product_ID) as total FROM product p WHERE $where";
$total_products_result = executeSingleResult($count_sql);
$total_products = $total_products_result ? $total_products_result['total'] : 0;
$total_pages = ceil($total_products / $limit);

// Truy vấn sản phẩm theo trang
$sql = "SELECT p.*, pi.image_url 
        FROM product p 
        LEFT JOIN productimage pi ON p.product_ID = pi.product_ID AND pi.is_primary = 1
        WHERE $where
        LIMIT $limit OFFSET $offset";

$products = executeResult($sql);

echo '<div class="product-recently"><div class="row">';
if (empty($products)) {
    echo '<p style="text-align: center; width: 100%; padding: 20px;">Không tìm thấy sản phẩm nào.</p>';
} else {
    foreach ($products as $item) {
        $imgPath = 'images/uploads/product/' . $item['image_url'];
        echo '
        <div class="product-item">
            <a href="details.php?product_ID=' . $item['product_ID'] . '">
                <img class="thumbnail" src="' . $imgPath . '" alt="">
                <div class="product_name"><p>' . htmlspecialchars($item['product_name']) . '</p></div>
                <div class="base_price"><span>' . number_format($item['base_price'], 0, ',', '.') . ' VNĐ</span></div>
            </a>
            <input type="number" id="num_' . $item['product_ID'] . '" value="1" min="1">
            <button class="add-cart" onclick="addToCart(' . $item['product_ID'] . ')">
                <i class="fas fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
        </div>';
    }
}
echo '</div></div>';

// Hiển thị phân trang
if ($total_pages > 1) {
    echo '<div class="pagination">';
    // Nút "Trang trước" với icon
    if ($current_page > 1) {
        $prev_page_link = 'index.php?page=' . ($current_page - 1);
        if (!empty($search)) $prev_page_link .= '&search=' . urlencode($search);
        if ($category_ID !== null) $prev_page_link .= '&category_ID=' . $category_ID;
        echo '<a href="' . $prev_page_link . '" class="arrow" title="Trang trước"><i class="fas fa-angle-left"></i></a>';
    }
    // Hiển thị tối đa 2 trang đầu, 2 trang cuối và 2 trang trước/sau trang hiện tại
    $range = 2;
    for ($i = 1; $i <= $total_pages; $i++) {
        if (
            $i == 1 || $i == $total_pages ||
            ($i >= $current_page - $range && $i <= $current_page + $range)
        ) {
            $page_link = 'index.php?page=' . $i;
            if (!empty($search)) $page_link .= '&search=' . urlencode($search);
            if ($category_ID !== null) $page_link .= '&category_ID=' . $category_ID;
            if ($i == $current_page) {
                echo '<span class="current-page">' . $i . '</span>';
            } else {
                echo '<a href="' . $page_link . '">' . $i . '</a>';
            }
        } elseif ($i == 2 && $current_page > $range + 2) {
            echo '<span style="border:none;background:none;">...</span>';
        } elseif ($i == $total_pages - 1 && $current_page < $total_pages - $range - 1) {
            echo '<span style="border:none;background:none;">...</span>';
        }
    }
    // Nút "Trang sau" với icon
    if ($current_page < $total_pages) {
        $next_page_link = 'index.php?page=' . ($current_page + 1);
        if (!empty($search)) $next_page_link .= '&search=' . urlencode($search);
        if ($category_ID !== null) $next_page_link .= '&category_ID=' . $category_ID;
        echo '<a href="' . $next_page_link . '" class="arrow" title="Trang sau"><i class="fas fa-angle-right"></i></a>';
    }
    echo '</div>';
}
// --- KẾT THÚC PHÂN TRANG ---
?>
        </div>
    </section>
</main>

<!-- Voice Search with SweetAlert -->
<script>
function startVoiceSearch() {
    if (!('webkitSpeechRecognition' in window)) {
        Swal.fire({
            icon: 'warning',
            title: 'Trình duyệt không hỗ trợ',
            text: 'Trình duyệt của bạn không hỗ trợ chức năng tìm kiếm bằng giọng nói. Vui lòng sử dụng Chrome hoặc Edge.'
        });
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'vi-VN';
    recognition.continuous = false;
    recognition.interimResults = false;

    const micBtn = document.querySelector(".search-form button");

    recognition.onstart = () => micBtn.classList.add("listening");
    recognition.onend = () => micBtn.classList.remove("listening");

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript.trim();
        if (!transcript) {
            Swal.fire({
                icon: 'info',
                title: 'Không nhận diện được giọng nói',
                text: 'Hệ thống không thể hiểu rõ bạn vừa nói gì. Vui lòng thử lại.'
            });
            return;
        }
        document.getElementById("searchInput").value = transcript;
        document.querySelector(".search-form").submit();
    };

    recognition.onerror = function(event) {
        micBtn.classList.remove("listening");
        let message = 'Đã xảy ra lỗi không xác định.';

        switch (event.error) {
            case 'not-allowed':
                message = 'Bạn đã từ chối quyền sử dụng micro. Vui lòng cho phép micro để sử dụng chức năng này.';
                break;
            case 'no-speech':
                message = 'Không phát hiện âm thanh. Vui lòng kiểm tra micro và thử lại.';
                break;
            case 'audio-capture':
                message = 'Không thể truy cập micro. Hãy đảm bảo thiết bị của bạn đã bật micro.';
                break;
            default:
                message = 'Lỗi: ' + event.error;
                break;
        }

        Swal.fire({
            icon: 'error',
            title: 'Lỗi giọng nói',
            text: message
        });
    };

    recognition.start();
}

</script>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
const swiper = new Swiper(".mySwiper", {
    loop: true,
    autoplay: {
        delay: 4000,
        disableOnInteraction: false,
    },
    pagination: { el: ".swiper-pagination", clickable: true },
    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
});
</script>

<!-- Cart & Chatbot -->
<script>
function updateCartCount() {
    $.get('api/cart_count.php', function(data) {
        $('#cart-count').text(data.count);
    });
}
function addToCart(id) {
    const num = document.querySelector('#num_' + id).value;

    // Hiệu ứng ảnh bay
    const productItem = document.querySelector('#num_' + id).closest('.product-item');
    const productImg = productItem.querySelector('img');
    const cartIcon = document.querySelector('.cart-icon i');

    const cloneImg = productImg.cloneNode(true);
    const imgRect = productImg.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();

    cloneImg.style.position = 'fixed';
    cloneImg.style.zIndex = 9999;
    cloneImg.style.left = imgRect.left + 'px';
    cloneImg.style.top = imgRect.top + 'px';
    cloneImg.style.width = imgRect.width + 'px';
    cloneImg.style.height = imgRect.height + 'px';
    cloneImg.style.transition = 'all 0.8s ease-in-out';

    document.body.appendChild(cloneImg);

    setTimeout(() => {
        cloneImg.style.left = cartRect.left + 'px';
        cloneImg.style.top = cartRect.top + 'px';
        cloneImg.style.width = '0px';
        cloneImg.style.height = '0px';
        cloneImg.style.opacity = 0.2;
    }, 20);

    setTimeout(() => {
        document.body.removeChild(cloneImg);
        cartIcon.classList.add('shake-cart');
        setTimeout(() => cartIcon.classList.remove('shake-cart'), 500);
    }, 900);

    // AJAX thêm vào giỏ
    $.post('/ShopAnVat/api/cookie.php', { action: 'add', id: id, num: num }, function () {
    updateCartCount();

    
});

}
</script>
<script>
function updateCartCount() {
  fetch('/ShopAnVat/api/cart_count.php')
    .then(response => response.json())
    .then(data => {
      const countElement = document.getElementById('cart-count');
      if (countElement) {
        countElement.textContent = data.count;
      }
    })
    .catch(error => {
      console.error('Lỗi khi lấy số lượng giỏ hàng:', error);
    });
}

document.addEventListener("DOMContentLoaded", function () {
  updateCartCount();
});
</script>

<?php require_once('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>