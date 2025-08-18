<?php
require_once('../../../helpers/startSession.php');
startRoleSession('admin');
require_once('../../../database/dbhelper.php');


$id = $question = $answer = $type = '';
$account_ID = $_SESSION['account_ID'] ?? 1;
$success_message = ''; // Biến để lưu thông báo thành công

// Nếu có ID thì là sửa
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $existing = executeSingleResult("SELECT * FROM knowledgebase WHERE id = ?", [$id]);
    if ($existing) {
        $question = $existing['question'];
        $answer = $existing['answer'];
        $type = $existing['type'];
    }
}

// Xử lý khi submit
if (!empty($_POST['question']) && !empty($_POST['answer'])) {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $type = $_POST['type'] ?? 'general';

    if (empty($id)) {
        $sql = "INSERT INTO knowledgebase (question, answer, type, created_by) VALUES (?, ?, ?, ?)";
        execute($sql, [$question, $answer, $type, $account_ID]);
        $success_message = 'Thêm tri thức thành công!';
    } else {
        $sql = "UPDATE knowledgebase SET question = ?, answer = ?, type = ?, updated_by = ? WHERE id = ?";
        execute($sql, [$question, $answer, $type, $account_ID, $id]);
        $success_message = 'Cập nhật tri thức thành công!';
    }
    // Không chuyển hướng ngay lập tức ở đây, mà sẽ chuyển hướng sau khi hiển thị SweetAlert2
    // header('Location: list_knowledge.php');
    // exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= empty($id) ? 'Thêm tri thức' : 'Cập nhật tri thức' ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css">
</head>
<body>
<a href="list_knowledge.php" class="btn btn-primary m-2">
    <i class="fas fa-arrow-left mr-1"></i> Quay Lại
</a>

<div class="container mt-3">
    <div class="card">
        <div class="card-header bg-info text-white text-center">
            <h4><?= empty($id) ? 'Thêm mới' : 'Chỉnh sửa' ?> Tri Thức Chatbot</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="form-group">
                    <label for="question">Câu hỏi</label>
                    <input type="text" class="form-control" name="question" id="question" required value="<?= htmlspecialchars($question) ?>">
                </div>

                <div class="form-group">
                    <label for="type">Loại tri thức</label>
                    <select class="form-control" name="type" id="type">
                        <option value="general" <?= $type == 'general' ? 'selected' : '' ?>>Chung</option>
                        <option value="shippingfee" <?= $type == 'shippingfee' ? 'selected' : '' ?>>Phí vận chuyển</option>
                        <option value="paymentmethod" <?= $type == 'paymentmethod' ? 'selected' : '' ?>>Phương thức thanh toán</option>
                    </select>


                </div>

                <div class="form-group">
                    <label for="answer">Câu trả lời</label>
                    <textarea id="answer" name="answer"><?= htmlspecialchars($answer) ?></textarea>
                </div>

                <button type="submit" class="btn <?= empty($id) ? 'btn-success' : 'btn-primary' ?>">
                    <?= empty($id) ? 'Thêm' : 'Lưu' ?>
                </button>
                <a href="list_knowledge.php" class="btn btn-warning">Trở về</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    $('#answer').summernote({
        height: 250,
        placeholder: 'Nhập câu trả lời chi tiết...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });

    // Hiển thị SweetAlert2 nếu có thông báo thành công
    <?php if (!empty($success_message)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '<?= $success_message ?>',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.href = 'list_knowledge.php'; // Chuyển hướng sau khi alert đóng
        });
    <?php endif; ?>
});
</script>
</body>
</html>