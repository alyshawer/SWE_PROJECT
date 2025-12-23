<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class ProfileController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $userData = getUserById($this->pdo, $user['id']);
        
        if (!$userData) {
            $_SESSION['error_message'] = 'User not found.';
            $this->redirect('index.php?page=dashboard');
        }
        
        // Load additional data based on user type
        $this->setPageTitle('My Profile');
        $this->setData('user', $userData);
        $this->setData('userData', ['user' => $userData]); // For backward compatibility
        
        if ($user['type'] == 'client') {
            // Get all jobs the client has offered
            $jobs = getUserJobs($this->pdo, $user['id']);
            $this->setData('jobs', $jobs);
        } elseif ($user['type'] == 'freelancer') {
            // Get all job history (applications) for the freelancer
            $applications = getFreelancerApplications($this->pdo, $user['id']);
            $this->setData('applications', $applications);
            // Get freelancer profile
            $profile = getFreelancerProfile($this->pdo, $user['id']);
            $this->setData('profile', $profile);
        }
        
        $this->render('profile');
    }
    
    public function edit() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $userData = getUserById($this->pdo, $user['id']);
        
        if (!$userData) {
            $_SESSION['error_message'] = 'User not found.';
            $this->redirect('index.php?page=profile');
        }
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate required fields
            if (empty($username)) {
                $errors[] = 'Username is required.';
            }
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format.';
            }
            if (empty($name)) {
                $errors[] = 'Name is required.';
            }
            
            // Check if username or email already exists (excluding current user)
            if (!empty($username)) {
                $sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$username, $email, $user['id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Username or email already exists.';
                }
            }
            
            // Handle password change if provided
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $errors[] = 'Current password is required to change password.';
                } elseif (!password_verify($current_password, $userData['password'])) {
                    $errors[] = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 6) {
                    $errors[] = 'New password must be at least 6 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $errors[] = 'New password and confirm password do not match.';
                }
            }
            
            if (empty($errors)) {
                // Update user info
                if (updateUser($this->pdo, $user['id'], $username, $email, $name, $phone)) {
                    // Update password if provided
                    if (!empty($new_password)) {
                        updateUserPassword($this->pdo, $user['id'], $new_password);
                    }
                    
                    // Update session data
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $name;
                    
                    logAuditAction($this->pdo, $user['id'], "Profile updated");
                    $_SESSION['success_message'] = 'Profile updated successfully!';
                    $this->redirect('index.php?page=profile');
                } else {
                    $_SESSION['error_message'] = 'Failed to update profile.';
                }
            }
        }
        
        $this->setPageTitle('Edit Profile');
        $this->setData('user', $userData);
        $this->setData('errors', $errors);
        $this->render('profile_edit');
    }
}
?>

