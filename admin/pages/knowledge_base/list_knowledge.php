<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');

$typeFilter = isset($_GET['type']) && $_GET['type'] !== '' ? $_GET['type'] : '';
$where = '';
if ($typeFilter) {
    $typeFilter = escapeString($typeFilter);
    $where = "WHERE k.type = '$typeFilter'";
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Tri Thức Chatbot</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }
        .card-header {
            background-color: #00a0b0;
            color: white;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 20px;
        }
        .btn i {
            margin-right: 5px;
        }
        .btn-edit {
            background-color: #ffc107;
            color: #000;
        }
        .btn-edit.btn-sm,
        .btn-secondary.btn-sm.dropdown-toggle {
            padding: 6px 12px;
            font-size: 14px;
            height: 36px;
            min-width: 90px;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .table td {
            vertical-align: middle !important;
        }
        .table th {
            background-color: #e9ecef;
        }
        .table-striped tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .table-striped tbody tr:nth-child(even) {
            background-color: #e0e0e0;
        }
        .inactive-cell {
            opacity: 0.5;
        }
        .gap-1 > * {
            margin-right: 0.25rem;
        }
        .gap-1 > *:last-child {
            margin-right: 0;
        }
        .dropdown-menu {
            z-index: 1050;
        }
        .d-flex.justify-content-center.gap-1 {
            gap: 4px;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <a href="add.php" class="btn btn-success ml-2">
                <i class="fas fa-plus"></i> Thêm tri thức
            </a>
        </div>
    </div>
<form method="GET" class="form-inline">
    <label for="type" class="mr-2 font-weight-bold">Loại câu hỏi:</label>
    <select name="type" id="type" class="form-control mr-2" onchange="this.form.submit()">
        <option value="">Tất cả</option>
        <option value="shippingfee" <?= (isset($_GET['type']) && $_GET['type'] == 'shippingfee') ? 'selected' : '' ?>>Phí vận chuyển</option>
        <option value="paymentmethod" <?= (isset($_GET['type']) && $_GET['type'] == 'paymentmethod') ? 'selected' : '' ?>>Phương thức thanh toán</option>
        <option value="general" <?= (isset($_GET['type']) && $_GET['type'] == 'general') ? 'selected' : '' ?>>Chung</option>

    </select>
    <div class="card shadow-sm">
        <div class="card-header">
            Danh sách tri thức Chatbot
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover table-striped m-0 text-center">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th>Câu hỏi</th>
                        <th>Câu trả lời</th>
                        <th>Người tạo</th>
                        <th>Trạng thái</th>
                        <th style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $page = isset($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
                    $limit = 10;
                    $start = ($page - 1) * $limit;

                    $sql = "SELECT k.*, a.fullname
        FROM knowledgebase k
        LEFT JOIN account a ON k.created_by = a.account_ID
        $where
        ORDER BY k.updated_by DESC
        LIMIT $start, $limit";
$data = executeResult($sql);

                    $index = $start + 1;

                    if (empty($data)) {
                        echo '<tr><td colspan="6" class="text-danger">Chưa có tri thức nào.</td></tr>';
                    } else {
                        foreach ($data as $item) {
                            $isDeleted = $item['is_deleted'] == 1;
                            $badge = $isDeleted
                                ? '<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã ẩn</span>'
                                : '<span class="badge badge-success"><i class="fas fa-check"></i> Hiển thị</span>';

                            echo '<tr>
                                    <td>' . $index++ . '</td>
                                    <td class="text-left pl-3 ' . ($isDeleted ? 'inactive-cell' : '') . '">' . htmlspecialchars($item['question']) . '</td>
                                    <td class="text-left pl-3 ' . ($isDeleted ? 'inactive-cell' : '') . '">' . htmlspecialchars(mb_strimwidth(strip_tags($item['answer']), 0, 100, "...")) . '</td>
                                    <td class="' . ($isDeleted ? 'inactive-cell' : '') . '">' . htmlspecialchars($item['fullname'] ?? 'Không rõ') . '</td>
                                    <td class="' . ($isDeleted ? 'inactive-cell' : '') . '">' . $badge . '</td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="add.php?id=' . $item['id'] . '" class="btn btn-edit btn-sm">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <div class="dropdown position-static">
                                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                    Tuỳ chọn
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item toggle-btn" href="#"
                                                        data-id="' . $item['id'] . '"
                                                        onclick="toggleKnowledgeStatus(' . $item['id'] . ', ' . ($isDeleted ? 0 : 1) . ')">
                                                        ' . ($isDeleted
                                                            ? '<i class="fas fa-check-circle text-success"></i> Kích hoạt lại'
                                                            : '<i class="fas fa-ban text-danger"></i> Vô hiệu hóa') . '
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>';
                        }
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <ul class="pagination justify-content-center">
            <?php
                $countSql = "SELECT COUNT(*) AS total FROM knowledgebase k $where";
                $result = executeSingleResult($countSql);
                $total = $result['total'] ?? 0;

                $total_pages = ceil($total / $limit);

                $query = $_GET;
                unset($query['page']);

                for ($i = 1; $i <= $total_pages; $i++) {
                    $query['page'] = $i;
                    $link = '?' . http_build_query($query);
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                            <a class="page-link" href="' . $link . '">' . $i . '</a>
                        </li>';
                }
            ?>
        </ul>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
function toggleKnowledgeStatus(id, status) {
    $.post("ajax.php", {
        action: "toggle",
        id: id,
        status: status
    }, function(response) {
        try {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.status === 'success') {
                const row = $('.toggle-btn[data-id="' + id + '"]').closest('tr');
                const questionCell = row.find('td:nth-child(2)');
                const answerCell = row.find('td:nth-child(3)');
                const creatorCell = row.find('td:nth-child(4)');
                const statusCell = row.find('td:nth-child(5)');
                const dropdown = row.find('.toggle-btn');

                if (status == 1) {
                    statusCell.html('<span class="badge badge-secondary"><i class="fas fa-ban"></i> Đã ẩn</span>');
                    questionCell.addClass('inactive-cell');
                    answerCell.addClass('inactive-cell');
                    creatorCell.addClass('inactive-cell');
                    dropdown.html('<i class="fas fa-check-circle text-success"></i> Kích hoạt lại');
                    dropdown.attr('onclick', 'toggleKnowledgeStatus(' + id + ', 0)');
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã vô hiệu hóa tri thức.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    statusCell.html('<span class="badge badge-success"><i class="fas fa-check"></i> Hiển thị</span>');
                    questionCell.removeClass('inactive-cell');
                    answerCell.removeClass('inactive-cell');
                    creatorCell.removeClass('inactive-cell');
                    dropdown.html('<i class="fas fa-ban text-danger"></i> Vô hiệu hóa');
                    dropdown.attr('onclick', 'toggleKnowledgeStatus(' + id + ', 1)');
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: 'Đã kích hoạt lại tri thức.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message || 'Cập nhật thất bại.',
                    confirmButtonText: 'Đóng'
                });
                console.log(data);
            }
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Lỗi phản hồi từ server.',
                confirmButtonText: 'Đóng'
            });
            console.error(e, response);
        }
    });
}
</script>
</body>
</html>