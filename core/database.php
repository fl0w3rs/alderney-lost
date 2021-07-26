<?php
use \RedBeanPHP\R as R;

R::addDatabase( 'main', 'mysql:host='. config["db_host"] .';dbname='. config["db_name"], config["db_user"], config["db_password"] );
R::selectDatabase('main', true);
if(!R::testConnection()) {
    $cntrl = new \App\Controllers\ErrorController;
    $cntrl->renderError('DBC', 'Не удалось подключиться к базе данных #1');
    die();
}

R::addDatabase( 'forum', 'mysql:host='. config["forum_db_host"] .';dbname='. config["forum_db_name"], config["forum_db_user"], config["forum_db_password"] );
R::selectDatabase('forum');
if(!R::testConnection()) {
    $cntrl = new \App\Controllers\ErrorController;
    $cntrl->renderError('DBC', 'Не удалось подключиться к базе данных #2');
    die();
}

R::ext('xdispense', function( $type ){ 
    return R::getRedBean()->dispense( $type ); 
});