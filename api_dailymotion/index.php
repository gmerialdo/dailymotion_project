<?php

require_once "App/Model/Model.php";
require_once "App/Controller/RegisterController.php";
require_once "App/Controller/Security.php";
require_once "conf.php";

Model::init();

$envProd=$GLOBALS["envProd"];

// show errors if not in envProd
if (!$envProd){
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// get POST data and URL through Security.php
$safeData = new Security([
    "post" => [
        "email" => FILTER_VALIDATE_EMAIL,
        "password" => ['filter' => FILTER_SANITIZE_SPECIAL_CHARS, 'flags' => FILTER_FLAG_STRIP_LOW],
        "password_confirmation" => ['filter' => FILTER_SANITIZE_SPECIAL_CHARS, 'flags' => FILTER_FLAG_STRIP_LOW],
        "validation_code" => ['filter' => FILTER_SANITIZE_STRING, 'flags' => FILTER_FLAG_STRIP_LOW],
        ],
]);
$url = $safeData->_url;

// routing
switch ($url[0]){
    case 'register':
        $register = new RegisterController($url);
        if(!isset($url[1])) $url[1] = null;
        switch ($url[1]) {
            case 'user':
                $response = $register->createUser();
                break;
            case 'verify':
                $response = $register->verifyCode();
                break;
            default:
                $response = [
                    "code" => "400",
                    "body" => ["error" => "invalid request"]
                ];
        };
    break;
    // later we can add other cases such as login, ...
    default:
        $response = [
            "code" => "400",
            "body" => ["error" => "invalid request"]
        ];
        break;
}

// display response
if(!isset($response["code"])) $response["code"] = 200;
http_response_code($response["code"]);
echo json_encode($response["body"]);




