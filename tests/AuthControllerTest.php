<?php
// TEST SUMMARY:
// This file tests AuthController features:
// - User registration (register): verifies user creation, redirect, and client profile
// - User login (login): checks session variables and redirect on success
// - Login failure: ensures error message is returned for invalid credentials
// All tests use a testable controller to capture redirects and inspect view data.
require_once __DIR__ . '/TestCaseBase.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/db_functions.php';

class TestableAuthController extends AuthController {
    public $lastRedirect = null;
    public function getData() {
        return $this->data;
    }
    protected function redirect($url) {
        // Capture redirect instead of exiting
        $this->lastRedirect = $url;
    }
}

class AuthControllerTest extends TestCaseBase
{
    public function testRegisterCreatesUser()
    {
        $_SESSION = [];

        $_POST = [
            'username' => 'signupuser',
            'email' => 'signup@example.com',
            'password' => 'Abcdef12',
            'type' => 'client',
            'name' => 'Signup User',
            'phone' => '+15551234567'
        ];

        $controller = new TestableAuthController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->register();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertStringContainsString('page=login', $controller->lastRedirect);
        $this->assertStringContainsString('registered=1', $controller->lastRedirect);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute(['signupuser']);
        $user = $stmt->fetch();
        $this->assertNotEmpty($user);
        $this->assertEquals('signup@example.com', $user['email']);
    }

    public function testLoginAuthenticatesUser()
    {
        // Insert user with known password
        $userId = $this->insertUser('loginuser', 'login@example.com', 'Abcdef12', 'client', 'Login User');

        $_SESSION = [];
        $_POST = ['email' => 'login@example.com', 'password' => 'Abcdef12'];

        $controller = new TestableAuthController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->login();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertEquals($userId, $_SESSION['user_id']);
        $this->assertStringContainsString('page=dashboard', $controller->lastRedirect);
    }

    public function testLoginFailsWithInvalidCredentials()
    {
        $this->insertUser('badlogin', 'bad@example.com', 'Abcdef12', 'client', 'Bad');

        $_SESSION = [];
        $_POST = ['email' => 'bad@example.com', 'password' => 'WrongPass1'];

        $controller = new TestableAuthController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->login();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertArrayNotHasKey('user_id', $_SESSION);

        $data = $controller->getData();
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Invalid email or password.', $data['errors']);
    }
}
