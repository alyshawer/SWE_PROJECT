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
        
        // For clients, get jobs with accepted applications and their payment info
        $jobsWithPaymentInfo = [];
        $jobFreelancerMap = [];
        if ($user['type'] == 'client') {
            $jobs = getUserJobs($this->pdo, $user['id']);
            foreach ($jobs as $job) {
                // Get ALL accepted applications for this job (not just completed ones)
                // This allows clients to create payments for any accepted application
                $sql = "SELECT a.*, u.name as freelancer_name, u.id as freelancer_id 
                        FROM applications a 
                        JOIN users u ON a.freelancer_id = u.id 
                        WHERE a.job_id = ? AND a.status = 'accepted'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$job['id']]);
                $applications = $stmt->fetchAll();
                
                if (!empty($applications)) {
                    $jobFreelancerMap[$job['id']] = [];
                    foreach ($applications as $app) {
                        // Check if payment already exists
                        $sql2 = "SELECT id FROM payments WHERE projectId = ? AND freelancerId = ?";
                        $stmt2 = $this->pdo->prepare($sql2);
                        $stmt2->execute([$job['id'], $app['freelancer_id']]);
                        $existingPayment = $stmt2->fetch();
                        
                        // Only show in jobsWithPaymentInfo if job is completed and payment info exists
                        if ($app['job_status'] == 'completed' && 
                            !$existingPayment && 
                            !empty($app['payment_method']) && 
                            !empty($app['payment_account_info'])) {
                            $jobsWithPaymentInfo[] = [
                                'job_id' => $job['id'],
                                'job_title' => $job['title'],
                                'job_budget' => $job['budget'],
                                'freelancer_id' => $app['freelancer_id'],
                                'freelancer_name' => $app['freelancer_name'],
                                'payment_method' => $app['payment_method'],
                                'payment_account_info' => $app['payment_account_info']
                            ];
                        }
                        
                        // Store in map for form population (include all accepted applications)
                        $jobFreelancerMap[$job['id']][] = [
                            'freelancer_id' => $app['freelancer_id'],
                            'freelancer_name' => $app['freelancer_name'],
                            'payment_method' => $app['payment_method'] ?? '',
                            'payment_account_info' => $app['payment_account_info'] ?? '',
                            'job_budget' => $job['budget'],
                            'status' => $app['status'],
                            'job_status' => $app['job_status']
                        ];
                    }
                }
            }
        }
        
        // Get user jobs for clients
        $userJobs = [];
        if ($user['type'] == 'client') {
            $userJobs = getUserJobs($this->pdo, $user['id']);
        }
        
        $this->setPageTitle('Payments');
        $this->setData('user', $user);
        $this->setData('payments', $payments);
        $this->setData('jobsWithPaymentInfo', $jobsWithPaymentInfo);
        $this->setData('jobFreelancerMap', $jobFreelancerMap ?? []);
        $this->setData('userJobs', $userJobs);
        $this->render('payments');
    }
    
    public function create() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'client') {
            $_SESSION['error_message'] = 'Only clients can create payments.';
            $this->redirect('index.php?page=payments');
        }
        
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $projectId = $_POST['project_id'] ?? 0;
            $freelancerId = $_POST['freelancer_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $paymentMethod = $_POST['payment_method'] ?? 'online';
            $freelancerAccount = trim($_POST['freelancer_account'] ?? '');
            
            // Validate freelancer account if payment method requires it
            if (in_array($paymentMethod, ['paypal', 'bank_transfer']) && empty($freelancerAccount)) {
                $_SESSION['error_message'] = 'Please enter freelancer ' . ($paymentMethod == 'paypal' ? 'PayPal email' : 'bank account details');
            } elseif (createPayment($this->pdo, $projectId, $_SESSION['user_id'], $freelancerId, $amount, $paymentMethod, $freelancerAccount)) {
                $_SESSION['success_message'] = 'Payment created successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to create payment.';
            }
        }
        
        $this->redirect('index.php?page=payments');
    }
    
    public function complete() {
        $this->requireLogin();
        
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['payment_id'])) {
            $paymentId = $_POST['payment_id'];
            $transactionId = trim($_POST['transaction_id'] ?? '');
            if (empty($transactionId)) {
                $transactionId = null;
            }
            
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
            
            // Enable error display temporarily for debugging
            $oldErrorReporting = error_reporting(E_ALL);
            $oldDisplayErrors = ini_set('display_errors', '0'); // Don't display, but log
            
            try {
                $result = completePayment($this->pdo, $paymentId, $transactionId);
                
                if ($result) {
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Payment {$paymentId} marked as completed");
                    $_SESSION['success_message'] = 'Payment completed successfully!';
                } else {
                    // Check payment status to see if it was actually completed
                    $paymentCheck = getPaymentById($this->pdo, $paymentId);
                    if ($paymentCheck && $paymentCheck['status'] == 'completed') {
                        $_SESSION['success_message'] = 'Payment was already completed!';
                    } else {
                        // Get last PDO error
                        $pdoError = $this->pdo->errorInfo();
                        $errorDetails = !empty($pdoError[2]) ? $pdoError[2] : 'Unknown database error';
                        $_SESSION['error_message'] = 'Failed to complete payment. Error: ' . htmlspecialchars($errorDetails) . '. Please check PHP error logs for details.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Payment completion PDO exception in controller: " . $e->getMessage());
                error_log("PDO Error Info: " . print_r($e->errorInfo(), true));
                $_SESSION['error_message'] = 'Database error: ' . htmlspecialchars($e->getMessage()) . ' (Code: ' . $e->getCode() . ')';
            } catch (Exception $e) {
                error_log("Payment completion exception in controller: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $_SESSION['error_message'] = 'Error completing payment: ' . htmlspecialchars($e->getMessage());
            } finally {
                error_reporting($oldErrorReporting);
                if ($oldDisplayErrors !== false) {
                    ini_set('display_errors', $oldDisplayErrors);
                }
            }
        }
        
        $this->redirect('index.php?page=payments');
    }
}
?>

