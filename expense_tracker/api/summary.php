<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Transaction.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $transaction = new Transaction($db);

    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'month';
    $selectedMonth = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
    $selectedYear = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;

    // คำนวณวันที่ตาม filter
    $today = new DateTime();
    
    if ($selectedMonth !== null && $selectedYear !== null) {
        // เลือกเดือน/ปี เฉพาะ
        $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
        $endDate = date('Y-m-t', strtotime($startDate));
        $filterLabel = sprintf('%s %d', getMonthNameThai($selectedMonth), $selectedYear + 543);
    } elseif ($selectedYear !== null) {
        // เลือกเฉพาะปี
        $startDate = sprintf('%04d-01-01', $selectedYear);
        $endDate = sprintf('%04d-12-31', $selectedYear);
        $filterLabel = sprintf('ปี %d', $selectedYear + 543);
    } else {
        // ใช้ filter พื้นฐาน
        switch($filter) {
            case 'month':
                $startDate = $today->format('Y-m-01');
                $endDate = $today->format('Y-m-t');
                $filterLabel = 'เดือนนี้';
                break;
            case 'year':
                $startDate = $today->format('Y-01-01');
                $endDate = $today->format('Y-12-31');
                $filterLabel = 'ปีนี้';
                break;
            case 'all':
            default:
                $startDate = '2000-01-01';
                $endDate = '2099-12-31';
                $filterLabel = 'ทั้งหมด';
                break;
        }
    }

    $summary = $transaction->getSummary($user_id, $startDate, $endDate);
    $monthlyData = $transaction->getMonthlySummary($user_id);
    $categoryData = $transaction->getByCategory($user_id, $startDate, $endDate);

    $totalIncome = floatval($summary['total_income'] ?? 0);
    $totalExpense = floatval($summary['total_expense'] ?? 0);
    $balance = $totalIncome - $totalExpense;
    $savingsRate = $totalIncome > 0 ? (($totalIncome - $totalExpense) / $totalIncome * 100) : 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'balance' => $balance,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'savings_rate' => round($savingsRate, 2),
                'total_transactions' => $summary['total_transactions'] ?? 0
            ],
            'monthly' => $monthlyData,
            'categories' => $categoryData,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filter' => $filter,
                'selected_month' => $selectedMonth,
                'selected_year' => $selectedYear,
                'filter_label' => $filterLabel
            ]
        ]
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

function getMonthNameThai($month) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $months[$month] ?? '';
}
?>