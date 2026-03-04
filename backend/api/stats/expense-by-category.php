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
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT 
                c.id,
                c.name,
                c.name_th,
                c.icon,
                c.color,
                SUM(t.amount) as total
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = :user_id 
            AND t.type = 'expense'
            AND YEAR(t.transaction_date) = :year 
            AND MONTH(t.transaction_date) = :month
            GROUP BY c.id, c.name, c.name_th, c.icon, c.color
            ORDER BY total DESC
            LIMIT :limit");
    
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindValue(":year", $year, PDO::PARAM_INT);
    $stmt->bindValue(":month", $month, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $categories = $stmt->fetchAll();

    // คำนวณยอดรวมทั้งหมด
    $totalExpense = array_sum(array_column($categories, 'total'));

    echo json_encode([
        "success" => true,
        "total_expense" => $totalExpense,
        "count" => count($categories),
        "data" => $categories
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>