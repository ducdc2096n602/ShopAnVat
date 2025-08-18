<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
require "layout/header.php";
require_once('database/config.php');
require_once('database/dbhelper.php');
require_once('utils/utility.php');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_GET['product_ID'])) {
    $product_ID = $_GET['product_ID'];
    $sql = 'SELECT * FROM product WHERE product_ID = ' . intval($product_ID) . ' AND is_deleted = 0';
    $product = executeSingleResult($sql);

    $sqlImages = 'SELECT * FROM ProductImage WHERE product_ID=' . $product_ID;
    $productImages = executeResult($sqlImages);

    if ($product == null) {
        header('Location: index.php');
        die();
    }
}
?>

<!-- Thanh tìm kiếm -->
<div class="search-wrapper">
  <form action="thucdon.php" method="GET" class="search-form">
    <input name="search" id="searchInput" type="text" placeholder="Nhập từ khóa sản phẩm" value="<?= htmlspecialchars($search) ?>">
    <button type="button" id="voiceBtn" onclick="startVoiceSearch()"><i class="fas fa-microphone"></i></button>
  </form>
</div>

<!-- Bootstrap + FontAwesome + SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/header.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main content -->
<main class="container mt-5">
  <div class="row d-flex justify-content-center">
    <!-- Ảnh nhỏ -->
    <div class="col-2 d-flex flex-column align-items-center">
      <?php foreach ($productImages as $index => $image): ?>
        <img src="images/uploads/product/<?= $image['image_url'] ?>"
             class="thumb-img <?= $index == 0 ? 'active' : '' ?>" 
             onclick="changeMainImage(this)">
      <?php endforeach; ?>
    </div>

    <!-- Ảnh lớn -->
    <div class="col-5 d-flex justify-content-center">
      <img id="main-image" 
           src="images/uploads/product/<?= $productImages[0]['image_url'] ?? 'default.jpg' ?>"
           class="main-img" 
           alt="Ảnh chính">
    </div>

    <!-- Thông tin sản phẩm -->
    <div class="col-5 product-info">
      
      <h3><strong><?= $product['product_name'] ?></strong></h3>
      <?php if (!empty($product['weight'])): ?>
    <p><strong>Khối lượng:</strong> <?= (floor($product['weight']) == $product['weight']) ? number_format($product['weight'], 0, ',', '.') : number_format($product['weight'], 2, ',', '.') ?> gram</p>
    <?php endif; ?>

      <p><strong></strong><br><?= ($product['description']) ?></p>
      <p><strong>Giá:</strong> 
        <span class="gia" data-price="<?= $product['base_price'] ?>">
          <?= number_format($product['base_price'], 0, ',', '.') ?>
        </span> VNĐ
      </p>

      <!-- Số lượng -->
      <div class="form-group mb-3">
        <label for="num">Số lượng:</label>
        <div class="quantity-control">
          <button onclick="changeQuantity(-1)">-</button>
          <input id="num" type="text" value="1" readonly>
          <button onclick="changeQuantity(1)">+</button>
        </div>
      </div>

      <!-- Thành tiền -->
      <p><strong>Thành tiền:</strong> 
         <span id="price"><?= number_format($product['base_price'], 0, ',', '.') ?></span> VNĐ
      </p>

      <!-- Nút chức năng -->
      <button class="btn-custom btn-cart me-2" onclick="addToCart(<?= $product['product_ID'] ?>)">
          <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
      </button>
      <button class="btn-custom btn-buy" onclick="buyNow(<?= $product['product_ID'] ?>)">
          <i class="fa-solid fa-bolt"></i> Mua ngay
      </button>
    </div>
  </div>
</main>

<!-- CSS -->
<style>
  .text-muted strong {
    font-weight: bold; /* Đảm bảo chữ "Mô tả sản phẩm" in đậm */
}

body {
    background-color: #f4f6f8;
}
.order-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
}
.order-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    max-width: 750px;
}
.order-card {
    background: linear-gradient(145deg, #ffffff, #f1f1f1);
    border: 1px solid #dcdcdc;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}
.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
}
.order-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #e1e4e8;
    font-size: 1rem;
    font-weight: 500;
}
.order-footer {
    background-color: #f1f3f5;
    padding: 1rem;
    font-weight: 600;
    font-size: 1rem;
    color: #c0392b;
    border-top: 1px solid #e0e0e0;
}
.table thead {
    background-color: #f0f3f5;
}
.table th, .table td {
    vertical-align: middle !important;
    text-align: center;
    padding: 0.75rem;
}
.product-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #ccc;
}
.price {
    color: #d35400;
    font-weight: 500;
}
.total {
    color: #e74c3c;
    font-weight: 700;
}
.red {
    color: #c0392b;
}
</style>
</html>

<?php require_once('layout/footer.php'); ?>

<style>
main.container {
  background: #fff;
  border-radius: 10px;
  padding: 30px;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}
.thumb-img {
  width: 70px;
  height: 70px;
  object-fit: cover;
  margin-bottom: 10px;
  border-radius: 6px;
  border: 2px solid transparent;
  transition: 0.2s;
  cursor: pointer;
}
.thumb-img:hover,
.thumb-img.active {
  border-color: #ff6f00;
  transform: scale(1.05);
}
.main-img {
  width: 100%;
  max-width: 400px;
  height: auto;
  aspect-ratio: 1 / 1; /* Đảm bảo tỷ lệ hình ảnh là 1:1 */
  object-fit: cover; /* Đảm bảo ảnh không bị méo */
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  max-height: 400px; /* Giới hạn chiều cao tối đa cho ảnh */
}

.product-info h3 {
  font-size: 28px;
  font-weight: bold;
  margin-bottom: 10px;
}
.product-info p {
  font-size: 16px;
  margin: 5px 0;
}
.gia, #price {
  color: #d32f2f;
  font-weight: bold;
  font-size: 20px;
}
.btn-custom {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border: none;
  font-size: 15px;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.btn-cart {
  background-color: #4CAF50;
  color: white;
}
.btn-cart:hover {
  background-color: #45a049;
}
.btn-buy {
  background-color: #f44336;
  color: white;
}
.btn-buy:hover {
  background-color: #e53935;
}
.btn-cart i, .btn-buy i {
  font-size: 18px;
}
.quantity-control {
  display: flex;
  align-items: center;
  gap: 5px;
}
.quantity-control button {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 6px 12px;
  font-size: 18px;
  border-radius: 6px;
  cursor: pointer;
  width: 40px;
  height: 40px;
  font-weight: bold;
  transition: 0.2s;
}
.quantity-control button:hover {
  background-color: #218838;
}
.quantity-control input {
  width: 50px;
  height: 40px;
  text-align: center;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 16px;
  font-weight: bold;
}

/* Voice search mic animation */
button.listening i {
  color: red;
  animation: pulse 1s infinite;
}
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
</style>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    updateCartCount();
    updatePrice();
});

function changeQuantity(change) {
    let input = document.getElementById('num');
    let qty = parseInt(input.value);
    if (isNaN(qty)) qty = 1;
    qty += change;
    if (qty < 1) qty = 1;
    input.value = qty;
    updatePrice();
}

function updatePrice() {
    let giaElement = document.querySelector('.gia');
    let basePrice = parseInt(giaElement.getAttribute('data-price'));
    let quantity = parseInt(document.getElementById('num').value);
    let total = basePrice * quantity;
    document.getElementById('price').innerText = total.toLocaleString('vi-VN');
}

function addToCart(id) {
    let num = document.getElementById('num').value;
    $.post('api/cookie.php', { action: 'add', id: id, num: num }, function(data) {
        updateCartCount();
    });
}

function buyNow(id) {
    let num = document.getElementById('num').value;
    $.post('api/cookie.php', { action: 'add', id: id, num: num }, function(data) {
        window.location.href = "checkout.php";
    });
}

function updateCartCount() {
    $.get('api/cart_count.php', function(data) {
        if (data.count !== undefined) {
            $('#cart-count').text(data.count);
        }
    });
}

function changeMainImage(el) {
    const src = el.src;
    document.getElementById('main-image').src = src;
    document.querySelectorAll('.thumb-img').forEach(img => img.classList.remove('active'));
    el.classList.add('active');
}

// Voice search dùng SweetAlert2 thay vì alert()
function startVoiceSearch() {
    if (!('webkitSpeechRecognition' in window)) {
        Swal.fire({
            icon: 'error',
            title: 'Không hỗ trợ',
            text: 'Trình duyệt của bạn không hỗ trợ tìm kiếm bằng giọng nói.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'vi-VN';
    recognition.interimResults = false;
    const micBtn = document.getElementById("voiceBtn");

    recognition.onstart = function () {
        micBtn.classList.add("listening");
    };
    recognition.onend = function () {
        micBtn.classList.remove("listening");
    };
    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript.trim();
        if (transcript.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Không nhận được giọng nói',
                text: 'Vui lòng nói rõ hơn.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        document.getElementById("searchInput").value = transcript;
        document.querySelector(".search-form").submit();
    };
    recognition.onerror = function(event) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi khi sử dụng giọng nói',
            text: event.error,
            confirmButtonColor: '#d33'
        });
        micBtn.classList.remove("listening");
    };

    recognition.start();
}
</script>

<?php include('chatbot.php'); ?>
