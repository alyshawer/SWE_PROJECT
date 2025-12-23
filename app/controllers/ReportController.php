<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../helpers/db_functions.php';

class ReportController extends BaseController {
    
    public function index() {
        $this->requireAdmin();
        
        $reports = getReports($this->pdo, $_SESSION['user_id']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
            $type = $_POST['report_type'] ?? '';
            $data = [];
            
            switch ($type) {
                case 'users':
                    $data = getAllUsers($this->pdo);
                    break;
                case 'projects':
                    $data = getJobs($this->pdo);
                    break;
                case 'payments':
                    $data = getPayments($this->pdo);
                    break;
            }
            
            if (createReport($this->pdo, $type, $data, $_SESSION['user_id'])) {
                $_SESSION['success_message'] = 'Report generated successfully!';
                $this->redirect('index.php?page=reports');
            } else {
                $_SESSION['error_message'] = 'Failed to generate report.';
            }
        }
        
        $this->setPageTitle('Reports');
        $this->setData('reports', $reports);
        $this->render('reports');
    }
    
    public function export() {
        $this->requireAdmin();
        
        if (isset($_GET['id']) && isset($_GET['format'])) {
            $report = getReportById($this->pdo, $_GET['id']);
            if ($report && $_GET['format'] == 'csv') {
                $data = json_decode($report['data'], true);
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="report_' . $report['id'] . '_' . $report['type'] . '.csv"');
                $fp = fopen('php://output', 'w');
                if (!empty($data) && is_array($data) && isset($data[0]) && is_array($data[0])) {
                    fputcsv($fp, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($fp, $row);
                    }
                }
                fclose($fp);
                exit;
            }
        }
        
        $this->redirect('index.php?page=reports');
    }
}
?>

