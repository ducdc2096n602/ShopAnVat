<?php
require_once('helpers/startSession.php');
startRoleSession('customer'); // Ép rõ session khách

require "layout/header.php";
require_once('database/config.php');
require_once('database/dbhelper.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_ID = isset($_GET['category_ID']) ? intval($_GET['category_ID']) : null; 
// Thêm các biến mới cho lọc giá và sắp xếp
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : ''; 
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : ''; 

// Thêm đoạn này để bật lỗi PHP trong quá trình phát triển
// Vui lòng xóa hoặc comment lại khi đưa lên môi trường thật
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SỬA ĐỔI QUAN TRỌNG:
// Kiểm tra nếu có thay đổi trong search, category_ID, price_range, hoặc sort_by, reset page về 1
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Lấy giá trị từ session để so sánh trạng thái trước đó
$prev_search_in_session = isset($_SESSION['prev_search']) ? $_SESSION['prev_search'] : '';
$prev_category_ID_in_session = isset($_SESSION['prev_category_ID']) ? $_SESSION['prev_category_ID'] : null;
$prev_price_range_in_session = isset($_SESSION['prev_price_range']) ? $_SESSION['prev_price_range'] : ''; 
$prev_sort_by_in_session = isset($_SESSION['prev_sort_by']) ? $_SESSION['prev_sort_by'] : ''; 

// Nếu bất kỳ tham số lọc nào thay đổi so với lần trước, reset current_page về 1
if ($search !== $prev_search_in_session || 
    $category_ID !== $prev_category_ID_in_session ||
    $price_range !== $prev_price_range_in_session || 
    $sort_by !== $prev_sort_by_in_session 
) {
    $current_page = 1;
}

// Lưu trạng thái hiện tại vào session để so sánh cho lần truy cập tiếp theo
$_SESSION['prev_search'] = $search;
$_SESSION['prev_category_ID'] = $category_ID;
$_SESSION['prev_price_range'] = $price_range; 
$_SESSION['prev_sort_by'] = $sort_by; 

$limit = 20; // Số sản phẩm mỗi trang

if ($current_page < 1) $current_page = 1; // Đảm bảo page không nhỏ hơn 1
$offset = ($current_page - 1) * $limit; 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

<link rel="stylesheet" href="css/index.css">
<style>
    /* CSS cho các bộ lọc mới */
    .filter-controls-top {
        margin-bottom: 25px; /* Khoảng cách với danh sách sản phẩm */
        padding: 15px;
        background-color: #f8f9fa; /* Nền nhẹ nhàng cho khu vực lọc */
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .filter-form-top {
        display: flex; /* SỬ DỤNG FLEXBOX ĐỂ CÁC BỘ LỌC NẰM NGANG */
        flex-wrap: wrap; /* Cho phép xuống dòng nếu không đủ chỗ */
        gap: 20px; /* Khoảng cách giữa các nhóm lọc */
        align-items: flex-end; /* Căn chỉnh các mục theo đáy */
        width: 100%; /* Đảm bảo form chiếm toàn bộ chiều rộng */
    }
    .filter-group-item {
        display: flex;
        flex-direction: column; /* Label trên, input/select dưới */
        flex: 1; /* Cho phép mỗi nhóm lọc co giãn */
        min-width: 180px; /* Đảm bảo độ rộng tối thiểu */
    }
    .filter-group-item label {
        font-weight: bold;
        margin-bottom: 8px;
        color: #343a40;
        font-size: 0.95em;
    }
    .filter-group-item select,
    .filter-group-item button {
        padding: 10px 15px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        font-size: 1em;
        min-width: 150px;
        box-sizing: border-box; /* Tính cả padding vào width */
        width: 100%; /* Đảm bảo select/button lấp đầy không gian nhóm */
    }
    .filter-group-item select:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .filter-group-item button {
        background-color: #007bff;
        color: white;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
        border: none;
        align-self: flex-end; /* Căn nút "Áp dụng" xuống dưới cùng */
        min-width: 100px;
    }
    .filter-group-item button:hover {
        background-color: #0056b3;
    }
    /* Responsive cơ bản cho bộ lọc */
    @media (max-width: 768px) {
        .filter-form-top {
            flex-direction: column; /* Chuyển sang cột trên màn hình nhỏ */
            align-items: stretch; /* Kéo dài các mục để lấp đầy chiều rộng */
        }
        .filter-group-item {
            width: 100%; /* Mỗi nhóm lọc chiếm toàn bộ chiều rộng */
            min-width: unset;
        }
    }
</style>

<main>
    <div class="search-wrapper">
        <form action="thucdon.php" method="GET" class="search-form">
            <input name="search" id="searchInput" type="text" placeholder="Nhập từ khóa sản phẩm" value="<?= htmlspecialchars($search) ?>">
            <?php 
            // Giữ lại các tham số lọc khác khi tìm kiếm
            if ($category_ID !== null) {
                echo '<input type="hidden" name="category_ID" value="' . $category_ID . '">';
            }
            if (!empty($price_range)) { 
                echo '<input type="hidden" name="price_range" value="' . htmlspecialchars($price_range) . '">';
            }
            if (!empty($sort_by)) { 
                echo '<input type="hidden" name="sort_by" value="' . htmlspecialchars($sort_by) . '">';
            }
            ?>
            
            <button type="button" onclick="startVoiceSearch()" title="Tìm kiếm bằng giọng nói">
                <i class="fas fa-microphone"></i>
            </button>
        </form>
    </div>

    <section class="main-layout main-layout-2col">
        <div class="row-2col">
            <aside class="sidebar">
                <h3>Danh mục</h3>
                <ul class="category-list">
                    <li><a href="thucdon.php<?php 
                        // Sử dụng http_build_query để quản lý tham số URL dễ hơn
                        $all_link_params = [];
                        if (!empty($search)) $all_link_params['search'] = urlencode($search);
                        if (!empty($price_range)) $all_link_params['price_range'] = urlencode($price_range); 
                        if (!empty($sort_by)) $all_link_params['sort_by'] = urlencode($sort_by); 
                        echo !empty($all_link_params) ? '?' . http_build_query($all_link_params) : ''; 
                        ?>"
                        class="<?php echo ($category_ID === null && empty($search)) ? 'active' : ''; ?>">Tất cả</a></li>
                    <?php
                    $categories = executeResult("SELECT * FROM category WHERE is_deleted = 0");
                    foreach ($categories as $cat) {
                        $category_link_params = ['category_ID' => $cat['category_ID']];
                        if (!empty($search)) $category_link_params['search'] = urlencode($search);
                        if (!empty($price_range)) $category_link_params['price_range'] = urlencode($price_range); 
                        if (!empty($sort_by)) $category_link_params['sort_by'] = urlencode($sort_by); 
                        $category_link = 'thucdon.php?' . http_build_query($category_link_params);
                        
                        $active_class = ($category_ID == $cat['category_ID'] && empty($search)) ? 'active' : '';

                        echo '<li><a href="' . $category_link . '" class="' . $active_class . '">' . htmlspecialchars($cat['category_name']) . '</a></li>';
                    }
                    ?>
                </ul>
            </aside>

            <div class="product-area">
                <?php
                // Xây dựng câu truy vấn sản phẩm
                $where_clauses = ["p.is_deleted = 0"]; // Bắt đầu với điều kiện cơ bản
                $order_by = ""; // Mặc định không sắp xếp
                $display_title = "Tất cả sản phẩm"; // Tiêu đề hiển thị mặc định

                if (!empty($search)) {
                    $where_clauses[] = "p.product_name LIKE '%" . addslashes($search) . "%'";
                    $display_title = 'Kết quả tìm kiếm: "' . htmlspecialchars($search) . '"';
                } 
                
                if ($category_ID !== null) {
                    $where_clauses[] = "p.category_ID = $category_ID";
                    // Lấy tên danh mục để hiển thị
                    $category_name_result = executeSingleResult("SELECT category_name FROM category WHERE category_ID = $category_ID");
                    $display_category_name = $category_name_result ? $category_name_result['category_name'] : 'Không xác định';
                    // Nếu đã có search, thêm danh mục vào tiêu đề
                    if (!empty($search)) {
                        $display_title .= ' trong danh mục: "' . htmlspecialchars($display_category_name) . '"';
                    } else {
                        $display_title = 'Sản phẩm thuộc danh mục: "' . htmlspecialchars($display_category_name) . '"';
                    }
                }

                // Áp dụng bộ lọc khoảng giá
                $price_ranges_options = [ 
                    '0-50000' => 'Dưới 50.000 VNĐ',
                    '50000-100000' => '50.000 - 100.000 VNĐ',
                    '100000-200000' => '100.000 - 200.000 VNĐ',
                    '200000-max' => 'Trên 200.000 VNĐ'
                ];

                if (!empty($price_range)) {
                    switch ($price_range) {
                        case '0-50000':
                            $where_clauses[] = "p.base_price <= 50000";
                            break;
                        case '50000-100000':
                            $where_clauses[] = "p.base_price >= 50000 AND p.base_price <= 100000";
                            break;
                        case '100000-200000':
                            $where_clauses[] = "p.base_price >= 100000 AND p.base_price <= 200000";
                            break;
                        case '200000-max':
                            $where_clauses[] = "p.base_price >= 200000";
                            break;
                    }
                    // Cập nhật tiêu đề hiển thị nếu có lọc giá
                    if (strpos($display_title, 'Tất cả sản phẩm') !== false || strpos($display_title, 'Sản phẩm thuộc danh mục') !== false || strpos($display_title, 'Kết quả tìm kiếm') !== false) {
                           $display_title .= ' (Giá: ' . htmlspecialchars($price_ranges_options[$price_range]) . ')'; 
                    } else {
                        $display_title = 'Sản phẩm có giá: "' . htmlspecialchars($price_ranges_options[$price_range]) . '"'; 
                    }
                }

                // Áp dụng sắp xếp
                $sort_options = [
                    '' => 'Mặc định',
                    'price_asc' => 'Giá: Thấp đến Cao',
                    'price_desc' => 'Giá: Cao đến Thấp'
                ];

                if (!empty($sort_by)) {
                    if ($sort_by === 'price_asc') {
                        $order_by = "ORDER BY p.base_price ASC";
                        if (strpos($display_title, 'Sắp xếp') === false) $display_title .= ' (Sắp xếp: Thấp đến Cao)';
                    } elseif ($sort_by === 'price_desc') {
                        $order_by = "ORDER BY p.base_price DESC";
                        if (strpos($display_title, 'Sắp xếp') === false) $display_title .= ' (Sắp xếp: Cao đến Thấp)';
                    }
                }

                echo '<h2>' . $display_title . '</h2>';
                ?>
                
                <div class="filter-controls-top">
                    <form action="thucdon.php" method="GET" class="filter-form-top">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php if ($category_ID !== null) { ?>
                            <input type="hidden" name="category_ID" value="<?= $category_ID ?>">
                        <?php } ?>
                        <input type="hidden" name="page" value="<?= $current_page ?>">

                        <div class="filter-group-item">
                            <label for="price_range_top">Lọc theo giá:</label>
                            <select id="price_range_top" name="price_range">
                                <option value="">Tất cả giá</option>
                                <?php
                                foreach ($price_ranges_options as $value => $label) {
                                    $selected = ($price_range == $value) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . $label . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group-item">
                            <label for="sort_by_top">Sắp xếp :</label>
                            <select id="sort_by_top" name="sort_by">
                                <?php
                                foreach ($sort_options as $value => $label) {
                                    $selected = ($sort_by == $value) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . $label . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="filter-group-item">
                            <button type="submit">Áp dụng</button>
                        </div>
                    </form>
                </div>
                <?php
                // Nối tất cả các điều kiện WHERE lại với nhau
                $where = implode(" AND ", $where_clauses);

                // --- BẮT ĐẦU PHÂN TRANG ---
                $count_sql = "SELECT COUNT(p.product_ID) as total FROM product p WHERE $where";
                $total_products_result = executeSingleResult($count_sql);
                $total_products = $total_products_result ? $total_products_result['total'] : 0;
                $total_pages = ceil($total_products / $limit);

                // Truy vấn sản phẩm theo trang
                $sql = "SELECT p.*, pi.image_url 
                                FROM product p 
                                LEFT JOIN productimage pi ON p.product_ID = pi.product_ID AND pi.is_primary = 1
                                WHERE $where
                                $order_by 
                                LIMIT $limit OFFSET $offset";

                $products = executeResult($sql);

                echo '<div class="product-recently"><div class="row">';
                if (empty($products)) {
                    echo '<p style="text-align: center; width: 100%; padding: 20px;">Không tìm thấy sản phẩm nào.</p>';
                } else {
                    foreach ($products as $item) {
                        $imgPath = 'images/uploads/product/' . $item['image_url'];
                        // Kiểm tra ảnh tồn tại, nếu không thì dùng ảnh placeholder
                        if (!file_exists($imgPath) || !is_file($imgPath)) {
                            $imgPath = 'images/placeholder.webp'; // Đảm bảo bạn có file này trong thư mục images
                        }
                        echo '
                        <div class="product-item">
                            <a href="details.php?product_ID=' . $item['product_ID'] . '">
                                <img class="thumbnail" src="' . $imgPath . '" alt="' . htmlspecialchars($item['product_name']) . '">
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
                        $prev_page_link_params = ['page' => ($current_page - 1)];
                        if (!empty($search)) $prev_page_link_params['search'] = urlencode($search);
                        if ($category_ID !== null) $prev_page_link_params['category_ID'] = $category_ID;
                        if (!empty($price_range)) $prev_page_link_params['price_range'] = urlencode($price_range); 
                        if (!empty($sort_by)) $prev_page_link_params['sort_by'] = urlencode($sort_by); 
                        echo '<a href="thucdon.php?' . http_build_query($prev_page_link_params) . '" class="arrow" title="Trang trước"><i class="fas fa-angle-left"></i></a>';
                    }

                    $range = 2;
                    for ($i = 1; $i <= $total_pages; $i++) {
                        if (
                            $i == 1 || $i == $total_pages ||
                            ($i >= $current_page - $range && $i <= $current_page + $range)
                        ) {
                            $page_link_params = ['page' => $i];
                            if (!empty($search)) $page_link_params['search'] = urlencode($search);
                            if ($category_ID !== null) $page_link_params['category_ID'] = $category_ID;
                            if (!empty($price_range)) $page_link_params['price_range'] = urlencode($price_range); 
                            if (!empty($sort_by)) $page_link_params['sort_by'] = urlencode($sort_by); 

                            $page_link = 'thucdon.php?' . http_build_query($page_link_params);
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
                        $next_page_link_params = ['page' => ($current_page + 1)];
                        if (!empty($search)) $next_page_link_params['search'] = urlencode($search);
                        if ($category_ID !== null) $next_page_link_params['category_ID'] = $category_ID;
                        if (!empty($price_range)) $next_page_link_params['price_range'] = urlencode($price_range); 
                        if (!empty($sort_by)) $next_page_link_params['sort_by'] = urlencode($sort_by); 
                        echo '<a href="thucdon.php?' . http_build_query($next_page_link_params) . '" class="arrow" title="Trang sau"><i class="fas fa-angle-right"></i></a>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>
</main>

<script>
function startVoiceSearch() {
    if (!('webkitSpeechRecognition' in window)) {
        Swal.fire({
            icon: 'warning',
            title: 'Trình duyệt không hỗ trợ',
            text: 'Trình duyệt của bạn không hỗ trợ tính năng tìm kiếm bằng giọng nói.'
        });
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'vi-VN';
    recognition.continuous = false;
    recognition.interimResults = false;

    const micBtn = document.querySelector(".search-form button[title='Tìm kiếm bằng giọng nói']"); 

    recognition.onstart = () => micBtn.classList.add("listening");
    recognition.onend = () => micBtn.classList.remove("listening");

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript.trim();
        if (transcript.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Không nghe rõ',
                text: 'Không nhận được nội dung giọng nói. Vui lòng thử lại.'
            });
            return;
        }
        document.getElementById("searchInput").value = transcript;
        document.querySelector(".search-form").submit();
    };

    recognition.onerror = function(event) {
        console.error("Lỗi nhận diện giọng nói: ", event.error); 
        Swal.fire({
            icon: 'error',
            title: 'Lỗi giọng nói',
            text: 'Không thể sử dụng giọng nói: ' + event.error + '. Vui lòng kiểm tra quyền truy cập microphone.'
        });
        micBtn.classList.remove("listening");
    };

    recognition.start();
}
</script>

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

<script>
function updateCartCount() {
    $.get('api/cart_count.php', function(data) {
        if (typeof data.count !== 'undefined') {
            $('#cart-count').text(data.count);
        } else {
            $('#cart-count').text('0'); 
        }
    }).fail(function() {
        console.error("Lỗi khi lấy số lượng giỏ hàng.");
        $('#cart-count').text('?'); 
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
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<?php require_once('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>