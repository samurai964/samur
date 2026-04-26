<?php

class Settings {

    private static $data = [];

    // 🔥 cache إضافي
    protected static $cache = [];

    public static function load($pdo) {
        try {
            // ✔ إصلاح العمود
            $stmt = $pdo->query("SELECT `name`,`value` FROM settings");

            self::$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            self::$cache = self::$data;

        } catch (Exception $e) {
            self::$data = [];
            self::$cache = [];
        }
    }

    public static function get($key, $default = null) {

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        return self::$data[$key] ?? $default;
    }

    public static function set($key, $value) {
        global $pdo;

        // ✔ تحقق من وجود القيمة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE `name` = ?");
        $stmt->execute([$key]);

        if ($stmt->fetchColumn() > 0) {

            // ✔ إصلاح المتغير
            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `name` = ?");
            $stmt->execute([$value, $key]);

        } else {

            $stmt = $pdo->prepare("INSERT INTO settings (`name`, `value`) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        // 🔥 تحديث الكاش
        self::$data[$key] = $value;
        self::$cache[$key] = $value;
    }

}
