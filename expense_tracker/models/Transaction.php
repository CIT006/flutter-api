<?php
class Transaction {
    private $conn;
    private $table = "transactions";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} 
                      (user_id, type, category_id, amount, description, transaction_date) 
                      VALUES (:user_id, :type, :category_id, :amount, :description, :transaction_date)";
            
            $stmt = $this->conn->prepare($query);
            
            $user_id = isset($data['user_id']) ? intval($data['user_id']) : 1;
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':transaction_date', $data['transaction_date']);
            
            if($stmt->execute()) {
                return ['success' => true, 'id' => $this->conn->lastInsertId()];
            }
            return ['success' => false, 'message' => 'Failed to create transaction'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function readAll($user_id, $limit = 100, $offset = 0) {
        try {
            $query = "SELECT t.*, c.name as category_name, c.name_th as category_name_th, 
                      c.icon as category_icon, c.color as category_color
                      FROM {$this->table} t
                      LEFT JOIN categories c ON t.category_id = c.id
                      WHERE t.user_id = :user_id
                      ORDER BY t.transaction_date DESC, t.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }

    public function readOne($id, $user_id) {
        try {
            $query = "SELECT t.*, c.name as category_name, c.name_th as category_name_th, 
                      c.icon as category_icon, c.color as category_color
                      FROM {$this->table} t
                      LEFT JOIN categories c ON t.category_id = c.id
                      WHERE t.id = :id AND t.user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }

    public function update($data) {
        try {
            $query = "UPDATE {$this->table} 
                      SET type = :type, category_id = :category_id, amount = :amount, 
                          description = :description, transaction_date = :transaction_date
                      WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':type', $data['type']);
            $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':transaction_date', $data['transaction_date']);
            
            if($stmt->execute()) {
                return ['success' => true];
            }
            return ['success' => false, 'message' => 'Failed to update transaction'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function delete($id, $user_id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                return ['success' => true];
            }
            return ['success' => false, 'message' => 'Failed to delete transaction'];
        } catch(PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getSummary($user_id, $startDate = null, $endDate = null) {
        try {
            $query = "SELECT 
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
                      COUNT(*) as total_transactions
                      FROM {$this->table}
                      WHERE user_id = :user_id";
            
            $params = [':user_id' => $user_id];
            
            if($startDate && $endDate) {
                $query .= " AND transaction_date BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            }
            
            $stmt = $this->conn->prepare($query);
            foreach($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetch();
        } catch(PDOException $e) {
            return ['total_income' => 0, 'total_expense' => 0, 'total_transactions' => 0];
        }
    }

    public function getMonthlySummary($user_id, $year = null) {
        try {
            $query = "SELECT 
                      YEAR(transaction_date) as year,
                      MONTH(transaction_date) as month,
                      COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                      COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
                      FROM {$this->table}
                      WHERE user_id = :user_id";
            
            $params = [':user_id' => $user_id];
            
            if($year) {
                $query .= " AND YEAR(transaction_date) = :year";
                $params[':year'] = $year;
            }
            
            $query .= " GROUP BY YEAR(transaction_date), MONTH(transaction_date)
                       ORDER BY year DESC, month DESC";
            
            $stmt = $this->conn->prepare($query);
            foreach($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getByCategory($user_id, $startDate, $endDate) {
        try {
            $query = "SELECT c.name, c.name_th, c.icon, c.color,
                      COALESCE(SUM(t.amount), 0) as total_amount,
                      COUNT(t.id) as transaction_count
                      FROM {$this->table} t
                      LEFT JOIN categories c ON t.category_id = c.id
                      WHERE t.user_id = :user_id 
                      AND t.type = 'expense'
                      AND t.transaction_date BETWEEN :start_date AND :end_date
                      GROUP BY t.category_id
                      ORDER BY total_amount DESC
                      LIMIT 5";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            return [];
        }
    }
}
?>