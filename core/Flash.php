<?php

class Flash {

    public static function set($type, $message) {
        $_SESSION['_flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function get() {

        if (!isset($_SESSION['_flash'])) return null;

        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);

        return $flash;
    }
}
?>
