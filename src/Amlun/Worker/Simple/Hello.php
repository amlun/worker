<?php
namespace Amlun\Worker\Simple;

use Amlun\Worker\Simple;

/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:46
 */
class Hello extends Simple
{
    function _do()
    {
        echo 'Hello' . PHP_EOL;
    }

}