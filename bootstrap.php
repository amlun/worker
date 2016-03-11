<?php
/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:11
 */
error_reporting(E_ALL | E_STRICT);
/**
 * 定义根目录
 */
define('ROOT', __DIR__);
/**
 * 定义BIN目录
 */
define('BIN_DIR', ROOT . DIRECTORY_SEPARATOR . 'bin');
/**
 * 定义config目录
 */
define('CON_DIR', ROOT . DIRECTORY_SEPARATOR . 'config');
/**
 * 自动加载
 */
require_once __DIR__ . '/vendor/autoload.php';
/**
 * 加载环境变量
 */
$env = getenv('APP_ENV');
/**
 * 加载配置
 */
$config_dir = CON_DIR;
if ($env) {
    $config_dir = CON_DIR . DIRECTORY_SEPARATOR . $env;
}
Amlun\Config::load($config_dir);