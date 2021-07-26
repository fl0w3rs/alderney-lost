<?php

namespace App\Controllers;

class ErrorController extends BaseController {
    public function renderError(string $title, $text = '') {
        if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return responseJson(['status' => 'error', 'error' => ['title' => $title, 'message' => $text]]);
        } else {
            $this->params['title'] = $title;
            $this->params['text'] = $text;
            
            return $this->render('error.twig');
        }
    }
}