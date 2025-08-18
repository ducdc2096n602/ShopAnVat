<?php
require_once('../../helpers/startSession.php');
startRoleSession('staff');

?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<?php include '../includes/navbar.php'; ?>
<?php require_once('../../database/dbhelper.php'); ?>

<?php
if (!isset($_SESSION['account_ID']) || $_SESSION['role_ID'] != 2) {
    header('Location: ../../login/login.php');
    exit();
}

$account_ID = $_SESSION['account_ID'];

$staff = executeSingleResult("SELECT staff_ID FROM Staff WHERE account_ID = $account_ID");
if (!$staff) {
    echo '<div class="alert alert-danger">Không tìm thấy thông tin nhân viên.</div>';
    exit();
}
$staff_ID = $staff['staff_ID'];

$pendingOrders     = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Chờ xác nhận'");
$confirmedOrders   = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Đã xác nhận' AND staff_ID = $staff_ID");
$preparingOrders   = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Đang chuẩn bị hàng' AND staff_ID = $staff_ID");
$shippingOrders    = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Đang giao hàng' AND staff_ID = $staff_ID");
$completedOrders   = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Hoàn tất' AND staff_ID = $staff_ID");
$cancelledOrders   = executeSingleResult("SELECT COUNT(*) as total FROM Orders WHERE status = 'Đã hủy' AND staff_ID = $staff_ID");

$recentActivities = executeResult("
    SELECT osh.order_ID, osh.status, osh.changed_at, acc.fullname AS staff_name
    FROM OrderStatusHistory osh
    JOIN Staff s ON osh.staff_ID = s.staff_ID
    JOIN Account acc ON s.account_ID = acc.account_ID
    WHERE osh.staff_ID = $staff_ID
    ORDER BY osh.changed_at DESC
    LIMIT 5
");
?>
  <div class="row">
    <!-- Thống kê đơn -->
    <div class="col-md-8">
      <div class="row mb-3">
        <div class="col-md-6">
          <div class="card p-3 text-white bg-warning h-100">
            <h5>Đơn chờ xác nhận</h5>
            <p class="mb-2"><?= $pendingOrders['total'] ?> đơn</p>
            <a href="list_pending_orders.php" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3 text-white bg-success h-100">
            <h5>Đã xác nhận</h5>
            <p class="mb-2"><?= $confirmedOrders['total'] ?> đơn</p>
            <a href="listorder.php?status=Đã xác nhận" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <div class="card p-3 text-white bg-primary h-100">
            <h5>Đang chuẩn bị hàng</h5>
            <p class="mb-2"><?= $preparingOrders['total'] ?> đơn</p>
            <a href="listorder.php?status=Đang chuẩn bị hàng" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3 text-white bg-info h-100">
            <h5>Đang giao hàng</h5>
            <p class="mb-2"><?= $shippingOrders['total'] ?> đơn</p>
            <a href="listorder.php?status=Đang giao hàng" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="card p-3 text-white bg-success h-100">
            <h5>Hoàn tất</h5>
            <p class="mb-2"><?= $completedOrders['total'] ?> đơn</p>
            <a href="listorder.php?status=Hoàn tất" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3 text-white bg-danger h-100">
            <h5>Đã hủy</h5>
            <p class="mb-2"><?= $cancelledOrders['total'] ?> đơn</p>
            <a href="listorder.php?status=Đã hủy" class="btn btn-light btn-sm">Xem chi tiết</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Lịch sử xử lý gần đây -->
    <div class="col-md-4">
      <div class="card p-3 bg-light h-100">
        <h5 class="text-dark mb-3">🕘 Lịch sử xử lý gần đây</h5>
        <ul class="list-unstyled small mb-0">
          <?php if (empty($recentActivities)): ?>
            <li class="text-muted">Chưa có hoạt động nào.</li>
          <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
              <li class="mb-2">
                📝 Đơn <strong>#<?= $activity['order_ID'] ?></strong> →
                <span class="text-primary"><?= htmlspecialchars($activity['status']) ?></span><br>
                <small class="text-muted"><?= date('H:i d/m/Y', strtotime($activity['changed_at'])) ?></small>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
        <a href="order_history.php" class="btn btn-outline-primary btn-sm mt-3 w-100">
          <i class="fas fa-clock"></i> Xem tất cả lịch sử xử lý
        </a>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
