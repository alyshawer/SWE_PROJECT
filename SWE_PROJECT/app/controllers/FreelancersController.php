<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class FreelancersController extends BaseController {

    public function index() {
        $this->requireLogin();

        $user = $this->getCurrentUser();

        // Handle sending offers (only for clients)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_offer']) && $user['type'] == 'client') {
            $freelancer_id = $_POST['freelancer_id'] ?? null;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $budget = $_POST['budget'] ?? 0;
            $completion_time = trim($_POST['completion_time'] ?? '');

            if (!empty($title) && !empty($description) && !empty($budget)) {
                if (createOffer($this->pdo, $user['id'], $freelancer_id, $title, $description, $budget, $completion_time)) {
                    $_SESSION['success_message'] = 'Offer sent successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to send offer. Please try again.';
                }
            } else {
                $_SESSION['error_message'] = 'Please fill in all required fields.';
            }

            $this->redirect('index.php?page=freelancers');
        }

        $freelancers = getAllFreelancers($this->pdo, $user['id']);

        $this->setPageTitle('Browse Freelancers');
        $this->setData('freelancers', $freelancers);
        $this->setData('user', $user);
        $this->render('freelancers_list');
    }

    // Return an HTML fragment for a freelancer profile (used by modal via AJAX)
    public function profile($id = null) {
        $this->requireLogin();

        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) {
            http_response_code(400);
            echo 'Missing id';
            return;
        }

        $profile = getUserWithProfile($this->pdo, $id);
        if (!$profile) {
            http_response_code(404);
            echo 'Profile not found';
            return;
        }

        // Render a small HTML fragment
        $out = '';
        $out .= '<div class="profile-modal">';
        $out .= '<div style="display:flex;gap:16px;align-items:center;margin-bottom:12px;">';
        $out .= '<div style="width:72px;height:72px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;">' . strtoupper(substr($profile['name'] ?? '', 0, 2)) . '</div>';
        $out .= '<div><h3>' . htmlspecialchars($profile['name'] ?? '') . '</h3>';
        $out .= '<p style="margin:0;color:#666">@' . htmlspecialchars($profile['username'] ?? '') . '</p>';
        if (!empty($profile['hourly_rate'])) {
            $out .= '<p style="margin:6px 0 0 0"><strong>Hourly:</strong> $' . number_format($profile['hourly_rate'],2) . '</p>';
        }
        $out .= '</div></div>';

        if (!empty($profile['bio'])) {
            $out .= '<div style="margin-bottom:10px;"><strong>About</strong><p>' . nl2br(htmlspecialchars($profile['bio'])) . '</p></div>';
        }

        if (!empty($profile['skills'])) {
            $skills = array_map('trim', explode(',', $profile['skills']));
            $out .= '<div style="margin-bottom:10px;"><strong>Skills</strong><div style="margin-top:6px;">';
            foreach ($skills as $s) {
                if ($s === '') continue;
                $out .= '<span style="display:inline-block;background:#007bff;color:#fff;padding:4px 10px;border-radius:12px;margin:3px;font-size:0.85em;">' . htmlspecialchars($s) . '</span>';
            }
            $out .= '</div></div>';
        }

        if (!empty($profile['portfolio_link'])) {
            $out .= '<div style="margin-bottom:8px;"><strong>Portfolio</strong><p><a href="' . htmlspecialchars($profile['portfolio_link']) . '" target="_blank">' . htmlspecialchars($profile['portfolio_link']) . '</a></p></div>';
        }

        if (!empty($profile['phone'])) {
            $out .= '<p><strong>Phone:</strong> ' . htmlspecialchars($profile['phone']) . '</p>';
        }

        $out .= '</div>';

        echo $out;
        return;
    }
}
