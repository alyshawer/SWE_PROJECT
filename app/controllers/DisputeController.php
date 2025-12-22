<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class DisputeController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $disputes = [];
        
        if ($user['type'] == 'admin') {
            $status = $_GET['status'] ?? null;
            $disputes = getDisputes($this->pdo, $status);
        } else {
            $sql = "SELECT d.*, j.title as project_title, u1.name as raised_by_name, u2.name as against_name 
                    FROM disputes d 
                    JOIN jobs j ON d.projectId = j.id 
                    JOIN users u1 ON d.raisedBy = u1.id 
                    JOIN users u2 ON d.against = u2.id 
                    WHERE d.raisedBy = ? OR d.against = ?
                    ORDER BY d.createdAt DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user['id'], $user['id']]);
            $disputes = $stmt->fetchAll();
        }
        
        $this->setPageTitle('Disputes');
        $this->setData('user', $user);
        $this->setData('disputes', $disputes);
        $this->render('disputes');
    }
    
    public function create() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = $_POST['project_id'] ?? 0;
            $against = $_POST['against'] ?? 0;
            $reason = trim($_POST['reason'] ?? '');
            
            if (!empty($reason)) {
                if (createDispute($this->pdo, $projectId, $_SESSION['user_id'], $against, $reason)) {
                    $_SESSION['success_message'] = 'Dispute raised successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to raise dispute.';
                }
            } else {
                $_SESSION['error_message'] = 'Please provide a reason for the dispute.';
            }
        }
        
        $this->redirect('index.php?page=disputes');
    }
    
    public function resolve() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispute_id'])) {
            $disputeId = $_POST['dispute_id'];
            if (resolveDispute($this->pdo, $disputeId, $_SESSION['user_id'])) {
                $_SESSION['success_message'] = 'Dispute resolved successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to resolve dispute.';
            }
        }
        
        $this->redirect('index.php?page=disputes');
    }
}
?>

