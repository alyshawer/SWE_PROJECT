<?php
require_once __DIR__ . '/UserModel.php';

// UML: Freelancer class extends User (Model)
class FreelancerModel extends UserModel {
    private $skills;
    private $rating;
    private $totalEarned;
    private $completedProjects;
    private $hourlyRate;
    private $portfolioLink;
    private $bio;
    private $availability;
    
    public function __construct($id, $username, $email, $password, $role, $isActive, $skills = null, $rating = 0, $totalEarned = 0, $completedProjects = 0, $hourlyRate = null, $portfolioLink = null, $bio = null, $availability = 'available') {
        parent::__construct($id, $username, $email, $password, $role, $isActive);
        $this->skills = $skills;
        $this->rating = $rating;
        $this->totalEarned = $totalEarned;
        $this->completedProjects = $completedProjects;
        $this->hourlyRate = $hourlyRate;
        $this->portfolioLink = $portfolioLink;
        $this->bio = $bio;
        $this->availability = $availability;
    }
    
    // UML: +submitBid()
    public function submitBid($pdo, $jobId, $proposal, $completionTime = null) {
        // Check if already applied
        $sql = "SELECT id FROM applications WHERE job_id = ? AND freelancer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jobId, $this->id]);
        if ($stmt->fetch()) {
            return false; // Already applied
        }
        
        $sql = "INSERT INTO applications (job_id, freelancer_id, proposal, completion_time, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$jobId, $this->id, $proposal, $completionTime]);
    }
    
    // UML: +updatePortfolio()
    public function updatePortfolio($pdo, $skills, $pastProjects, $portfolioLink, $cvFilename, $bio, $hourlyRate) {
        $sql = "INSERT INTO freelancer_profiles (user_id, skills, past_projects, portfolio_link, cv_filename, bio, hourly_rate) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                skills = ?, past_projects = ?, portfolio_link = ?, cv_filename = ?, bio = ?, hourly_rate = ?, updated_at = NOW()";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $this->id, $skills, $pastProjects, $portfolioLink, $cvFilename, $bio, $hourlyRate,
            $skills, $pastProjects, $portfolioLink, $cvFilename, $bio, $hourlyRate
        ]);
        
        if ($result) {
            $this->skills = $skills;
            $this->portfolioLink = $portfolioLink;
            $this->bio = $bio;
            $this->hourlyRate = $hourlyRate;
        }
        
        return $result;
    }
    
    // UML: +withdrawEarnings()
    public function withdrawEarnings($pdo, $amount) {
        if ($amount > $this->totalEarned) {
            return false; // Cannot withdraw more than earned
        }
        
        // Update totalEarned (in real system, this would interface with payment gateway)
        $sql = "UPDATE freelancer_profiles SET totalEarned = totalEarned - ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$amount, $this->id])) {
            $this->totalEarned -= $amount;
            return true;
        }
        return false;
    }
    
    // UML: +acceptProject()
    public function acceptProject($pdo, $applicationId) {
        $sql = "UPDATE applications SET status = 'accepted', job_status = 'in_progress', started_at = NOW() 
                WHERE id = ? AND freelancer_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$applicationId, $this->id]);
    }
    
    // Get freelancer's applications
    public function getApplications($pdo) {
        $sql = "SELECT a.*, j.title as job_title, j.description as job_description, j.budget as job_budget,
                       u.name as client_name, u.email as client_email
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                JOIN users u ON j.client_id = u.id 
                WHERE a.freelancer_id = ? 
                ORDER BY a.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    // Get freelancer's ratings
    public function getRatings($pdo) {
        $sql = "SELECT r.*, u.name as client_name, j.title as job_title
                FROM ratings r 
                JOIN users u ON r.client_id = u.id 
                JOIN applications a ON r.application_id = a.id 
                JOIN jobs j ON a.job_id = j.id 
                WHERE r.freelancer_id = ? 
                ORDER BY r.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    // Get average rating
    public function getAverageRating($pdo) {
        $sql = "SELECT AVG(rating) as average_rating, COUNT(*) as total_ratings 
                FROM ratings 
                WHERE freelancer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
        $result = $stmt->fetch();
        if ($result && $result['average_rating']) {
            $this->rating = round($result['average_rating'], 2);
        }
        return $result;
    }
    
    // Method to update earnings when payment is completed
    public function addEarnings($pdo, $amount) {
        $sql = "UPDATE freelancer_profiles SET totalEarned = totalEarned + ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$amount, $this->id])) {
            $this->totalEarned += $amount;
            return true;
        }
        return false;
    }
    
    // Method to increment completed projects
    public function incrementCompletedProjects($pdo) {
        $sql = "UPDATE freelancer_profiles SET completedProjects = completedProjects + 1 WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$this->id])) {
            $this->completedProjects++;
            return true;
        }
        return false;
    }
    
    // Update availability
    public function updateAvailability($pdo, $availability) {
        $sql = "UPDATE freelancer_profiles SET availability = ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$availability, $this->id])) {
            $this->availability = $availability;
            return true;
        }
        return false;
    }
    
    // Getters
    public function getSkills() { return $this->skills; }
    public function getRating() { return $this->rating; }
    public function getTotalEarned() { return $this->totalEarned; }
    public function getCompletedProjects() { return $this->completedProjects; }
    public function getHourlyRate() { return $this->hourlyRate; }
    public function getPortfolioLink() { return $this->portfolioLink; }
    public function getBio() { return $this->bio; }
    public function getAvailability() { return $this->availability; }
    
    // Static method to create FreelancerModel from database
    public static function loadFromDatabase($pdo, $userId) {
        $sql = "SELECT u.*, fp.skills, fp.hourly_rate, fp.portfolio_link, fp.bio, fp.totalEarned, 
                       fp.completedProjects, fp.availability
                FROM users u 
                LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id 
                WHERE u.id = ? AND u.type = 'freelancer'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $data = $stmt->fetch();
        
        if ($data) {
            // Get average rating
            $ratingSql = "SELECT AVG(rating) as avg_rating FROM ratings WHERE freelancer_id = ?";
            $ratingStmt = $pdo->prepare($ratingSql);
            $ratingStmt->execute([$userId]);
            $ratingData = $ratingStmt->fetch();
            $rating = $ratingData['avg_rating'] ?? 0;
            
            return new FreelancerModel(
                $data['id'],
                $data['username'],
                $data['email'],
                $data['password'],
                $data['type'],
                $data['isActive'] ?? true,
                $data['skills'],
                $rating,
                $data['totalEarned'] ?? 0,
                $data['completedProjects'] ?? 0,
                $data['hourly_rate'],
                $data['portfolio_link'],
                $data['bio'],
                $data['availability'] ?? 'available'
            );
        }
        return null;
    }
}
?>

