<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/../helpers/view_helper.php'; ?>

<div class="container">
    <?php showMessage(); ?>

    <h2>Browse Freelancers</h2>
    <?php if ($user['type'] == 'freelancer'): ?>
        <p>View other freelancers' profiles and skills</p>
    <?php else: ?>
        <p>Find talented freelancers for your projects</p>
    <?php endif; ?>

    <?php if (empty($freelancers)): ?>
        <div class="card" style="text-align: center; padding: 50px;">
            <h3>No freelancers available at the moment</h3>
            <p>Check back later for new freelancers!</p>
        </div>
    <?php else: ?>
        <div class="freelancers-grid">
            <?php foreach ($freelancers as $freelancer): ?>
                <div class="freelancer-card">
                    <div class="freelancer-header">
                        <div class="freelancer-avatar">
                            <?php echo strtoupper(substr($freelancer['name'], 0, 2)); ?>
                        </div>
                        <div class="freelancer-info">
                            <h3><?php echo htmlspecialchars($freelancer['name']); ?></h3>
                            <p class="username">@<?php echo htmlspecialchars($freelancer['username']); ?></p>
                            <?php if (!empty($freelancer['hourly_rate'])): ?>
                                <div class="hourly-rate">
                                    <span class="rate-amount">$<?php echo number_format($freelancer['hourly_rate'], 2); ?></span>
                                    <span class="rate-label">/hour</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="freelancer-content">
                        <?php if (!empty($freelancer['bio'])): ?>
                            <div class="bio">
                                <p><?php echo htmlspecialchars(substr($freelancer['bio'], 0, 150)); ?><?php echo strlen($freelancer['bio']) > 150 ? '...' : ''; ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($freelancer['skills'])): ?>
                            <div class="skills">
                                <h4>Skills:</h4>
                                <div class="skills-list">
                                    <?php 
                                    $skills = explode(',', $freelancer['skills']);
                                    $displaySkills = array_slice($skills, 0, 3);
                                    foreach ($displaySkills as $skill): 
                                        $skill = trim($skill);
                                        if (!empty($skill)):
                                    ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (count($skills) > 3):
                                    ?>
                                        <span class="skill-tag more">+<?php echo count($skills) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($freelancer['availability'])): ?>
                            <div class="availability">
                                <span class="availability-badge availability-<?php echo $freelancer['availability']; ?>">
                                    <?php echo ucfirst($freelancer['availability']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="freelancer-actions">
                        <button onclick="openProfileModal(<?php echo $freelancer['id']; ?>)" class="btn btn-sm">View Profile</button>
                        <?php if ($user['type'] == 'client'): ?>
                            <button onclick="openOfferModal(<?php echo $freelancer['id']; ?>, '<?php echo htmlspecialchars($freelancer['name']); ?>')" 
                                    class="btn btn-primary btn-sm">Send Offer</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Offer Modal (kept inline for simplicity) -->
<div id="offerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Send Offer to <span id="freelancerName"></span></h3>
            <span class="close" onclick="closeOfferModal()">&times;</span>
        </div>
        
        <form method="POST" id="offerForm" action="<?php echo base_url('index.php?page=freelancers'); ?>">
            <input type="hidden" name="freelancer_id" id="freelancerId">
            
            <div class="form-group">
                <label for="title">Project Title:</label>
                <input type="text" id="title" name="title" required placeholder="Enter project title">
            </div>
            
            <div class="form-group">
                <label for="description">Project Description:</label>
                <textarea id="description" name="description" rows="4" required 
                    placeholder="Describe the project requirements, scope, and what you're looking for..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="budget">Budget ($):</label>
                <input type="number" id="budget" name="budget" step="0.01" min="0" required 
                    placeholder="Enter your budget">
            </div>
            
            <div class="form-group">
                <label for="completion_time">Expected Completion Time:</label>
                <input type="text" id="completion_time" name="completion_time" 
                    placeholder="e.g., 2 weeks, 1 month, 3 days">
                <small>Optional: When you need this project completed</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeOfferModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="send_offer" class="btn btn-primary">Send Offer</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Minimal styles copied from legacy page for consistency */
.freelancers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; margin-top: 30px; }
.freelancer-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
.freelancer-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
.freelancer-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; display: flex; align-items: center; gap: 20px; }
.freelancer-avatar { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5em; font-weight: bold; border: 2px solid rgba(255,255,255,0.3); }
.freelancer-info h3 { margin: 0 0 5px 0; font-size: 1.3em; font-weight: 500; }
.hourly-rate { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 15px; display: inline-block; }
.freelancer-content { padding: 25px; }
.freelancer-actions { padding: 0 25px 25px 25px; display: flex; gap: 10px; }
.freelancer-actions .btn { flex: 1; text-align: center; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: white; margin: 5% auto; padding: 0; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
.close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
.modal form { padding: 25px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
</style>

<script>
function openOfferModal(freelancerId, freelancerName) {
    document.getElementById('freelancerId').value = freelancerId;
    document.getElementById('freelancerName').textContent = freelancerName;
    document.getElementById('offerModal').style.display = 'block';
}

function closeOfferModal() {
    document.getElementById('offerModal').style.display = 'none';
    document.getElementById('offerForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('offerModal');
    if (event.target == modal) {
        closeOfferModal();
    }
}
</script>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3>Freelancer Profile</h3>
            <span class="close" onclick="closeProfileModal()">&times;</span>
        </div>
        <div id="profileBody" style="padding:20px;">
            <!-- profile HTML injected here -->
        </div>
    </div>
</div>

<script>
function openProfileModal(id) {
    const modal = document.getElementById('profileModal');
    const body = document.getElementById('profileBody');
    body.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'block';

    fetch('index.php?page=freelancers&action=profile&id=' + encodeURIComponent(id))
        .then(resp => {
            if (!resp.ok) throw new Error('Profile not found');
            return resp.text();
        })
        .then(html => { body.innerHTML = html; })
        .catch(err => { body.innerHTML = '<div class="alert alert-error">Could not load profile.</div>'; console.error(err); });
}

function closeProfileModal() {
    document.getElementById('profileModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
