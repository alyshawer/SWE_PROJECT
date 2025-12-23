<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';
require_once __DIR__ . '/../models/AdminModel.php';

class AdminController extends BaseController {
    
    public function index() {
        $this->requireAdmin();
        
        updateAdminLastLogin($this->pdo, $_SESSION['user_id']);
        
        $users = getAllUsers($this->pdo);
        $jobs = getJobs($this->pdo);
        
        // Get client names for jobs
        foreach ($jobs as &$job) {
            $sql = "SELECT username, name FROM users WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$job['client_id']]);
            $client = $stmt->fetch();
            $job['client_username'] = $client['username'] ?? 'Unknown';
            $job['client_name'] = $client['name'] ?? 'Unknown';
        }
        unset($job); // Break reference
        
        $categories = getCategories($this->pdo);
        
        $analytics = null;
        if (isset($_GET['analytics'])) {
            $admin = new AdminModel($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], '', $_SESSION['type'], true);
            $analytics = $admin->generateAnalytics($this->pdo);
        }
        
        $recentTransactions = [];
        if (isset($_GET['transactions'])) {
            $admin = new AdminModel($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], '', $_SESSION['type'], true);
            $recentTransactions = $admin->monitorTransactions($this->pdo, 10);
        }
        
        $this->setPageTitle('Admin Panel');
        $this->setData('users', $users);
        $this->setData('jobs', $jobs);
        $this->setData('categories', $categories);
        $this->setData('analytics', $analytics);
        $this->setData('recentTransactions', $recentTransactions);
        $this->render('admin');
    }
    
    public function deleteUser() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            if (deleteUser($this->pdo, $user_id)) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "User {$user_id} deleted by admin");
                $_SESSION['success_message'] = 'User deleted successfully!';
            } else {
                $_SESSION['error_message'] = 'Cannot delete this user (protected admin account).';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
    
    public function suspendUser() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $admin = new AdminModel($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], '', $_SESSION['type'], true);
            if ($admin->suspendAccount($this->pdo, $user_id)) {
                $_SESSION['success_message'] = 'User suspended successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to suspend user.';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
    
    public function activateUser() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $sql = "UPDATE users SET isActive = TRUE WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute([$user_id])) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "User {$user_id} activated by admin");
                $_SESSION['success_message'] = 'User activated successfully!';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
    
    public function deleteJob() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
            $job_id = $_POST['job_id'];
            if (deleteJob($this->pdo, $job_id)) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Job {$job_id} deleted by admin");
                $_SESSION['success_message'] = 'Job deleted successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to delete job.';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
    
    public function createCategory() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['category_name'] ?? '';
            $description = $_POST['category_description'] ?? '';
            
            if (createCategory($this->pdo, $name, $description)) {
                $_SESSION['success_message'] = 'Category created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create category.';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
    
    public function deleteCategory() {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
            $category_id = $_POST['category_id'];
            if (deleteCategory($this->pdo, $category_id)) {
                $_SESSION['success_message'] = 'Category deleted successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to delete category.';
            }
        }
        
        $this->redirect('index.php?page=admin');
    }
}
?>

