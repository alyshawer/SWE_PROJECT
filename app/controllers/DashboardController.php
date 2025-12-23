<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class DashboardController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $data = [];
        
        if ($user['type'] == 'client') {
            $jobs = getUserJobs($this->pdo, $user['id']);
            // Enrich jobs with application and payment information
            foreach ($jobs as &$job) {
                $job['applications'] = getApplicationsForJob($this->pdo, $job['id']);
                // Get payment for this job if exists
                $sql = "SELECT * FROM payments WHERE projectId = ? ORDER BY createdAt DESC LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$job['id']]);
                $payment = $stmt->fetch();
                $job['payment'] = ($payment !== false) ? $payment : null;
            }
            unset($job); // Break reference
            $data['user_jobs'] = $jobs;
        } elseif ($user['type'] == 'freelancer') {
            $applications = getFreelancerApplications($this->pdo, $user['id']);
            // Enrich applications with payment information
            foreach ($applications as &$application) {
                // Get payment for this job/application
                $sql = "SELECT * FROM payments WHERE projectId = ? AND freelancerId = ? ORDER BY createdAt DESC LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$application['job_id'], $user['id']]);
                $payment = $stmt->fetch();
                $application['payment'] = ($payment !== false) ? $payment : null;
            }
            unset($application); // Break reference
            $data['applications'] = $applications;
            $data['offers'] = getOffersForFreelancer($this->pdo, $user['id']);
            $data['profile'] = getFreelancerProfile($this->pdo, $user['id']);
        }
        
        $this->setPageTitle('Dashboard');
        $this->setData('user', $user);
        $this->setData('data', $data);
        $this->render('dashboard');
    }
    
    public function deleteJob() {
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
            $job_id = $_POST['job_id'];
            
            // Check if job has payments or active applications before attempting deletion
            $sql_check_payment = "SELECT id FROM payments WHERE projectId = ? AND status IN ('pending', 'completed')";
            $stmt_check_payment = $this->pdo->prepare($sql_check_payment);
            $stmt_check_payment->execute([$job_id]);
            if ($stmt_check_payment->fetch()) {
                $_SESSION['error_message'] = 'Cannot delete job with existing payments. Please cancel or complete all payments first.';
                $this->redirect('index.php?page=dashboard');
                return;
            }
            
            $sql_check_app = "SELECT id FROM applications WHERE job_id = ? AND status = 'accepted' AND job_status NOT IN ('canceled')";
            $stmt_check_app = $this->pdo->prepare($sql_check_app);
            $stmt_check_app->execute([$job_id]);
            if ($stmt_check_app->fetch()) {
                $_SESSION['error_message'] = 'Cannot delete job with active/accepted applications. Please cancel or complete the job first.';
                $this->redirect('index.php?page=dashboard');
                return;
            }
            
            if (deleteJob($this->pdo, $job_id, $_SESSION['user_id'])) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Job {$job_id} deleted");
                $_SESSION['success_message'] = 'Job deleted successfully!';
            } else {
                $_SESSION['error_message'] = 'Cannot delete this job. It may have active applications or payments.';
            }
        }
        
        $this->redirect('index.php?page=dashboard');
    }

    // Show browse freelancers page (uses existing legacy page)
    public function browseFreelancers() {
        $this->requireLogin();
        // Use the MVC `FreelancersController` instead of legacy pages/browse_freelancers.php
        $this->redirect('index.php?page=freelancers');
    }
    
    public function editPortfolio() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can edit their portfolio.';
            $this->redirect('index.php?page=dashboard');
        }
        
        $user = $this->getCurrentUser();
        $profile = getFreelancerProfile($this->pdo, $user['id']);
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $skills = $_POST['skills'] ?? '';
            $past_projects = $_POST['past_projects'] ?? '';
            $portfolio_link = $_POST['portfolio_link'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $hourly_rate = $_POST['hourly_rate'] ?? 0;
            $cv_filename = $_POST['cv_filename'] ?? null; // TODO: Handle file upload
            
            // Validate hourly rate
            $hourly_rate = floatval($hourly_rate);
            if ($hourly_rate < 0) {
                $hourly_rate = 0;
            }
            
            // Use updateFreelancerProfile (handles both create and update)
            if ($profile) {
                // Update existing profile
                if (updateFreelancerProfile($this->pdo, $user['id'], $skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate)) {
                    logAuditAction($this->pdo, $user['id'], "Freelancer portfolio updated");
                    $_SESSION['success_message'] = 'Portfolio updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update portfolio.';
                }
            } else {
                // Create new profile
                if (createFreelancerProfile($this->pdo, $user['id'], $skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate)) {
                    logAuditAction($this->pdo, $user['id'], "Freelancer portfolio created");
                    $_SESSION['success_message'] = 'Portfolio created successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to create portfolio.';
                }
            }
            
            $this->redirect('index.php?page=dashboard');
        }
        
        // Display edit form
        $this->setPageTitle('Edit Portfolio');
        $this->setData('user', $user);
        $this->setData('profile', $profile);
        $this->render('freelancer_portfolio_edit');
    }
    
    public function updateJobStatus() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can update job status.';
            $this->redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_POST['job_status'])) {
            $application_id = $_POST['application_id'];
            $job_status = $_POST['job_status'];
            
            // Verify the application belongs to this freelancer
            $sql = "SELECT id, job_id, payment_method, payment_account_info FROM applications WHERE id = ? AND freelancer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$application_id, $_SESSION['user_id']]);
            $app = $stmt->fetch();
            
            if (!$app) {
                $_SESSION['error_message'] = 'Unauthorized action.';
                $this->redirect('index.php?page=dashboard');
            }
            
            if ($job_status == 'canceled') {
                // If canceled, mark application as rejected
                $sql2 = "UPDATE applications SET status = 'rejected', job_status = 'not_started' WHERE id = ?";
                $stmt2 = $this->pdo->prepare($sql2);
                if ($stmt2->execute([$application_id])) {
                    // Re-open the job
                    $sql3 = "UPDATE jobs SET status = 'active' WHERE id = ?";
                    $stmt3 = $this->pdo->prepare($sql3);
                    $stmt3->execute([$app['job_id']]);
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Job canceled for application {$application_id}");
                    $_SESSION['success_message'] = 'Job canceled successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to cancel job.';
                }
            } elseif ($job_status == 'completed') {
                // If marking as completed, check if payment info is already provided
                if (empty($app['payment_method']) || empty($app['payment_account_info'])) {
                    // Redirect to payment info entry page
                    $_SESSION['pending_job_status_update'] = ['application_id' => $application_id, 'job_status' => $job_status];
                    $this->redirect('index.php?page=dashboard&action=enter_payment_info&application_id=' . $application_id);
                    return;
                } else {
                    // Payment info exists, proceed with status update
                    if ($this->completeJobStatusUpdate($application_id, $app['job_id'])) {
                        $_SESSION['success_message'] = 'Job status updated successfully!';
                    } else {
                        $_SESSION['error_message'] = 'Failed to update job status.';
                    }
                }
            } else {
                // Update the job status for other statuses
                if (updateApplicationJobStatus($this->pdo, $application_id, $job_status)) {
                    if ($job_status == 'in_progress') {
                        $sql2 = "UPDATE jobs SET status = 'in_progress' WHERE id = ?";
                        $stmt2 = $this->pdo->prepare($sql2);
                        $stmt2->execute([$app['job_id']]);
                    }
                    
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Job status updated to {$job_status} for application {$application_id}");
                    $_SESSION['success_message'] = 'Job status updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update job status.';
                }
            }
        }
        
        $this->redirect('index.php?page=dashboard');
    }
    
    private function completeJobStatusUpdate($application_id, $job_id) {
        // Update the job status to completed
        if (updateApplicationJobStatus($this->pdo, $application_id, 'completed')) {
            // Also update the job status
            $sql2 = "UPDATE jobs SET status = 'completed' WHERE id = ?";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([$job_id]);
            
            logAuditAction($this->pdo, $_SESSION['user_id'], "Job status updated to completed for application {$application_id}");
            return true;
        }
        return false;
    }
    
    public function acceptOffer() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can accept offers.';
            $this->redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
            $offer_id = $_POST['offer_id'];
            
            $application_id = acceptOffer($this->pdo, $offer_id, $_SESSION['user_id']);
            
            if ($application_id) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Offer {$offer_id} accepted, created application {$application_id}");
                $_SESSION['success_message'] = 'Offer accepted successfully! A job has been created and you can now manage it like any other application.';
            } else {
                $_SESSION['error_message'] = 'Failed to accept offer. It may have already been accepted or rejected.';
            }
        }
        
        $this->redirect('index.php?page=dashboard');
    }
    
    public function rejectOffer() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can reject offers.';
            $this->redirect('index.php?page=dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
            $offer_id = $_POST['offer_id'];
            
            if (rejectOffer($this->pdo, $offer_id, $_SESSION['user_id'])) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Offer {$offer_id} rejected");
                $_SESSION['success_message'] = 'Offer rejected successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to reject offer. It may have already been processed.';
            }
        }
        
        $this->redirect('index.php?page=dashboard');
    }
    
    public function enterPaymentInfo() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can enter payment information.';
            $this->redirect('index.php?page=dashboard');
        }
        
        $application_id = $_GET['application_id'] ?? $_POST['application_id'] ?? null;
        if (!$application_id) {
            $_SESSION['error_message'] = 'Application ID is required.';
            $this->redirect('index.php?page=dashboard');
        }
        
        // Verify the application belongs to this freelancer and get job details
        $sql = "SELECT a.*, j.title as job_title, j.budget as job_budget, j.client_id 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                WHERE a.id = ? AND a.freelancer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$application_id, $_SESSION['user_id']]);
        $application = $stmt->fetch();
        
        if (!$application) {
            $_SESSION['error_message'] = 'Application not found or unauthorized.';
            $this->redirect('index.php?page=dashboard');
        }
        
        $errors = [];
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_info'])) {
            $payment_method = trim($_POST['payment_method'] ?? '');
            $payment_account_info = trim($_POST['payment_account_info'] ?? '');
            
            // Validation
            if (empty($payment_method)) {
                $errors[] = 'Payment method is required.';
            } elseif (!in_array($payment_method, ['paypal', 'bank_transfer', 'online'])) {
                $errors[] = 'Invalid payment method.';
            }
            
            if (empty($payment_account_info)) {
                if ($payment_method == 'paypal') {
                    $errors[] = 'PayPal email is required.';
                } elseif ($payment_method == 'bank_transfer') {
                    $errors[] = 'Bank account details are required.';
                }
                // 'online' doesn't require account info
            }
            
            if (empty($errors)) {
                // Update application with payment info
                $sql = "UPDATE applications SET payment_method = ?, payment_account_info = ? WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                if ($stmt->execute([$payment_method, $payment_account_info, $application_id])) {
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Payment info entered for application {$application_id}");
                    
                    // If there's a pending status update, complete it
                    if (isset($_SESSION['pending_job_status_update']) && 
                        $_SESSION['pending_job_status_update']['application_id'] == $application_id) {
                        $job_status = $_SESSION['pending_job_status_update']['job_status'];
                        unset($_SESSION['pending_job_status_update']);
                        
                        if ($job_status == 'completed') {
                            if ($this->completeJobStatusUpdate($application_id, $application['job_id'])) {
                                $_SESSION['success_message'] = 'Payment information saved and job marked as completed!';
                            } else {
                                $_SESSION['success_message'] = 'Payment information saved. Job status update failed.';
                            }
                        }
                    } else {
                        $_SESSION['success_message'] = 'Payment information saved successfully!';
                    }
                    
                    $this->redirect('index.php?page=dashboard');
                    return;
                } else {
                    $errors[] = 'Failed to save payment information.';
                }
            }
        }
        
        $this->setPageTitle('Enter Payment Information');
        $this->setData('application', $application);
        $this->setData('errors', $errors);
        $this->render('freelancer_payment_info');
    }
}
?>

