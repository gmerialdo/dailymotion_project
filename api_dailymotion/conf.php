<?php

//environnement production
$GLOBALS["envProd"] = false;

//database connection
$GLOBALS["db"] = [
    "host"      => 'mysql',
    "user"      => 'root',
    "password"  => 'root',
    "database"  => 'dailymotion'
];

$GLOBALS["api_mail_url"] = 'http://localhost/api_mail_server/sendmail';
