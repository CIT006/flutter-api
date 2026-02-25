<?php
// 1. Header สำหรับอนุญาตให้เข้าถึงและบอกว่าเป็น JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'connn.php';

// 2. ประกาศตัวแปร Array ไว้ก่อนเสมอ
$output = array();

 $sql = "SELECT * FROM intern_tumbol where amphur_code='$amphur_code' ORDER BY tumbol_name";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $output[] = $row;
    }
}

// 3. ใช้คำสั่ง header แบบเก่า แทน http_response_code
// (จริงๆ ถ้าทำงานสำเร็จ PHP จะส่ง 200 ให้อัตโนมัติอยู่แล้ว บรรทัดนี้ใส่เพื่อความชัวร์ หรือจะลบออกก็ได้ครับ)
header("HTTP/1.1 200 OK");

// 4. ส่ง JSON ออกไป
echo json_encode($output);

mysqli_close($conn);
?>