<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="form-container">
    <h2>Login</h2>
    
    <?php showMessage(); ?>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">Registration successful! Please login.</div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo base_url('index.php?page=login'); ?>">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <p>Don't have an account? <a href="<?php echo base_url('index.php?page=register'); ?>">Register here</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

