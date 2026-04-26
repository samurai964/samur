<?php

class AdsModel {
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getAds($filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM `{$this->prefix}ads` WHERE 1=1";
        $params = [];

        if (!empty($filters["status"])) {
            $sql .= " AND status = ?";
            $params[] = $filters["status"];
        }
        if (!empty($filters["ad_type"])) {
            $sql .= " AND ad_type = ?";
            $params[] = $filters["ad_type"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAds($filters = []) {
        $sql = "SELECT COUNT(*) FROM `{$this->prefix}ads` WHERE 1=1";
        $params = [];

        if (!empty($filters["status"])) {
            $sql .= " AND status = ?";
            $params[] = $filters["status"];
        }
        if (!empty($filters["ad_type"])) {
            $sql .= " AND ad_type = ?";
            $params[] = $filters["ad_type"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getAdById($adId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}ads` WHERE id = ?");
        $stmt->execute([$adId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAd($data) {
        $sql = "INSERT INTO `{$this->prefix}ads` (title, description, target_url, image_url, budget, start_date, end_date, ad_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["title"],
            $data["description"],
            $data["target_url"],
            $data["image_url"],
            $data["budget"],
            $data["start_date"],
            $data["end_date"],
            $data["ad_type"],
            $data["status"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateAd($adId, $data) {
        $sql = "UPDATE `{$this->prefix}ads` SET title = ?, description = ?, target_url = ?, image_url = ?, budget = ?, start_date = ?, end_date = ?, ad_type = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["title"],
            $data["description"],
            $data["target_url"],
            $data["image_url"],
            $data["budget"],
            $data["start_date"],
            $data["end_date"],
            $data["ad_type"],
            $data["status"],
            $adId
        ]);
        return $stmt->rowCount();
    }

    public function deleteAd($adId) {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}ads` WHERE id = ?");
        $stmt->execute([$adId]);
        return $stmt->rowCount();
    }

    public function getActiveAds() {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}ads` WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW() ORDER BY RAND() LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recordClick($adId, $userId, $ipAddress) {
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}ad_clicks` (ad_id, user_id, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$adId, $userId, $ipAddress]);
        
        // Update ad budget and click count
        $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}ads` SET clicks = clicks + 1, budget_spent = budget_spent + ? WHERE id = ?");
        // Assuming a cost per click (CPC) of 0.1 for now, this should be configurable
        $cpc = 0.1;
        $stmt->execute([$cpc, $adId]);
    }

    public function recordImpression($adId, $userId, $ipAddress) {
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}ad_impressions` (ad_id, user_id, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$adId, $userId, $ipAddress]);

        // Update ad impression count
        $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}ads` SET impressions = impressions + 1 WHERE id = ?");
        $stmt->execute([$adId]);
    }

    public function getAdStats() {
        $stats = [];
        $stats["total_ads"] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}ads`")->fetchColumn();
        $stats["active_ads"] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}ads` WHERE status = 'active'")->fetchColumn();
        $stats["total_clicks"] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}ad_clicks`")->fetchColumn();
        $stats["total_impressions"] = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}ad_impressions`")->fetchColumn();
        $stats["total_budget_spent"] = $this->pdo->query("SELECT SUM(budget_spent) FROM `{$this->prefix}ads`")->fetchColumn();
        return $stats;
    }
}

?>

