<?php
// ปิด Error เพื่อป้องกัน Text ประหลาดแทรกใน JSON
error_reporting(0); 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'connn.php';

// เช็คการเชื่อมต่อ
if (!$conn) {
    echo json_encode(array("status" => "error", "message" => "เชื่อมต่อฐานข้อมูลไม่สำเร็จ"));
    exit();
}

// รับค่าจาก Flutter
$xcase        = isset($_POST['xcase']) ? $_POST['xcase'] : '';
$std_id       = isset($_POST['std_id']) ? $_POST['std_id'] : '';
$term         = isset($_POST['term']) ? $_POST['term'] : '';
$old_std_id   = isset($_POST['old_std_id']) ? $_POST['old_std_id'] : $std_id;
$old_term     = isset($_POST['old_term']) ? $_POST['old_term'] : $term;
$type_intern  = isset($_POST['type_intern']) ? $_POST['type_intern'] : '';
$company_id   = isset($_POST['company_id']) ? $_POST['company_id'] : '';
$contact_name = isset($_POST['contact_name']) ? $_POST['contact_name'] : '';
$contact_telno= isset($_POST['contact_telno']) ? $_POST['contact_telno'] : '';
$start_date   = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date     = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// ตรวจสอบค่าว่าง (เฉพาะกรณีเพิ่มข้อมูล)
if ($xcase == 1 && ($std_id == "" || $term == "")) {
    echo json_encode(array("status" => "error", "message" => "กรุณาระบุรหัสนักศึกษาและเทอม"));
    exit();
}

$sql = "";

// --- CASE 1: เพิ่มข้อมูล ---
if ($xcase == 1) {
    // เช็คข้อมูลซ้ำ
    $check_sql = "SELECT std_id FROM intern_company_recived WHERE std_id = '$std_id' AND term = '$term'";
    $check_query = mysqli_query($conn, $check_sql);

    // ** ดักจับ Error ตรงนี้ (แก้ปัญหา Warning Line 39) **
    if (!$check_query) {
        echo json_encode(array("status" => "error", "message" => "SQL Check Error: " . mysqli_error($conn)));
        exit();
    }

    if (mysqli_num_rows($check_query) > 0) {
        echo json_encode(array("status" => "error", "message" => "มีข้อมูลของรหัส $std_id ในเทอม $term แล้ว"));
        exit();
    }

    $sql = "INSERT INTO intern_company_recived 
            (std_id, term, type_intern, company_id, contact_name, contact_telno, start_date, end_date, void)
            VALUES 
            ('$std_id', '$term', '$type_intern', '$company_id', '$contact_name', '$contact_telno', '$start_date', '$end_date', 0)";

// --- CASE 2: แก้ไขข้อมูล ---
} elseif ($xcase == 2) {
    // ✅ อัปเดตเป็น std_id และ term ใหม่ โดยค้นหาจาก old_std_id และ old_term
    $sql = "UPDATE intern_company_recived SET
            std_id='$std_id',
            term='$term',
            type_intern='$type_intern',
            company_id='$company_id',
            contact_name='$contact_name',
            contact_telno='$contact_telno',
            start_date='$start_date',
            end_date='$end_date'
            WHERE std_id='$old_std_id' AND term='$old_term'";

// --- CASE 3: ยกเลิก/ลบ (Void) ---
} elseif ($xcase == 3) {
    $sql = "UPDATE intern_company_recived SET void = 1 WHERE std_id='$std_id' AND term='$term'";
} else {
    echo json_encode(array("status" => "error", "message" => "ไม่พบเงื่อนไข xcase (ส่งมาเป็น: $xcase)"));
    exit();
}

// --- รันคำสั่ง SQL ---
if (mysqli_query($conn, $sql)) {
    // ใช้ header แทน http_response_code (แก้ปัญหา Fatal error Line 69)
    header("HTTP/1.1 200 OK");
    echo json_encode(array("status" => "success", "message" => "บันทึกข้อมูลเรียบร้อย"));
} else {
    echo json_encode(array("status" => "error", "message" => "SQL Save Error: " . mysqli_error($conn)));
}

mysqli_close($conn);
?>