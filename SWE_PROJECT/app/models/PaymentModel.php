<?php
// UML: Payment class (Model)
class PaymentModel {
    private $id;
    private $projectId;
    private $clientId;
    private $freelancerId;
    private $amount;
    private $status;
    private $paymentMethod;
    private $transactionId;
    private $createdAt;
    private $completedAt;
    
    public function __construct($id, $projectId, $clientId, $freelancerId, $amount, $status, $paymentMethod = null, $transactionId = null, $createdAt = null, $completedAt = null) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->clientId = $clientId;
        $this->freelancerId = $freelancerId;
        $this->amount = $amount;
        $this->status = $status;
        $this->paymentMethod = $paymentMethod;
        $this->transactionId = $transactionId;
        $this->createdAt = $createdAt;
        $this->completedAt = $completedAt;
    }
    
    // Create a new payment
    public static function create($pdo, $projectId, $clientId, $freelancerId, $amount, $paymentMethod = 'online') {
        $sql = "INSERT INTO payments (projectId, clientId, freelancerId, amount, status, paymentMethod) 
                VALUES (?, ?, ?, ?, 'pending', ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$projectId, $clientId, $freelancerId, $amount, $paymentMethod])) {
            return $pdo->lastInsertId();
        }
        return false;
    }
    
    // Complete payment
    public function complete($pdo, $transactionId = null) {
        $sql = "UPDATE payments SET status = 'completed', transactionId = ?, completedAt = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$transactionId, $this->id])) {
            $this->status = 'completed';
            $this->transactionId = $transactionId;
            
            // Update freelancer earnings directly via SQL
            $sql2 = "UPDATE freelancer_profiles SET totalEarned = totalEarned + ? WHERE user_id = ?";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$this->amount, $this->freelancerId]);
            
            // Log the action
            require_once __DIR__ . '/AuditLogModel.php';
            AuditLogModel::logAction($pdo, $this->clientId, "Payment completed for project {$this->projectId}");
            return true;
        }
        return false;
    }
    
    // Cancel payment
    public function cancel($pdo) {
        $sql = "UPDATE payments SET status = 'cancelled' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$this->id])) {
            $this->status = 'cancelled';
            return true;
        }
        return false;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getProjectId() { return $this->projectId; }
    public function getClientId() { return $this->clientId; }
    public function getFreelancerId() { return $this->freelancerId; }
    public function getAmount() { return $this->amount; }
    public function getStatus() { return $this->status; }
    public function getPaymentMethod() { return $this->paymentMethod; }
    public function getTransactionId() { return $this->transactionId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getCompletedAt() { return $this->completedAt; }
    
    // Static method to get payment by ID
    public static function getById($pdo, $id) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            return new PaymentModel(
                $row['id'],
                $row['projectId'],
                $row['clientId'],
                $row['freelancerId'],
                $row['amount'],
                $row['status'],
                $row['paymentMethod'],
                $row['transactionId'],
                $row['createdAt'],
                $row['completedAt']
            );
        }
        return null;
    }
}
?>

