<?php
namespace colTaoBao\basic;

/**
 * 日志类
 *
 * @package colTaoBao\basic
 * @author dzer <d20053140@gmail.com>
 * @version 2.0
 */
class Log
{
    private static $instance = null;
    private $config;
    private $file = [];
    private $file_path;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Log(Config::get('log'));
        }
        return self::$instance;
    }

    private function __construct($config)
    {
        $this->config = $config;
        $this->file_path = isset($config) ? $config : '/var/log/coltaobao';

        if (!file_exists($this->file_path)) {
            @mkdir($this->file_path, 0755, true);
        }
    }

    public function debug($content)
    {
        $this->save('_DEBUG', $content);
    }

    public function error($content)
    {
        $this->save('_ERROR', $content);
    }

    public function info($content)
    {
        $this->save('_INFO', $content);
    }

    public function warning($content)
    {
        $this->save('_WARNING', $content);
    }

    protected function save($path, $content)
    {
        $log_file = $this->file_path . $path . '_' . date("Y-m-d");
        if (!isset($this->file[$path])) {
            $last = $this->file_path . $path . '_' . date("Y-m-d", strtotime("-1 day"));
            if (isset($this->file[$last])) {
                fclose($this->file[$last]);
                unset($this->file[$last]);
            }
            $this->file[$path] = fopen($log_file, 'a');
        }
        if (is_array($content)) {
            $str = date('Y-m-d H:i:s') . ": " . var_export($content, true);
        } else {
            $str = date("Y-m-d H:i:s") . ": " . $content;
        }
        fwrite($this->file[$path], $str . "\r\n");
    }
}
