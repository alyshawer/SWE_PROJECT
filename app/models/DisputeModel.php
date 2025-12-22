<?php
// UML: Dispute class (Model)
class DisputeModel {
    private $id;
    private $projectId;
    private $raisedBy;
    private $against;
    private $reason;
    private $status;
    private $resolvedBy;
    
    public function __construct($id, $projectId, $raisedBy, $against, $reason, $status, $resolvedBy = null) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->raisedBy = $raisedBy;
        $this->against = $against;
        $this->reason = $reason;
        $this->status = $status;
        $this->resolvedBy = $resolvedBy;
    }
    
    // UML: +raiseDispute()
    public static function raiseDispute($pdo, $projectId, $raisedBy, $against, $reason) {
        $sql = "INSERT INTO disputes (projectId, raisedBy, against, reason, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$projectId, $raisedBy, $against, $reason])) {
            // Log the action
            require_once __DIR__ . '/AuditLogModel.php';
            AuditLogModel::logAction($pdo, $raisedBy, "Dispute raised for project {$projectId}");
            return $pdo->lastInsertId();
        }
        return false;
    }
    
    // UML: +resolve()
    public function resolve($pdo, $resolvedBy) {
        $sql = "UPDATE disputes SET status = 'resolved', resolvedBy = ?, resolvedAt = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$resolvedBy, $this->id])) {
            $this->status = 'resolved';
            $this->resolvedBy = $resolvedBy;
            require_once __DIR__ . '/AuditLogModel.php';
            AuditLogModel::logAction($pdo, $resolvedBy, "Dispute {$this->id} resolved");
            return true;
        }
        return false;
    }
    
    // UML: +escalate()
    public function escalate($pdo) {
        $sql = "UPDATE disputes SET status = 'escalated' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$this->id])) {
            $this->status = 'escalated';
            require_once __DIR__ . '/AuditLogModel.php';
            AuditLogModel::logAction($pdo, $this->raisedBy, "Dispute {$this->id} escalated");
            return true;
        }
        return false;
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getProjectId() { return $this->projectId; }
    public function getRaisedBy() { return $this->raisedBy; }
    public function getAgainst() { return $this->against; }
    public function getReason() { return $this->reason; }
    public function getStatus() { return $this->status; }
    public function getResolvedBy() { return $this->resolvedBy; }
    
    // Static method to get dispute by ID
    public static function getById($pdo, $id) {
        $sql = "SELECT * FROM disputes WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            return new DisputeModel(
                $row['id'],
                $row['projectId'],
                $row['raisedBy'],
                $row['against'],
                $row['reason'],
                $row['status'],
                $row['resolvedBy']
            );
        }
        return null;
    }
}
?>

