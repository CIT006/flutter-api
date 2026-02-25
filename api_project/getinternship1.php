<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'connn.php';

// --- แก้ไข SQL ให้เรียบง่ายและตรงกับชื่อตารางของคุณ ---
// ดึงข้อมูลจากตารางหลักโดยตรง ไม่ Join ตารางอื่นเพื่อป้องกัน Error เรื่องชื่อตารางผิด
$sql = "SELECT * FROM intern_company_recived WHERE void = 0 ORDER BY std_id ASC";

$result = mysqli_query($conn, $sql);

// ตรวจสอบว่า SQL Error หรือไม่
if (!$result) {
    // ถ้า Error ให้แสดงข้อความแจ้งเตือนแทน []
    echo json_encode(array(
        "status" => "error", 
        "message" => "SQL Error: " . mysqli_error($conn)
    ));
    exit();
}

$output = array();
while ($row = mysqli_fetch_assoc($result)) {
    $output[] = $row;
}

echo json_encode($output);
mysqli_close($conn);
?>