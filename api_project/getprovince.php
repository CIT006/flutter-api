<?php
// ปิดไม่ให้ PHP แสดง Error เป็น HTML ออกมาปนกับ JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// *** ตรวจสอบชื่อไฟล์ให้ถูกต้อง ***
include 'connn.php'; 

// ตรวจสอบว่าเชื่อมต่อได้จริงไหม
if (!$conn) {
    http_response_code(500);
    echo json_encode(array("error" => "Database connection failed"));
    exit();
}

$output = array();

$sql = "SELECT province_code, name_th FROM intern_provinces ORDER BY name_th";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)){
        $output[] = $row;
    }
    http_response_code(200);
    echo json_encode($output);
} else {
    // ถ้า SQL ผิด ให้ส่ง JSON แจ้ง
    http_response_code(500);
    echo json_encode(array("error" => "SQL Error: " . mysqli_error($conn)));
}

mysqli_close($conn);
?>