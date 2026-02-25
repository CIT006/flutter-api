<?php
// 1. Headers แก้ปัญหา CORS และ Connection
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

include("connn.php");

// ฟังก์ชันรับค่า String
function getInput($conn, $key) {
    return isset($_REQUEST[$key]) ? mysqli_real_escape_string($conn, trim($_REQUEST[$key])) : '';
}

// รับค่าจาก Flutter
$term = getInput($conn, 'term');           // PK: ภาคการศึกษา
$start_date = getInput($conn, 'start_date'); // วันที่เริ่ม
$end_date = getInput($conn, 'end_date');     // วันที่สิ้นสุด
$xcase = isset($_REQUEST['xcase']) ? (int) $_REQUEST['xcase'] : 0;

$sql = "";

// --- CASE 1: เพิ่มข้อมูล (INSERT) ---
if ($xcase == 1) {
    // ตรวจสอบว่ามี Term นี้อยู่แล้วหรือไม่ (เพราะเป็น PK)
    $checkSql = "SELECT term FROM intern_term WHERE term = '$term'";
    $checkQuery = mysqli_query($conn, $checkSql);
    
    if (mysqli_num_rows($checkQuery) > 0) {
        // ถ้าซ้ำ ให้ส่ง error กลับไป
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array("status" => "error", "message" => "Duplicate Term ID"));
        exit();
    }

    $sql = "INSERT INTO intern_term(term, start_date, end_date) 
            VALUES('$term', '$start_date', '$end_date')";
}

// --- CASE 2: แก้ไขข้อมูล (UPDATE) ---
if ($xcase == 2) {
    // แก้ไขวันที่ โดยอ้างอิงจาก term เดิม
    $sql = "UPDATE intern_term SET 
            start_date='$start_date', 
            end_date='$end_date' 
            WHERE term='$term'";
}

// --- CASE 3: ลบข้อมูล (DELETE) ---
if ($xcase == 3) {
    $sql = "DELETE FROM intern_term WHERE term='$term'";
}

// ประมวลผล SQL
if (!empty($sql)) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Operation successful"));
    } else {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array("status" => "error", "message" => "Database Error: " . mysqli_error($conn)));
    }
} else {
    // กรณีไม่มีการเข้าเงื่อนไข xcase หรือ SQL ว่าง
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array("status" => "error", "message" => "Invalid Action (xcase)"));
}
?>