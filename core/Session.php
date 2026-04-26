<?php
// Final Max CMS - Core Session Management

class Session {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }

    public static function flash($message, $type = 'success') {
        self::set('flash_message', ['message' => $message, 'type' => $type]);
    }

    public static function getFlash() {
        if (self::get('flash_message')) {
            $message = self::get('flash_message');
            self::delete('flash_message');
            return $message;
        }
        return null;
    }
}

// Initialize session automatically when config is loaded
// Session::init(); // تم التعليق لمنع بدء الجلسة مبكراً
?>



