<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h2>Welcome to Freelancer Job Board</h2>
            <p>Connect talented freelancers with clients looking for quality work</p>
            <a href="<?php echo base_url('index.php?page=register&type=freelancer'); ?>" class="btn">Join as Freelancer</a>
            <a href="<?php echo base_url('index.php?page=register&type=client'); ?>" class="btn">Join as Client</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="features">
        <div class="feature">
            <h3>For Freelancers</h3>
            <p>Find exciting projects, build your portfolio, and grow your career</p>
        </div>
        <div class="feature">
            <h3>For Clients</h3>
            <p>Post jobs, find talented freelancers, and get quality work done</p>
        </div>
        <div class="feature">
            <h3>Secure Platform</h3>
            <p>Safe payments and professional environment</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

