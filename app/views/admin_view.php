<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Admin Panel</h2>
        <p>Manage users, jobs, and system settings</p>
    </div>
    
    <?php showMessage(); ?>
    
    <!-- Quick Actions -->
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="dashboard-card">
            <h3>Quick Actions</h3>
            <p>Access key admin features</p>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <a href="index.php?page=reports" class="btn">View Reports</a>
                <a href="index.php?page=payments" class="btn">Monitor Transactions</a>
                <a href="index.php?page=audit_logs" class="btn">Audit Logs</a>
                <a href="index.php?page=admin&analytics=1" class="btn">Generate Analytics</a>
                <a href="index.php?page=admin&action=users" class="btn">Manage Users</a>
            </div>
        </div>
        
        <?php if (isset($analytics)): ?>
            <div class="dashboard-card">
                <h3>System Analytics</h3>
                <p><strong>Total Users:</strong> <?php echo $analytics['total_users']; ?></p>
                <p><strong>Total Projects:</strong> <?php echo $analytics['total_projects']; ?></p>
                <p><strong>Total Payments:</strong> <?php echo $analytics['total_payments']; ?></p>
                <p><strong>Total Revenue:</strong> $<?php echo number_format($analytics['total_revenue'] ?? 0, 2); ?></p>
                <p><strong>Active Freelancers:</strong> <?php echo $analytics['active_freelancers']; ?></p>
                <p><strong>Active Clients:</strong> <?php echo $analytics['active_clients']; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($recentTransactions)): ?>
            <div class="dashboard-card">
                <h3>Recent Transactions</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recentTransactions as $txn): ?>
                        <div style="padding: 10px; border-bottom: 1px solid #eee;">
                            <p><strong><?php echo htmlspecialchars($txn['project_title']); ?></strong></p>
                            <p>$<?php echo number_format($txn['amount'], 2); ?> - <?php echo ucfirst($txn['status']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="admin-panel">
        <h3>User Management (UML: manageUser, suspendAccount)</h3>
        <div class="table">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['type']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td>
                                <?php if (isset($user['isActive']) && $user['isActive']): ?>
                                    <span style="color: #28a745;">Active</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['is_deletable']): ?>
                                    <form method="POST" action="index.php?page=admin&action=delete_user" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="admin-panel">
        <h3>Job Management</h3>
        <div class="table">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Budget</th>
                        <th>Client</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?php echo $job['id']; ?></td>
                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                            <td>$<?php echo number_format($job['budget'], 2); ?></td>
                            <td>
                                <?php echo htmlspecialchars($job['client_username'] ?? 'Unknown'); ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="index.php?page=admin&action=delete_job" style="display: inline;">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" name="delete_job" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this job?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    

    
    <div class="admin-actions">
        <a href="index.php?page=dashboard" class="btn">Back to Dashboard</a>
        <a href="index.php?page=admin&transactions=1" class="btn">Monitor Transactions</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

