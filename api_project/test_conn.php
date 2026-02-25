<?php
// บรรทัดนี้สำคัญมาก! ต้องอยู่บนสุด
header('Content-Type: text/html; charset=utf-8'); 

$servername = "localhost";
$username = "root";
$password = "671413006";
$dbname = "register";

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . mysqli_connect_error());
}

// ตั้งค่าภาษาในฐานข้อมูล (อันนี้คุณทำถูกแล้ว)
mysqli_set_charset($conn, "utf8"); 

echo "✅ เชื่อมต่อฐานข้อมูลสำเร็จ!";

mysqli_close($conn); // ควรปิดการเชื่อมต่อเมื่อเสร็จงาน
?>