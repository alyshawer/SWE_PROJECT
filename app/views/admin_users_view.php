<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Manage Users</h2>
        <p>Create, suspend, activate or delete users from the platform</p>
    </div>

    <?php showMessage(); ?>

    <div class="card" style="margin-bottom: 20px;">
        <h4>Create New User</h4>
        <form method="POST" action="index.php?page=admin&action=addUser">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required pattern="^[a-zA-Z0-9_]{3,20}$" title="3-20 characters: letters, numbers, underscore">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$" title="Enter a valid email address (e.g., user@example.com)">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="client">Client</option>
                    <option value="freelancer">Freelancer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn">Create User</button>
        </form>
    </div>

    <div class="admin-panel">
        <h3>Existing Users</h3>
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

    <div class="admin-actions">
        <a href="index.php?page=admin" class="btn">Back to Admin Panel</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>