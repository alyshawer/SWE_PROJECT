<?php
require_once __DIR__ . '/UserModel.php';

// UML: Client class extends User (Model)
class ClientModel extends UserModel {
    private $companyName;
    private $totalSpent;
    private $postedProjects;
    
    public function __construct($id, $username, $email, $password, $role, $isActive, $companyName = null, $totalSpent = 0, $postedProjects = 0) {
        parent::__construct($id, $username, $email, $password, $role, $isActive);
        $this->companyName = $companyName;
        $this->totalSpent = $totalSpent;
        $this->postedProjects = $postedProjects;
    }
    
    // UML: +postProject()
    public function postProject($pdo, $title, $description, $budget) {
        $sql = "INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$title, $description, $budget, $this->id])) {
            // Update postedProjects count
            $this->updatePostedProjects($pdo);
            return true;
        }
        return false;
    }
    
    // UML: +reviewBids()
    public function reviewBids($pdo, $jobId) {
        $sql = "SELECT a.*, u.name as freelancer_name, u.email as freelancer_email, 
                       fp.skills, fp.hourly_rate, fp.portfolio_link
                FROM applications a 
                JOIN users u ON a.freelancer_id = u.id 
                LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id
                WHERE a.job_id = ? AND a.status = 'pending'
                ORDER BY a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jobId]);
        return $stmt->fetchAll();
    }
    
    // UML: +awardProject()
    public function awardProject($pdo, $applicationId) {
        // Update application status to accepted
        $sql = "UPDATE applications SET status = 'accepted' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$applicationId])) {
            // Reject other applications for the same job
            $sql2 = "SELECT job_id FROM applications WHERE id = ?";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$applicationId]);
            $app = $stmt2->fetch();
            if ($app) {
                $sql3 = "UPDATE applications SET status = 'rejected' 
                        WHERE job_id = ? AND id != ? AND status = 'pending'";
                $stmt3 = $pdo->prepare($sql3);
                $stmt3->execute([$app['job_id'], $applicationId]);
            }
            return true;
        }
        return false;
    }
    
    // UML: +makePayment()
    public function makePayment($pdo, $projectId, $freelancerId, $amount, $paymentMethod = 'online') {
        $sql = "INSERT INTO payments (projectId, clientId, freelancerId, amount, status, paymentMethod) 
                VALUES (?, ?, ?, ?, 'pending', ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$projectId, $this->id, $freelancerId, $amount, $paymentMethod])) {
            // Update totalSpent
            $this->updateTotalSpent($pdo, $amount);
            return true;
        }
        return false;
    }
    
    private function updatePostedProjects($pdo) {
        $sql = "UPDATE client_profiles SET postedProjects = postedProjects + 1 WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
    }
    
    private function updateTotalSpent($pdo, $amount) {
        $sql = "UPDATE client_profiles SET totalSpent = totalSpent + ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$amount, $this->id]);
    }
    
    // Getters
    public function getCompanyName() { return $this->companyName; }
    public function getTotalSpent() { return $this->totalSpent; }
    public function getPostedProjects() { return $this->postedProjects; }
}
?>

