<?php
// Database helper functions - moved from includes/db.php

// Convenience: get PDO from provided variable or Database singleton
require_once __DIR__ . '/../core/Database.php';
if (!function_exists('db_get_pdo')) {
    function db_get_pdo($maybe = null) {
        if ($maybe instanceof PDO) return $maybe;
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) return $pdo;
        return Database::getInstance()->getConnection();
    }
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

function validateUsername($username) {
    // 3-20 characters, alphanumeric and underscore only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePhone($phone) {
    // Basic phone validation - digits, spaces, dashes, parentheses, plus
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone);
}

function validateName($name) {
    // 2-50 characters, letters, spaces, hyphens, apostrophes
    return preg_match('/^[a-zA-Z\s\-\']{2,50}$/', $name);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to insert a new user with hashed password
function insertUser($pdo, $username, $email, $password, $type, $name, $phone = null) {
    $pdo = db_get_pdo($pdo);
    $hashedPassword = hashPassword($password);
    $sql = "INSERT INTO users (username, email, password, type, name, phone) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$username, $email, $hashedPassword, $type, $name, $phone]);
}

// Function to check user login with password verification
function checkUser($pdo, $email, $password) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        return $user;
    }
    return false;
}

// Simple function to get all jobs
function getJobs($pdo) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM jobs ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Simple function to insert job
function insertJob($pdo, $title, $description, $budget, $client_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$title, $description, $budget, $client_id]);
}

// Simple function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Simple function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}

// Simple function to get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'type' => $_SESSION['type'],
            'name' => $_SESSION['name']
        ];
    }
    return null;
}

// Admin functions
function isAdmin() {
    return isset($_SESSION['type']) && $_SESSION['type'] == 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

function getAllUsers($pdo) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function deleteUser($pdo, $user_id) {
    // Check if user is deletable
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT is_deletable FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_deletable']) {
        return false;
    }
    
    // Delete user and their jobs
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_id]);
}

function deleteJob($pdo, $job_id, $user_id = null) {
    $pdo = db_get_pdo($pdo);
    $sql = "DELETE FROM jobs WHERE id = ?";
    $params = [$job_id];
    
    // If user_id provided, only allow deletion by job owner or admin
    if ($user_id) {
        $sql .= " AND (client_id = ? OR ? IN (SELECT id FROM users WHERE type = 'admin'))";
        $params[] = $user_id;
        $params[] = $_SESSION['user_id'];
    }
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function getUserJobs($pdo, $user_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function canDeleteJob($pdo, $job_id, $user_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT client_id FROM jobs WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    return $job && ($job['client_id'] == $user_id || isAdmin());
}

// Function to get job details with client information
function getJobWithClient($pdo, $job_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT j.*, u.name as client_name, u.email as client_email, u.username as client_username, u.phone as client_phone, u.created_at as client_joined 
            FROM jobs j 
            JOIN users u ON j.client_id = u.id 
            WHERE j.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    return $stmt->fetch();
}

// Function to apply for a job (now supports optional bid amount)
function applyForJob($pdo, $job_id, $freelancer_id, $proposal, $completion_time = null, $bid_amount = null) {
    $pdo = db_get_pdo($pdo);
    // First check if application already exists
    $sql = "SELECT id FROM applications WHERE job_id = ? AND freelancer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id, $freelancer_id]);
    
    if ($stmt->fetch()) {
        return false; // Application already exists
    }
    
    // Ensure job is active
    $sql = "SELECT status FROM jobs WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    if (!$job || ($job['status'] ?? 'active') !== 'active') {
        return false; // cannot apply to non-active jobs
    }

    // Normalize bid amount if provided
    if ($bid_amount !== null) {
        if ($bid_amount === '') $bid_amount = null;
        else {
            // allow numeric string input
            if (!is_numeric($bid_amount)) {
                $bid_amount = null;
            } else {
                $bid_amount = number_format((float)$bid_amount, 2, '.', '');
            }
        }
    }

    // Insert new application (store bid_amount if provided)
    $sql = "INSERT INTO applications (job_id, freelancer_id, proposal, completion_time, bid_amount, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$job_id, $freelancer_id, $proposal, $completion_time, $bid_amount]);
}

// Freelancer profile functions
function createFreelancerProfile($pdo, $user_id, $skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate) {
    $pdo = db_get_pdo($pdo);
    $sql = "INSERT INTO freelancer_profiles (user_id, skills, past_projects, portfolio_link, cv_filename, bio, hourly_rate) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_id, $skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate]);
}

function updateFreelancerProfile($pdo, $user_id, $skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate) {
    $pdo = db_get_pdo($pdo);
    $sql = "UPDATE freelancer_profiles SET skills = ?, past_projects = ?, portfolio_link = ?, cv_filename = ?, bio = ?, hourly_rate = ?, updated_at = NOW() WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$skills, $past_projects, $portfolio_link, $cv_filename, $bio, $hourly_rate, $user_id]);
}

function getFreelancerProfile($pdo, $user_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM freelancer_profiles WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getUserWithProfile($pdo, $user_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT u.*, fp.skills, fp.past_projects, fp.portfolio_link, fp.cv_filename, fp.bio, fp.hourly_rate, fp.availability 
            FROM users u 
            LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id 
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Offer functions
function createOffer($pdo, $client_id, $freelancer_id, $job_id, $amount, $message = '') {
    $pdo = db_get_pdo($pdo);
    $sql = "INSERT INTO offers (client_id, freelancer_id, job_id, amount, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$client_id, $freelancer_id, $job_id, $amount, $message])) {
        logAuditAction($pdo, $client_id, "Offer created for freelancer {$freelancer_id} on job {$job_id}");
        return $pdo->lastInsertId();
    }
    return false;
}

function getOffersForFreelancer($pdo, $freelancer_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT o.*, u.name as client_name, u.email as client_email, u.phone as client_phone 
            FROM offers o 
            JOIN users u ON o.client_id = u.id 
            WHERE o.freelancer_id = ? 
            ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$freelancer_id]);
    return $stmt->fetchAll();
}

function updateOfferStatus($pdo, $offer_id, $status) {
    $pdo = db_get_pdo($pdo);
    $sql = "UPDATE offers SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$status, $offer_id]);
}

// Application management functions
function getApplicationsForJob($pdo, $job_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT a.*, u.name as freelancer_name, u.email as freelancer_email, u.phone as freelancer_phone,
                   fp.skills, fp.hourly_rate, fp.portfolio_link, fp.cv_filename
            FROM applications a 
            JOIN users u ON a.freelancer_id = u.id 
            LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id
            WHERE a.job_id = ? 
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    return $stmt->fetchAll();
}

function updateApplicationStatus($pdo, $application_id, $status) {
    $pdo = db_get_pdo($pdo);
    // When accepting an application, ensure only one accepted per job
    if ($status === 'accepted') {
        // get job_id
        $sql = "SELECT job_id FROM applications WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);
        $row = $stmt->fetch();
        if (!$row) return false;
        $job_id = $row['job_id'];

        // check if another application is already accepted
        $sql = "SELECT id FROM applications WHERE job_id = ? AND status = 'accepted'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$job_id]);
        if ($stmt->fetch()) {
            return false; // another application already accepted
        }

        try {
            $pdo->beginTransaction();

            // accept this application and mark job_status
            $sql = "UPDATE applications SET status = 'accepted', job_status = 'in_progress', started_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$application_id]);

            // set job to in_progress
            $sql2 = "UPDATE jobs SET status = 'in_progress' WHERE id = ?";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$job_id]);

            // reject other pending applications for the job
            $sql3 = "UPDATE applications SET status = 'rejected' WHERE job_id = ? AND id != ? AND status != 'rejected'";
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute([$job_id, $application_id]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return false;
        }
    }

    // For other status changes, simple update
    if ($status === 'rejected') {
        $sql = "UPDATE applications SET status = 'rejected', job_status = 'not_started' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$application_id]);
    }

    if ($status === 'completed') {
        $sql = "UPDATE applications SET status = 'accepted', job_status = 'completed', completed_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute([$application_id]);
        if ($res) {
            // mark job completed
            $sql2 = "SELECT job_id FROM applications WHERE id = ?";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$application_id]);
            $row = $stmt2->fetch();
            if ($row) {
                $sql3 = "UPDATE jobs SET status = 'completed' WHERE id = ?";
                $stmt3 = $pdo->prepare($sql3);
                $stmt3->execute([$row['job_id']]);
            }
        }
        return $res;
    }

    // fallback
    $sql = "UPDATE applications SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$status, $application_id]);
}

/**
 * Reopen a job by rejecting the previously accepted application and
 * setting the job status back to 'active' so the client can reassign.
 */
function reopenApplication($pdo, $application_id, $job_id, $client_id = null) {
    $pdo = db_get_pdo($pdo);
    // Ensure caller is authorized if client_id provided
    if ($client_id) {
        $sql = "SELECT client_id FROM jobs WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        if (!$job || $job['client_id'] != $client_id) {
            return false;
        }
    }

    try {
        // Mark the accepted application as rejected
        $sql = "UPDATE applications SET status = 'rejected', job_status = 'not_started' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$application_id]);

        // Re-open the job
        $sql2 = "UPDATE jobs SET status = 'active' WHERE id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$job_id]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getFreelancerApplications($pdo, $freelancer_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT a.*, j.title as job_title, j.description as job_description, j.budget as job_budget,
                   u.name as client_name, u.email as client_email
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON j.client_id = u.id 
            WHERE a.freelancer_id = ? 
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$freelancer_id]);
    return $stmt->fetchAll();
}

// Get all freelancers with profiles (excluding current user)
function getAllFreelancers($pdo, $exclude_user_id = null) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT u.*, fp.skills, fp.hourly_rate, fp.availability, fp.bio
            FROM users u 
            LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id 
            WHERE u.type = 'freelancer'";
    
    $params = [];
    if ($exclude_user_id) {
        $sql .= " AND u.id != ?";
        $params[] = $exclude_user_id;
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Update user profile
function updateUser($pdo, $user_id, $username, $email, $name, $phone) {
    $pdo = db_get_pdo($pdo);
    $sql = "UPDATE users SET username = ?, email = ?, name = ?, phone = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$username, $email, $name, $phone, $user_id]);
}

// Job status management functions
function updateApplicationJobStatus($pdo, $application_id, $job_status) {
    $pdo = db_get_pdo($pdo);
    $sql = "UPDATE applications SET job_status = ?";
    $params = [$job_status];
    
    if ($job_status == 'in_progress') {
        $sql .= ", started_at = NOW()";
    } elseif ($job_status == 'completed') {
        $sql .= ", completed_at = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $application_id;
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function getApplicationWithJobStatus($pdo, $application_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT a.*, j.title as job_title, j.description as job_description, j.budget as job_budget,
                   u.name as client_name, u.email as client_email
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON j.client_id = u.id 
            WHERE a.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$application_id]);
    return $stmt->fetch();
}

// Rating functions
function createRating($pdo, $application_id, $client_id, $freelancer_id, $rating, $review) {
    $pdo = db_get_pdo($pdo);
    $sql = "INSERT INTO ratings (application_id, client_id, freelancer_id, rating, review, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$application_id, $client_id, $freelancer_id, $rating, $review]);
}

function getFreelancerRatings($pdo, $freelancer_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT r.*, u.name as client_name, j.title as job_title
            FROM ratings r 
            JOIN users u ON r.client_id = u.id 
            JOIN applications a ON r.application_id = a.id 
            JOIN jobs j ON a.job_id = j.id 
            WHERE r.freelancer_id = ? 
            ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$freelancer_id]);
    return $stmt->fetchAll();
}

function getFreelancerAverageRating($pdo, $freelancer_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT AVG(rating) as average_rating, COUNT(*) as total_ratings 
            FROM ratings 
            WHERE freelancer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$freelancer_id]);
    return $stmt->fetch();
}

function hasRatedApplication($pdo, $application_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT id FROM ratings WHERE application_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$application_id]);
    return $stmt->fetch() ? true : false;
}

function getApplicationsForRating($pdo, $client_id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT a.*, j.title as job_title, u.name as freelancer_name, u.email as freelancer_email
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON a.freelancer_id = u.id 
            WHERE j.client_id = ? AND a.status = 'accepted' AND a.job_status = 'completed'
            AND a.id NOT IN (SELECT application_id FROM ratings WHERE application_id IS NOT NULL)
            ORDER BY a.completed_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id]);
    return $stmt->fetchAll();
}

// Report functions (UML: Report class)
function createReport($pdo, $type, $data, $generatedBy) {
    $pdo = db_get_pdo($pdo);
    $jsonData = json_encode($data);
    $sql = "INSERT INTO reports (type, data, generatedBy) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$type, $jsonData, $generatedBy])) {
        return $pdo->lastInsertId();
    }
    return false;
}

function getReports($pdo, $generatedBy = null) {
    $pdo = db_get_pdo($pdo);
    if ($generatedBy) {
        $sql = "SELECT * FROM reports WHERE generatedBy = ? ORDER BY createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$generatedBy]);
    } else {
        $sql = "SELECT * FROM reports ORDER BY createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getReportById($pdo, $id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT * FROM reports WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Audit Log functions (UML: AuditLog class)
function logAuditAction($pdo, $userId, $action, $ipAddress = null) {
    $pdo = db_get_pdo($pdo);
    if ($ipAddress === null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    $sql = "INSERT INTO audit_logs (userId, action, ipAddress) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $action, $ipAddress]);
}

function getAuditLogs($pdo, $userId = null, $limit = 100) {
    $pdo = db_get_pdo($pdo);
    // Ensure limit is an integer to avoid SQL syntax issues with some MariaDB versions
    $limitInt = max(1, (int)$limit);
    if ($userId) {
        $sql = "SELECT al.*, u.username, u.name FROM audit_logs al 
                LEFT JOIN users u ON al.userId = u.id 
                WHERE al.userId = ? ORDER BY al.timestamp DESC LIMIT " . $limitInt;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    } else {
        $sql = "SELECT al.*, u.username, u.name FROM audit_logs al 
                LEFT JOIN users u ON al.userId = u.id 
                ORDER BY al.timestamp DESC LIMIT " . $limitInt;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Dispute functions (UML: Dispute class)
function createDispute($pdo, $projectId, $raisedBy, $against, $reason) {
    $pdo = db_get_pdo($pdo);
    $sql = "INSERT INTO disputes (projectId, raisedBy, against, reason, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$projectId, $raisedBy, $against, $reason])) {
        logAuditAction($pdo, $raisedBy, "Dispute raised for project {$projectId}");
        return $pdo->lastInsertId();
    }
    return false;
}

function getDisputes($pdo, $status = null) {
    $pdo = db_get_pdo($pdo);
    if ($status) {
        $sql = "SELECT d.*, j.title as project_title, u1.name as raised_by_name, u2.name as against_name 
                FROM disputes d 
                JOIN jobs j ON d.projectId = j.id 
                JOIN users u1 ON d.raisedBy = u1.id 
                JOIN users u2 ON d.against = u2.id 
                WHERE d.status = ? 
                ORDER BY d.createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
    } else {
        $sql = "SELECT d.*, j.title as project_title, u1.name as raised_by_name, u2.name as against_name 
                FROM disputes d 
                JOIN jobs j ON d.projectId = j.id 
                JOIN users u1 ON d.raisedBy = u1.id 
                JOIN users u2 ON d.against = u2.id 
                ORDER BY d.createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getDisputeById($pdo, $id) {
    $pdo = db_get_pdo($pdo);
    $sql = "SELECT d.*, j.title as project_title, u1.name as raised_by_name, u2.name as against_name 
            FROM disputes d 
            JOIN jobs j ON d.projectId = j.id 
            JOIN users u1 ON d.raisedBy = u1.id 
            JOIN users u2 ON d.against = u2.id 
            WHERE d.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function resolveDispute($pdo, $disputeId, $resolvedBy) {
    $pdo = db_get_pdo($pdo);
    $sql = "UPDATE disputes SET status = 'resolved', resolvedBy = ?, resolvedAt = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$resolvedBy, $disputeId])) {
        logAuditAction($pdo, $resolvedBy, "Dispute {$disputeId} resolved");
        return true;
    }
    return false;
}

// Payment functions (UML: Payment class)
function createPayment($pdo, $projectId, $clientId, $freelancerId, $amount, $paymentMethod = 'online') {
    $sql = "INSERT INTO payments (projectId, clientId, freelancerId, amount, status, paymentMethod) 
            VALUES (?, ?, ?, ?, 'pending', ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$projectId, $clientId, $freelancerId, $amount, $paymentMethod])) {
        return $pdo->lastInsertId();
    }
    return false;
}

function getPayments($pdo, $userId = null, $role = null) {
    if ($userId && $role == 'client') {
        $sql = "SELECT p.*, j.title as project_title, u.name as freelancer_name 
                FROM payments p 
                JOIN jobs j ON p.projectId = j.id 
                JOIN users u ON p.freelancerId = u.id 
                WHERE p.clientId = ? 
                ORDER BY p.createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    } elseif ($userId && $role == 'freelancer') {
        $sql = "SELECT p.*, j.title as project_title, u.name as client_name 
                FROM payments p 
                JOIN jobs j ON p.projectId = j.id 
                JOIN users u ON p.clientId = u.id 
                WHERE p.freelancerId = ? 
                ORDER BY p.createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    } else {
        $sql = "SELECT p.*, j.title as project_title, u1.name as client_name, u2.name as freelancer_name 
                FROM payments p 
                JOIN jobs j ON p.projectId = j.id 
                JOIN users u1 ON p.clientId = u1.id 
                JOIN users u2 ON p.freelancerId = u2.id 
                ORDER BY p.createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getPaymentById($pdo, $id) {
    $sql = "SELECT * FROM payments WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function completePayment($pdo, $paymentId, $transactionId = null) {
    // Compute platform fee (default 10%) and update records atomically
    $pdo = db_get_pdo($pdo);
    $payment = getPaymentById($pdo, $paymentId);
    if (!$payment) return false;

    $amount = (float)$payment['amount'];
    $feeRate = 0.10; // TODO: read from config
    $platformFee = round($amount * $feeRate, 2);
    $freelancerNet = round($amount - $platformFee, 2);

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE payments SET status = 'completed', transactionId = ?, completedAt = NOW(), platform_fee = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$transactionId, $platformFee, $paymentId]);

        // Update freelancer earnings (add net amount)
        $sql2 = "UPDATE freelancer_profiles SET totalEarned = COALESCE(totalEarned,0) + ? WHERE user_id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$freelancerNet, $payment['freelancerId']]);

        // Increment platform revenue stored in system_config under key 'platform_revenue'
        $sql3 = "SELECT config_value FROM system_config WHERE config_key = 'platform_revenue' LIMIT 1";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute();
        $row = $stmt3->fetch();
        if ($row && is_numeric($row['config_value'])) {
            $newTotal = round((float)$row['config_value'] + $platformFee, 2);
            $sql4 = "UPDATE system_config SET config_value = ?, updated_at = NOW() WHERE config_key = 'platform_revenue'";
            $stmt4 = $pdo->prepare($sql4);
            $stmt4->execute([$newTotal]);
        } else {
            $sql4 = "INSERT INTO system_config (config_key, config_value, updated_at) VALUES ('platform_revenue', ?, NOW())";
            $stmt4 = $pdo->prepare($sql4);
            $stmt4->execute([$platformFee]);
        }

        $pdo->commit();

        logAuditAction($pdo, $payment['clientId'], "Payment completed for project {$payment['projectId']}; fee={$platformFee}");
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

// Client profile functions
function getClientProfile($pdo, $user_id) {
    $sql = "SELECT u.*, cp.companyName, cp.totalSpent, cp.postedProjects 
            FROM users u 
            LEFT JOIN client_profiles cp ON u.id = cp.user_id 
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function createOrUpdateClientProfile($pdo, $user_id, $companyName) {
    $sql = "INSERT INTO client_profiles (user_id, companyName) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE companyName = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_id, $companyName, $companyName]);
}

// Admin profile functions
function getAdminProfile($pdo, $user_id) {
    $sql = "SELECT u.*, ap.permissionLevel, ap.lastLogin, ap.actionsLog 
            FROM users u 
            LEFT JOIN admin_profiles ap ON u.id = ap.user_id 
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function updateAdminLastLogin($pdo, $user_id) {
    $sql = "INSERT INTO admin_profiles (user_id, lastLogin) VALUES (?, NOW())
            ON DUPLICATE KEY UPDATE lastLogin = NOW()";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_id]);
}

// Category functions (for Admin manageCategories)
function getCategories($pdo) {
    $sql = "SELECT * FROM categories ORDER BY name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function createCategory($pdo, $name, $description) {
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$name, $description]);
}

function updateCategory($pdo, $id, $name, $description) {
    $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$name, $description, $id]);
}

function deleteCategory($pdo, $id) {
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}
?>

