<?php
// UML: User abstract class (Model)
abstract class UserModel {
    protected $id;
    protected $username;
    protected $email;
    protected $password;
    protected $role;
    protected $isActive;
    
    public function __construct($id, $username, $email, $password, $role, $isActive = true) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->isActive = $isActive;
    }
    
    // UML: +login()
    public function login() {
        // Implementation handled by session management
        $_SESSION['user_id'] = $this->id;
        $_SESSION['username'] = $this->username;
        $_SESSION['email'] = $this->email;
        $_SESSION['role'] = $this->role;
        $_SESSION['login_time'] = time();
        return true;
    }
    
    // UML: +logout()
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }
    
    // UML: +updateProfile()
    public function updateProfile($pdo, $username, $email, $name, $phone) {
        $sql = "UPDATE users SET username = ?, email = ?, name = ?, phone = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$username, $email, $name, $phone, $this->id]);
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getIsActive() { return $this->isActive; }
    
    // Setters
    public function setUsername($username) { $this->username = $username; }
    public function setEmail($email) { $this->email = $email; }
    public function setIsActive($isActive) { $this->isActive = $isActive; }
}
?>

