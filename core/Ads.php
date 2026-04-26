<?php

class Ads
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // جلب الإعلانات
    public function getAds($placement)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ads
            WHERE placement = ? AND is_active = 1 AND (budget IS NULL OR budget > 0)
        ");

        $stmt->execute([$placement]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // إنشاء إعلان
    public function createAd($userId, $title, $content, $placement, $budget)
    {
        $stmt = $this->db->prepare("
            INSERT INTO ads (user_id, title, content, placement, budget)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$userId, $title, $content, $placement, $budget]);
    }

    // تسجيل مشاهدة الإعلان
    public function recordView($ad)
    {
        // زيادة المشاهدات
        $stmt = $this->db->prepare("
            UPDATE ads SET views = views + 1 WHERE id = ?
        ");
        $stmt->execute([$ad['id']]);

        $cost = $ad['cost_per_view'];

        // تحديث الأرباح والميزانية
        $stmt = $this->db->prepare("
            UPDATE ads SET 
                earnings = earnings + ?, 
                budget = budget - ?
            WHERE id = ?
        ");
        $stmt->execute([$cost, $cost, $ad['id']]);

        return $cost;
    }
}
