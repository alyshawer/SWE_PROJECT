<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Audit Logs</h2>
        <p>System-wide activity logs (admin only)</p>
    </div>
    
    <div class="admin-actions" style="margin-bottom: 20px;">
        <a href="<?php echo base_url('index.php?page=audit_logs&limit=50'); ?>" class="btn">Last 50</a>
        <a href="<?php echo base_url('index.php?page=audit_logs&limit=100'); ?>" class="btn">Last 100</a>
        <a href="<?php echo base_url('index.php?page=audit_logs&limit=500'); ?>" class="btn">Last 500</a>
    </div>
    
    <div class="table">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px;">
                            No audit logs found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo htmlspecialchars($log['name'] ?? $log['username'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['ipAddress'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M j, Y H:i:s', strtotime($log['timestamp'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-actions" style="margin-top: 20px;">
        <a href="<?php echo base_url('index.php?page=admin'); ?>" class="btn">Admin Panel</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

