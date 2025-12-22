<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>
<?php require_once __DIR__ . '/../config/database.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Payments Management</h2>
        <p><?php echo isAdmin() ? 'Monitor all transactions' : 'View your payments'; ?></p>
    </div>
    
    <?php showMessage(); ?>
    
    <?php if ($user['type'] == 'client'): ?>
        <div class="card">
            <h3>Create New Payment</h3>
            <form method="POST" action="index.php?page=payments&action=create">
                <div class="form-group">
                    <label>Project:</label>
                    <select name="project_id" required>
                        <option value="">Select Project</option>
                        <?php
                        $userJobs = getUserJobs($pdo, $user['id']);
                        foreach ($userJobs as $job): ?>
                            <option value="<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Freelancer:</label>
                    <select name="freelancer_id" required>
                        <option value="">Select Freelancer</option>
                        <?php
                        $freelancers = getAllFreelancers($pdo);
                        foreach ($freelancers as $freelancer): ?>
                            <option value="<?php echo $freelancer['id']; ?>"><?php echo htmlspecialchars($freelancer['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount:</label>
                    <input type="number" name="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Payment Method:</label>
                    <select name="payment_method">
                        <option value="online">Online</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>
                <button type="submit" name="create_payment" class="btn">Create Payment</button>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Payment History</h3>
        <div class="table">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project</th>
                        <?php if (isAdmin()): ?>
                            <th>Client</th>
                            <th>Freelancer</th>
                        <?php else: ?>
                            <th><?php echo $user['type'] == 'client' ? 'Freelancer' : 'Client'; ?></th>
                        <?php endif; ?>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 30px;">
                                No payments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td><?php echo htmlspecialchars($payment['project_title'] ?? 'N/A'); ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?php echo htmlspecialchars($payment['client_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['freelancer_name'] ?? 'N/A'); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($user['type'] == 'client' ? ($payment['freelancer_name'] ?? 'N/A') : ($payment['client_name'] ?? 'N/A')); ?></td>
                                <?php endif; ?>
                                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($payment['paymentMethod'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($payment['createdAt'])); ?></td>
                                <td>
                                    <?php if ($payment['status'] == 'pending'): ?>
                                        <?php if (isAdmin()): ?>
                                            <!-- Admin can complete any payment -->
                                            <form method="POST" action="index.php?page=payments&action=complete" style="display: inline;">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                <input type="text" name="transaction_id" placeholder="Transaction ID" required style="width: 150px; padding: 6px; margin-right: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                                <button type="submit" name="complete_payment" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Complete this payment?')">
                                                    Complete Payment
                                                </button>
                                            </form>
                                        <?php elseif ($user['type'] == 'client' && isset($payment['clientId']) && $payment['clientId'] == $user['id']): ?>
                                            <!-- Client can complete their own payments -->
                                            <form method="POST" action="index.php?page=payments&action=complete" style="display: inline;">
                                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                <input type="text" name="transaction_id" placeholder="Transaction ID (optional)" style="width: 150px; padding: 6px; margin-right: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                                <button type="submit" name="complete_payment" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Mark this payment as completed?')">
                                                    Mark as Completed
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #999; font-style: italic;">-</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="admin-actions" style="margin-top: 20px;">
        <a href="index.php?page=dashboard" class="btn">Back to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

