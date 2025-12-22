<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Disputes Management</h2>
        <p><?php echo isAdmin() ? 'Manage all disputes' : 'View your disputes'; ?></p>
    </div>
    
    <?php showMessage(); ?>
    
    <?php if (isAdmin()): ?>
        <div class="admin-actions" style="margin-bottom: 20px;">
            <a href="<?php echo base_url('index.php?page=disputes&status=pending'); ?>" class="btn">Pending</a>
            <a href="<?php echo base_url('index.php?page=disputes&status=resolved'); ?>" class="btn">Resolved</a>
            <a href="<?php echo base_url('index.php?page=disputes&status=escalated'); ?>" class="btn">Escalated</a>
            <a href="<?php echo base_url('index.php?page=disputes'); ?>" class="btn">All</a>
        </div>
    <?php endif; ?>
    
    <div class="table">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project</th>
                    <th>Raised By</th>
                    <th>Against</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Created</th>
                    <?php if (isAdmin()): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($disputes)): ?>
                    <tr>
                        <td colspan="<?php echo isAdmin() ? '8' : '7'; ?>" style="text-align: center; padding: 30px;">
                            No disputes found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($disputes as $dispute): ?>
                        <tr>
                            <td><?php echo $dispute['id']; ?></td>
                            <td><?php echo htmlspecialchars($dispute['project_title']); ?></td>
                            <td><?php echo htmlspecialchars($dispute['raised_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($dispute['against_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($dispute['reason'], 0, 50)) . (strlen($dispute['reason']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $dispute['status']; ?>">
                                    <?php echo ucfirst($dispute['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($dispute['createdAt'])); ?></td>
                            <?php if (isAdmin() && $dispute['status'] == 'pending'): ?>
                                <td>
                                    <form method="POST" action="<?php echo base_url('index.php?page=disputes&action=resolve'); ?>" style="display: inline;">
                                        <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                                        <button type="submit" name="resolve_dispute" class="btn btn-success btn-sm"
                                                onclick="return confirm('Resolve this dispute?')">
                                            Resolve
                                        </button>
                                    </form>
                                </td>
                            <?php elseif (isAdmin()): ?>
                                <td>-</td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="admin-actions" style="margin-top: 20px;">
        <a href="<?php echo base_url('index.php?page=dashboard'); ?>" class="btn">Back to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

