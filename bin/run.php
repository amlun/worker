#! /usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:54
 */
use Amlun\Worker;
/**
 * 初始化操作
 */
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

/**
 * worker run
 */
$worker = new Amlun\Worker\Simple\Hello('hello');