<?php
require_once __DIR__ . '/UserModel.php';

// UML: Admin class extends User (Model)
class AdminModel extends UserModel {
    private $permissionLevel;
    private $lastLogin;
    private $actionsLog;
    
    public function __construct($id, $username, $email, $password, $role, $isActive, $permissionLevel = 1, $lastLogin = null, $actionsLog = null) {
        parent::__construct($id, $username, $email, $password, $role, $isActive);
        $this->permissionLevel = $permissionLevel;
        $this->lastLogin = $lastLogin;
        $this->actionsLog = $actionsLog;
    }
    
    // UML: +manageUser()
    public function manageUser($pdo, $userId, $action, $data = []) {
        switch ($action) {
            case 'suspend':
                return $this->suspendAccount($pdo, $userId);
            case 'activate':
                $sql = "UPDATE users SET isActive = TRUE WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([$userId]);
            case 'delete':
                require_once __DIR__ . '/../helpers/db_functions.php';
                return deleteUser($pdo, $userId);
            default:
                return false;
        }
    }
    
    // UML: +suspendAccount()
    public function suspendAccount($pdo, $userId) {
        $sql = "UPDATE users SET isActive = FALSE WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$userId])) {
            require_once __DIR__ . '/../helpers/db_functions.php';
            logAuditAction($pdo, $userId, "Account suspended by admin {$this->id}");
            return true;
        }
        return false;
    }
    
    // UML: +viewReports()
    public function viewReports($pdo) {
        $sql = "SELECT * FROM reports WHERE generatedBy = ? ORDER BY createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    // UML: +manageCategories()
    public function manageCategories($pdo, $action, $categoryId = null, $name = null, $description = null) {
        require_once __DIR__ . '/../helpers/db_functions.php';
        switch ($action) {
            case 'create':
                return createCategory($pdo, $name, $description);
            case 'update':
                return updateCategory($pdo, $categoryId, $name, $description);
            case 'delete':
                return deleteCategory($pdo, $categoryId);
            case 'toggle':
                $sql = "UPDATE categories SET isActive = NOT isActive WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([$categoryId]);
            default:
                return false;
        }
    }
    
    // UML: +resolveDispute()
    public function resolveDispute($pdo, $disputeId, $resolution) {
        require_once __DIR__ . '/../helpers/db_functions.php';
        $sql = "UPDATE disputes SET status = 'resolved', resolvedBy = ?, resolvedAt = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$this->id, $disputeId])) {
            logAuditAction($pdo, null, "Dispute {$disputeId} resolved by admin {$this->id}");
            return true;
        }
        return false;
    }
    
    // UML: +systemConfig()
    public function systemConfig($pdo, $configKey, $configValue) {
        // In a real system, this would manage system configuration
        $sql = "INSERT INTO system_config (config_key, config_value, updated_by, updated_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?, updated_at = NOW()";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$configKey, $configValue, $this->id, $configValue, $this->id]);
    }
    
    // UML: +generateAnalytics()
    public function generateAnalytics($pdo) {
        require_once __DIR__ . '/../helpers/db_functions.php';
        $analytics = [
            'total_users' => 0,
            'total_projects' => 0,
            'total_payments' => 0,
            'total_disputes' => 0,
            'active_freelancers' => 0,
            'active_clients' => 0
        ];
        
        $sql = "SELECT COUNT(*) as count FROM users";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $analytics['total_users'] = $stmt->fetch()['count'];
        
        $sql = "SELECT COUNT(*) as count FROM jobs";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $analytics['total_projects'] = $stmt->fetch()['count'];
        
        // Count completed payments and sum platform_fee (platform revenue)
        // Revenue should only be the platform_fee, not the full payment amount
        try {
            $sql = "SELECT COUNT(*) as count FROM payments WHERE status = 'completed'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $analytics['total_payments'] = $result['count'] ?? 0;
            
            // Sum only platform_fee for revenue (platform fee is the actual revenue)
            // Use COALESCE to return 0 instead of NULL if no payments exist
            $sql2 = "SELECT COALESCE(SUM(platform_fee), 0) as total FROM payments WHERE status = 'completed' AND platform_fee IS NOT NULL";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute();
            $result2 = $stmt2->fetch();
            $analytics['total_revenue'] = (float)($result2['total'] ?? 0);
        } catch (PDOException $e) {
            // Likely missing platform_fee column on older DB schema - fall back to counting completed payments
            $sql = "SELECT COUNT(*) as count FROM payments WHERE status = 'completed'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $analytics['total_payments'] = $result['count'] ?? 0;
            $analytics['total_revenue'] = 0; // Cannot calculate revenue without platform_fee column
        }
        
        $sql = "SELECT COUNT(*) as count FROM disputes WHERE status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $analytics['total_disputes'] = $stmt->fetch()['count'];
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE type = 'freelancer' AND isActive = TRUE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $analytics['active_freelancers'] = $stmt->fetch()['count'];
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE type = 'client' AND isActive = TRUE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $analytics['active_clients'] = $stmt->fetch()['count'];
        
        return $analytics;
    }
    
    // UML: +monitorTransactions()
    public function monitorTransactions($pdo, $limit = 50) {
        $sql = "SELECT p.*, u1.name as client_name, u2.name as freelancer_name, j.title as project_title
                FROM payments p
                JOIN users u1 ON p.clientId = u1.id
                JOIN users u2 ON p.freelancerId = u2.id
                JOIN jobs j ON p.projectId = j.id
                ORDER BY p.createdAt DESC
                LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    private function logAction($pdo, $userId, $action) {
        require_once __DIR__ . '/../helpers/db_functions.php';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        logAuditAction($pdo, $userId, $action, $ipAddress);
    }
    
    // Getters
    public function getPermissionLevel() { return $this->permissionLevel; }
    public function getLastLogin() { return $this->lastLogin; }
    public function getActionsLog() { return $this->actionsLog; }
}
?>

