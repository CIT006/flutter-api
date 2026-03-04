<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอก username และ password"]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username = :username OR email = :username");
    $stmt->bindParam(":username", $data->username);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "ไม่พบผู้ใช้งานนี้"]);
        exit();
    }

    $user = $stmt->fetch();

    if (password_verify($data->password, $user['password'])) {
        $token = bin2hex(random_bytes(32));
        
        // เก็บ token ใน session หรือ database (สำหรับ production ควรใช้ JWT)
        echo json_encode([
            "success" => true,
            "message" => "เข้าสู่ระบบสำเร็จ",
            "data" => [
                "user_id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "token" => $token
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "รหัสผ่านไม่ถูกต้อง"]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>