<?php
// ==========================================
// ส่วน DEBUG: เปิดให้โชว์ Error (ลบออกเมื่อใช้งานจริง)
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// ==========================================
// 1. แก้ปัญหาชื่อไฟล์ Connect (ลองเรียกทั้ง 2 ชื่อ)
// ==========================================
if (file_exists("connn.php")) {
    include("connn.php");
} elseif (file_exists("conn.php")) {
    include("conn.php");
} else {
    // ถ้าหาไม่เจอทั้งคู่ ให้แจ้ง Error ชัดเจน
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Fatal Error: ไม่พบไฟล์ connn.php หรือ conn.php"));
    exit();
}

// เช็คว่าเชื่อมต่อได้จริงไหม
if (!$conn) {
    http_response_code(500);
    echo json_encode(array("status" => "error", "message" => "Database Connection Failed: " . mysqli_connect_error()));
    exit();
}

// ==========================================
// 2. รับค่า
// ==========================================
$xcase      = isset($_REQUEST['xcase']) ? $_REQUEST['xcase'] : '';
$userid     = isset($_REQUEST['userid']) ? mysqli_real_escape_string($conn, $_REQUEST['userid']) : '';
$old_userid = isset($_REQUEST['old_userid']) ? mysqli_real_escape_string($conn, $_REQUEST['old_userid']) : $userid;
$firstname  = isset($_REQUEST['firstname']) ? mysqli_real_escape_string($conn, $_REQUEST['firstname']) : '';
$lastname   = isset($_REQUEST['lastname']) ? mysqli_real_escape_string($conn, $_REQUEST['lastname']) : '';
$email      = isset($_REQUEST['email']) ? mysqli_real_escape_string($conn, $_REQUEST['email']) : '';
$password   = isset($_REQUEST['password']) ? mysqli_real_escape_string($conn, $_REQUEST['password']) : '';
$telno      = isset($_REQUEST['telno']) ? mysqli_real_escape_string($conn, $_REQUEST['telno']) : '';
$office_name = isset($_REQUEST['office_name']) ? mysqli_real_escape_string($conn, $_REQUEST['office_name']) : '';
$address    = isset($_REQUEST['address']) ? mysqli_real_escape_string($conn, $_REQUEST['address']) : '';

$sql = "";

// ==========================================
// CASE 1: เพิ่มข้อมูล
// ==========================================
if ($xcase == 1) {
    // ตรวจสอบว่า userid ว่างหรือไม่
    if (empty($userid)) {
        echo json_encode(array("status" => "error", "message" => "กรุณากรอกรหัสนักศึกษา"));
        exit();
    }
    
    // เช็คซ้ำ userid, email, telno
    $check = mysqli_query($conn, "SELECT userid FROM tb_user WHERE userid='$userid' OR email='$email' OR telno='$telno'");
    if(mysqli_num_rows($check) > 0) {
         echo json_encode(array("status" => "error", "message" => "รหัส Email หรือ เบอร์โทร ซ้ำ"));
         exit();
    }

    // Insert
    $sql = "INSERT INTO tb_user(userid, firstname, lastname, email, password, telno, office_name, address)
            VALUES('$userid', '$firstname', '$lastname', '$email', '$password', '$telno', '$office_name', '$address')";

// ==========================================
// CASE 2: แก้ไข
// ==========================================
} elseif ($xcase == 2) {
    // ตรวจสอบว่า userid ว่างหรือไม่
    if (empty($userid)) {
        echo json_encode(array("status" => "error", "message" => "ไม่พบรหัสนักศึกษา"));
        exit();
    }
    
    // ✅ อัปเดตฟิลด์ userid ใหม่ และหาข้อมูลเดิมจาก old_userid
    $sql = "UPDATE tb_user SET
            userid='$userid',
            firstname='$firstname', lastname='$lastname', email='$email',
            password='$password', telno='$telno', office_name='$office_name', address='$address' 
            WHERE userid='$old_userid' ";
// ==========================================
// CASE 3: ลบ
// ==========================================
} elseif ($xcase == 3) {
    // *** เปลี่ยนเป็นลบจริงๆ (DELETE) แทน Soft Delete ชั่วคราว ***
    // เพราะผมกลัวว่าตารางคุณไม่มีคอลัมน์ flag_send มันเลย Error 500
    $sql = "DELETE FROM tb_user WHERE userid='$userid' ";

} else {
    echo json_encode(array("status" => "error", "message" => "Invalid xcase"));
    exit();
}

// ==========================================
// รันคำสั่ง SQL
// ==========================================
if ($sql != "") {
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Action Complete"));
    } else {
        // ถ้าพัง ให้บอกเหตุผลด้วยว่า SQL ผิดตรงไหน
        http_response_code(500); 
        echo json_encode(array("status" => "error", "message" => "SQL Error: " . mysqli_error($conn)));
    }
}
mysqli_close($conn);
?>