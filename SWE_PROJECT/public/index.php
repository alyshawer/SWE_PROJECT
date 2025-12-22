<?php
// Public entry point - only file accessible from web
session_start();

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Autoloader for classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
        APP_PATH . '/core/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load helper functions
require_once APP_PATH . '/helpers/db_functions.php';
require_once APP_PATH . '/helpers/view_helper.php';

// Load database connection (creates $pdo variable)
require_once APP_PATH . '/config/database.php';

// Simple Router for MVC
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Route mapping
$routes = [
    'home' => ['HomeController', 'index'],
    'login' => ['AuthController', 'login'],
    'register' => ['AuthController', 'register'],
    'logout' => ['AuthController', 'logout'],
    'dashboard' => ['DashboardController', 'index'],
    'browse_freelancers' => ['DashboardController', 'browseFreelancers'],
    'freelancers' => ['FreelancersController', 'index'],
    'jobs' => ['JobController', 'index'],
    'job' => ['JobController', 'show'],
    'post_job' => ['JobController', 'create'],
    'apply_job' => ['JobController', 'apply'],
    'admin' => ['AdminController', 'index'],
    'disputes' => ['DisputeController', 'index'],
    'reports' => ['ReportController', 'index'],
    'payments' => ['PaymentController', 'index'],
    'audit_logs' => ['AuditLogController', 'index'],
];

// Default to home if no page specified
if (empty($page) || $page == 'index.php') {
    $page = 'home';
}

// Handle special actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    switch ($page) {
        case 'admin':
            $routes[$page] = ['AdminController', $action];
            break;
        case 'dashboard':
            if ($action == 'delete_job') {
                $routes[$page] = ['DashboardController', 'deleteJob'];
            } elseif ($action == 'edit_portfolio') {
                $routes[$page] = ['DashboardController', 'editPortfolio'];
            }
            break;
        case 'freelancers':
            if ($action == 'profile') {
                $routes[$page] = ['FreelancersController', 'profile'];
            }
            break;
        case 'disputes':
            if ($action == 'create') {
                $routes[$page] = ['DisputeController', 'create'];
            } elseif ($action == 'resolve') {
                $routes[$page] = ['DisputeController', 'resolve'];
            }
            break;
        case 'reports':
            if ($action == 'export') {
                $routes[$page] = ['ReportController', 'export'];
            }
            break;
        case 'payments':
            if ($action == 'create') {
                $routes[$page] = ['PaymentController', 'create'];
            } elseif ($action == 'complete') {
                $routes[$page] = ['PaymentController', 'complete'];
            }
            break;
    }
}

// Handle POST requests for actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $routes['admin'] = ['AdminController', 'deleteUser'];
        $page = 'admin';
    } elseif (isset($_POST['suspend_user'])) {
        $routes['admin'] = ['AdminController', 'suspendUser'];
        $page = 'admin';
    } elseif (isset($_POST['activate_user'])) {
        $routes['admin'] = ['AdminController', 'activateUser'];
        $page = 'admin';
    } elseif (isset($_POST['delete_job']) && $page == 'admin') {
        $routes['admin'] = ['AdminController', 'deleteJob'];
    } elseif (isset($_POST['create_category'])) {
        $routes['admin'] = ['AdminController', 'createCategory'];
        $page = 'admin';
    } elseif (isset($_POST['delete_category'])) {
        $routes['admin'] = ['AdminController', 'deleteCategory'];
        $page = 'admin';
    } elseif (isset($_POST['raise_dispute'])) {
        $routes['disputes'] = ['DisputeController', 'create'];
        $page = 'disputes';
    } elseif (isset($_POST['resolve_dispute'])) {
        $routes['disputes'] = ['DisputeController', 'resolve'];
        $page = 'disputes';
    } elseif (isset($_POST['generate_report'])) {
        $routes['reports'] = ['ReportController', 'index'];
        $page = 'reports';
    } elseif (isset($_POST['create_payment'])) {
        $routes['payments'] = ['PaymentController', 'create'];
        $page = 'payments';
    } elseif (isset($_POST['complete_payment'])) {
        $routes['payments'] = ['PaymentController', 'complete'];
        $page = 'payments';
    } elseif (isset($_POST['delete_job']) && $page == 'dashboard') {
        $routes['dashboard'] = ['DashboardController', 'deleteJob'];
    } elseif (isset($_POST['apply_job'])) {
        $routes['apply_job'] = ['JobController', 'apply'];
        $page = 'apply_job';
    }
    elseif (isset($_POST['update_application_status'])) {
        // Route job page POST to JobController->updateApplicationStatus
        $routes['job'] = ['JobController', 'updateApplicationStatus'];
        $page = 'job';
    }
}

// Route to controller
if (isset($routes[$page])) {
    list($controllerName, $method) = $routes[$page];
    $controllerFile = APP_PATH . '/controllers/' . $controllerName . '.php';
    
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controller = new $controllerName($pdo);
        
        if ($id) {
            $controller->$method($id);
        } else {
            $controller->$method();
        }
    } else {
        die("Controller not found: {$controllerName}");
    }
} else {
    // Default home page - route to HomeController
    $controllerFile = APP_PATH . '/controllers/HomeController.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        $controller = new HomeController($pdo);
        $controller->index();
    } else {
        die("HomeController not found");
    }
}
?>

