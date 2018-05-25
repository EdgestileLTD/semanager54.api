<?php

error_reporting(E_ALL);
// ошибка журнала
function log_error( $num, $str, $file, $line, $context = null )
{
    if($num > 8){
        $a = explode('api',$file);
        $file = array_pop($a);
        writeLog($num.'['.$file.'|'.$line.'] '.$str, 'ERROR');
    }
}

set_error_handler('log_error');


spl_autoload_register(function ($class) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file))
        include_once $file;
    {
        if (file_exists(DOCUMENT_ROOT . '/lib/classes/' . $class . '.class.php')) {
            require_once DOCUMENT_ROOT . '/lib/classes/' . $class . '.class.php';
        } else {
            if (strpos($class, 'plugin_') !== false) {
                if ($handle = opendir(DOCUMENT_ROOT . '/lib/plugins/')) {
                    while (false !== ($file = readdir($handle))) {

                        if (is_dir(DOCUMENT_ROOT . '/lib/plugins/' . $file)
                            && strpos($file, "plugin_") !== false && strpos(strtolower($class), $file) !== false
                        ) {
                            require_once DOCUMENT_ROOT . '/lib/plugins/' . $file . '/' . strtolower($class) . '.class.php';
                        }
                    }
                }
                closedir($handle);
            }
        }
    }
});
