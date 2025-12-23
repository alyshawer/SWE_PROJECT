<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>My Profile</h2>
            <a href="index.php?page=profile&action=edit" class="btn">Edit Profile</a>
        </div>

        <?php showMessage(); ?>

        <?php 
        $user = $user ?? [];
        $userType = $user['type'] ?? 'unknown';
        ?>

        <!-- User Information -->
        <div class="profile-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Personal Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>Name:</strong>
                    <p><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <strong>Username:</strong>
                    <p><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div>
                    <strong>Email:</strong>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <?php if (!empty($user['phone'])): ?>
                <div>
                    <strong>Phone:</strong>
                    <p><?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <strong>User Type:</strong>
                    <p><span class="status-badge status-<?php echo $userType; ?>"><?php echo ucfirst($userType); ?></span></p>
                </div>
                <div>
                    <strong>Member Since:</strong>
                    <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <?php if ($userType == 'client' && isset($jobs)): ?>
            <!-- Client: All Jobs -->
            <div class="profile-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>All Jobs Offered (<?php echo count($jobs ?? []); ?>)</h3>
                <?php if (empty($jobs)): ?>
                    <p style="color: #666; margin-top: 10px;">You haven't posted any jobs yet.</p>
                    <a href="index.php?page=post_job" class="btn" style="margin-top: 10px;">Post Your First Job</a>
                <?php else: ?>
                    <div style="margin-top: 15px;">
                        <?php foreach ($jobs as $job): ?>
                            <div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p style="color: #666; margin: 5px 0; font-size: 0.9em;">
                                            <?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?>
                                            <?php echo strlen($job['description']) > 150 ? '...' : ''; ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right; margin-left: 15px;">
                                        <span class="status-badge status-<?php echo $job['status']; ?>" style="display: inline-block;">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 20px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                    <div>
                                        <strong>Budget:</strong> $<?php echo number_format($job['budget'], 2); ?>
                                    </div>
                                    <div>
                                        <strong>Posted:</strong> <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                    <div style="margin-left: auto;">
                                        <a href="index.php?page=job&id=<?php echo $job['id']; ?>" class="btn btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($userType == 'freelancer' && isset($applications)): ?>
            <!-- Freelancer: Job History -->
            <div class="profile-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Job History (<?php echo count($applications ?? []); ?>)</h3>
                <?php if (empty($applications)): ?>
                    <p style="color: #666; margin-top: 10px;">You haven't applied to any jobs yet.</p>
                    <a href="index.php?page=jobs" class="btn" style="margin-top: 10px;">Browse Available Jobs</a>
                <?php else: ?>
                    <div style="margin-top: 15px;">
                        <?php foreach ($applications as $application): ?>
                            <div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($application['job_title']); ?></h4>
                                        <p style="color: #666; margin: 5px 0; font-size: 0.9em;">
                                            <strong>Client:</strong> <?php echo htmlspecialchars($application['client_name']); ?>
                                        </p>
                                        <?php if (!empty($application['proposal'])): ?>
                                        <p style="color: #666; margin: 5px 0; font-size: 0.9em;">
                                            <?php echo htmlspecialchars(substr($application['proposal'], 0, 150)); ?>
                                            <?php echo strlen($application['proposal']) > 150 ? '...' : ''; ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right; margin-left: 15px;">
                                        <span class="status-badge status-<?php echo $application['status']; ?>" style="display: inline-block; margin-bottom: 5px;">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                        <?php if (!empty($application['job_status'])): ?>
                                        <br>
                                        <span class="status-badge status-<?php echo $application['job_status']; ?>" style="display: inline-block; margin-top: 5px;">
                                            <?php echo ucfirst(str_replace('_', ' ', $application['job_status'])); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 20px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                    <div>
                                        <strong>Budget:</strong> $<?php echo number_format($application['job_budget'], 2); ?>
                                    </div>
                                    <?php if (!empty($application['completion_time'])): ?>
                                    <div>
                                        <strong>Completion Time:</strong> <?php echo htmlspecialchars($application['completion_time']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong>Applied:</strong> <?php echo date('M j, Y', strtotime($application['created_at'])); ?>
                                    </div>
                                    <?php if (!empty($application['completed_at'])): ?>
                                    <div>
                                        <strong>Completed:</strong> <?php echo date('M j, Y', strtotime($application['completed_at'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Freelancer Profile Info -->
            <?php if (isset($profile) && $profile): ?>
            <div class="profile-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Freelancer Profile</h3>
                <div style="margin-top: 15px;">
                    <?php if (!empty($profile['skills'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Skills:</strong>
                        <p><?php echo htmlspecialchars($profile['skills']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['hourly_rate'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Hourly Rate:</strong>
                        <p>$<?php echo number_format($profile['hourly_rate'], 2); ?>/hour</p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['bio'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Bio:</strong>
                        <p><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['portfolio_link'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Portfolio:</strong>
                        <p><a href="<?php echo htmlspecialchars($profile['portfolio_link']); ?>" target="_blank"><?php echo htmlspecialchars($profile['portfolio_link']); ?></a></p>
                    </div>
                    <?php endif; ?>
                    <a href="index.php?page=dashboard&action=edit_portfolio" class="btn btn-sm">Edit Portfolio</a>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($userType == 'admin'): ?>
            <!-- Admin: Additional Info -->
            <div class="profile-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Admin Information</h3>
                <p style="color: #666; margin-top: 10px;">You have administrative access to manage the platform.</p>
                <a href="index.php?page=admin" class="btn" style="margin-top: 10px;">Go to Admin Panel</a>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="index.php?page=dashboard" class="btn">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

