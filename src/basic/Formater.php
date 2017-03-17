<?php

namespace dzer\coltaobao\basic;

/**
 * Class Formater.
 *
 * @author dzer <d20053140@gmail.com>
 *
 * @version 2.0
 */
class Formater
{
    public static function fatal($error, $trace = true)
    {
        $exceptionHash = [
            'className' => 'fatal',
            'message'   => $error['message'],
            'code'      => $error['code'],
            'file'      => $error['file'],
            'line'      => $error['line'],
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'trace'     => [],
        ];
        if ($trace) {
            $traceItems = debug_backtrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = [
                    'file'     => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line'     => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args'     => [],
                ];
                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }
                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }
                if (!empty($traceItem['args'])) {
                    foreach ($traceItem['args'] as $argsItem) {
                        $traceHash['args'][] = \var_export($argsItem, true);
                    }
                }
                $exceptionHash['trace'][] = $traceHash;
            }
        }

        return $exceptionHash;
    }

    public static function exception(\Exception $exception, $trace = true)
    {
        $exceptionHash = [
            'className' => 'Exception',
            'message'   => $exception->getMessage(),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'userAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'trace'     => [],
        ];
        if ($trace) {
            $traceItems = $exception->getTrace();
            foreach ($traceItems as $traceItem) {
                $traceHash = [
                    'file'     => isset($traceItem['file']) ? $traceItem['file'] : 'null',
                    'line'     => isset($traceItem['line']) ? $traceItem['line'] : 'null',
                    'function' => isset($traceItem['function']) ? $traceItem['function'] : 'null',
                    'args'     => [],
                ];
                if (!empty($traceItem['class'])) {
                    $traceHash['class'] = $traceItem['class'];
                }
                if (!empty($traceItem['type'])) {
                    $traceHash['type'] = $traceItem['type'];
                }
                if (!empty($traceItem['args'])) {
                    foreach ($traceItem['args'] as $argsItem) {
                        $traceHash['args'][] = \var_export($argsItem, true);
                    }
                }
                $exceptionHash['trace'][] = $traceHash;
            }
        }

        return $exceptionHash;
    }
}
