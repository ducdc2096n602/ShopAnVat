<!-- sidebar.php -->
<div class="d-flex" style="min-height: 100vh;">
  <!-- Sidebar -->
  <div class="bg-primary text-white p-3 sidebar" style="width: 220px;">
    <h4 class="fw-bold">ADMIN PANEL</h4>
    <ul class="nav flex-column mt-4">

      <!-- Sản phẩm -->
      <li class="nav-item">
        <a class="nav-link text-white" data-bs-toggle="collapse" href="#productMenu" role="button">
          📦 Sản phẩm
        </a>
        <div class="collapse ps-3" id="productMenu">
          <a href="/ShopAnVat/admin/pages/category_product_manage/list_category_product.php" class="nav-link text-white"> Danh mục sản phẩm</a>
          <a href="/ShopAnVat/admin/pages/product_manage/listproduct.php" class="nav-link text-white"> Quản lý sản phẩm</a>
        </div>
      </li>

      <!-- Đơn hàng -->
      <li class="nav-item">
        <a href="/ShopAnVat/admin/pages/order_manage/listorder.php" class="nav-link text-white">📑 Đơn hàng</a>
      </li>

      <!-- Tin tức -->
      <li class="nav-item">
        <a class="nav-link text-white" data-bs-toggle="collapse" href="#newsMenu" role="button">
          📰 Tin tức
        </a>
        <div class="collapse ps-3" id="newsMenu">
          <a href="/ShopAnVat/admin/pages/categorynews_manage/list_category_news.php" class="nav-link text-white"> Danh mục tin tức</a>
          <a href="/ShopAnVat/admin/pages/news_manage/listnews.php" class="nav-link text-white"> Quản lý tin tức</a>
        </div>
      </li>

      <!-- Voucher -->
      <li class="nav-item">
        <a href="/ShopAnVat/admin/pages/voucher_manage/listvoucher.php" class="nav-link text-white">🎁 Voucher</a>
      </li>

      <li class="nav-item">
        <a href="/ShopAnVat/admin/pages/knowledge_base/list_knowledge.php" class="nav-link text-white">🤖Chatbot</a>
      </li>

      <!-- Người dùng -->
      <li class="nav-item">
        <a class="nav-link text-white" data-bs-toggle="collapse" href="#userMenu" role="button">
          👤 Người dùng
        </a>
        <div class="collapse ps-3" id="userMenu">
          <a href="/ShopAnVat/admin/pages/account_manage/customer_account/listcustomer.php" class="nav-link text-white"> Khách hàng</a>
          <a href="/ShopAnVat/admin/pages/account_manage/staff_account/liststaff.php" class="nav-link text-white"> Nhân viên</a>
        </div>
      </li>

    </ul>
  </div>

  <!-- Nội dung chính -->
  <div class="flex-grow-1 p-4">
    <!-- Nội dung sẽ được hiển thị ở đây -->
