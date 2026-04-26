<?php

require_once ROOT_PATH . '/core/Model.php';
require_once ROOT_PATH . '/core/Security.php';

class InternalAdsModel extends Model {
    private $pdo;
    protected $prefix;

    public function __construct($pdo, $prefix) {
        parent::__construct($pdo);
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    // إضافة إعلان داخلي جديد
    public function addInternalAd($title, $content, $imageUrl, $linkUrl, $position, $pages, $startDate, $endDate, $priority, $isActive) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}internal_ads` 
                (`title`, `content`, `image_url`, `link_url`, `position`, `pages`, 
                 `start_date`, `end_date`, `priority`, `is_active`, `created_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $pagesJson = json_encode($pages);
            $stmt->execute([
                $title, $content, $imageUrl, $linkUrl, $position, 
                $pagesJson, $startDate, $endDate, $priority, $isActive
            ]);

            return ['success' => true, 'message' => 'تم إضافة الإعلان بنجاح.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة الإعلان: ' . $e->getMessage()];
        }
    }

    // تحديث إعلان داخلي
    public function updateInternalAd($adId, $title, $content, $imageUrl, $linkUrl, $position, $pages, $startDate, $endDate, $priority, $isActive) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}internal_ads` 
                SET `title` = ?, `content` = ?, `image_url` = ?, `link_url` = ?, 
                    `position` = ?, `pages` = ?, `start_date` = ?, `end_date` = ?, 
                    `priority` = ?, `is_active` = ?, `updated_at` = NOW()
                WHERE `id` = ?
            ");
            
            $pagesJson = json_encode($pages);
            $stmt->execute([
                $title, $content, $imageUrl, $linkUrl, $position, 
                $pagesJson, $startDate, $endDate, $priority, $isActive, $adId
            ]);

            return ['success' => true, 'message' => 'تم تحديث الإعلان بنجاح.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث الإعلان: ' . $e->getMessage()];
        }
    }

    // حذف إعلان داخلي
    public function deleteInternalAd($adId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}internal_ads` WHERE `id` = ?");
            $stmt->execute([$adId]);

            return ['success' => true, 'message' => 'تم حذف الإعلان بنجاح.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف الإعلان: ' . $e->getMessage()];
        }
    }

    // تغيير حالة الإعلان
    public function toggleInternalAdStatus($adId) {
        try {
            // جلب الحالة الحالية
            $stmt = $this->pdo->prepare("SELECT `is_active` FROM `{$this->prefix}internal_ads` WHERE `id` = ?");
            $stmt->execute([$adId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus === false) {
                return ['success' => false, 'message' => 'الإعلان غير موجود.'];
            }

            // تغيير الحالة
            $newStatus = $currentStatus ? 0 : 1;
            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}internal_ads` SET `is_active` = ? WHERE `id` = ?");
            $stmt->execute([$newStatus, $adId]);

            $statusText = $newStatus ? 'مفعل' : 'غير مفعل';
            return ['success' => true, 'message' => "تم تغيير حالة الإعلان إلى: $statusText"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تغيير حالة الإعلان: ' . $e->getMessage()];
        }
    }

    // جلب جميع الإعلانات الداخلية
    public function getAllInternalAds($limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}internal_ads` 
                ORDER BY `priority` DESC, `created_at` DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // عد جميع الإعلانات الداخلية
    public function countAllInternalAds() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}internal_ads`");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // جلب إعلان داخلي بالمعرف
    public function getInternalAdById($adId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}internal_ads` WHERE `id` = ?");
            $stmt->execute([$adId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب الإعلانات النشطة لصفحة وموضع معين
    public function getActiveAdsForPage($page, $position) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}internal_ads` 
                WHERE `is_active` = 1 
                AND `position` = ? 
                AND (
                    `pages` = '[]' OR 
                    `pages` LIKE ? OR 
                    `pages` LIKE ? OR 
                    `pages` LIKE ?
                )
                AND (
                    `start_date` IS NULL OR `start_date` <= NOW()
                )
                AND (
                    `end_date` IS NULL OR `end_date` >= NOW()
                )
                ORDER BY `priority` DESC, `created_at` DESC
            ");
            
            $pagePattern1 = '%"' . $page . '"%';
            $pagePattern2 = '%[' . $page . ']%';
            $pagePattern3 = '%[' . $page . ',%';
            
            $stmt->execute([$position, $pagePattern1, $pagePattern2, $pagePattern3]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // تسجيل مشاهدة إعلان
    public function recordAdView($adId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}internal_ads` 
                SET `views` = `views` + 1 
                WHERE `id` = ?
            ");
            $stmt->execute([$adId]);

            return ['success' => true, 'message' => 'تم تسجيل المشاهدة.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تسجيل المشاهدة.'];
        }
    }

    // تسجيل نقرة إعلان
    public function recordAdClick($adId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}internal_ads` 
                SET `clicks` = `clicks` + 1 
                WHERE `id` = ?
            ");
            $stmt->execute([$adId]);

            return ['success' => true, 'message' => 'تم تسجيل النقرة.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تسجيل النقرة.'];
        }
    }

    // جلب إحصائيات الإعلانات الداخلية
    public function getInternalAdsStats() {
        try {
            $stats = [];

            // إجمالي الإعلانات
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}internal_ads`");
            $stmt->execute();
            $stats['total_ads'] = $stmt->fetchColumn();

            // الإعلانات النشطة
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}internal_ads` WHERE `is_active` = 1");
            $stmt->execute();
            $stats['active_ads'] = $stmt->fetchColumn();

            // إجمالي المشاهدات
            $stmt = $this->pdo->prepare("SELECT SUM(`views`) FROM `{$this->prefix}internal_ads`");
            $stmt->execute();
            $stats['total_views'] = $stmt->fetchColumn() ?: 0;

            // إجمالي النقرات
            $stmt = $this->pdo->prepare("SELECT SUM(`clicks`) FROM `{$this->prefix}internal_ads`");
            $stmt->execute();
            $stats['total_clicks'] = $stmt->fetchColumn() ?: 0;

            // معدل النقر
            $stats['click_rate'] = $stats['total_views'] > 0 ? 
                round(($stats['total_clicks'] / $stats['total_views']) * 100, 2) : 0;

            return $stats;
        } catch (PDOException $e) {
            return [
                'total_ads' => 0,
                'active_ads' => 0,
                'total_views' => 0,
                'total_clicks' => 0,
                'click_rate' => 0
            ];
        }
    }

    // جلب أفضل الإعلانات أداءً
    public function getTopPerformingAds($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *, 
                       CASE 
                           WHEN `views` > 0 THEN ROUND((`clicks` / `views`) * 100, 2)
                           ELSE 0 
                       END as `click_rate`
                FROM `{$this->prefix}internal_ads` 
                WHERE `is_active` = 1 
                ORDER BY `clicks` DESC, `views` DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // البحث في الإعلانات
    public function searchInternalAds($query, $limit = 20, $offset = 0) {
        try {
            $searchTerm = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT * FROM `{$this->prefix}internal_ads` 
                WHERE `title` LIKE ? OR `content` LIKE ?
                ORDER BY `priority` DESC, `created_at` DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // عد نتائج البحث
    public function countSearchResults($query) {
        try {
            $searchTerm = '%' . $query . '%';
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM `{$this->prefix}internal_ads` 
                WHERE `title` LIKE ? OR `content` LIKE ?
            ");
            $stmt->execute([$searchTerm, $searchTerm]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}

?>

