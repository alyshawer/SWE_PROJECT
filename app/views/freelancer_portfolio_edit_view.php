<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <div class="dashboard-header">
        <h2>Edit My Portfolio</h2>
        <p>Update your profile to attract more clients</p>
    </div>
    
    <?php showMessage(); ?>
    
    <div class="form-container">
        <form method="POST" action="index.php?page=dashboard&action=edit_portfolio">
            <div class="form-group">
                <label for="skills">Skills *</label>
                <textarea id="skills" name="skills" rows="4" required placeholder="e.g., PHP, JavaScript, MySQL, Web Design, SEO"><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                <small style="color: #666;">List your skills separated by commas</small>
            </div>
            
            <div class="form-group">
                <label for="past_projects">Past Projects</label>
                <textarea id="past_projects" name="past_projects" rows="4" placeholder="Describe your previous work and achievements"><?php echo htmlspecialchars($profile['past_projects'] ?? ''); ?></textarea>
                <small style="color: #666;">Describe your previous projects, achievements, or experience</small>
            </div>
            
            <div class="form-group">
                <label for="portfolio_link">Portfolio Link</label>
                <input type="url" id="portfolio_link" name="portfolio_link" placeholder="https://yourportfolio.com" value="<?php echo htmlspecialchars($profile['portfolio_link'] ?? ''); ?>">
                <small style="color: #666;">Link to your online portfolio or website</small>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="5" placeholder="Tell clients about yourself, your experience, and what makes you unique"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                <small style="color: #666;">Write a brief description about yourself and your expertise</small>
            </div>
            
            <div class="form-group">
                <label for="hourly_rate">Hourly Rate ($)</label>
                <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0" placeholder="50.00" value="<?php echo htmlspecialchars($profile['hourly_rate'] ?? ''); ?>">
                <small style="color: #666;">Your preferred hourly rate in USD</small>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    <?php echo $profile ? 'Update Portfolio' : 'Create Portfolio'; ?>
                </button>
                <a href="index.php?page=dashboard" class="btn" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

