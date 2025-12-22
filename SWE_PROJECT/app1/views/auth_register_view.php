<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="form-container">
    <h2>Create Account</h2>
    <p>Join as a <?php echo isset($user_type) && $user_type ? ucfirst($user_type) : 'Freelancer or Client'; ?></p>
    
    <?php showMessage(); ?>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo base_url('index.php?page=register'); ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label>Account Type:</label>
            <select name="type" required>
                <option value="">Select Type</option>
                <option value="freelancer" <?php echo (isset($user_type) && $user_type == 'freelancer') ? 'selected' : ''; ?>>Freelancer</option>
                <option value="client" <?php echo (isset($user_type) && $user_type == 'client') ? 'selected' : ''; ?>>Client</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
            <small>Must be at least 8 characters with uppercase, lowercase, and number</small>
        </div>
        
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label>Phone (Optional):</label>
            <input type="text" name="phone">
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <p>Already have an account? <a href="<?php echo base_url('index.php?page=login'); ?>">Login here</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

