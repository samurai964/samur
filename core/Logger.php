<?php

class Logger {

    public static function log($message) {

        $file = __DIR__ . '/../logs/app.log';

        $date = date('Y-m-d H:i:s');

        file_put_contents($file, "[$date] $message\n", FILE_APPEND);
    }
}
?>
