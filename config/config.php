<?php
/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:31
 */

/**
 * configs
 */

// redis config
$config['redis'] = [
    'default' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 10
    ]
];

// redis queue config
$config['queue'] = [
    'default' => 'default'
];