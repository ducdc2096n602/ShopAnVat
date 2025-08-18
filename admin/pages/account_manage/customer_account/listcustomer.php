<?php
require_once('../../../../database/dbhelper.php');
require_once('../../../../helpers/startSession.php');
startRoleSession('admin');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý khách hàng</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <style>
    .btn-sm { margin: 2px 0; width: 90px; white-space: nowrap; }
    .action-buttons { display: flex; justify-content: center; gap: 0.25rem; }
    .table th, .table td { vertical-align: middle !important; white-space: nowrap; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: #ffffff; }
    .table-striped tbody tr:nth-of-type(even) { background-color: #e0e0e0; }
    .table-hover tbody tr:hover { background-color: #cfe2ff; }
    .inactive-cell { opacity: 0.5; }
    .dropdown-menu { z-index: 1050; }
    .btn-edit { background-color: #ffc107; color: #000; }
    .inactive {
  opacity: 0.5;
}
/* Giới hạn chiều rộng cột trạng thái */
.table th:nth-child(7), 
.table td:nth-child(7) {
  max-width: 130px;
  width: 120px;
}

/* Giới hạn chiều rộng cột thao tác */
.table th:nth-child(8), 
.table td:nth-child(8) {
  max-width: 150px;
  width: 150px;
}

/* Cho phép text và nút co lại */
.table td {
  white-space: normal; /* Không ép một dòng ở 2 cột cuối */
}

.action-buttons {
  flex-wrap: wrap; /* Cho nút xuống hàng nếu chật */
  gap: 4px;
}
.table-bordered th,
.table-bordered td {
  border: 2px solid #000 !important; /* Viền dày và đậm */
}

.table-bordered {
  border: 2px solid #000 !important; /* Viền ngoài bảng */
}
.form-control {
  border: 2px solid #000 !important; /* viền đen đậm */
  box-shadow: none; /* bỏ hiệu ứng xanh khi focus */
}

.form-control:focus {
  border-color: #000 !important; /* giữ viền đen khi focus */
  box-shadow: 0 0 3px rgba(0,0,0,0.5); /* thêm bóng nhẹ nếu muốn */
}

  </style>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="../../index.php" class="btn btn-primary">
      <i class="fas fa-home"></i> Trang chủ
    </a>
    <form class="form-inline" method="GET">
      <input type="text" name="keyword" class="form-control mr-2" placeholder="Tìm theo tên, email, sđt" value="<?= $_GET['keyword'] ?? '' ?>">
      <select name="status" class="form-control mr-2">
        <option value="">-- Trạng thái --</option>
        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
        <option value="2" <?= ($_GET['status'] ?? '') === '2' ? 'selected' : '' ?>>Vô hiệu hóa</option>
      </select>
      <button type="submit" class="btn btn-primary">Tìm kiếm</button>
    </form>
  </div>

  <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success text-center"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-bordered table-hover table-striped text-center">
      <thead>
        <tr style="background-color: #e9ecef;">
          <th colspan="8" class="text-center" style="padding: 20px 0; font-size: 22px;">Quản lý Khách hàng</th>
        </tr>
        <tr class="thead-light">
          <th>STT</th>
          <th>Username</th>
          <th>Họ tên</th>
          <th>SĐT</th>
          <th>Email</th>
          <th>Ngày tạo</th>
          <th>Trạng thái</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $start = ($page - 1) * $limit;

        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? '';

        $conditions = [];
        if (!empty($keyword)) {
          $kw = addslashes($keyword);
          $conditions[] = "(a.fullname LIKE '%$kw%' OR a.email LIKE '%$kw%' OR a.phone_number LIKE '%$kw%')";
        }
        if ($status !== '') {
          $conditions[] = "a.status = " . intval($status);
        }

        $whereSQL = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT c.*, a.username, a.fullname, a.phone_number, a.email, a.status, a.created_at
                FROM Customer c
                JOIN Account a ON c.account_ID = a.account_ID
                $whereSQL
                ORDER BY a.created_at DESC
                LIMIT $start, $limit";
        $customerList = executeResult($sql);
        $index = $start + 1;

        if (empty($customerList)) {
          echo '<tr><td colspan="8" class="text-danger">Không có khách hàng nào.</td></tr>';
        } else {
          foreach ($customerList as $row):
            $isInactive = $row['status'] == 2;
            $badge = $isInactive
              ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Vô hiệu hóa</span>'
              : '<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>';
      ?>
      <tr id="row-<?= $row['account_ID'] ?>">
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= $index++ ?></td>
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= htmlspecialchars($row['username']) ?></td>
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= htmlspecialchars($row['fullname']) ?></td>
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= htmlspecialchars($row['phone_number']) ?></td>
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= htmlspecialchars($row['email']) ?></td>
  <td class="<?= $isInactive ? 'inactive' : '' ?>"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
  <td class="status-cell"><?= $badge ?></td>
  <td class="action-buttons">
    <a href="editcustomer.php?account_ID=<?= $row['account_ID'] ?>" class="btn btn-edit btn-sm">
      <i class="fas fa-edit"></i> Sửa
    </a>
    <div class="dropdown position-static">
      <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
        Tuỳ chọn
      </button>
      <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item toggle-btn" href="#"
           data-id="<?= $row['account_ID'] ?>"
           onclick="toggleStatus(<?= $row['account_ID'] ?>, <?= $isInactive ? 1 : 2 ?>)">
           <?= $isInactive
             ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
             : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa' ?>
        </a>
      </div>
    </div>
  </td>
</tr>

      <?php endforeach; } ?>
      </tbody>
    </table>
  </div>

  <!-- Phân trang -->
  <div class="mt-3">
    <ul class="pagination justify-content-center">
    <?php
      $countQuery = "SELECT COUNT(*) AS total FROM Customer c JOIN Account a ON c.account_ID = a.account_ID $whereSQL";
      $countResult = executeSingleResult($countQuery);
      $total = $countResult['total'] ?? 0;
      $totalPages = ceil($total / $limit);

      $queryString = $_GET;
      unset($queryString['page']);

      for ($i = 1; $i <= $totalPages; $i++):
        $queryString['page'] = $i;
        $link = '?' . http_build_query($queryString);
    ?>
      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a class="page-link" href="<?= $link ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    </ul>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
function toggleStatus(account_ID, newStatus) {
  $.post('ajax.php', {
    account_ID: account_ID,
    action: 'toggle_status',
    status: newStatus
  }, function(res) {
    if (res.status === 'success') {
      const row = $('#row-' + account_ID);
      const cells = row.find('td:not(.status-cell):not(:last-child)');
      const statusCell = row.find('.status-cell');
      const dropdownBtn = row.find('.toggle-btn');

      if (newStatus === 1) {
        cells.removeClass('inactive');
        statusCell.html('<span class="badge badge-success"><i class="fas fa-check"></i> Đang hoạt động</span>');
        dropdownBtn.html('<i class="fas fa-ban text-danger"></i> Vô hiệu hóa');
        dropdownBtn.attr('onclick', `toggleStatus(${account_ID}, 2)`);
      } else {
        cells.addClass('inactive');
        statusCell.html('<span class="badge badge-secondary"><i class="fas fa-ban"></i> Vô hiệu hóa</span>');
        dropdownBtn.html('<i class="fas fa-check-circle text-success"></i> Kích hoạt lại');
        dropdownBtn.attr('onclick', `toggleStatus(${account_ID}, 1)`);
      }
    } else {
      alert('Thao tác thất bại');
    }
  }, 'json');
}

</script>
</body>
</html>

