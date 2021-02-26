<?php

require_once "App/Security.php";

// show errors if not in Prod
if (getenv('ENV_PROD')){
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// get POST data and URL through Security.php
$safeData = new Security([
    "post" => [
        "email" => FILTER_SANITIZE_EMAIL,
        "validation_code" => FILTER_SANITIZE_NUMBER_INT,
        ],
]);
$url = $safeData->_url;

// routing
switch ($url[0]){
    case 'sendmail':
        if(!isset($safeData->_post["email"]) || !isset($safeData->_post["validation_code"])){
            $response = [
                "code" => "400",
                "body" => ["error" => "missing data"]
            ];
        }
        else {
            $response = [
                "body" => [
                    "validation_code" => $safeData->_post["validation_code"],
                    "success" => "an email was sent to ".$safeData->_post["email"]
                ]
            ];
        }
        break;
    default:
        $response = [
            "code" => "400",
            "body" => ["error" => "invalid request"]
        ];
        break;
}

// display response
echo json_encode($response["body"]);
