<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <div class="form-container">
        <h2>Edit Profile</h2>

        <?php showMessage(); ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" action="index.php?page=profile&action=edit">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-group">
                <label for="name">Full Name: *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Username: *</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email: *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>

            <hr style="margin: 30px 0; border: none; border-top: 2px solid #eee;">

            <h3 style="margin-bottom: 15px;">Change Password (Leave blank to keep current password)</h3>

            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password">
                <small class="form-text text-muted">Required only if you want to change your password</small>
            </div>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" minlength="6">
                <small class="form-text text-muted">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn">Update Profile</button>
                <a href="index.php?page=profile" class="btn" style="background: #6c757d;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

