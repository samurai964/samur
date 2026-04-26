<?php

class PointsModel {
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getUserPoints($userId) {
        $stmt = $this->pdo->prepare("SELECT `points` FROM `{$this->prefix}users` WHERE `id` = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function addPointsToUser($userId, $points, $action = null, $referenceId = null, $referenceType = null) {
        $this->pdo->beginTransaction();
        try {
            // تحديث نقاط المستخدم
            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}users` SET `points` = `points` + ? WHERE `id` = ?");
            $stmt->execute([$points, $userId]);

            // تسجيل المعاملة
            $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}point_transactions` (user_id, points, action, reference_id, reference_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $points, $action, $referenceId, $referenceType]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error adding points: " . $e->getMessage());
            return false;
        }
    }

    public function getLeaderboard($limit = 10) {
        $stmt = $this->pdo->prepare("SELECT u.username, u.avatar, u.points FROM `{$this->prefix}users` u ORDER BY u.points DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPointsSettings() {
        $settings = [];
        $stmt = $this->pdo->query("SELECT `key`, `value` FROM `{$this->prefix}settings` WHERE `key` LIKE 'point_%' OR `key` LIKE 'online_time_points_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row["key"]] = $row["value"];
        }

        // Default settings
        return array_merge([
            "point_registration" => 100,
            "point_login_daily" => 10,
            "point_create_topic" => 50,
            "point_create_comment" => 5,
            "point_receive_like" => 2,
            "point_give_like" => -1,
            "point_complete_course" => 200,
            "point_complete_lesson" => 10,
            "point_create_service" => 100,
            "point_complete_freelance_project" => 150,
            "online_time_points_enabled" => 0,
            "online_time_points_per_hour" => 5,
            "online_time_points_max_daily" => 50,
        ], $settings);
    }

    public function updatePointsSettings($settings) {
        $this->pdo->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute([$key, $value]);
            }
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating points settings: " . $e->getMessage());
            return false;
        }
    }

    public function getLastOnlineTime($userId) {
        $stmt = $this->pdo->prepare("SELECT `last_online_at` FROM `{$this->prefix}users` WHERE `id` = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function updateLastOnlineTime($userId) {
        $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}users` SET `last_online_at` = NOW() WHERE `id` = ?");
        $stmt->execute([$userId]);
    }

    public function getPointsStats() {
        $stats = [];
        $stats["total_points_awarded"] = $this->pdo->query("SELECT SUM(points) FROM `{$this->prefix}point_transactions` WHERE points > 0")->fetchColumn() ?: 0;
        $stats["total_points_spent"] = $this->pdo->query("SELECT SUM(points) FROM `{$this->prefix}point_transactions` WHERE points < 0")->fetchColumn() ?: 0;
        $stats["total_users_with_points"] = $this->pdo->query("SELECT COUNT(DISTINCT user_id) FROM `{$this->prefix}point_transactions`")->fetchColumn() ?: 0;
        return $stats;
    }
}

?>

