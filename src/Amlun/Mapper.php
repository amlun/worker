<?php
namespace Amlun;
    /**
     * Created by PhpStorm.
     * User: lunweiwei
     * Date: 16/3/11
     * Time: 上午11:15
     */
/**
 * Class Map
 * @package DataFlow\Mapper
 */
abstract class Mapper
{
    /**
     * 所有的包含的值
     * @var array
     */
    protected static $_data = [];
    /**
     * 映射对应的值
     * @var array
     */
    protected static $_map_data = [];

    public static function from($type, $value)
    {
        $key = array_search($value, static::$_map_data[$type]);
        if (isset(static::$_data[$key])) {
            return static::$_data[$key];
        }
        throw new Exception("Can not find the {$type} of Mapper data, value is {$value}, key is {$key}");
    }

    public static function data()
    {
        return static::$_data;
    }
}