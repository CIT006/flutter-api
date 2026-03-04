<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

try {
    $database = new Database();
    $conn = $database->getConnection();

    // รายได้ทั้งหมด
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'income'
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalIncome = floatval($stmt->fetch()['total']);

    // รายจ่ายทั้งหมด
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE user_id = :user_id AND type = 'expense'
        AND YEAR(transaction_date) = :year AND MONTH(transaction_date) = :month");
    $stmt->execute([":user_id" => $userId, ":year" => $year, ":month" => $month]);
    $totalExpense = floatval($stmt->fetch()['total']);

    // คงเหลือ
    $balance = $totalIncome - $totalExpense;

    // อัตราการออม
    $savingsRate = $totalIncome > 0 ? (($totalIncome - $totalExpense) / $totalIncome * 100) : 0;

    echo json_encode([
        "success" => true,
        "data" => [
            "total_income" => $totalIncome,
            "total_expense" => $totalExpense,
            "balance" => $balance,
            "savings_rate" => round($savingsRate, 2),
            "month" => $month,
            "year" => $year
        ]
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>