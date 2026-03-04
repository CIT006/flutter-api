<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณาระบุ ID"]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // ดึงข้อมูล transaction ก่อนลบเพื่ออัปเดต summary
    $stmt = $conn->prepare("SELECT user_id, transaction_date FROM transactions WHERE id = :id");
    $stmt->execute([":id" => $data->id]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = :id");
        $stmt->execute([":id" => $data->id]);

        // อัปเดต monthly_summaries
        $year = date('Y', strtotime($transaction['transaction_date']));
        $month = date('n', strtotime($transaction['transaction_date']));
        updateMonthlySummary($conn, $transaction['user_id'], $year, $month);

        echo json_encode([
            "success" => true,
            "message" => "ลบข้อมูลสำเร็จ"
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "ไม่พบข้อมูล"]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}

function updateMonthlySummary($conn, $userId, $year, $month) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'income' 
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalIncome = $stmt->fetch()['total'];

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'expense' 
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalExpense = $stmt->fetch()['total'];

    $balance = $totalIncome - $totalExpense;

    $stmt = $conn->prepare("INSERT INTO monthly_summaries 
        (user_id, year, month, total_income, total_expense, balance) 
        VALUES (:user_id, :year, :month, :income, :expense, :balance)
        ON DUPLICATE KEY UPDATE 
        total_income = :income, 
        total_expense = :expense, 
        balance = :balance");
    
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