<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class PaymentController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $payments = [];
        
        if ($user['type'] == 'admin') {
            $payments = getPayments($this->pdo);
        } else {
            $payments = getPayments($this->pdo, $user['id'], $user['type']);
        }
        
        $this->setPageTitle('Payments');
        $this->setData('user', $user);
        $this->setData('payments', $payments);
        $this->render('payments');
    }
    
    public function create() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'client') {
            $_SESSION['error_message'] = 'Only clients can create payments.';
            $this->redirect('index.php?page=payments');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $projectId = $_POST['project_id'] ?? 0;
            $freelancerId = $_POST['freelancer_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $paymentMethod = $_POST['payment_method'] ?? 'online';
            
            if (createPayment($this->pdo, $projectId, $_SESSION['user_id'], $freelancerId, $amount, $paymentMethod)) {
                $_SESSION['success_message'] = 'Payment created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create payment.';
            }
        }
        
        $this->redirect('index.php?page=payments');
    }
    
    public function complete() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
            $paymentId = $_POST['payment_id'];
            $transactionId = $_POST['transaction_id'] ?? null;
            
            // Verify authorization: Admin can complete any payment, clients can only complete their own
            $payment = getPaymentById($this->pdo, $paymentId);
            if (!$payment) {
                $_SESSION['error_message'] = 'Payment not found.';
                $this->redirect('index.php?page=payments');
            }
            
            $user = $this->getCurrentUser();
            // Admin can complete any payment, clients can only complete their own
            if ($user['type'] != 'admin') {
                if ($user['type'] == 'client' && (!isset($payment['clientId']) || $payment['clientId'] != $user['id'])) {
                    $_SESSION['error_message'] = 'You can only complete your own payments.';
                    $this->redirect('index.php?page=payments');
                } elseif ($user['type'] == 'freelancer') {
                    $_SESSION['error_message'] = 'Only clients and admins can complete payments.';
                    $this->redirect('index.php?page=payments');
                }
            }
            
            if ($payment['status'] != 'pending') {
                $_SESSION['error_message'] = 'This payment is already processed.';
                $this->redirect('index.php?page=payments');
            }
            
            if (completePayment($this->pdo, $paymentId, $transactionId)) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Payment {$paymentId} marked as completed");
                $_SESSION['success_message'] = 'Payment completed successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to complete payment.';
            }
        }
        
        $this->redirect('index.php?page=payments');
    }
}
?>

