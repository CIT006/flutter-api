<?php
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
$month = isset($_GET['month']) ? intval($_GET['month']) : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "SELECT 
                t.id,
                t.type,
                t.category_id,
                c.name as category_name,
                c.name_th as category_name_th,
                c.icon as category_icon,
                c.color as category_color,
                t.amount,
                t.description,
                t.transaction_date,
                t.created_at
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.user_id = :user_id";

    $params = [":user_id" => $userId];

    if ($month !== null && $year !== null) {
        $sql .= " AND MONTH(t.transaction_date) = :month AND YEAR(t.transaction_date) = :year";
        $params[":month"] = $month;
        $params[":year"] = $year;
    }

    if ($type !== null && in_array($type, ['income', 'expense'])) {
        $sql .= " AND t.type = :type";
        $params[":type"] = $type;
    }

    $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $transactions = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "count" => count($transactions),
        "data" => $transactions
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>