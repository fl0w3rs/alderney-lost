<?php

namespace App\Controllers;

use App\Facades\Auth;

class AuthController extends BaseController {
    // public function __construct() {
    //     parent::__construct();
    //     // if(!Auth::haveAccessToAlderney()) {
    //     //     echo "fffffffffffffff";
    //     // }
    // }


    public function renderLogin() {
        return $this->render('login.twig');
    }

    public function apiLogin() {
        return responseJson(['access' => Auth::haveAccessToAlderney()]);
    }
}