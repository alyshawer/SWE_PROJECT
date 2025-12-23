<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/db_functions.php';
require_once __DIR__ . '/../../helpers/view_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Freelancer Job Board</title>
    <link rel="stylesheet" href="<?php echo base_url('css/style.css'); ?>">
</head>
<body>
    <div class="header">
        <h1>Freelancer Job Board</h1>
        <p>Connect freelancers with clients</p>
    </div>
    
    <div class="nav">
        <a href="<?php echo base_url('index.php'); ?>">Home</a>
        <a href="<?php echo base_url('index.php?page=jobs'); ?>">Browse Jobs</a>
        
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo base_url('index.php?page=dashboard'); ?>">Dashboard</a>
            
            <?php if ($_SESSION['type'] == 'freelancer'): ?>
                <!-- Portfolio is accessible from dashboard -->
            <?php endif; ?>
            
            <?php if ($_SESSION['type'] == 'client'): ?>
                <a href="<?php echo base_url('index.php?page=post_job'); ?>">Post Job</a>
                <a href="<?php echo base_url('index.php?page=freelancers'); ?>">Browse Freelancers</a>
                <a href="<?php echo base_url('index.php?page=payments'); ?>">Payments</a>
            <?php endif; ?>
            
            <?php if (isAdmin()): ?>
                <a href="<?php echo base_url('index.php?page=admin'); ?>">Admin Panel</a>
                <a href="<?php echo base_url('index.php?page=reports'); ?>">Reports</a>
                <a href="<?php echo base_url('index.php?page=payments'); ?>">Payments</a>
                <a href="<?php echo base_url('index.php?page=audit_logs'); ?>">Audit Logs</a>
            <?php elseif (in_array($_SESSION['type'], ['client', 'freelancer'])): ?>
            <?php endif; ?>
            
            <a href="<?php echo base_url('index.php?page=profile'); ?>">My Profile</a>
            <a href="<?php echo base_url('index.php?page=logout'); ?>">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        <?php else: ?>
            <a href="<?php echo base_url('index.php?page=register'); ?>">Register</a>
            <a href="<?php echo base_url('index.php?page=login'); ?>">Login</a>
        <?php endif; ?>
    </div>

