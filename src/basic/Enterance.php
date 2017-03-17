<?php
namespace dzer\coltaobao\basic;
/**
 * 自动注册
 *
 * @package dzer\coltaobao\basic
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class Enterance
{
    private static $classPath = array();
    public static $rootPath;
    public static $configPath;

    final public static function autoLoader($class)
    {
        if (isset(self::$classPath[$class])) {
            require self::$classPath[$class];
            return;
        }
        $baseClasspath = \str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $libs = array(
            self::$rootPath . DIRECTORY_SEPARATOR . 'app',
            self::$rootPath,
        );
        foreach ($libs as $lib) {
            $classpath = $lib . DIRECTORY_SEPARATOR . $baseClasspath;
            if (\is_file($classpath)) {
                self::$classPath[$class] = $classpath;
                require "{$classpath}";
                return;
            }
        }
    }

    public static function run($runPath, $configPath = '')
    {
        self::$rootPath = $runPath;
        self::$configPath = !empty($configPath) ? $configPath : ($runPath . '/config/');
        \spl_autoload_register(__CLASS__ . '::autoLoader');
        Config::load(self::$configPath);

        $timeZone = Config::get('timeZone', 'Asia/Shanghai');
        \date_default_timezone_set($timeZone);
    }
}



