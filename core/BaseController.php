<?php

class BaseController {

    protected $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function view($path, $data = []) {
        extract($data);
        require $path;
    }

}
