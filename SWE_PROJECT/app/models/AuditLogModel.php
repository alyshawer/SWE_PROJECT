<?php
// UML: AuditLog class (Model)
class AuditLogModel {
    private $id;
    private $userId;
    private $action;
    private $timestamp;
    private $ipAddress;
    
    public function __construct($id, $userId, $action, $timestamp, $ipAddress) {
        $this->id = $id;
        $this->userId = $userId;
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->ipAddress = $ipAddress;
    }
    
    // UML: +logAction()
    public static function logAction($pdo, $userId, $action, $ipAddress = null) {
        if ($ipAddress === null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $sql = "INSERT INTO audit_logs (userId, action, ipAddress) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$userId, $action, $ipAddress]);
    }
    
    // UML: +getLogs()
    public static function getLogs($pdo, $userId = null, $limit = 100) {
        if ($userId) {
            $sql = "SELECT * FROM audit_logs WHERE userId = ? ORDER BY timestamp DESC LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
        } else {
            $sql = "SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getAction() { return $this->action; }
    public function getTimestamp() { return $this->timestamp; }
    public function getIpAddress() { return $this->ipAddress; }
}
?>

