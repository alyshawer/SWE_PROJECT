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
    


    // Show separate Manage Users page where admin can add/remove users
    public function users() {
        $this->requireAdmin();

        $users = getAllUsers($this->pdo);

        $this->setPageTitle('Manage Users');
        $this->setData('users', $users);
        $this->render('admin_users');
    }

    // Handle adding a new user from the Manage Users page
    public function addUser() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $type = $_POST['type'] ?? 'client';
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? null);

            $errors = [];

            // Username validation and uniqueness
            if (!validateUsername($username)) {
                $errors[] = 'Invalid username. Use 3-20 alphanumeric characters or underscores.';
            } else {
                $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errors[] = 'Username already exists. Please choose a different one.';
                }
            }

            // Email validation and uniqueness (accept any valid email format)
            if (!validateEmail($email)) {
                $errors[] = 'Invalid email address.';
            } else {
                $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already exists. Please choose a different one.';
                }
            }

            // Password & name validation
            if (!validatePassword($password)) {
                $errors[] = 'Password must be at least 8 characters, include upper/lowercase and a number.';
            }
            if (!validateName($name)) {
                $errors[] = 'Invalid name.';
            }
            if (!empty($phone) && !validatePhone($phone)) {
                $errors[] = 'Please enter a valid phone number.';
            }

            if (!empty($errors)) {
                $_SESSION['error_message'] = implode(' ', $errors);
                $this->redirect('index.php?page=admin&action=users');
                return;
            }

            // Insert user
            if (insertUser($this->pdo, $username, $email, $password, $type, $name, $phone)) {
                // Ensure the created account is deletable (non-protected)
                $newId = $this->pdo->lastInsertId();
                try {
                    $stmt = $this->pdo->prepare('UPDATE users SET is_deletable = 1 WHERE id = ?');
                    $stmt->execute([$newId]);
                } catch (Exception $e) {
                    // ignore - not critical
                }

                logAuditAction($this->pdo, $_SESSION['user_id'], "User {$newId} created by admin");
                $_SESSION['success_message'] = 'User created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create user. Email or username may already exist.';
            }
        }

        $this->redirect('index.php?page=admin&action=users');
    }
}
?>

