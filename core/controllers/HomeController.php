<?php

namespace App\Controllers;

use \RedBeanPHP\R as R;

use App\Facades\Auth;
use App\Facades\Test;

class HomeController extends BaseController {
    // public function __construct() {
    //     parent::__construct();
    // }

    public function renderHome() {
        $user_groups = Auth::getForumUserGroups();

        R::selectDatabase('main');
        $this->params['tests'] = R::getAssocRow("SELECT * FROM tests");

        foreach($this->params['tests'] as $k => $v) {
            if(!Test::canUserStartTest($v['id'])) {
                unset($this->params['tests'][$k]);
                continue;
            }
        }

        $this->params['is_admin'] = Auth::isAdmin();

        // var_dump($this->params['tests']);

        return $this->render('home.twig');
    }
}