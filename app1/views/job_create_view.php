<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="form-container">
    <h2>Post a New Job</h2>
    
    <?php showMessage(); ?>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo base_url('index.php?page=post_job'); ?>">
        <div class="form-group">
            <label>Job Title:</label>
            <input type="text" name="title" required>
        </div>
        
        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" required></textarea>
        </div>
        
        <div class="form-group">
            <label>Budget ($):</label>
            <input type="number" name="budget" step="0.01" min="0" required>
        </div>
        
        <button type="submit" class="btn">Post Job</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="<?php echo base_url('index.php?page=dashboard'); ?>" class="btn">Back to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

