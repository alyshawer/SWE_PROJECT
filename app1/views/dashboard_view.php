<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>Account Type: <?php echo ucfirst($user['type']); ?> | Login Time: <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
    </div>
    
    <?php showMessage(); ?>
    
    <div class="dashboard-grid">
        <?php if ($user['type'] == 'client'): ?>
            <div class="dashboard-card">
                <h3>My Jobs (<?php echo count($data['user_jobs'] ?? []); ?>)</h3>
                    <?php if (empty($data['user_jobs'])): ?>
                    <p>No jobs posted yet</p>
                    <a href="index.php?page=post_job" class="btn">Post Your First Job</a>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($data['user_jobs'] as $job): ?>
                            <div class="job-card" style="margin: 10px 0; padding: 15px;">
                                <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                                <p class="budget">$<?php echo number_format($job['budget'], 2); ?></p>
                                <p><?php echo date('M j, Y', strtotime($job['created_at'])); ?></p>
                                <form method="POST" action="index.php?page=dashboard&action=delete_job" style="display: inline;">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this job?')">
                                        Delete
                                    </button>
                                </form>
                                <a href="index.php?page=job&id=<?php echo $job['id']; ?>" class="btn btn-sm">View Applications</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="dashboard-card">
                <h3>Find Freelancers</h3>
                <p>Search and send offers to freelancers directly from your dashboard.</p>
                <a href="index.php?page=freelancers" class="btn">Browse Freelancers</a>
            </div>
        <?php endif; ?>
        
        <?php if ($user['type'] == 'freelancer'): ?>
            <div class="dashboard-card">
                <h3>My Portfolio</h3>
                <p>Keep your profile updated to attract more clients</p>
                <a href="index.php?page=dashboard&action=edit_portfolio" class="btn btn-primary" style="margin-top: 10px;">
                    <?php 
                    $profile = $data['profile'] ?? null;
                    echo $profile ? '✏️ Edit Portfolio' : '➕ Create Portfolio';
                    ?>
                </a>
                <?php if ($profile): ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <?php if (!empty($profile['skills'])): ?>
                            <p><strong>Skills:</strong> <?php echo htmlspecialchars(substr($profile['skills'], 0, 100)); ?><?php echo strlen($profile['skills']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($profile['hourly_rate'])): ?>
                            <p><strong>Hourly Rate:</strong> $<?php echo number_format($profile['hourly_rate'], 2); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h3>My Applications (<?php echo count($data['applications'] ?? []); ?>)</h3>
                    <?php if (empty($data['applications'])): ?>
                    <p>No applications submitted yet</p>
                    <a href="index.php?page=jobs" class="btn">Browse Jobs</a>
                <?php else: ?>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach (array_slice($data['applications'], 0, 3) as $application): ?>
                            <div class="application-summary" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                                <h5><?php echo htmlspecialchars($application['job_title']); ?></h5>
                                <p><strong>Client:</strong> <?php echo htmlspecialchars($application['client_name']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo $application['status']; ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </p>
                                <?php if (isset($application['bid_amount']) && $application['bid_amount'] !== null): ?>
                                    <p><strong>Bid:</strong> $<?php echo number_format($application['bid_amount'], 2); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($data['applications']) > 3): ?>
                            <p><em>... and <?php echo count($data['applications']) - 3; ?> more applications</em></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h3>Received Offers (<?php echo count($data['offers'] ?? []); ?>)</h3>
                <?php if (empty($data['offers'])): ?>
                    <p>No offers received yet</p>
                <?php else: ?>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach (array_slice($data['offers'], 0, 3) as $offer): ?>
                            <div class="offer-summary" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                                <h5><?php echo htmlspecialchars($offer['title']); ?></h5>
                                <p><strong>From:</strong> <?php echo htmlspecialchars($offer['client_name']); ?></p>
                                <p><strong>Budget:</strong> $<?php echo number_format($offer['budget'], 2); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-<?php echo $offer['status']; ?>">
                                        <?php echo ucfirst($offer['status']); ?>
                                    </span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($data['offers']) > 3): ?>
                            <p><em>... and <?php echo count($data['offers']) - 3; ?> more offers</em></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="admin-actions">
        <?php if ($user['type'] == 'client'): ?>
            <a href="index.php?page=freelancers" class="btn">Browse Freelancers</a>
        <?php elseif ($user['type'] == 'freelancer'): ?>
            <a href="index.php?page=dashboard&action=edit_portfolio" class="btn btn-primary">Edit Portfolio</a>
        <?php endif; ?>
        <a href="index.php?page=logout" class="btn btn-danger">Logout</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

