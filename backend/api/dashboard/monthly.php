<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT 
                year,
                month,
                total_income,
                total_expense,
                balance
            FROM monthly_summaries
            WHERE user_id = :user_id
            ORDER BY year DESC, month DESC
            LIMIT :limit");
    
    $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();

    $summaries = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "count" => count($summaries),
        "data" => $summaries
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>