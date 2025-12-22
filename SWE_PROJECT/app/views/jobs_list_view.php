<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <h2>Available Jobs</h2>
    
    <?php showMessage(); ?>
    
    <?php if (empty($jobs)): ?>
        <div class="card" style="text-align: center; padding: 50px;">
            <h3>No jobs available at the moment</h3>
            <p>Be the first to post a job!</p>
            <?php if (isLoggedIn() && $_SESSION['type'] == 'client'): ?>
                <a href="<?php echo base_url('index.php?page=post_job'); ?>" class="btn">Post a Job</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="jobs-grid">
        <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <div class="job-card-body">
                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                    <p class="muted small">Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?> | Budget: $<?php echo number_format($job['budget'], 2); ?></p>
                    <p><?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 220))); ?><?php echo (strlen($job['description'])>220)?'...':''; ?></p>
                </div>
                <div class="job-card-footer">
                    <div>
                        <a href="<?php echo base_url('index.php?page=job&id=' . $job['id']); ?>" class="btn btn-sm">View Details</a>
                        <?php if (isLoggedIn() && $_SESSION['type'] == 'client' && $job['client_id'] == $_SESSION['user_id']): ?>
                            <form method="POST" action="<?php echo base_url('index.php?page=dashboard&action=delete_job'); ?>" style="display: inline;">
                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this job?')">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.jobs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;margin-top:20px}
.job-card{background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06);overflow:hidden;display:flex;flex-direction:column}
.job-card-body{padding:18px}
.job-card-footer{padding:12px 18px;border-top:1px solid #f1f1f1;display:flex;justify-content:space-between;align-items:center}
.job-card h3{margin:0 0 8px;font-size:1.1rem}
.muted{color:#6c757d}
.small{font-size:0.85rem}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

