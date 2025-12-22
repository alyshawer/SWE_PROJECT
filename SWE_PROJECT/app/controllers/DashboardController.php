<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class DashboardController extends BaseController {
    
    public function index() {
        $this->requireLogin();
        
        $user = $this->getCurrentUser();
        $data = [];
        
        if ($user['type'] == 'client') {
            $data['user_jobs'] = getUserJobs($this->pdo, $user['id']);
        } elseif ($user['type'] == 'freelancer') {
            $data['applications'] = getFreelancerApplications($this->pdo, $user['id']);
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
            if (deleteJob($this->pdo, $job_id, $_SESSION['user_id'])) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Job {$job_id} deleted");
                $_SESSION['success_message'] = 'Job deleted successfully!';
            } else {
                $_SESSION['error_message'] = 'Cannot delete this job.';
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
}
?>

