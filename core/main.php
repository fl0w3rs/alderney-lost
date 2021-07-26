<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/config.php';
// require __DIR__ . '/logging.php';
require __DIR__ . '/database.php';

function responseJson(array $arr) {
    header("Content-type: application/json");
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

$input = file_get_contents("php://input");
if(!empty($input) && isJson($input)) {
    $_POST = array_merge($_POST, json_decode($input, true));

}