<?php

class SettingsModel {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // 🔹 جلب الإعدادات (مع استثناء csrf_token)
    public function getSettings() {
        try {
            $stmt = $this->pdo->query("
                SELECT `name`, `value` 
                FROM settings 
                WHERE name NOT IN ('csrf_token')
            ");

            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            return is_array($rows) ? $rows : [];

        } catch (Exception $e) {
            return [];
        }
    }

    // 🔹 تحديث الإعدادات (احترافي)
    public function updateSettings($data) {
        try {

            $stmt = $this->pdo->prepare("
                INSERT INTO settings (`name`, `value`)
                VALUES (:name, :value)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
            ");

            foreach ($data as $name => $value) {

                // ❌ تجاهل csrf_token
                if ($name === 'csrf_token') continue;

                $stmt->execute([
                    ':name'  => $name,
                    ':value' => is_array($value) ? json_encode($value) : $value
                ]);
            }

            return true;

        } catch (Exception $e) {
            die("DB ERROR: " . $e->getMessage());
        }
    }

}
?>
