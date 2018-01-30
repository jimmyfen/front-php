<?php

if (!function_exists('config')) {
    function config($file) {
        if (is_file(BASE_PATH . DS . 'config' . DS . $file . '.php')) {
            return include(BASE_PATH . DS . 'config' . DS . $file . '.php');
        }
        return false;
    }
}

if (!function_exists('model')) {
    function model($model) {
        if (is_file(APP_PATH . DS . 'model' . DS . ucwords($model) . '.php')) {
            include_once(APP_PATH . DS . 'model' . DS . ucwords($model) . '.php');
            $model = '\model\\'.ucwords($model);
            if (class_exists($model)) {
                $m = new $model();
                return $m;
            }
        }
    }
}

if (!function_exists('get_openid')) {
    function get_openid($key, $expire = 2592000) {
        $file = BASE_PATH . DS . 'data' . DS . 'session' . DS . $key;
        if (!is_file($file) || filemtime($file) + $expire < time()) {
            return false;
        }
        $session_data = unserialize(substr(file_get_contents($file), 7));
        return $session_data['openid'];
    }
}