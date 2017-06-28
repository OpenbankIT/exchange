<?php
$config = new \Phalcon\Config([
    "project" => [
        'production'     => 0,
        'log'            => 1,
        'title'          => 'exchange.smartmoney.com.ua',
        'admin_name'     => 'admin',
        'admin_email'    => 'no-reply@iam.dk',
        'upload_dir'     => APP_PATH . 'public/uploads',
        'log_path'       => APP_PATH . 'logs/debug.log',
        'crypt_key'      => 'kQJHN3axcT128602_xUAH3Nyzc63mi8z'
    ],
    "modules" => [
        'frontend'
    ],
    "db" => [
        "adapter"        => "Mysql",
        "host"           => "localhost",
        "username"       => "root",
        "password"       => "",
        "name"           => "iam",
    ],
    "mongo" => [
        'name'           => 'exchange'
    ],
    "smtp" => [
        'host'           => 'smtp.gmail.com',
        'port'           => '465',
        'security'       => 'ssl',
        'username'       => 'login',
        'password'       => 'pwd',
    ],

    "exchange" => [
        "bitcoind" => [
            'host'          => 'bitcoin.smartmoney.com.ua',
            'port'          => 80,
            'user'          => 'bitcoin',
            'pass'          => '6la9W1VqHtji8IwYHOPPmEjp',
            'count_of_confirmations' => 1,
            'decimal_mask'  => '%.8f',
            'rate_url'      => 'https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5',
            'rate_actual'   => 10*60 //in seconds
        ],
        "privat24" => [
            'merchant'      => '120056',
            'secret_key'    => 'GHdI6DOzJTF6vtWP8q7sUwkJmWdisn5M',
            'service_url'   => 'https://api.privatbank.ua/p24api/ishop',
        ],
        "wm" => [
            'wmid'          => '275506368062',
            'wmu'           => 'U244483486935',
            'wmz'           => 'Z128298799543',
            'secret_key'    => '5hP5QNixtoBY2YQS1025mHKN',
            'service_url'   => 'https://merchant.webmoney.ru/lmi/payment.asp',
        ],
    ],
    
    "cashier" => [
        "url" => "http://cashier.smartmoney.com.ua/issue",
        "username" => "username",
        "password" => "password"  
    ],

    'horizon'  => [
        'host'    => 'blockchain.smartmoney.com.ua',
        'port'    => '80',
        'valid_account_types' => [0, 1]
    ],

    "assets" => [
        'EUAH',
        'DUAH',
    ],
    'link' => [
        'name' => 'AtticLab',
        'url' => 'http://atticlab.net/'
    ],
    'allowed_languages' => ['en', 'ru', 'ua']
]);