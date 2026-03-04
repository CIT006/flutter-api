<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->user_id) || empty($data->type) || empty($data->category_id) || 
    empty($data->amount) || empty($data->transaction_date)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // ✅ ตรวจสอบว่ามี user_id นี้หรือไม่ ถ้าไม่มีให้สร้าง
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = :user_id");
    $stmt->execute([":user_id" => $data->user_id]);
    
    if ($stmt->rowCount() === 0) {
        // สร้าง user ใหม่
        $stmt = $conn->prepare("INSERT INTO users (id, username, email, password) 
            VALUES (:user_id, :username, :email, :password)");
        $stmt->execute([
            ":user_id" => $data->user_id,
            ":username" => "user_" . $data->user_id,
            ":email" => "user" . $data->user_id . "@example.com",
            ":password" => password_hash("password", PASSWORD_BCRYPT)
        ]);
    }

    // ✅ ตรวจสอบว่ามี category_id นี้หรือไม่
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = :category_id");
    $stmt->execute([":category_id" => $data->category_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ไม่พบหมวดหมู่นี้"]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO transactions 
        (user_id, type, category_id, amount, description, transaction_date) 
        VALUES (:user_id, :type, :category_id, :amount, :description, :transaction_date)");

    $stmt->bindParam(":user_id", $data->user_id);
    $stmt->bindParam(":type", $data->type);
    $stmt->bindParam(":category_id", $data->category_id);
    $stmt->bindParam(":amount", $data->amount);
    $stmt->bindParam(":description", $data->description);
    $stmt->bindParam(":transaction_date", $data->transaction_date);

    if ($stmt->execute()) {
        $transactionId = $conn->lastInsertId();
        
        // อัปเดต monthly_summaries
        $year = date('Y', strtotime($data->transaction_date));
        $month = date('n', strtotime($data->transaction_date));
        
        updateMonthlySummary($conn, $data->user_id, $year, $month);

        echo json_encode([
            "success" => true,
            "message" => "บันทึกข้อมูลสำเร็จ",
            "data" => [
                "id" => $transactionId
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "ไม่สามารถบันทึกข้อมูลได้"]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Server Error: " . $e->getMessage(),
        "error_code" => $e->getCode()
    ]);
}

function updateMonthlySummary($conn, $userId, $year, $month) {
    // คำนวณรายได้
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'income' 
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalIncome = $stmt->fetch()['total'];

    // คำนวณรายจ่าย
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'expense' 
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalExpense = $stmt->fetch()['total'];

    $balance = $totalIncome - $totalExpense;

    // ✅ แก้ไข: ใช้ VALUES() สำหรับ ON DUPLICATE KEY UPDATE
    $stmt = $conn->prepare("INSERT INTO monthly_summaries 
        (user_id, year, month, total_income, total_expense, balance) 
        VALUES (:user_id, :year, :month, :income, :expense, :balance)
        ON DUPLICATE KEY UPDATE 
        total_income = VALUES(total_income), 
        total_expense = VALUES(total_expense), 
        balance = VALUES(balance)");
    
    $stmt->execute([
        ":user_id" => $userId,
        ":year" => $year,
        ":month" => $month,
        ":income" => $totalIncome,
        ":expense" => $totalExpense,
        ":balance" => $balance
    ]);
}
?>