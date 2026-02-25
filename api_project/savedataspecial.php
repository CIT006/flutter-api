<?php
// ==========================================
// 1. ปิด Error Warning (สำคัญมาก! เพื่อแก้ปัญหา SyntaxError: Unexpected token <)
// ==========================================
error_reporting(0); 

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// เช็ค Preflight Request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// ==========================================
// 2. เชื่อมต่อฐานข้อมูล
// ==========================================
// ลองหาไฟล์เชื่อมต่อ (รองรับทั้ง connn.php และ conn.php)
if (file_exists("connn.php")) {
    include("connn.php");
} elseif (file_exists("conn.php")) {
    include("conn.php");
} else {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Fatal Error: ไม่พบไฟล์ connn.php"));
    exit();
}

// เช็คตัวแปร connection ให้ชัวร์
if (!isset($conn) && isset($connn)) { $conn = $connn; }

if (!$conn) {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Database Connection Failed"));
    exit();
}

// ==========================================
// 3. ตั้งค่าตัวแปรให้ตรงกับ Database (special_project)
// ==========================================
$table_name = "special_project"; // ✅ แก้ชื่อตารางให้ตรงกับรูปภาพ

// รับค่าจาก Flutter
$xcase         = isset($_REQUEST['xcase']) ? $_REQUEST['xcase'] : '';
$userid     = isset($_REQUEST['userid']) ? mysqli_real_escape_string($conn, $_REQUEST['userid']) : '';
$Special_topic = isset($_REQUEST['Special_topic']) ? mysqli_real_escape_string($conn, $_REQUEST['Special_topic']) : '';
$status        = isset($_REQUEST['status']) ? mysqli_real_escape_string($conn, $_REQUEST['status']) : '';

$sql = "";

// ==========================================
// CASE 1: เพิ่มข้อมูล (Insert)
// ==========================================
if ($xcase == 1) {

    if ($userid == "") {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array(
            "status" => "error",
            "message" => "ไม่พบ userid"
        ));
        exit();
    }

    // ใช้ userid จาก Flutter โดยตรง
    $sql = "INSERT INTO $table_name (userid, Special_topic, status)
            VALUES ('$userid', '$Special_topic', '$status')";


// ==========================================
// CASE 2: แก้ไข (Update)
// ==========================================
} elseif ($xcase == 2) {
    
    if ($userid == "") {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array("status" => "error", "message" => "ไม่พบ userid สำหรับแก้ไข"));
        exit();
    }

    $sql = "UPDATE $table_name SET 
            Special_topic = '$Special_topic',
            status = '$status'
            WHERE userid = '$userid'";

// ==========================================
// CASE 3: ลบ (Delete)
// ==========================================
} elseif ($xcase == 3) {

    if ($userid == "") {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array("status" => "error", "message" => "ไม่พบ userid สำหรับลบ"));
        exit();
    }

    $sql = "DELETE FROM $table_name WHERE userid = '$userid'";

} else {
    // กรณีไม่ส่ง xcase
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array("status" => "error", "message" => "Invalid xcase parameter"));
    exit();
}

// ==========================================
// 4. รันคำสั่ง SQL
// ==========================================
if ($sql != "") {
    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("HTTP/1.1 200 OK");
        echo json_encode(array("status" => "success", "message" => "Action Complete"));
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        // ส่ง Error ของ MySQL กลับไปดูด้วย (จะได้รู้ว่าผิดตรงไหน)
        echo json_encode(array("status" => "error", "message" => "SQL Error: " . mysqli_error($conn)));
    }
} else {
    echo json_encode(array("status" => "error", "message" => "No SQL command generated"));
}

mysqli_close($conn);
?>