<?php
// Base Controller class for MVC architecture
abstract class BaseController {
    protected $pdo;
    protected $viewPath;
    protected $data = [];
    
    public function __construct($pdo = null) {
        // Accept an injected PDO or fall back to the singleton
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            // lazy-load Database singleton
            require_once APP_PATH . '/core/Database.php';
            $this->pdo = Database::getInstance()->getConnection();
        }
        $this->viewPath = APP_PATH . '/views/';
    }
    
    // Render a view - uses flat file structure
    protected function render($view, $data = []) {
        $this->data = array_merge($this->data, $data);
        extract($this->data);
        
        // Convert nested path to flattened filename (e.g., 'auth/login' -> 'auth_login_view')
        $viewFile = str_replace('/', '_', $view);
        $viewFile = $this->viewPath . $viewFile . '_view.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View not found: {$view} (looking for: {$viewFile})");
        }
    }
    
    // Redirect to a URL
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    // Check if user is logged in
    protected function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('index.php?page=login');
        }
    }
    
    // Check if user is admin
    protected function requireAdmin() {
        $this->requireLogin();
        if (!isset($_SESSION['type']) || $_SESSION['type'] != 'admin') {
            $this->redirect('index.php?page=dashboard');
        }
    }
    
    // Get current user
    protected function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'type' => $_SESSION['type'],
                'name' => $_SESSION['name']
            ];
        }
        return null;
    }
    
    // Set page title
    protected function setPageTitle($title) {
        $this->data['page_title'] = $title;
    }
    
    // Add data to view
    protected function setData($key, $value) {
        $this->data[$key] = $value;
    }
}
?>

