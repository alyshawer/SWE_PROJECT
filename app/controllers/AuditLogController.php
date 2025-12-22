<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class AuditLogController extends BaseController {
    
    public function index() {
        // Audit logs should be visible only to administrators
        $this->requireAdmin();

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $logs = getAuditLogs($this->pdo, null, $limit);
        
        $this->setPageTitle('Audit Logs');
        $this->setData('logs', $logs);
        $this->render('audit_logs');
    }
}
?>

