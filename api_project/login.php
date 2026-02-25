<?php
error_reporting(0); 

// เปิดใช้งาน CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// เชื่อมต่อฐานข้อมูล
include("connn.php");

// 1. รับค่า
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// 2. ตรวจสอบค่าว่าง
if (empty($username) || empty($password)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array("status" => "error", "message" => "กรุณากรอกข้อมูลให้ครบ"));
    exit();
}

// 3. สร้างรหัสผ่าน md5
$hashedPassword = md5($password);

// 4. เตรียมข้อมูลเพื่อป้องกัน SQL Injection (วิธีนี้ใช้ได้ทุกเวอร์ชัน)
$clean_username = mysqli_real_escape_string($conn, $username);
$clean_password = mysqli_real_escape_string($conn, $hashedPassword);

// 5. เขียน SQL แบบธรรมดา (รองรับ AppServ เก่า)
$sql = "SELECT * FROM tb_user 
        WHERE (email = '$clean_username' OR telno = '$clean_username') 
        AND password = '$clean_password'";

// 6. สั่งทำงาน
$result = mysqli_query($conn, $sql);

// ตรวจสอบว่า Query ผ่านไหม
if (!$result) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(array("status" => "error", "message" => "SQL Error: " . mysqli_error($conn)));
    exit();
}

// 7. ตรวจสอบจำนวนแถว
if (mysqli_num_rows($result) == 1) {
    // เจอผู้ใช้
    $user = mysqli_fetch_assoc($result);
    
    header("HTTP/1.1 200 OK");
    unset($user['password']); // ลบรหัสผ่านออก
    
    echo json_encode(array(
        "status" => "success",
        "message" => "Login successful",
        "user" => $user
    ));
} else {
    // ไม่เจอ
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(array("status" => "error", "message" => "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง"));
}

mysqli_close($conn);
?>