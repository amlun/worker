<?php
namespace Amlun;
/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:14
 */
/**
 * Class Config
 * @package DataFlow
 */
class Config
{
    private static $_data = [];
    private static $_loaded = false;
    const _DEFAULT = 'default';

    public static function load($path)
    {
        if (self::$_loaded) {
            return self::$_data;
        }
        $paths = self::getValidPath($path);
        foreach ($paths as $path) {
            require $path;
            // Try and load file
            self::$_data = array_replace_recursive(self::$_data, $config);
        }
        self::$_loaded = true;
        return self::$_data;
    }

    public static function data()
    {
        return self::$_data;
    }

    public static function get($type, $name, $check = true)
    {
        if (isset(self::$_data[$type][$name])) {
            return self::$_data[$type][$name];
        } elseif (isset(self::$_data[$type][self::_DEFAULT])) {
            return self::$_data[$type][self::_DEFAULT];
        } elseif ($check) {
            throw new Exception("Can not load the {$type} config!");
        }
        return false;
    }

    public static function getValidPath($path)
    {
        // If `$path` is array
        if (is_array($path)) {
            return self::getPathFromArray($path);
        }
        // If `$path` is a directory
        if (is_dir($path)) {
            $paths = glob($path . '/*.php');
            if (empty($paths)) {
                throw new Exception("Configuration directory: [$path] is empty");
            }
            return $paths;
        }
        // If `$path` is not a file, throw an exception
        if (!file_exists($path)) {
            throw new Exception("Configuration file: [$path] cannot be found");
        }
        return array($path);
    }

    public static function getPathFromArray($path)
    {
        $paths = array();
        foreach ($path as $unverifiedPath) {
            try {
                // Check if `$unverifiedPath` is optional
                // If it exists, then it's added to the list
                // If it doesn't, it throws an exception which we catch
                if ($unverifiedPath[0] !== '?') {
                    $paths = array_merge($paths, self::getValidPath($unverifiedPath));
                    continue;
                }
                $optionalPath = ltrim($unverifiedPath, '?');
                $paths = array_merge($paths, self::getValidPath($optionalPath));
            } catch (Exception $e) {
                // If `$unverifiedPath` is optional, then skip it
                if ($unverifiedPath[0] === '?') {
                    continue;
                }
                // Otherwise rethrow the exception
                throw $e;
            }
        }
        return $paths;
    }
}