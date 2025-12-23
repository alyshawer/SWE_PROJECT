<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class AuthController extends BaseController {
    
    public function login() {
        // Check if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $errors = [];
        
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($email)) {
                $errors[] = "Email is required.";
            } elseif (!validateEmail($email)) {
                $errors[] = "Please enter a valid email address.";
            }
            
            if (empty($password)) {
                $errors[] = "Password is required.";
            }
            
            if (empty($errors)) {
                $user = checkUser($this->pdo, $email, $password);
                
                if ($user) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['type'] = $user['type'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['login_time'] = time();
                    
                    // Log login action
                    logAuditAction($this->pdo, $user['id'], "User logged in");
                    
                    // Update admin last login if admin
                    if ($user['type'] == 'admin') {
                        updateAdminLastLogin($this->pdo, $user['id']);
                    }
                    
                    $this->redirect('index.php?page=dashboard');
                } else {
                    $errors[] = "Invalid email or password.";
                }
            }
        }
        
        $this->setPageTitle('Login');
        $this->setData('errors', $errors);
        $this->render('auth_login');
    }
    
    public function register() {
        // Check if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('index.php?page=dashboard');
        }
        
        $user_type = $_GET['type'] ?? '';
        $user_type = in_array($user_type, ['freelancer', 'client']) ? $user_type : '';
        $errors = [];
        
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $type = $_POST['type'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validation
            if (empty($username)) {
                $errors[] = "Username is required.";
            } elseif (!validateUsername($username)) {
                $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
            } else {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errors[] = "Username already exists. Please choose a different one.";
                }
            }
            
            if (empty($email)) {
                $errors[] = "Email is required.";
            } elseif (!validateEmail($email)) {
                $errors[] = "Please enter a valid email address.";
            } else {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email already exists. Please choose a different one.";
                }
            }
            
            if (empty($password)) {
                $errors[] = "Password is required.";
            } elseif (!validatePassword($password)) {
                $errors[] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.";
            }
            
            if (empty($name)) {
                $errors[] = "Name is required.";
            } elseif (!validateName($name)) {
                $errors[] = "Name must be 2-50 characters long and contain only letters, spaces, hyphens, and apostrophes.";
            }
            
            if (!empty($phone) && !validatePhone($phone)) {
                $errors[] = "Please enter a valid phone number.";
            }
            
            if (empty($errors) && in_array($type, ['freelancer', 'client'])) {
                if (insertUser($this->pdo, $username, $email, $password, $type, $name, $phone)) {
                    // Create profile if client
                    if ($type == 'client') {
                        $userId = $this->pdo->lastInsertId();
                        createOrUpdateClientProfile($this->pdo, $userId, null);
                    }
                    
                    logAuditAction($this->pdo, $this->pdo->lastInsertId(), "User registered: {$type}");
                    $this->redirect('index.php?page=login&registered=1');
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        }
        
        $this->setPageTitle('Register');
        $this->setData('errors', $errors);
        $this->setData('user_type', $user_type);
        $this->render('auth_register');
    }
    
    public function logout() {
        logAuditAction($this->pdo, $_SESSION['user_id'] ?? null, "User logged out");
        
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->redirect('index.php');
    }
}
?>

