<?php
// เปิดใช้งาน CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
// เชื่อมต่อฐานข้อมูล
include("connn.php");
// รับค่าจาก client
$userid = $_REQUEST['userid'];
$firstname = $_REQUEST['firstname'];
$lastname = $_REQUEST['lastname'];
$password = $_REQUEST['password'];
$email = $_REQUEST['email'];
$telno = $_REQUEST['telno'];
$office_name = $_REQUEST['office_name'];
$xcase = $_REQUEST['xcase'];
$address = $_REQUEST['address'];
// กรณีเป็น 1 คือการเพิ่มใหม่
if ($xcase == 1) {
    // เช็คว่าเคยลงทะเบียนหรือยัง โดยเช็คจาก email หรือ telno
    $sql = "SELECT * FROM tb_user WHERE email='$email' OR telno='$telno' ";
    $objQuery = mysqli_query($conn, $sql) or die(mysqli_error($conn));
    $count = mysqli_num_rows($objQuery);
    if ($count > 0) {
        while ($row0 = mysqli_fetch_array($objQuery)) {
            if ($row0["email"] == $email) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(array("status" => "Email นี้ได้ลงทะเบียนเแล้วไม่สามารถลงทะเบียนซ้้าได้", "message" => "Database error: " . mysqli_error($conn)));
                return;
            }
            if ($row0["telno"] == $telno) {
                header("HTTP/1.1 401 Unauthorized");
                echo json_encode(array("status" => "เบอร์โทรศัพท์นี้ได้ลงทะเบียนเแล้วไม่สามารถลงทะเบียนซ้้าได้", "message" => "Database error: " . mysqli_error($conn)));
                return;
            }
        }
    }
    $no = 1;
    // ดึงค่า MAX(userid) แล้ว +1
    $sql = "SELECT MAX(userid) AS maxid FROM tb_user";
    $objQuery = mysqli_query($conn, $sql) or die(mysqli_error($conn));
    while ($row1 = mysqli_fetch_array($objQuery)) {
        if ($row1["maxid"] != "") {
            $no = substr($row1["maxid"], -5) + 1;
        }
    }
    $newuserid = "00000" . (string) $no;
    $newuserid = substr($newuserid, -5);
    // เข้ารหัสรหัสผ่าน
    $hashedPassword = md5($password);
    $sql = "insert into tb_user(userid,firstname,lastname,email,telno,password,office_name,address)
values('$newuserid','$firstname','$lastname','$email','$telno','$hashedPassword','$office_name','$address')";
} else {
    $sql = "UPDATE tb_user SET
firstname='$firstname',lastname='$lastname',telno='$telno',office_name='$office_name',address='$address'
WHERE userid='$userid' ";
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