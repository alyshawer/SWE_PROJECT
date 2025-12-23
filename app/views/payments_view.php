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
        <?php 
        // Show completed jobs with payment info from freelancers
        $jobsWithPaymentInfo = $data['jobsWithPaymentInfo'] ?? [];
        if (!empty($jobsWithPaymentInfo)): 
        ?>
            <div class="card" style="background: #e8f5e9; border-left: 4px solid #4caf50;">
                <h3>Pending Payments - Freelancer Payment Info Available</h3>
                <p>The following completed jobs have freelancer payment information ready:</p>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($jobsWithPaymentInfo as $jobInfo): ?>
                        <li style="padding: 10px; margin: 10px 0; background: white; border-radius: 5px;">
                            <strong><?php echo htmlspecialchars($jobInfo['job_title']); ?></strong> - 
                            Freelancer: <?php echo htmlspecialchars($jobInfo['freelancer_name']); ?><br>
                            <small>
                                Payment Method: <?php echo ucfirst(str_replace('_', ' ', $jobInfo['payment_method'])); ?> | 
                                Account: <?php echo htmlspecialchars($jobInfo['payment_account_info']); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>Create New Payment</h3>
            <?php 
            // Debug info - variables are extracted by BaseController, so they're available directly
            // But we can also access via $data array
            $userJobs = isset($userJobs) ? $userJobs : (isset($data['userJobs']) ? $data['userJobs'] : []);
            $jobFreelancerMap = isset($jobFreelancerMap) ? $jobFreelancerMap : (isset($data['jobFreelancerMap']) ? $data['jobFreelancerMap'] : []);
            
            // Debug: Check what we have
            if (empty($userJobs)) {
                // Try to get jobs directly to debug
                require_once __DIR__ . '/../helpers/db_functions.php';
                require_once __DIR__ . '/../config/database.php';
                $debugJobs = getUserJobs($pdo, $user['id']);
                if (!empty($debugJobs)) {
                    $userJobs = $debugJobs;
                    echo '<div class="alert" style="background: #fff3cd; border-left: 4px solid #ffc107;">Debug: Found ' . count($debugJobs) . ' job(s) directly from database.</div>';
                } else {
                    echo '<div class="alert alert-error">No projects found for user ID: ' . htmlspecialchars($user['id']) . '. Please create a job first.</div>';
                }
            } else {
                echo '<div style="color: #666; font-size: 0.9em; margin-bottom: 10px;">Found ' . count($userJobs) . ' project(s)</div>';
            }
            ?>
            <form method="POST" action="index.php?page=payments&action=create" id="paymentForm">
                <div class="form-group">
                    <label>Project:</label>
                    <select name="project_id" id="project_id" required onchange="updateFreelancerOptions(); return false;">
                        <option value="">Select Project</option>
                        <?php
                        if (!empty($userJobs)):
                            foreach ($userJobs as $job): ?>
                                <option value="<?php echo $job['id']; ?>" data-budget="<?php echo $job['budget']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </option>
                            <?php endforeach;
                        endif; ?>
                    </select>
                    <?php if (!empty($userJobs)): ?>
                        <small style="color: #666;">Found <?php echo count($userJobs); ?> project(s)</small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Freelancer:</label>
                    <select name="freelancer_id" id="freelancer_id" required onchange="loadFreelancerPaymentInfo(); return false;">
                        <option value="">First select a project</option>
                    </select>
                </div>
                <script>
                    // Store the job-freelancer map from PHP - convert keys to strings for consistency
                    var jobFreelancerMapRaw = <?php echo json_encode($jobFreelancerMap); ?>;
                    var jobFreelancerMap = {};
                    
                    // Convert numeric keys to strings for consistent access
                    for (var key in jobFreelancerMapRaw) {
                        if (jobFreelancerMapRaw.hasOwnProperty(key)) {
                            jobFreelancerMap[String(key)] = jobFreelancerMapRaw[key];
                        }
                    }
                    
                    console.log('Job Freelancer Map loaded:', jobFreelancerMap);
                    console.log('Number of jobs in map:', Object.keys(jobFreelancerMap).length);
                    
                    function updateFreelancerOptions() {
                        console.log('updateFreelancerOptions called');
                        var projectSelect = document.getElementById('project_id');
                        var freelancerSelect = document.getElementById('freelancer_id');
                        
                        if (!projectSelect) {
                            console.error('project_id element not found');
                            return;
                        }
                        if (!freelancerSelect) {
                            console.error('freelancer_id element not found');
                            return;
                        }
                        
                        var selectedJobId = String(projectSelect.value); // Convert to string for consistent lookup
                        console.log('Selected job ID:', selectedJobId, 'Type:', typeof selectedJobId);
                        console.log('Available keys in map:', Object.keys(jobFreelancerMap));
                        console.log('Map has key?', selectedJobId in jobFreelancerMap);
                        
                        // Clear current options
                        freelancerSelect.innerHTML = '<option value="">Select Freelancer</option>';
                        
                        if (selectedJobId && jobFreelancerMap && jobFreelancerMap[selectedJobId]) {
                            var applications = jobFreelancerMap[selectedJobId];
                            console.log('Applications for job:', applications);
                            
                            if (applications && applications.length > 0) {
                                applications.forEach(function(app) {
                                    // Show all accepted applications (not just completed)
                                    if (app.status == 'accepted') {
                                        var option = document.createElement('option');
                                        option.value = app.freelancer_id;
                                        
                                        // Add job status indicator to the name
                                        var statusText = '';
                                        if (app.job_status == 'completed') {
                                            statusText = ' (Completed)';
                                        } else if (app.job_status == 'in_progress') {
                                            statusText = ' (In Progress)';
                                        }
                                        
                                        option.textContent = app.freelancer_name + statusText;
                                        option.setAttribute('data-payment-method', app.payment_method || '');
                                        option.setAttribute('data-payment-account', app.payment_account_info || '');
                                        option.setAttribute('data-job-budget', app.job_budget || '');
                                        option.setAttribute('data-job-status', app.job_status || '');
                                        freelancerSelect.appendChild(option);
                                    }
                                });
                            }
                            
                            // If no freelancers found, show a message
                            if (freelancerSelect.options.length === 1) {
                                var option = document.createElement('option');
                                option.value = '';
                                option.textContent = 'No accepted freelancers for this project';
                                option.disabled = true;
                                freelancerSelect.appendChild(option);
                            }
                        } else {
                            // No job selected or no applications for this job
                            var option = document.createElement('option');
                            option.value = '';
                            option.textContent = selectedJobId ? 'No accepted freelancers for this project' : 'First select a project';
                            option.disabled = true;
                            freelancerSelect.appendChild(option);
                        }
                        
                        // Clear payment info when project changes
                        var infoDisplay = document.getElementById('freelancer_payment_info_display');
                        if (infoDisplay) {
                            infoDisplay.style.display = 'none';
                        }
                        var paymentMethod = document.getElementById('payment_method');
                        var accountInput = document.getElementById('freelancer_account');
                        var amountInput = document.getElementById('amount');
                        if (paymentMethod) paymentMethod.value = 'online';
                        if (accountInput) accountInput.value = '';
                        if (amountInput) amountInput.value = '';
                        if (typeof toggleAccountField === 'function') {
                            toggleAccountField();
                        }
                    }
                </script>
                <div class="form-group">
                    <label>Amount:</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Payment Method:</label>
                    <select name="payment_method" id="payment_method" onchange="toggleAccountField()">
                        <option value="online">Online</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>
                <div class="form-group" id="account_field" style="display: none;">
                    <label id="account_label">Freelancer Account:</label>
                    <input type="text" name="freelancer_account" id="freelancer_account" 
                           placeholder="Enter PayPal email or bank account details">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Enter the freelancer's PayPal email or bank account details where payment should be sent
                    </small>
                </div>
                <div id="freelancer_payment_info_display" style="display: none; margin: 15px 0; padding: 15px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                    <strong>Freelancer Payment Information:</strong>
                    <p style="margin: 5px 0;">
                        <strong>Payment Method:</strong> <span id="info_payment_method"></span><br>
                        <strong>Account Info:</strong> <span id="info_payment_account"></span>
                    </p>
                    <small style="color: #666;">This information was provided by the freelancer. You can use it to process the payment.</small>
                </div>
                <script>
                    function loadFreelancerPaymentInfo() {
                        const freelancerSelect = document.getElementById('freelancer_id');
                        const selectedFreelancer = freelancerSelect.options[freelancerSelect.selectedIndex];
                        const infoDisplay = document.getElementById('freelancer_payment_info_display');
                        const paymentMethodSelect = document.getElementById('payment_method');
                        const accountInput = document.getElementById('freelancer_account');
                        const amountInput = document.getElementById('amount');
                        
                        if (selectedFreelancer && selectedFreelancer.value && !selectedFreelancer.disabled) {
                            const paymentMethod = selectedFreelancer.getAttribute('data-payment-method');
                            const paymentAccount = selectedFreelancer.getAttribute('data-payment-account');
                            const jobBudget = selectedFreelancer.getAttribute('data-job-budget');
                            const jobStatus = selectedFreelancer.getAttribute('data-job-status');
                            
                            // Set amount to job budget if available and input is empty
                            if (jobBudget && !amountInput.value) {
                                amountInput.value = parseFloat(jobBudget).toFixed(2);
                            }
                            
                            // Show freelancer payment info if available and job is completed
                            if (paymentMethod && paymentAccount && jobStatus == 'completed') {
                                document.getElementById('info_payment_method').textContent = paymentMethod.replace('_', ' ').toUpperCase();
                                document.getElementById('info_payment_account').textContent = paymentAccount;
                                if (infoDisplay) {
                                    infoDisplay.style.display = 'block';
                                }
                                
                                // Auto-fill payment method and account
                                paymentMethodSelect.value = paymentMethod;
                                toggleAccountField();
                                accountInput.value = paymentAccount;
                            } else {
                                if (infoDisplay) {
                                    infoDisplay.style.display = 'none';
                                }
                                // If no payment info, still show account field based on selected method
                                toggleAccountField();
                            }
                        } else {
                            if (infoDisplay) {
                                infoDisplay.style.display = 'none';
                            }
                            toggleAccountField();
                        }
                    }
                    
                    function toggleAccountField() {
                        const method = document.getElementById('payment_method').value;
                        const accountField = document.getElementById('account_field');
                        const accountLabel = document.getElementById('account_label');
                        const accountInput = document.getElementById('freelancer_account');
                        
                        if (!accountField || !accountLabel || !accountInput) {
                            return; // Elements not found yet
                        }
                        
                        if (method === 'paypal') {
                            accountField.style.display = 'block';
                            accountLabel.textContent = 'Freelancer PayPal Email:';
                            accountInput.placeholder = 'e.g., freelancer@example.com';
                            accountInput.required = true;
                        } else if (method === 'bank_transfer') {
                            accountField.style.display = 'block';
                            accountLabel.textContent = 'Freelancer Bank Account:';
                            accountInput.placeholder = 'Bank name, account number, routing number, etc.';
                            accountInput.required = true;
                        } else {
                            accountField.style.display = 'none';
                            accountInput.required = false;
                        }
                    }
                </script>
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
                                                <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($payment['id']); ?>">
                                                <?php if (!empty($payment['transactionId']) && $payment['status'] == 'pending'): ?>
                                                    <div style="margin-bottom: 5px; font-size: 0.85em; color: #666; padding: 5px; background: #f0f0f0; border-radius: 3px;">
                                                        <?php if ($payment['paymentMethod'] == 'paypal'): ?>
                                                            <strong>PayPal:</strong> <?php echo htmlspecialchars($payment['transactionId']); ?>
                                                        <?php elseif ($payment['paymentMethod'] == 'bank_transfer'): ?>
                                                            <strong>Bank Account:</strong> <?php echo htmlspecialchars($payment['transactionId']); ?>
                                                        <?php else: ?>
                                                            <strong>Account:</strong> <?php echo htmlspecialchars($payment['transactionId']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="text" name="transaction_id" id="txn_id_<?php echo $payment['id']; ?>" 
                                                       placeholder="Transaction ID (optional)" 
                                                       style="width: 150px; padding: 6px; margin-right: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"
                                                       value="">
                                                <button type="submit" name="complete_payment" value="1" class="btn btn-success btn-sm"
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

