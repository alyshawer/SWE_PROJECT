<?php
// TEST SUMMARY:
// This file tests AdminController features:
// - Adding a user (addUser): verifies DB insert and redirect
// - Deleting a user (deleteUser): checks is_deletable flag and DB removal
// - Deleting a job (deleteJob): ensures job and related applications are deleted
// All tests use a testable controller to capture redirects and assert DB state.
require_once __DIR__ . '/TestCaseBase.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/helpers/db_functions.php';

class TestableAdminController extends AdminController {
    public $lastRedirect = null;
    protected function redirect($url) {
        // override to avoid exit() and capture redirection URL
        $this->lastRedirect = $url;
    }
}

class AdminControllerTest extends TestCaseBase
{
    public function testAddUserCreatesUser()
    {
        // Insert an admin user in session
        $adminId = $this->insertUser('adminuser', 'admin@example.com', 'Abcdef12', 'admin', 'Admin', null, 0);

        // Simulate session for admin
        $_SESSION = [];
        $_SESSION['user_id'] = $adminId;
        $_SESSION['username'] = 'adminuser';
        $_SESSION['type'] = 'admin';

        // Prepare post data
        $_POST = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Abcdef12',
            'type' => 'client',
            'name' => 'New User',
            'phone' => '+1234567890'
        ];

        $controller = new TestableAdminController($this->pdo);
        // Simulate HTTP POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->addUser();
        unset($_SERVER['REQUEST_METHOD']);

        // Check last redirect and that user exists
        $this->assertStringContainsString('action=users', $controller->lastRedirect);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute(['newuser']);
        $user = $stmt->fetch();
        $this->assertNotEmpty($user);
        $this->assertEquals('newuser@example.com', $user['email']);
    }

    public function testDeleteUserRespectsDeletableFlag()
    {
        $adminId = $this->insertUser('admin2', 'admin2@example.com', 'Abcdef12', 'admin', 'Admin2', null, 0);
        $delUserId = $this->insertUser('todelete', 'todelete@example.com', 'Abcdef12', 'client', 'To Delete', null, 1);

        $_SESSION = ['user_id' => $adminId, 'username' => 'admin2', 'type' => 'admin'];
        $_POST = ['user_id' => $delUserId];

        $controller = new TestableAdminController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->deleteUser();
        unset($_SERVER['REQUEST_METHOD']);

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$delUserId]);
        $user = $stmt->fetch();
        $this->assertFalse($user, 'User should be deleted');

        // Attempt to delete protected admin (is_deletable = 0) should not delete
        $protectedId = $this->insertUser('protected', 'prot@example.com', 'Abcdef12', 'client', 'Prot', null, 0);
        $_POST = ['user_id' => $protectedId];
        $controller->deleteUser();
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$protectedId]);
        $still = $stmt->fetch();
        $this->assertNotEmpty($still, 'Protected user should not be deleted');
    }

    public function testDeleteJobDeletesAndCascades()
    {
        $adminId = $this->insertUser('admin3', 'admin3@example.com', 'Abcdef12', 'admin', 'Admin3', null, 0);
        // create a client user
        $clientId = $this->insertUser('client1', 'client1@example.com', 'Abcdef12', 'client', 'Client', null, 1);

        // create a job
        $stmt = $this->pdo->prepare('INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Job1', 'Desc', 100, $clientId]);
        $jobId = (int)$this->pdo->lastInsertId();

        // create an application and no payments
        $stmt = $this->pdo->prepare('INSERT INTO applications (job_id, freelancer_id, proposal, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$jobId, 0, 'proposal', 'pending']);

        $_SESSION = ['user_id' => $adminId, 'username' => 'admin3', 'type' => 'admin'];
        $_POST = ['job_id' => $jobId];

        $controller = new TestableAdminController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->deleteJob();
        unset($_SERVER['REQUEST_METHOD']);

        // job should be deleted
        $stmt = $this->pdo->prepare('SELECT * FROM jobs WHERE id = ?');
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        $this->assertFalse($job, 'Job should be deleted');

        // applications should also be gone
        $stmt = $this->pdo->prepare('SELECT * FROM applications WHERE job_id = ?');
        $stmt->execute([$jobId]);
        $apps = $stmt->fetchAll();
        $this->assertEmpty($apps, 'Applications should be deleted along with job');
    }
}
