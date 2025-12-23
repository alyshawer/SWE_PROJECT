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
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($data['user_jobs'] as $job): ?>
                            <div class="job-card" style="margin: 15px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                    <div>
                                        <h4 style="margin: 0 0 10px 0;"><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p class="budget" style="font-size: 1.1em; font-weight: bold; color: #667eea; margin: 5px 0;">
                                            $<?php echo number_format($job['budget'], 2); ?>
                                        </p>
                                        <p style="color: #666; font-size: 0.9em; margin: 5px 0;">
                                            Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="status-badge status-<?php echo $job['status'] ?? 'active'; ?>">
                                            <?php echo ucfirst($job['status'] ?? 'active'); ?>
                                        </span>
                                    </div>
                                </div>

                                <?php if (!empty($job['applications'])): ?>
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                        <strong style="display: block; margin-bottom: 10px;">Applications (<?php echo count($job['applications']); ?>):</strong>
                                        <?php foreach ($job['applications'] as $application): ?>
                                            <div style="background: #f8f9fa; padding: 12px; margin: 8px 0; border-radius: 6px; border-left: 3px solid #667eea;">
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                    <strong><?php echo htmlspecialchars($application['freelancer_name']); ?></strong>
                                                    <span class="status-badge status-<?php echo $application['status']; ?>">
                                                        <?php echo ucfirst($application['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($application['status'] == 'accepted'): ?>
                                                    <?php 
                                                    $jobStatus = $application['job_status'] ?? 'not_started';
                                                    $statusLabels = [
                                                        'not_started' => 'Waiting to Start',
                                                        'in_progress' => 'Working On It',
                                                        'completed' => 'Finished'
                                                    ];
                                                    $statusLabel = $statusLabels[$jobStatus] ?? ucfirst($jobStatus);
                                                    ?>
                                                    <div style="margin-top: 8px;">
                                                        <strong>Progress Status:</strong> 
                                                        <span class="status-badge status-<?php echo $jobStatus; ?>">
                                                            <?php echo $statusLabel; ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if (!empty($job['payment']) && is_array($job['payment'])): ?>
                                                        <div style="margin-top: 6px;">
                                                            <strong>Payment:</strong> 
                                                            <span class="status-badge status-<?php echo $job['payment']['status'] ?? 'pending'; ?>">
                                                                <?php 
                                                                $paymentStatus = $job['payment']['status'] ?? 'pending';
                                                                if ($paymentStatus == 'completed') {
                                                                    echo 'Paid';
                                                                } elseif ($paymentStatus == 'pending') {
                                                                    echo 'Pending Payment';
                                                                } else {
                                                                    echo ucfirst($paymentStatus);
                                                                }
                                                                ?>
                                                            </span>
                                                            <?php if ($paymentStatus == 'completed'): ?>
                                                                <span style="color: #28a745; margin-left: 8px;">✓ Completed</span>
                                                            <?php elseif ($paymentStatus == 'pending' && $jobStatus == 'completed'): ?>
                                                                <span style="color: #ffc107; margin-left: 8px;">⚠ Finished but not paid</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php elseif ($jobStatus == 'completed'): ?>
                                                        <div style="margin-top: 6px; color: #dc3545;">
                                                            <strong>Payment:</strong> <span style="color: #dc3545;">⚠ Not created yet</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="margin-top: 6px; color: #666;">
                                                            <strong>Payment:</strong> <span>Not required yet</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($application['bid_amount'])): ?>
                                                        <div style="margin-top: 6px; color: #666;">
                                                            <strong>Bid Amount:</strong> $<?php echo number_format($application['bid_amount'], 2); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($application['status'] == 'pending'): ?>
                                                    <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">
                                                        Waiting for your response
                                                    </p>
                                                <?php elseif ($application['status'] == 'rejected'): ?>
                                                    <p style="margin: 5px 0 0 0; color: #999; font-size: 0.9em; font-style: italic;">
                                                        Application rejected
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; color: #999; font-style: italic;">
                                        No applications yet
                                    </div>
                                <?php endif; ?>

                                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; display: flex; gap: 10px;">
                                    <a href="index.php?page=job&id=<?php echo $job['id']; ?>" class="btn btn-sm" style="flex: 1;">
                                        <?php echo !empty($job['applications']) ? 'View/Manage Applications' : 'View Job'; ?>
                                    </a>
                                    <form method="POST" action="index.php?page=dashboard&action=delete_job" style="display: inline;">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Delete this job?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
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
                <h3>My Jobs & Applications (<?php echo count($data['applications'] ?? []); ?>)</h3>
                    <?php if (empty($data['applications'])): ?>
                    <p>No applications submitted yet</p>
                    <a href="index.php?page=jobs" class="btn">Browse Jobs</a>
                <?php else: ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($data['applications'] as $application): ?>
                            <div class="application-summary" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <h5 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($application['job_title']); ?></h5>
                                        <p style="margin: 0; color: #666;"><strong>Client:</strong> <?php echo htmlspecialchars($application['client_name']); ?></p>
                                        <p style="margin: 5px 0; color: #667eea; font-weight: bold;">Budget: $<?php echo number_format($application['job_budget'], 2); ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $application['status']; ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($application['status'] == 'accepted'): ?>
                                    <?php 
                                    $jobStatus = $application['job_status'] ?? 'not_started';
                                    $statusLabels = [
                                        'not_started' => 'Waiting to Start',
                                        'in_progress' => 'Working On It',
                                        'completed' => 'Finished'
                                    ];
                                    $statusLabel = $statusLabels[$jobStatus] ?? ucfirst($jobStatus);
                                    ?>
                                    
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
                                        <div style="margin-bottom: 10px;">
                                            <strong>Current Progress:</strong> 
                                            <span class="status-badge status-<?php echo $jobStatus; ?>">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Payment Status -->
                                        <?php if (!empty($application['payment']) && is_array($application['payment'])): ?>
                                            <?php $paymentStatus = $application['payment']['status'] ?? 'pending'; ?>
                                            <div style="margin-bottom: 10px;">
                                                <strong>Payment Status:</strong> 
                                                <span class="status-badge status-<?php echo $paymentStatus; ?>">
                                                    <?php 
                                                    if ($paymentStatus == 'completed') {
                                                        echo '✓ Paid';
                                                    } elseif ($paymentStatus == 'pending') {
                                                        echo 'Pending Payment';
                                                    } else {
                                                        echo ucfirst($paymentStatus);
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-bottom: 10px; color: #666;">
                                                <strong>Payment:</strong> <span>Not created yet</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Status Update Form -->
                                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
                                            <form method="POST" action="index.php?page=dashboard&action=update_job_status" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <label style="font-weight: bold; margin-right: 5px;">Update Status:</label>
                                                <select name="job_status" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; margin-right: 8px;">
                                                    <option value="not_started" <?php echo $jobStatus == 'not_started' ? 'selected' : ''; ?>>Waiting to Start</option>
                                                    <option value="in_progress" <?php echo $jobStatus == 'in_progress' ? 'selected' : ''; ?>>Working On It</option>
                                                    <option value="completed" <?php echo $jobStatus == 'completed' ? 'selected' : ''; ?>>Finished (Waiting for Payment)</option>
                                                    <option value="canceled" <?php echo ($application['status'] == 'rejected' || $jobStatus == 'canceled') ? 'selected' : ''; ?>>Cancel the Deal</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm" onclick="return confirm('Update job status?')">
                                                    Update Status
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php elseif ($application['status'] == 'pending'): ?>
                                    <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9em;">
                                        Waiting for client response
                                    </p>
                                <?php elseif ($application['status'] == 'rejected'): ?>
                                    <p style="margin: 10px 0 0 0; color: #999; font-size: 0.9em; font-style: italic;">
                                        Application was rejected
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($application['bid_amount'])): ?>
                                    <p style="margin: 8px 0 0 0; color: #666; font-size: 0.9em;">
                                        <strong>Your Bid:</strong> $<?php echo number_format($application['bid_amount'], 2); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h3>Received Offers (<?php echo count($data['offers'] ?? []); ?>)</h3>
                <?php if (empty($data['offers'])): ?>
                    <p>No offers received yet</p>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($data['offers'] as $offer): ?>
                            <div class="offer-summary" style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <h5 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($offer['title']); ?></h5>
                                        <p style="margin: 5px 0; color: #666;"><strong>From:</strong> <?php echo htmlspecialchars($offer['client_name']); ?></p>
                                        <p style="margin: 5px 0; color: #667eea; font-weight: bold;">Budget: $<?php echo number_format($offer['budget'], 2); ?></p>
                                        <?php if (!empty($offer['description'])): ?>
                                            <p style="margin: 10px 0; color: #555; font-size: 0.9em;"><?php echo nl2br(htmlspecialchars(substr($offer['description'], 0, 150))); ?><?php echo strlen($offer['description']) > 150 ? '...' : ''; ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($offer['completion_time'])): ?>
                                            <p style="margin: 5px 0; color: #666; font-size: 0.9em;"><strong>Completion Time:</strong> <?php echo htmlspecialchars($offer['completion_time']); ?></p>
                                        <?php endif; ?>
                                        <p style="margin: 5px 0; color: #666; font-size: 0.85em;">Received: <?php echo date('M j, Y', strtotime($offer['created_at'])); ?></p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="status-badge status-<?php echo $offer['status']; ?>">
                                            <?php echo ucfirst($offer['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($offer['status'] == 'pending'): ?>
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; display: flex; gap: 10px;">
                                        <form method="POST" action="index.php?page=dashboard&action=accept_offer" style="display: inline;">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm"
                                                    onclick="return confirm('Accept this offer? A job will be created and you can start working on it.')">
                                                ✓ Accept Offer
                                            </button>
                                        </form>
                                        <form method="POST" action="index.php?page=dashboard&action=reject_offer" style="display: inline;">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Reject this offer? This action cannot be undone.')">
                                                ✗ Reject Offer
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($offer['status'] == 'accepted'): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #d4edda; border-radius: 4px; color: #155724;">
                                        <strong>✓ Accepted:</strong> This offer has been converted to a job. Check "My Jobs & Applications" above to manage it.
                                    </div>
                                <?php elseif ($offer['status'] == 'rejected'): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-radius: 4px; color: #721c24;">
                                        <strong>✗ Rejected:</strong> This offer was rejected.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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

