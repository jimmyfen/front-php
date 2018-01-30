<?php
namespace core;
class Controller
{
    public function __construct(){}

    public function json($code = 200, $msg = 'ok', $data = [])
    {
        header('Content-Type: text/json; charset=UTF-8');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    public function post()
    {
        $data = array();
        if (!empty($_POST)) {
            $data = $_POST;
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $value[$k] = htmlspecialchars($v);
                    }
                    $data[$key] = $value;
                    continue;
                }
                $data[$key] = htmlspecialchars($value);
            }
        }

        return $data;
    }
}