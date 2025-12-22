<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class JobController extends BaseController {
    
    public function index() {
        $jobs = getJobs($this->pdo);
        $this->setPageTitle('Browse Jobs');
        $this->setData('jobs', $jobs);
        $this->render('jobs_list');
    }
    
    public function show($id) {
        $job = getJobWithClient($this->pdo, $id);
        if (!$job) {
            $_SESSION['error_message'] = 'Job not found.';
            $this->redirect('index.php?page=jobs');
        }
        
        $applications = [];
        if (isset($_SESSION['user_id']) && $_SESSION['type'] == 'client' && $job['client_id'] == $_SESSION['user_id']) {
            $applications = getApplicationsForJob($this->pdo, $id);
        }
        
        $this->setPageTitle('Job Details');
        $this->setData('job', $job);
        $this->setData('applications', $applications);
        $this->render('job_detail');
    }
    
    public function create() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'client') {
            $_SESSION['error_message'] = 'Only clients can post jobs.';
            $this->redirect('index.php?page=dashboard');
        }
        
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $budget = $_POST['budget'] ?? 0;
            
            if (empty($title)) {
                $errors[] = "Job title is required.";
            }
            
            if (empty($description)) {
                $errors[] = "Job description is required.";
            }
            
            if (empty($budget) || $budget <= 0) {
                $errors[] = "Valid budget is required.";
            }
            
            if (empty($errors)) {
                if (insertJob($this->pdo, $title, $description, $budget, $_SESSION['user_id'])) {
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Job posted: {$title}");
                    $_SESSION['success_message'] = 'Job posted successfully!';
                    $this->redirect('index.php?page=dashboard');
                } else {
                    $errors[] = "Failed to post job. Please try again.";
                }
            }
        }
        
        $this->setPageTitle('Post Job');
        $this->setData('errors', $errors);
        $this->render('job_create');
    }
    
    public function apply() {
        $this->requireLogin();
        
        if ($_SESSION['type'] != 'freelancer') {
            $_SESSION['error_message'] = 'Only freelancers can apply for jobs.';
            $this->redirect('index.php?page=jobs');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $job_id = $_POST['job_id'] ?? 0;
            $proposal = trim($_POST['proposal'] ?? '');
            $completion_time = trim($_POST['completion_time'] ?? '');
            $bid_amount = $_POST['bid_amount'] ?? null;
            
            if (empty($proposal)) {
                $_SESSION['error_message'] = 'Proposal is required.';
            } else {
                if (applyForJob($this->pdo, $job_id, $_SESSION['user_id'], $proposal, $completion_time, $bid_amount)) {
                    logAuditAction($this->pdo, $_SESSION['user_id'], "Applied for job {$job_id}");
                    $_SESSION['success_message'] = 'Application submitted successfully!';
                } else {
                    $_SESSION['error_message'] = 'You have already applied for this job.';
                }
            }
            
            $this->redirect('index.php?page=job&id=' . $job_id);
        }
    }

    public function updateApplicationStatus() {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
            $application_id = $_POST['application_id'];
            $status = $_POST['status'];

            // Call helper to update status
            if (updateApplicationStatus($this->pdo, $application_id, $status)) {
                logAuditAction($this->pdo, $_SESSION['user_id'], "Updated application {$application_id} to {$status}");
                $_SESSION['success_message'] = 'Application status updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update application status.';
            }

            // Redirect back to job page if job id provided via GET or POST
            $job_id = $_GET['id'] ?? $_POST['job_id'] ?? null;
            if ($job_id) {
                $this->redirect('index.php?page=job&id=' . $job_id);
            } else {
                $this->redirect('index.php?page=dashboard');
            }
        }
        $this->redirect('index.php?page=dashboard');
    }
}
?>

