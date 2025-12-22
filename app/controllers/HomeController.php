<?php
require_once __DIR__ . '/../core/BaseController.php';

class HomeController extends BaseController {
    
    public function index() {
        $this->setPageTitle('Home');
        $this->render('home');
    }
}
?>

