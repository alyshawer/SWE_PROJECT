<?php
// TEST SUMMARY:
// This file tests PaymentController features:
// - Creating a payment (create): verifies DB insert and validation
// - Completing a payment (complete): checks status update and freelancer earnings
// - Validation for missing PayPal account
// All tests use a testable controller to capture redirects and assert DB state.
require_once __DIR__ . '/TestCaseBase.php';
require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../app/helpers/db_functions.php';

class TestablePaymentController extends PaymentController {
    public $lastRedirect = null;
    protected function redirect($url) {
        // Capture redirect instead of exiting
        $this->lastRedirect = $url;
    }
}

class PaymentControllerTest extends TestCaseBase
{
    public function testCreatePaymentAsClientSucceeds()
    {
        // Insert client and freelancer
        $clientId = $this->insertUser('clientp', 'clientp@example.com', 'Abcdef12', 'client', 'Client P');
        $freelancerId = $this->insertUser('freelp', 'freelp@example.com', 'Abcdef12', 'freelancer', 'Free L');

        // Create a job owned by client
        $stmt = $this->pdo->prepare('INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Job Pay', 'Desc', 100, $clientId]);
        $jobId = (int)$this->pdo->lastInsertId();

        $_SESSION = ['user_id' => $clientId, 'username' => 'clientp', 'type' => 'client'];
        $_POST = [
            'project_id' => $jobId,
            'freelancer_id' => $freelancerId,
            'amount' => 100,
            'payment_method' => 'paypal',
            'freelancer_account' => 'freelancer@example.com'
        ];

        $controller = new TestablePaymentController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->create();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertStringContainsString('page=payments', $controller->lastRedirect);

        // Payment should exist
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE projectId = ? AND freelancerId = ?');
        $stmt->execute([$jobId, $freelancerId]);
        $payment = $stmt->fetch();
        $this->assertNotEmpty($payment);
        $this->assertEquals('pending', $payment['status']);
        $this->assertEquals('freelancer@example.com', $payment['transactionId']);
    }

    public function testCreatePaymentMissingAccountFailsForPaypal()
    {
        $clientId = $this->insertUser('clientq', 'clientq@example.com', 'Abcdef12', 'client', 'Client Q');
        $freelancerId = $this->insertUser('freelq', 'freelq@example.com', 'Abcdef12', 'freelancer', 'Free Q');

        $stmt = $this->pdo->prepare('INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Job Pay2', 'Desc', 200, $clientId]);
        $jobId = (int)$this->pdo->lastInsertId();

        $_SESSION = ['user_id' => $clientId, 'username' => 'clientq', 'type' => 'client'];
        $_POST = [
            'project_id' => $jobId,
            'freelancer_id' => $freelancerId,
            'amount' => 200,
            'payment_method' => 'paypal',
            'freelancer_account' => ''
        ];

        $controller = new TestablePaymentController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->create();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertEquals('Please enter freelancer PayPal email', $_SESSION['error_message']);
    }

    public function testCompletePaymentByClientSucceedsAndUpdatesEarnings()
    {
        $clientId = $this->insertUser('clientc', 'clientc@example.com', 'Abcdef12', 'client', 'Client C');
        $freelancerId = $this->insertUser('freelc', 'freelc@example.com', 'Abcdef12', 'freelancer', 'Free C');

        // Create job
        $stmt = $this->pdo->prepare('INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Job Pay3', 'Desc', 1000, $clientId]);
        $jobId = (int)$this->pdo->lastInsertId();

        // Insert a pending payment for client->freelancer
        $paymentId = createPayment($this->pdo, $jobId, $clientId, $freelancerId, 1000, 'online', null);
        $this->assertNotFalse($paymentId);

        // Ensure initial payment status is pending
        $p = getPaymentById($this->pdo, $paymentId);
        $this->assertEquals('pending', $p['status']);

        $_SESSION = ['user_id' => $clientId, 'username' => 'clientc', 'type' => 'client'];
        $_POST = ['payment_id' => $paymentId, 'transaction_id' => 'TX-12345'];

        $controller = new TestablePaymentController($this->pdo);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller->complete();
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertStringContainsString('page=payments', $controller->lastRedirect);
        $this->assertEquals('Payment completed successfully!', $_SESSION['success_message']);

        // Check payment updated to completed
        $p2 = getPaymentById($this->pdo, $paymentId);
        $this->assertEquals('completed', $p2['status']);
        $this->assertEquals('TX-12345', $p2['transactionId']);

        // Freelancer profile should have totalEarned updated (10% fee by default)
        $stmt = $this->pdo->prepare('SELECT totalEarned FROM freelancer_profiles WHERE user_id = ?');
        $stmt->execute([$freelancerId]);
        $row = $stmt->fetch();
        $this->assertNotEmpty($row);
        // 1000 - 10% = 900
        $this->assertEquals(900, (int)$row['totalEarned']);
    }
}
