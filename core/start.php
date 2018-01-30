<?php

if (DEBUG) {
    ini_set('display_errors', 'On');
}

defined('CORE_PATH') || define('CORE_PATH', BASE_PATH . DS . 'core');

if (is_file(BASE_PATH . DS . 'common' . DS . 'functions.php')) {
    include_once BASE_PATH . DS . 'common' . DS . 'functions.php';
}

if (!function_exists('load')) {
    function load($classname){
        $core_file = str_replace('\\', '/', BASE_PATH . DS . $classname . '.php');
        if (is_file($core_file)) {
            include_once($core_file);
            return;
        }
        $controller_file = str_replace('\\', '/', APP_PATH . DS . $classname . '.php');
        if (!is_file($controller_file)) {
            exit($classname . ' 控制器不存在！');
        }
        include_once($controller_file);
    }
}

spl_autoload_register('load');

if (!isset($_GET['method'])) {
    $_GET['method'] = 'index';
}

$url = $_GET['method'];

if (isset($url)) {
    $url = htmlspecialchars($url);
    $method_arr = explode('.', $url);
    $len = count($method_arr);
    if ($len === 0) {
        $controller = new \controller\Index;
        $method = 'index';
    } elseif ($len === 1) {
        $c = '\controller\\' . ucwords($method_arr[0]);
        $controller = new $c;
        $method = 'index';
    } else {
        $c = '\controller\\' . ucwords($method_arr[0]);
        $controller = new $c;
        $method = $method_arr[1];
    }
    if (!method_exists($controller, $method)) {
        exit($method . '方法不存在！');
    }
    $controller->$method();
}

