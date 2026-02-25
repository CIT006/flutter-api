<?php
// เปิดใช้งาน CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// เชื่อมต่อฐานข้อมูล
include("connn.php");

// รับค่าจาก client
$work_date = $_REQUEST['work_date'];
$std_id = $_REQUEST['std_id'];
$term = $_REQUEST['term'];
$type_intern = $_REQUEST['type_intern'];
$work_details = $_REQUEST['work_details'];
$problems = $_REQUEST['problems'];
$troubleshoot = $_REQUEST['troubleshoot'];
$xcase = $_REQUEST['xcase'];

if ($xcase == 1) {
    // *** แก้ไขตรงนี้: เพิ่ม void และค่า '1' เข้าไป ***
    $sql = "INSERT INTO intern_dailywork(work_date, std_id, term, type_intern, work_details, problems, troubleshoot, void)
            VALUES('$work_date', '$std_id', '$term', '$type_intern', '$work_details', '$problems', '$troubleshoot', '0')";

} elseif ($xcase == 2) {
    $sql = "UPDATE intern_dailywork SET
            work_date='$work_date', std_id='$std_id', term='$term', type_intern='$type_intern', work_details='$work_details', problems='$problems', troubleshoot='$troubleshoot' 
            WHERE std_id='$std_id' ";
            
            
} elseif ($xcase == 3) {
    // การลบแบบ Soft Delete (เปลี่ยนสถานะ void เป็น 0)
    $sql = "UPDATE intern_dailywork SET void=1 WHERE std_id='$std_id' ";
}

$result = mysqli_query($conn, $sql);

if ($result) {
    header("HTTP/1.1 200 OK");
    echo json_encode(array("status" => "success", "message" => "Registration successful"));
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array("status" => "error", "message" => "Database error: " . mysqli_error($conn)));
}
?>