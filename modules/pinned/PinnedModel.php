<?php

class PinnedModel {
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getPinPricing() {
        $settings = [];
        $stmt = $this->pdo->query("SELECT `key`, `value` FROM `{$this->prefix}settings` WHERE `key` LIKE 'pin_price_%' OR `key` LIKE 'enable_topic_pinning' OR `key` LIKE 'max_pinned_topics' OR `key` LIKE 'admin_free_pinning' OR `key` LIKE 'show_pinned_ticker' OR `key` LIKE 'ticker_speed' OR `key` LIKE 'pin_discount_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row["key"]] = $row["value"];
        }

        // Default settings
        return array_merge([
            'pin_price_1_month' => 10,
            'pin_price_6_months' => 50,
            'pin_price_1_year' => 100,
            'enable_topic_pinning' => 1,
            'max_pinned_topics' => 20,
            'admin_free_pinning' => 0,
            'show_pinned_ticker' => 1,
            'ticker_speed' => 50,
            'pin_discount_6_months' => 10,
            'pin_discount_1_year' => 20,
        ], $settings);
    }

    public function updatePinPricing($settings) {
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
            error_log("Error updating pin pricing settings: " . $e->getMessage());
            return false;
        }
    }

    public function getTopicPinStatus($topicId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}pinned_topics` WHERE `topic_id` = ? AND `status` = 'active' AND `expires_at` > NOW()");
        $stmt->execute([$topicId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPinnedTopic($data) {
        $sql = "INSERT INTO `{$this->prefix}pinned_topics` (topic_id, user_id, duration, price, payment_method, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['topic_id'],
            $data['user_id'],
            $data['duration'],
            $data['price'],
            $data['payment_method'],
            $data['expires_at'],
            $data['status']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateTopicPinStatus($topicId, $status, $expiresAt = null) {
        $sql = "UPDATE `{$this->prefix}topics` SET `is_pinned` = ?, `pinned_until` = ? WHERE `id` = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([($status === 'active' ? 1 : 0), $expiresAt, $topicId]);
        return $stmt->rowCount();
    }

    public function expirePinnedTopic($pinId) {
        $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}pinned_topics` SET `status` = 'expired' WHERE `id` = ?");
        $stmt->execute([$pinId]);
        return $stmt->rowCount();
    }

    public function getPinnedTopics($limit = 20) {
        $stmt = $this->pdo->query("SELECT t.id, t.title, t.slug, t.views, t.likes, t.created_at, u.username, u.avatar, c.name as category_name, c.slug as category_slug, pt.expires_at FROM `{$this->prefix}topics` t JOIN `{$this->prefix}users` u ON t.author_id = u.id LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id JOIN `{$this->prefix}pinned_topics` pt ON t.id = pt.topic_id WHERE t.status = 'published' AND t.is_pinned = 1 AND pt.status = 'active' AND pt.expires_at > NOW() ORDER BY pt.created_at DESC LIMIT {$limit}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPinnedTopics($limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("SELECT pt.id, pt.duration, pt.price, pt.payment_method, pt.expires_at, pt.status, pt.created_at, t.id as topic_id, t.title, t.slug, u.username, u.email FROM `{$this->prefix}pinned_topics` pt JOIN `{$this->prefix}topics` t ON pt.topic_id = t.id JOIN `{$this->prefix}users` u ON pt.user_id = u.id ORDER BY pt.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAllPinnedTopics() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}pinned_topics`");
        return $stmt->fetchColumn();
    }

    public function getPinningStats() {
        $stats = [];
        $stats['total_revenue'] = $this->pdo->query("SELECT SUM(price) FROM `{$this->prefix}pinned_topics` WHERE payment_method != 'admin_free'")->fetchColumn() ?: 0;
        $stats['active_pins'] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}pinned_topics` WHERE status = 'active' AND expires_at > NOW()")->fetchColumn();
        $stats['total_pins'] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}pinned_topics`")->fetchColumn();
        $stats['avg_price'] = $this->pdo->query("SELECT AVG(price) FROM `{$this->prefix}pinned_topics` WHERE payment_method != 'admin_free' AND price > 0")->fetchColumn() ?: 0;
        return $stats;
    }

    public function cleanupExpiredPins() {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}pinned_topics` SET `status` = 'expired' WHERE `status` = 'active' AND `expires_at` <= NOW()");
            $stmt->execute();

            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}topics` t LEFT JOIN `{$this->prefix}pinned_topics` pt ON t.id = pt.topic_id SET t.is_pinned = 0, t.pinned_until = NULL WHERE t.is_pinned = 1 AND (pt.status != 'active' OR pt.expires_at <= NOW() OR pt.id IS NULL)");
            $stmt->execute();

            $this->pdo->commit();
            return ['success' => true, 'message' => 'تم تنظيف المواضيع المنتهية الصلاحية'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Cleanup error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في التنظيف: ' . $e->getMessage()];
        }
    }
}

?>

