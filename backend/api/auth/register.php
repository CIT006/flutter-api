<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->username) || empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // ตรวจสอบ username ซ้ำ
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(":username", $data->username);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "Username หรือ Email นี้มีผู้ใช้งานแล้ว"]);
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $stmt->bindParam(":username", $data->username);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $hashedPassword);

    if ($stmt->execute()) {
        $userId = $conn->lastInsertId();
        echo json_encode([
            "success" => true,
            "message" => "สมัครสมาชิกสำเร็จ",
            "data" => [
                "user_id" => $userId,
                "username" => $data->username
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "ไม่สามารถสมัครสมาชิกได้"]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>