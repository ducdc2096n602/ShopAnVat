<?php
require_once('config.php');

function getConnection() {
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);
    if (!$conn) {
        // Ghi log lỗi kết nối CSDL
        error_log("Kết nối CSDL thất bại: " . mysqli_connect_error());
        die("Kết nối CSDL thất bại: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4'); 

    return $conn;
}

// Dùng cho INSERT, UPDATE, DELETE (có hoặc không có tham số)
function execute($sql, $params = []) {
    $conn = getConnection();
    $success = false; // Mặc định là thất bại

    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            error_log("SQL Error (no params): " . mysqli_error($conn) . " | SQL: " . $sql);
        } else {
            $success = true;
        }
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            error_log("Lỗi prepare: " . mysqli_error($conn) . " | SQL: " . $sql);
            mysqli_close($conn); // Đóng kết nối nếu prepare lỗi
            return false; // Trả về false ngay lập tức
        }

        $types = str_repeat('s', count($params)); // mặc định là string
        // Kiểm tra xem bind_param có lỗi không
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
             error_log("Lỗi bind_param: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
             mysqli_stmt_close($stmt);
             mysqli_close($conn);
             return false;
        }

        // Kiểm tra xem execute có lỗi không
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            error_log("Lỗi execute statement: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
    return $success; // Trả về true nếu thành công, false nếu thất bại
}


// Dùng khi trả về nhiều dòng (SELECT * FROM ...)
function executeResult($sql, $params = []) {
    $conn = getConnection();
    $data = [];

    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            while ($row = mysqli_fetch_array($result, 1)) {
                $data[] = $row;
            }
        } else {
            error_log("SQL Error (executeResult no params): " . mysqli_error($conn) . " | SQL: " . $sql);
        }
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            error_log("Lỗi prepare (executeResult): " . mysqli_error($conn) . " | SQL: " . $sql);
            mysqli_close($conn);
            return [];
        }

        $types = str_repeat('s', count($params));
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            error_log("Lỗi bind_param (executeResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return [];
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_array($result, 1)) {
                    $data[] = $row;
                }
            } else {
                error_log("Lỗi get_result (executeResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
            }
        } else {
            error_log("Lỗi execute statement (executeResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
    return $data;
}


// Dùng khi chỉ cần 1 dòng
function executeSingleResult($sql, $params = []) {
    $conn = getConnection();
    $row = null;

    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_array($result, 1);
        } else {
            error_log("SQL Error (executeSingleResult no params): " . mysqli_error($conn) . " | SQL: " . $sql);
        }
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            error_log("Lỗi prepare (executeSingleResult): " . mysqli_error($conn) . " | SQL: " . $sql);
            mysqli_close($conn);
            return null;
        }

        $types = str_repeat('s', count($params));
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            error_log("Lỗi bind_param (executeSingleResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            return null;
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $row = mysqli_fetch_array($result, 1);
            } else {
                error_log("Lỗi get_result (executeSingleResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
            }
        } else {
            error_log("Lỗi execute statement (executeSingleResult): " . mysqli_stmt_error($stmt) . " | SQL: " . $sql . " | Params: " . implode(', ', $params));
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
    return $row;

  


}

function escapeString($str) {
    $conn = getConnection();
    $safe = mysqli_real_escape_string($conn, $str);
    mysqli_close($conn);
    return $safe;
}
?>