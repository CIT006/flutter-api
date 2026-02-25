<?php
// 1. Headers แก้ปัญหา CORS และ Connection
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

include("connn.php");

// ฟังก์ชันรับค่า String
function getInput($conn, $key)
{
    return isset($_REQUEST[$key]) ? mysqli_real_escape_string($conn, trim($_REQUEST[$key])) : '';
}

// รับค่าจาก Flutter
$company_id = getInput($conn, 'company_id');
$old_company_id = getInput($conn, 'old_company_id');
$company_name = getInput($conn, 'company_name');
$address = getInput($conn, 'address');
$tumbol_code = getInput($conn, 'tumbol_code');
$amphur_code = getInput($conn, 'amphur_code');
$contact_name = getInput($conn, 'contact_name');
$telno = getInput($conn, 'telno');
$xcase = isset($_REQUEST['xcase']) ? (int) $_REQUEST['xcase'] : 0;

// *** เพิ่ม: รับค่าพิกัด Latitude / Longitude ***
$latitude = getInput($conn, 'latitude');
$longitude = getInput($conn, 'longitude');

// รับค่าตัวเลข (Province / Postcode)
$province_code = isset($_REQUEST['province_code']) && $_REQUEST['province_code'] !== ''
    ? "'" . mysqli_real_escape_string($conn, $_REQUEST['province_code']) . "'"
    : "'0'";

$postcode = isset($_REQUEST['postcode']) && $_REQUEST['postcode'] !== ''
    ? "'" . mysqli_real_escape_string($conn, $_REQUEST['postcode']) . "'"
    : "'0'";

$sql = "";

// --- CASE 1: เพิ่มข้อมูล (INSERT) ---
if ($xcase == 1) {
    // สร้าง ID ใหม่
    $no = 1;
    $maxSql = "SELECT MAX(company_id) AS maxid FROM intern_company";
    $objQuery = mysqli_query($conn, $maxSql);
    if ($objQuery) {
        $row1 = mysqli_fetch_array($objQuery);
        if ($row1["maxid"] != "") {
            $no = intval($row1["maxid"]) + 1;
        }
    }
    $company_id = str_pad($no, 4, '0', STR_PAD_LEFT);

    // *** เพิ่ม latitude, longitude ในคำสั่ง INSERT ***
    $sql = "INSERT INTO intern_company(company_id, company_name, tumbol_code, province_code, telno, amphur_code, postcode, address, contact_name, latitude, longitude)
            VALUES('$company_id', '$company_name', '$tumbol_code', $province_code, '$telno', '$amphur_code', $postcode, '$address', '$contact_name', '$latitude', '$longitude')";
}

// --- CASE 2: แก้ไขข้อมูล (UPDATE) ---
if ($xcase == 2) {
    // ถ้า Flutter ไม่ได้ส่ง old_company_id มา ให้ใช้ค่า company_id เดิมไปก่อนกันเหนียว
    if (empty($old_company_id)) {
        $old_company_id = $company_id;
    }

    // *** เพิ่ม company_id='$company_id' ในคำสั่ง UPDATE ***
    $sql = "UPDATE intern_company SET 
            company_id='$company_id',   
            company_name='$company_name',
            address='$address',
            tumbol_code='$tumbol_code',
            amphur_code='$amphur_code',  
            province_code=$province_code,
            postcode=$postcode,
            telno='$telno',
            contact_name='$contact_name',
            latitude='$latitude',
            longitude='$longitude'
            WHERE company_id='$old_company_id'";
}
// --- CASE 3: ลบข้อมูล (DELETE) ---
if ($xcase == 3) {
    $sql = "DELETE FROM intern_company WHERE company_id='$company_id'";
}

// ประมวลผล
if (!empty($sql)) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        echo json_encode(array("status" => "success", "message" => "Operation successful"));
    } else {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(array("status" => "error", "message" => "Database Error: " . mysqli_error($conn) . " SQL: " . $sql));
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(array("status" => "error", "message" => "Invalid Action (xcase)"));
}
?>