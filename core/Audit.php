<?php

class Audit {

    public static function log($action, $details = '') {

        $file = __DIR__ . '/../logs/audit.log';

        $user = $_SESSION['user_id'] ?? 'guest';
        $date = date('Y-m-d H:i:s');

        $line = "[$date] user:$user action:$action details:$details\n";

        file_put_contents($file, $line, FILE_APPEND);
    }
}
?>
