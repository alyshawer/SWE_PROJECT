<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="form-container">
        <h2>Enter Payment Information</h2>
        <p class="subtitle">Please provide your payment details to receive payment for this completed job.</p>

        <?php showMessage(); ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px; padding: 20px; background: #f8f9fa;">
            <h4 style="margin-top: 0;">Job Details</h4>
            <p><strong>Job Title:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
            <p><strong>Budget:</strong> $<?php echo number_format($application['job_budget'], 2); ?></p>
        </div>

        <form method="POST" action="index.php?page=dashboard&action=enter_payment_info">
            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
            <input type="hidden" name="save_payment_info" value="1">
            
            <div class="form-group">
                <label for="payment_method">Payment Method *</label>
                <select id="payment_method" name="payment_method" required onchange="toggleAccountField()">
                    <option value="">Select Payment Method</option>
                    <option value="paypal" <?php echo (isset($application['payment_method']) && $application['payment_method'] == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                    <option value="bank_transfer" <?php echo (isset($application['payment_method']) && $application['payment_method'] == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="online" <?php echo (isset($application['payment_method']) && $application['payment_method'] == 'online') ? 'selected' : ''; ?>>Online Payment (Platform)</option>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Choose how you want to receive payment for this job
                </small>
            </div>
            
            <div class="form-group" id="account_field" style="display: none;">
                <label id="account_label">Account Information:</label>
                <input type="text" id="payment_account_info" name="payment_account_info" 
                       value="<?php echo htmlspecialchars($application['payment_account_info'] ?? ''); ?>"
                       placeholder="Enter payment account details">
                <small id="account_help" style="color: #666; display: block; margin-top: 5px;">
                    Enter your payment account information
                </small>
            </div>
            
            <script>
                function toggleAccountField() {
                    const method = document.getElementById('payment_method').value;
                    const accountField = document.getElementById('account_field');
                    const accountLabel = document.getElementById('account_label');
                    const accountInput = document.getElementById('payment_account_info');
                    const accountHelp = document.getElementById('account_help');
                    
                    if (method === 'paypal') {
                        accountField.style.display = 'block';
                        accountLabel.textContent = 'PayPal Email:';
                        accountInput.placeholder = 'e.g., freelancer@example.com';
                        accountInput.required = true;
                        accountHelp.textContent = 'Enter your PayPal email address where payment should be sent';
                    } else if (method === 'bank_transfer') {
                        accountField.style.display = 'block';
                        accountLabel.textContent = 'Bank Account Details:';
                        accountInput.placeholder = 'Bank name, account number, routing number, etc.';
                        accountInput.required = true;
                        accountHelp.textContent = 'Enter your bank account details (bank name, account number, routing number, etc.)';
                    } else {
                        accountField.style.display = 'none';
                        accountInput.required = false;
                        accountInput.value = '';
                    }
                }
                
                // Initialize on page load
                window.onload = function() {
                    toggleAccountField();
                };
            </script>
            
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                <strong>Note:</strong> This information will be shared with the client so they can process your payment. 
                Make sure all details are accurate.
            </div>
            
            <button type="submit" class="btn" style="margin-top: 20px;">Save Payment Information</button>
        </form>
        
        <div class="back-link" style="margin-top: 15px;">
            <a href="index.php?page=dashboard">← Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

