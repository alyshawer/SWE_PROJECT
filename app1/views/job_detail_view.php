<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <?php showMessage(); ?>
    
    <?php if (isset($job)): ?>
        <div class="card">
            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
            <p class="budget">Budget: $<?php echo number_format($job['budget'], 2); ?></p>
            <p><strong>Description:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            <p><strong>Posted by:</strong> <?php echo htmlspecialchars($job['client_name']); ?></p>
            <p><strong>Posted on:</strong> <?php echo date('M j, Y', strtotime($job['created_at'])); ?></p>
        </div>
        
        <?php if (isLoggedIn() && $_SESSION['type'] == 'freelancer'): ?>
            <div class="card">
                <h3>Apply for this Job</h3>
                <form method="POST" action="<?php echo base_url('index.php?page=apply_job'); ?>">
                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                    <div class="form-group">
                        <label>Proposal:</label>
                        <textarea name="proposal" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Estimated Completion Time:</label>
                        <input type="text" name="completion_time" placeholder="e.g., 2 weeks">
                    </div>
                    <div class="form-group">
                        <label>Your Bid (USD):</label>
                        <input type="number" name="bid_amount" step="0.01" min="0.01" placeholder="e.g., 250.00" required>
                    </div>
                    <button type="submit" name="apply_job" class="btn">Submit Application</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (isLoggedIn() && $_SESSION['type'] == 'client' && $job['client_id'] == $_SESSION['user_id'] && !empty($applications)): ?>
            <div class="card">
                <h3>Applications (<?php echo count($applications); ?>)</h3>
                <?php foreach ($applications as $application): ?>
                    <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">
                        <p><strong>Freelancer:</strong> <?php echo htmlspecialchars($application['freelancer_name']); ?></p>
                        <p><strong>Proposal:</strong> <?php echo nl2br(htmlspecialchars($application['proposal'])); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($application['status']); ?></p>
                        <?php if (isset($application['bid_amount']) && $application['bid_amount'] !== null): ?>
                            <p><strong>Bid:</strong> $<?php echo number_format($application['bid_amount'], 2); ?></p>
                        <?php endif; ?>
                        <?php if ($application['status'] == 'pending'): ?>
                            <div style="margin-top:10px;">
                                <form method="POST" action="<?php echo base_url('index.php?page=job&id=' . $job['id']); ?>" style="display:inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="status" value="accepted">
                                    <button type="submit" name="update_application_status" class="btn btn-success btn-sm" onclick="return confirm('Accept this application?')">Accept</button>
                                </form>
                                <form method="POST" action="<?php echo base_url('index.php?page=job&id=' . $job['id']); ?>" style="display:inline; margin-left:8px;">
                                    <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" name="update_application_status" class="btn btn-danger btn-sm" onclick="return confirm('Reject this application?')">Reject</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-error">Job not found.</div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="<?php echo base_url('index.php?page=jobs'); ?>" class="btn">Back to Jobs</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

