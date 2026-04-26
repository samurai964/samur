<?php
/**
 * محرك التحليلات للحملات الإعلانية
 * يجمع ويحلل البيانات لتوفير إحصائيات شاملة ومفيدة
 */

class AnalyticsEngine {
    private $db;
    private $cache_duration = 300; // 5 دقائق
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * الحصول على إحصائيات شاملة للحملة
     * @param int $campaign_id معرف الحملة
     * @param string $period الفترة الزمنية (today, week, month, all)
     * @return array إحصائيات الحملة
     */
    public function getCampaignAnalytics($campaign_id, $period = 'all') {
        $cache_key = "campaign_analytics_{$campaign_id}_{$period}";
        $cached_data = $this->getFromCache($cache_key);
        
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        $date_condition = $this->getDateCondition($period);
        
        try {
            // الإحصائيات الأساسية
            $basic_stats = $this->getCampaignBasicStats($campaign_id, $date_condition);
            
            // إحصائيات الأداء
            $performance_stats = $this->getCampaignPerformanceStats($campaign_id, $date_condition);
            
            // إحصائيات الاستهداف
            $targeting_stats = $this->getCampaignTargetingStats($campaign_id, $date_condition);
            
            // إحصائيات الأجهزة والمتصفحات
            $device_stats = $this->getCampaignDeviceStats($campaign_id, $date_condition);
            
            // إحصائيات الوقت
            $time_stats = $this->getCampaignTimeStats($campaign_id, $date_condition);
            
            // إحصائيات المواقع
            $placement_stats = $this->getCampaignPlacementStats($campaign_id, $date_condition);
            
            $analytics = [
                'campaign_id' => $campaign_id,
                'period' => $period,
                'basic_stats' => $basic_stats,
                'performance_stats' => $performance_stats,
                'targeting_stats' => $targeting_stats,
                'device_stats' => $device_stats,
                'time_stats' => $time_stats,
                'placement_stats' => $placement_stats,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveToCache($cache_key, $analytics);
            return $analytics;
            
        } catch (Exception $e) {
            error_log("خطأ في جلب تحليلات الحملة: " . $e->getMessage());
            return $this->getEmptyAnalytics($campaign_id, $period);
        }
    }
    
    /**
     * الحصول على إحصائيات المعلن
     * @param int $advertiser_id معرف المعلن
     * @param string $period الفترة الزمنية
     * @return array إحصائيات المعلن
     */
    public function getAdvertiserAnalytics($advertiser_id, $period = 'all') {
        $cache_key = "advertiser_analytics_{$advertiser_id}_{$period}";
        $cached_data = $this->getFromCache($cache_key);
        
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        $date_condition = $this->getDateCondition($period);
        
        try {
            // إحصائيات عامة
            $sql = "SELECT 
                        COUNT(DISTINCT c.id) as total_campaigns,
                        COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.id END) as active_campaigns,
                        COUNT(DISTINCT CASE WHEN c.status = 'paused' THEN c.id END) as paused_campaigns,
                        COUNT(DISTINCT CASE WHEN c.status = 'completed' THEN c.id END) as completed_campaigns,
                        SUM(c.budget) as total_budget,
                        SUM(c.current_spend) as total_spend,
                        SUM(c.total_impressions) as total_impressions,
                        SUM(c.total_clicks) as total_clicks,
                        AVG(CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END) as avg_ctr,
                        AVG(CASE WHEN c.total_clicks > 0 THEN c.current_spend / c.total_clicks ELSE 0 END) as avg_cpc
                    FROM fmc_ad_campaigns c
                    WHERE c.advertiser_id = ? {$date_condition}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$advertiser_id]);
            $overview = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // أفضل الحملات أداءً
            $top_campaigns = $this->getAdvertiserTopCampaigns($advertiser_id, $date_condition);
            
            // إحصائيات الإنفاق اليومي
            $daily_spend = $this->getAdvertiserDailySpend($advertiser_id, $period);
            
            // إحصائيات الأداء الشهري
            $monthly_performance = $this->getAdvertiserMonthlyPerformance($advertiser_id);
            
            $analytics = [
                'advertiser_id' => $advertiser_id,
                'period' => $period,
                'overview' => $overview,
                'top_campaigns' => $top_campaigns,
                'daily_spend' => $daily_spend,
                'monthly_performance' => $monthly_performance,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveToCache($cache_key, $analytics);
            return $analytics;
            
        } catch (Exception $e) {
            error_log("خطأ في جلب تحليلات المعلن: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على إحصائيات النظام العامة
     * @param string $period الفترة الزمنية
     * @return array إحصائيات النظام
     */
    public function getSystemAnalytics($period = 'all') {
        $cache_key = "system_analytics_{$period}";
        $cached_data = $this->getFromCache($cache_key);
        
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        $date_condition = $this->getDateCondition($period);
        
        try {
            // إحصائيات عامة
            $overview = $this->getSystemOverview($date_condition);
            
            // إحصائيات الإيرادات
            $revenue_stats = $this->getSystemRevenueStats($date_condition);
            
            // أفضل المعلنين
            $top_advertisers = $this->getTopAdvertisers($date_condition);
            
            // أفضل الحملات
            $top_campaigns = $this->getTopCampaigns($date_condition);
            
            // إحصائيات الأداء اليومي
            $daily_performance = $this->getSystemDailyPerformance($period);
            
            // إحصائيات الاستهداف
            $targeting_insights = $this->getSystemTargetingInsights($date_condition);
            
            // إحصائيات الأجهزة والمتصفحات
            $device_insights = $this->getSystemDeviceInsights($date_condition);
            
            $analytics = [
                'period' => $period,
                'overview' => $overview,
                'revenue_stats' => $revenue_stats,
                'top_advertisers' => $top_advertisers,
                'top_campaigns' => $top_campaigns,
                'daily_performance' => $daily_performance,
                'targeting_insights' => $targeting_insights,
                'device_insights' => $device_insights,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->saveToCache($cache_key, $analytics);
            return $analytics;
            
        } catch (Exception $e) {
            error_log("خطأ في جلب تحليلات النظام: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على الإحصائيات الأساسية للحملة
     */
    private function getCampaignBasicStats($campaign_id, $date_condition) {
        $sql = "SELECT 
                    c.name,
                    c.status,
                    c.budget,
                    c.current_spend,
                    c.total_impressions,
                    c.total_clicks,
                    c.start_date,
                    c.end_date,
                    CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END as ctr,
                    CASE WHEN c.total_clicks > 0 THEN c.current_spend / c.total_clicks ELSE 0 END as avg_cpc,
                    CASE WHEN c.total_impressions > 0 THEN (c.current_spend / c.total_impressions) * 1000 ELSE 0 END as avg_cpm,
                    CASE WHEN c.budget > 0 THEN (c.current_spend / c.budget) * 100 ELSE 0 END as budget_used_percent
                FROM fmc_ad_campaigns c
                WHERE c.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * الحصول على إحصائيات الأداء للحملة
     */
    private function getCampaignPerformanceStats($campaign_id, $date_condition) {
        // إحصائيات يومية للأسبوع الماضي
        $sql = "SELECT 
                    DATE(ai.created_at) as date,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id AND DATE(ai.created_at) = DATE(ac.created_at)
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? 
                AND ai.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(ai.created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $daily_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات ساعية لليوم الحالي
        $sql = "SELECT 
                    HOUR(ai.created_at) as hour,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id AND HOUR(ai.created_at) = HOUR(ac.created_at)
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? 
                AND DATE(ai.created_at) = CURDATE()
                GROUP BY HOUR(ai.created_at)
                ORDER BY hour";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $hourly_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'daily_performance' => $daily_performance,
            'hourly_performance' => $hourly_performance
        ];
    }
    
    /**
     * الحصول على إحصائيات الاستهداف للحملة
     */
    private function getCampaignTargetingStats($campaign_id, $date_condition) {
        // إحصائيات الدول
        $sql = "SELECT 
                    COALESCE(u.country, 'غير محدد') as country,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                LEFT JOIN fmc_users u ON ai.user_id = u.id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY u.country
                ORDER BY impressions DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $country_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات الأعمار
        $sql = "SELECT 
                    CASE 
                        WHEN u.age BETWEEN 18 AND 24 THEN '18-24'
                        WHEN u.age BETWEEN 25 AND 34 THEN '25-34'
                        WHEN u.age BETWEEN 35 AND 44 THEN '35-44'
                        WHEN u.age BETWEEN 45 AND 54 THEN '45-54'
                        WHEN u.age >= 55 THEN '55+'
                        ELSE 'غير محدد'
                    END as age_group,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                LEFT JOIN fmc_users u ON ai.user_id = u.id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY age_group
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $age_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات الجنس
        $sql = "SELECT 
                    COALESCE(u.gender, 'غير محدد') as gender,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                LEFT JOIN fmc_users u ON ai.user_id = u.id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY u.gender
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $gender_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'country_stats' => $country_stats,
            'age_stats' => $age_stats,
            'gender_stats' => $gender_stats
        ];
    }
    
    /**
     * الحصول على إحصائيات الأجهزة للحملة
     */
    private function getCampaignDeviceStats($campaign_id, $date_condition) {
        $sql = "SELECT 
                    CASE 
                        WHEN ai.user_agent LIKE '%Mobile%' OR ai.user_agent LIKE '%Android%' OR ai.user_agent LIKE '%iPhone%' THEN 'موبايل'
                        WHEN ai.user_agent LIKE '%Tablet%' OR ai.user_agent LIKE '%iPad%' THEN 'تابلت'
                        ELSE 'سطح المكتب'
                    END as device_type,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY device_type
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على إحصائيات الوقت للحملة
     */
    private function getCampaignTimeStats($campaign_id, $date_condition) {
        // إحصائيات الأيام
        $sql = "SELECT 
                    CASE DAYOFWEEK(ai.created_at)
                        WHEN 1 THEN 'الأحد'
                        WHEN 2 THEN 'الاثنين'
                        WHEN 3 THEN 'الثلاثاء'
                        WHEN 4 THEN 'الأربعاء'
                        WHEN 5 THEN 'الخميس'
                        WHEN 6 THEN 'الجمعة'
                        WHEN 7 THEN 'السبت'
                    END as day_name,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY DAYOFWEEK(ai.created_at), day_name
                ORDER BY DAYOFWEEK(ai.created_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $day_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إحصائيات الساعات
        $sql = "SELECT 
                    HOUR(ai.created_at) as hour,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY HOUR(ai.created_at)
                ORDER BY hour";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        $hour_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'day_stats' => $day_stats,
            'hour_stats' => $hour_stats
        ];
    }
    
    /**
     * الحصول على إحصائيات المواقع للحملة
     */
    private function getCampaignPlacementStats($campaign_id, $date_condition) {
        $sql = "SELECT 
                    CASE 
                        WHEN ai.page_url LIKE '%/topics/%' THEN 'صفحات المواضيع'
                        WHEN ai.page_url LIKE '%/ads/%' THEN 'الإعلانات المبوبة'
                        WHEN ai.page_url LIKE '%/directory/%' THEN 'دليل المواقع'
                        WHEN ai.page_url LIKE '%/courses/%' THEN 'الدورات'
                        WHEN ai.page_url = '/' OR ai.page_url LIKE '%/home%' THEN 'الصفحة الرئيسية'
                        ELSE 'صفحات أخرى'
                    END as placement,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                WHERE ca.campaign_id = ? {$date_condition}
                GROUP BY placement
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على أفضل حملات المعلن
     */
    private function getAdvertiserTopCampaigns($advertiser_id, $date_condition) {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.status,
                    c.total_impressions,
                    c.total_clicks,
                    c.current_spend,
                    CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END as ctr,
                    CASE WHEN c.total_clicks > 0 THEN c.current_spend / c.total_clicks ELSE 0 END as avg_cpc
                FROM fmc_ad_campaigns c
                WHERE c.advertiser_id = ? {$date_condition}
                ORDER BY c.total_impressions DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$advertiser_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على الإنفاق اليومي للمعلن
     */
    private function getAdvertiserDailySpend($advertiser_id, $period) {
        $days = $this->getPeriodDays($period);
        
        $sql = "SELECT 
                    DATE(ac.created_at) as date,
                    SUM(CASE WHEN c.bid_strategy = 'cpc' THEN c.max_cpc ELSE (c.max_cpm / 1000) * 0.02 END) as daily_spend
                FROM fmc_ad_clicks ac
                JOIN fmc_campaign_ads ca ON ac.ad_id = ca.id
                JOIN fmc_ad_campaigns c ON ca.campaign_id = c.id
                WHERE c.advertiser_id = ?
                AND ac.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(ac.created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$advertiser_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على الأداء الشهري للمعلن
     */
    private function getAdvertiserMonthlyPerformance($advertiser_id) {
        $sql = "SELECT 
                    DATE_FORMAT(ai.created_at, '%Y-%m') as month,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    SUM(CASE WHEN c.bid_strategy = 'cpc' THEN c.max_cpc ELSE (c.max_cpm / 1000) * 0.02 END) as spend
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                JOIN fmc_ad_campaigns c ON ca.campaign_id = c.id
                WHERE c.advertiser_id = ?
                AND ai.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(ai.created_at, '%Y-%m')
                ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$advertiser_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على نظرة عامة على النظام
     */
    private function getSystemOverview($date_condition) {
        $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_campaigns,
                    COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.id END) as active_campaigns,
                    COUNT(DISTINCT c.advertiser_id) as total_advertisers,
                    COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.advertiser_id END) as active_advertisers,
                    SUM(c.total_impressions) as total_impressions,
                    SUM(c.total_clicks) as total_clicks,
                    SUM(c.current_spend) as total_revenue,
                    AVG(CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END) as avg_ctr
                FROM fmc_ad_campaigns c
                WHERE 1=1 {$date_condition}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * الحصول على إحصائيات الإيرادات
     */
    private function getSystemRevenueStats($date_condition) {
        $sql = "SELECT 
                    SUM(c.current_spend) as total_revenue,
                    AVG(c.current_spend) as avg_campaign_revenue,
                    SUM(CASE WHEN c.bid_strategy = 'cpc' THEN c.current_spend ELSE 0 END) as cpc_revenue,
                    SUM(CASE WHEN c.bid_strategy = 'cpm' THEN c.current_spend ELSE 0 END) as cpm_revenue,
                    COUNT(DISTINCT CASE WHEN c.current_spend > 0 THEN c.id END) as revenue_generating_campaigns
                FROM fmc_ad_campaigns c
                WHERE 1=1 {$date_condition}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * الحصول على أفضل المعلنين
     */
    private function getTopAdvertisers($date_condition) {
        $sql = "SELECT 
                    c.advertiser_id,
                    u.username as advertiser_name,
                    u.email as advertiser_email,
                    COUNT(DISTINCT c.id) as total_campaigns,
                    SUM(c.current_spend) as total_spend,
                    SUM(c.total_impressions) as total_impressions,
                    SUM(c.total_clicks) as total_clicks,
                    AVG(CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END) as avg_ctr
                FROM fmc_ad_campaigns c
                JOIN fmc_users u ON c.advertiser_id = u.id
                WHERE 1=1 {$date_condition}
                GROUP BY c.advertiser_id, u.username, u.email
                ORDER BY total_spend DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على أفضل الحملات
     */
    private function getTopCampaigns($date_condition) {
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.advertiser_id,
                    u.username as advertiser_name,
                    c.total_impressions,
                    c.total_clicks,
                    c.current_spend,
                    CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END as ctr,
                    CASE WHEN c.total_clicks > 0 THEN c.current_spend / c.total_clicks ELSE 0 END as avg_cpc
                FROM fmc_ad_campaigns c
                JOIN fmc_users u ON c.advertiser_id = u.id
                WHERE 1=1 {$date_condition}
                ORDER BY c.total_impressions DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على الأداء اليومي للنظام
     */
    private function getSystemDailyPerformance($period) {
        $days = $this->getPeriodDays($period);
        
        $sql = "SELECT 
                    DATE(ai.created_at) as date,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    COUNT(DISTINCT ca.campaign_id) as active_campaigns,
                    SUM(CASE WHEN c.bid_strategy = 'cpc' THEN c.max_cpc ELSE (c.max_cpm / 1000) * 0.02 END) as revenue
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id AND DATE(ai.created_at) = DATE(ac.created_at)
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                JOIN fmc_ad_campaigns c ON ca.campaign_id = c.id
                WHERE ai.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(ai.created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على رؤى الاستهداف للنظام
     */
    private function getSystemTargetingInsights($date_condition) {
        // أفضل الدول أداءً
        $sql = "SELECT 
                    COALESCE(u.country, 'غير محدد') as country,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                LEFT JOIN fmc_users u ON ai.user_id = u.id
                WHERE 1=1 {$date_condition}
                GROUP BY u.country
                ORDER BY impressions DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $top_countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // أفضل الفئات العمرية
        $sql = "SELECT 
                    CASE 
                        WHEN u.age BETWEEN 18 AND 24 THEN '18-24'
                        WHEN u.age BETWEEN 25 AND 34 THEN '25-34'
                        WHEN u.age BETWEEN 35 AND 44 THEN '35-44'
                        WHEN u.age BETWEEN 45 AND 54 THEN '45-54'
                        WHEN u.age >= 55 THEN '55+'
                        ELSE 'غير محدد'
                    END as age_group,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                LEFT JOIN fmc_users u ON ai.user_id = u.id
                WHERE 1=1 {$date_condition}
                GROUP BY age_group
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $age_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'top_countries' => $top_countries,
            'age_groups' => $age_groups
        ];
    }
    
    /**
     * الحصول على رؤى الأجهزة للنظام
     */
    private function getSystemDeviceInsights($date_condition) {
        $sql = "SELECT 
                    CASE 
                        WHEN ai.user_agent LIKE '%Mobile%' OR ai.user_agent LIKE '%Android%' OR ai.user_agent LIKE '%iPhone%' THEN 'موبايل'
                        WHEN ai.user_agent LIKE '%Tablet%' OR ai.user_agent LIKE '%iPad%' THEN 'تابلت'
                        ELSE 'سطح المكتب'
                    END as device_type,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id
                WHERE 1=1 {$date_condition}
                GROUP BY device_type
                ORDER BY impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على شرط التاريخ
     */
    private function getDateCondition($period) {
        switch ($period) {
            case 'today':
                return "AND DATE(created_at) = CURDATE()";
            case 'week':
                return "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'month':
                return "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            case 'quarter':
                return "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
            case 'year':
                return "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
            default:
                return "";
        }
    }
    
    /**
     * الحصول على عدد الأيام للفترة
     */
    private function getPeriodDays($period) {
        switch ($period) {
            case 'today':
                return 1;
            case 'week':
                return 7;
            case 'month':
                return 30;
            case 'quarter':
                return 90;
            case 'year':
                return 365;
            default:
                return 30;
        }
    }
    
    /**
     * الحصول من الكاش
     */
    private function getFromCache($key) {
        // تطبيق بسيط للكاش - يمكن استخدام Redis أو Memcached
        $cache_file = sys_get_temp_dir() . "/ad_analytics_cache_{$key}.json";
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->cache_duration) {
            $data = file_get_contents($cache_file);
            return json_decode($data, true);
        }
        
        return null;
    }
    
    /**
     * الحفظ في الكاش
     */
    private function saveToCache($key, $data) {
        $cache_file = sys_get_temp_dir() . "/ad_analytics_cache_{$key}.json";
        file_put_contents($cache_file, json_encode($data));
    }
    
    /**
     * الحصول على تحليلات فارغة
     */
    private function getEmptyAnalytics($campaign_id, $period) {
        return [
            'campaign_id' => $campaign_id,
            'period' => $period,
            'basic_stats' => [],
            'performance_stats' => ['daily_performance' => [], 'hourly_performance' => []],
            'targeting_stats' => ['country_stats' => [], 'age_stats' => [], 'gender_stats' => []],
            'device_stats' => [],
            'time_stats' => ['day_stats' => [], 'hour_stats' => []],
            'placement_stats' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * تنظيف الكاش القديم
     */
    public function cleanOldCache() {
        $cache_dir = sys_get_temp_dir();
        $files = glob($cache_dir . "/ad_analytics_cache_*.json");
        
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > ($this->cache_duration * 2)) {
                unlink($file);
            }
        }
    }
    
    /**
     * إنشاء تقرير مخصص
     * @param array $params معاملات التقرير
     * @return array بيانات التقرير
     */
    public function generateCustomReport($params) {
        $report_type = $params['type'] ?? 'campaign';
        $start_date = $params['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $params['end_date'] ?? date('Y-m-d');
        $filters = $params['filters'] ?? [];
        
        try {
            switch ($report_type) {
                case 'campaign':
                    return $this->generateCampaignReport($start_date, $end_date, $filters);
                case 'advertiser':
                    return $this->generateAdvertiserReport($start_date, $end_date, $filters);
                case 'performance':
                    return $this->generatePerformanceReport($start_date, $end_date, $filters);
                default:
                    return [];
            }
        } catch (Exception $e) {
            error_log("خطأ في إنشاء التقرير المخصص: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * إنشاء تقرير الحملات
     */
    private function generateCampaignReport($start_date, $end_date, $filters) {
        $where_conditions = ["DATE(c.created_at) BETWEEN ? AND ?"];
        $params = [$start_date, $end_date];
        
        // إضافة فلاتر إضافية
        if (!empty($filters['advertiser_id'])) {
            $where_conditions[] = "c.advertiser_id = ?";
            $params[] = $filters['advertiser_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "c.status = ?";
            $params[] = $filters['status'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    c.id,
                    c.name,
                    c.status,
                    c.budget,
                    c.current_spend,
                    c.total_impressions,
                    c.total_clicks,
                    c.start_date,
                    c.end_date,
                    u.username as advertiser_name,
                    CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END as ctr,
                    CASE WHEN c.total_clicks > 0 THEN c.current_spend / c.total_clicks ELSE 0 END as avg_cpc
                FROM fmc_ad_campaigns c
                JOIN fmc_users u ON c.advertiser_id = u.id
                WHERE {$where_clause}
                ORDER BY c.total_impressions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء تقرير المعلنين
     */
    private function generateAdvertiserReport($start_date, $end_date, $filters) {
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    COUNT(DISTINCT c.id) as total_campaigns,
                    SUM(c.budget) as total_budget,
                    SUM(c.current_spend) as total_spend,
                    SUM(c.total_impressions) as total_impressions,
                    SUM(c.total_clicks) as total_clicks,
                    AVG(CASE WHEN c.total_impressions > 0 THEN (c.total_clicks / c.total_impressions) * 100 ELSE 0 END) as avg_ctr
                FROM fmc_users u
                JOIN fmc_ad_campaigns c ON u.id = c.advertiser_id
                WHERE DATE(c.created_at) BETWEEN ? AND ?
                GROUP BY u.id, u.username, u.email
                ORDER BY total_spend DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء تقرير الأداء
     */
    private function generatePerformanceReport($start_date, $end_date, $filters) {
        $sql = "SELECT 
                    DATE(ai.created_at) as date,
                    COUNT(DISTINCT ai.id) as impressions,
                    COUNT(DISTINCT ac.id) as clicks,
                    COUNT(DISTINCT ca.campaign_id) as active_campaigns,
                    COUNT(DISTINCT c.advertiser_id) as active_advertisers,
                    SUM(CASE WHEN c.bid_strategy = 'cpc' THEN c.max_cpc ELSE (c.max_cpm / 1000) * 0.02 END) as revenue,
                    CASE WHEN COUNT(DISTINCT ai.id) > 0 THEN (COUNT(DISTINCT ac.id) / COUNT(DISTINCT ai.id)) * 100 ELSE 0 END as ctr
                FROM fmc_ad_impressions ai
                LEFT JOIN fmc_ad_clicks ac ON ai.ad_id = ac.ad_id AND DATE(ai.created_at) = DATE(ac.created_at)
                JOIN fmc_campaign_ads ca ON ai.ad_id = ca.id
                JOIN fmc_ad_campaigns c ON ca.campaign_id = c.id
                WHERE DATE(ai.created_at) BETWEEN ? AND ?
                GROUP BY DATE(ai.created_at)
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

