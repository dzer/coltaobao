<?php

/**
 * 自动注册类
 *
 * @author dx <358654744@qq.com>
 * @date 2015-11-04
 * @version 1.0
 */
TbAutoloader::register();

class TbAutoloader
{

    /**
     * 注册自动加载方法
     *
     * @return bool
     */
    public static function register()
    {
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
        $register = spl_autoload_register(array('TbAutoloader', 'load'));
        return $register;
    }

    /**
     * 自动加载类
     *
     * @param string $className 类名
     * @return null
     */
    public static function load($className)
    {
        if ((class_exists($className, false)) || (strpos($className, 'Tb') !== 0)) {
            return false;
        }
        $classFilePath = TB_ROOT . '/lib/' . $className . '.php';
        if (file_exists($classFilePath) === false) {
            $classFilePath = TB_ROOT . '/basic/' . $className . '.php';
        }
        if ((file_exists($classFilePath) !== false) && (is_readable($classFilePath) !== false)) {
            require ($classFilePath);
        }
        return true;
    }

}
