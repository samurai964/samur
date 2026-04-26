<?php
/**
 * محرك الاستهداف للإعلانات
 * يحدد أي إعلانات يجب عرضها للمستخدمين بناءً على معايير الاستهداف
 */

class AdTargetingEngine {
    private $db;
    private $user_data;
    private $page_context;
    
    public function __construct($database) {
        $this->db = $database;
        $this->user_data = $this->getUserData();
        $this->page_context = $this->getPageContext();
    }
    
    /**
     * الحصول على الإعلانات المناسبة للعرض
     * @param string $placement موقع عرض الإعلان
     * @param int $limit عدد الإعلانات المطلوبة
     * @return array قائمة الإعلانات المناسبة
     */
    public function getTargetedAds($placement = 'general', $limit = 3) {
        try {
            // جلب الحملات النشطة
            $active_campaigns = $this->getActiveCampaigns();
            
            if (empty($active_campaigns)) {
                return [];
            }
            
            // تطبيق معايير الاستهداف
            $targeted_campaigns = [];
            foreach ($active_campaigns as $campaign) {
                $targeting_score = $this->calculateTargetingScore($campaign);
                if ($targeting_score > 0) {
                    $campaign['targeting_score'] = $targeting_score;
                    $targeted_campaigns[] = $campaign;
                }
            }
            
            // ترتيب الحملات حسب نقاط الاستهداف والمزايدة
            usort($targeted_campaigns, function($a, $b) {
                // أولاً حسب نقاط الاستهداف
                if ($a['targeting_score'] != $b['targeting_score']) {
                    return $b['targeting_score'] <=> $a['targeting_score'];
                }
                // ثم حسب المزايدة
                return $b['max_bid'] <=> $a['max_bid'];
            });
            
            // جلب الإعلانات للحملات المختارة
            $selected_ads = [];
            foreach (array_slice($targeted_campaigns, 0, $limit) as $campaign) {
                $ads = $this->getCampaignAds($campaign['id'], $placement);
                if (!empty($ads)) {
                    $selected_ads = array_merge($selected_ads, $ads);
                }
            }
            
            // تطبيق قواعد التنويع وتجنب التكرار
            $final_ads = $this->applyDiversityRules($selected_ads, $limit);
            
            // تسجيل عرض الإعلانات
            $this->logAdImpressions($final_ads);
            
            return $final_ads;
            
        } catch (Exception $e) {
            error_log("خطأ في محرك الاستهداف: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * حساب نقاط الاستهداف للحملة
     * @param array $campaign بيانات الحملة
     * @return float نقاط الاستهداف (0-100)
     */
    private function calculateTargetingScore($campaign) {
        $score = 0;
        $max_score = 100;
        
        // الاستهداف الجغرافي (25 نقطة)
        $geo_score = $this->calculateGeoTargetingScore($campaign);
        $score += $geo_score * 0.25;
        
        // الاستهداف اللغوي (15 نقطة)
        $language_score = $this->calculateLanguageTargetingScore($campaign);
        $score += $language_score * 0.15;
        
        // الاستهداف الديموغرافي (20 نقطة)
        $demo_score = $this->calculateDemographicTargetingScore($campaign);
        $score += $demo_score * 0.20;
        
        // الاستهداف بالاهتمامات (20 نقطة)
        $interest_score = $this->calculateInterestTargetingScore($campaign);
        $score += $interest_score * 0.20;
        
        // الاستهداف بالكلمات المفتاحية (15 نقطة)
        $keyword_score = $this->calculateKeywordTargetingScore($campaign);
        $score += $keyword_score * 0.15;
        
        // الاستهداف الزمني (5 نقاط)
        $time_score = $this->calculateTimeTargetingScore($campaign);
        $score += $time_score * 0.05;
        
        return min($score, $max_score);
    }
    
    /**
     * حساب نقاط الاستهداف الجغرافي
     */
    private function calculateGeoTargetingScore($campaign) {
        $user_country = $this->user_data['country'] ?? 'SA';
        $target_countries = explode(',', $campaign['target_countries'] ?? '');
        
        if (empty($target_countries) || in_array('all', $target_countries)) {
            return 50; // نقاط متوسطة للاستهداف العام
        }
        
        if (in_array($user_country, $target_countries)) {
            return 100; // نقاط كاملة للاستهداف المطابق
        }
        
        // التحقق من المناطق القريبة (دول الخليج مثلاً)
        $gulf_countries = ['SA', 'AE', 'KW', 'QA', 'BH', 'OM'];
        if (in_array($user_country, $gulf_countries)) {
            foreach ($target_countries as $target) {
                if (in_array($target, $gulf_countries)) {
                    return 70; // نقاط جيدة للمناطق القريبة
                }
            }
        }
        
        return 0; // لا يوجد تطابق جغرافي
    }
    
    /**
     * حساب نقاط الاستهداف اللغوي
     */
    private function calculateLanguageTargetingScore($campaign) {
        $user_language = $this->user_data['language'] ?? 'ar';
        $target_languages = explode(',', $campaign['target_languages'] ?? '');
        
        if (empty($target_languages) || in_array('all', $target_languages)) {
            return 50;
        }
        
        if (in_array($user_language, $target_languages)) {
            return 100;
        }
        
        return 0;
    }
    
    /**
     * حساب نقاط الاستهداف الديموغرافي
     */
    private function calculateDemographicTargetingScore($campaign) {
        $score = 0;
        $factors = 0;
        
        // العمر
        if (isset($this->user_data['age'])) {
            $user_age = $this->user_data['age'];
            $min_age = $campaign['target_age_min'] ?? 18;
            $max_age = $campaign['target_age_max'] ?? 65;
            
            if ($user_age >= $min_age && $user_age <= $max_age) {
                $score += 100;
            } else {
                // نقاط جزئية للأعمار القريبة
                $age_diff = min(abs($user_age - $min_age), abs($user_age - $max_age));
                if ($age_diff <= 5) {
                    $score += 70;
                } elseif ($age_diff <= 10) {
                    $score += 40;
                }
            }
            $factors++;
        }
        
        // الجنس
        if (isset($this->user_data['gender'])) {
            $user_gender = $this->user_data['gender'];
            $target_gender = $campaign['target_gender'] ?? 'all';
            
            if ($target_gender === 'all' || $target_gender === $user_gender) {
                $score += 100;
            }
            $factors++;
        }
        
        return $factors > 0 ? $score / $factors : 50;
    }
    
    /**
     * حساب نقاط الاستهداف بالاهتمامات
     */
    private function calculateInterestTargetingScore($campaign) {
        $user_interests = $this->user_data['interests'] ?? [];
        $target_interests = explode('،', $campaign['target_interests'] ?? '');
        
        if (empty($target_interests) || empty($user_interests)) {
            return 30; // نقاط افتراضية
        }
        
        $matches = 0;
        $total_targets = count($target_interests);
        
        foreach ($target_interests as $target_interest) {
            $target_interest = trim($target_interest);
            foreach ($user_interests as $user_interest) {
                if (stripos($user_interest, $target_interest) !== false || 
                    stripos($target_interest, $user_interest) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $total_targets > 0 ? ($matches / $total_targets) * 100 : 30;
    }
    
    /**
     * حساب نقاط الاستهداف بالكلمات المفتاحية
     */
    private function calculateKeywordTargetingScore($campaign) {
        $page_content = $this->page_context['content'] ?? '';
        $page_keywords = $this->page_context['keywords'] ?? [];
        $target_keywords = explode('،', $campaign['target_keywords'] ?? '');
        
        if (empty($target_keywords)) {
            return 50;
        }
        
        $matches = 0;
        $total_keywords = count($target_keywords);
        
        foreach ($target_keywords as $keyword) {
            $keyword = trim($keyword);
            
            // البحث في محتوى الصفحة
            if (stripos($page_content, $keyword) !== false) {
                $matches++;
                continue;
            }
            
            // البحث في كلمات الصفحة المفتاحية
            foreach ($page_keywords as $page_keyword) {
                if (stripos($page_keyword, $keyword) !== false || 
                    stripos($keyword, $page_keyword) !== false) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $total_keywords > 0 ? ($matches / $total_keywords) * 100 : 50;
    }
    
    /**
     * حساب نقاط الاستهداف الزمني
     */
    private function calculateTimeTargetingScore($campaign) {
        $current_time = new DateTime();
        $current_hour = (int)$current_time->format('H');
        $current_day = (int)$current_time->format('w'); // 0 = Sunday
        
        // التحقق من الجدولة الزمنية للحملة
        $time_targeting = json_decode($campaign['time_targeting'] ?? '{}', true);
        
        if (empty($time_targeting)) {
            return 100; // لا توجد قيود زمنية
        }
        
        // التحقق من الأيام
        if (isset($time_targeting['days']) && !empty($time_targeting['days'])) {
            if (!in_array($current_day, $time_targeting['days'])) {
                return 0;
            }
        }
        
        // التحقق من الساعات
        if (isset($time_targeting['hours']) && !empty($time_targeting['hours'])) {
            $start_hour = $time_targeting['hours']['start'] ?? 0;
            $end_hour = $time_targeting['hours']['end'] ?? 23;
            
            if ($current_hour < $start_hour || $current_hour > $end_hour) {
                return 0;
            }
        }
        
        return 100;
    }
    
    /**
     * جلب الحملات النشطة
     */
    private function getActiveCampaigns() {
        $sql = "SELECT c.*, 
                       CASE 
                           WHEN c.bid_strategy = 'cpc' THEN c.max_cpc
                           WHEN c.bid_strategy = 'cpm' THEN c.max_cpm
                           ELSE 0
                       END as max_bid
                FROM fmc_ad_campaigns c 
                WHERE c.status = 'active' 
                AND c.start_date <= CURDATE() 
                AND (c.end_date IS NULL OR c.end_date >= CURDATE())
                AND c.current_spend < c.budget
                ORDER BY c.priority DESC, max_bid DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * جلب إعلانات الحملة
     */
    private function getCampaignAds($campaign_id, $placement) {
        $sql = "SELECT a.*, c.bid_strategy, c.max_cpc, c.max_cpm, c.advertiser_id
                FROM fmc_campaign_ads a
                JOIN fmc_ad_campaigns c ON a.campaign_id = c.id
                WHERE a.campaign_id = ? 
                AND a.status = 'active'
                AND (a.placement = ? OR a.placement = 'all')
                ORDER BY a.priority DESC, RAND()
                LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaign_id, $placement]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تطبيق قواعد التنويع
     */
    private function applyDiversityRules($ads, $limit) {
        if (empty($ads)) {
            return [];
        }
        
        $final_ads = [];
        $used_advertisers = [];
        $used_campaigns = [];
        
        // تجنب تكرار المعلنين والحملات
        foreach ($ads as $ad) {
            if (count($final_ads) >= $limit) {
                break;
            }
            
            $advertiser_id = $ad['advertiser_id'];
            $campaign_id = $ad['campaign_id'];
            
            // تجنب أكثر من إعلان واحد لكل معلن في نفس الصفحة
            if (in_array($advertiser_id, $used_advertisers)) {
                continue;
            }
            
            // تجنب أكثر من إعلان واحد لكل حملة في نفس الصفحة
            if (in_array($campaign_id, $used_campaigns)) {
                continue;
            }
            
            $final_ads[] = $ad;
            $used_advertisers[] = $advertiser_id;
            $used_campaigns[] = $campaign_id;
        }
        
        // إذا لم نحصل على العدد المطلوب، نضيف المزيد مع السماح بالتكرار
        if (count($final_ads) < $limit) {
            foreach ($ads as $ad) {
                if (count($final_ads) >= $limit) {
                    break;
                }
                
                // تجنب الإعلانات المضافة بالفعل
                $already_added = false;
                foreach ($final_ads as $existing_ad) {
                    if ($existing_ad['id'] === $ad['id']) {
                        $already_added = true;
                        break;
                    }
                }
                
                if (!$already_added) {
                    $final_ads[] = $ad;
                }
            }
        }
        
        return $final_ads;
    }
    
    /**
     * تسجيل عرض الإعلانات
     */
    private function logAdImpressions($ads) {
        if (empty($ads)) {
            return;
        }
        
        $user_id = $this->user_data['id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($ads as $ad) {
            try {
                $sql = "INSERT INTO fmc_ad_impressions 
                        (ad_id, campaign_id, user_id, ip_address, user_agent, page_url, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $ad['id'],
                    $ad['campaign_id'],
                    $user_id,
                    $ip_address,
                    $user_agent,
                    $page_url
                ]);
                
                // تحديث عداد المشاهدات في الحملة
                $this->updateCampaignStats($ad['campaign_id'], 'impression');
                
            } catch (Exception $e) {
                error_log("خطأ في تسجيل عرض الإعلان: " . $e->getMessage());
            }
        }
    }
    
    /**
     * تسجيل نقرة على الإعلان
     */
    public function logAdClick($ad_id) {
        try {
            // جلب بيانات الإعلان
            $sql = "SELECT a.*, c.bid_strategy, c.max_cpc, c.max_cpm 
                    FROM fmc_campaign_ads a
                    JOIN fmc_ad_campaigns c ON a.campaign_id = c.id
                    WHERE a.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ad_id]);
            $ad = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ad) {
                return false;
            }
            
            $user_id = $this->user_data['id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // تسجيل النقرة
            $sql = "INSERT INTO fmc_ad_clicks 
                    (ad_id, campaign_id, user_id, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $ad_id,
                $ad['campaign_id'],
                $user_id,
                $ip_address,
                $user_agent
            ]);
            
            // حساب التكلفة
            $cost = $this->calculateClickCost($ad);
            
            // تحديث إحصائيات الحملة
            $this->updateCampaignStats($ad['campaign_id'], 'click', $cost);
            
            return $ad['destination_url'];
            
        } catch (Exception $e) {
            error_log("خطأ في تسجيل نقرة الإعلان: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حساب تكلفة النقرة
     */
    private function calculateClickCost($ad) {
        if ($ad['bid_strategy'] === 'cpc') {
            return $ad['max_cpc'];
        } elseif ($ad['bid_strategy'] === 'cpm') {
            // حساب تكلفة النقرة بناءً على CPM ومعدل النقر المتوقع
            $estimated_ctr = 0.02; // 2% معدل نقر افتراضي
            return ($ad['max_cpm'] / 1000) * $estimated_ctr;
        }
        
        return 0;
    }
    
    /**
     * تحديث إحصائيات الحملة
     */
    private function updateCampaignStats($campaign_id, $type, $cost = 0) {
        try {
            if ($type === 'impression') {
                $sql = "UPDATE fmc_ad_campaigns 
                        SET total_impressions = total_impressions + 1 
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$campaign_id]);
                
            } elseif ($type === 'click') {
                $sql = "UPDATE fmc_ad_campaigns 
                        SET total_clicks = total_clicks + 1,
                            current_spend = current_spend + ?
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$cost, $campaign_id]);
            }
            
        } catch (Exception $e) {
            error_log("خطأ في تحديث إحصائيات الحملة: " . $e->getMessage());
        }
    }
    
    /**
     * جلب بيانات المستخدم
     */
    private function getUserData() {
        $user_data = [
            'id' => null,
            'country' => 'SA',
            'language' => 'ar',
            'age' => null,
            'gender' => null,
            'interests' => []
        ];
        
        // جلب بيانات المستخدم المسجل
        if (isset($_SESSION['user_id'])) {
            try {
                $sql = "SELECT id, country, language, age, gender, interests 
                        FROM fmc_users WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $user_data = array_merge($user_data, $user);
                    if ($user['interests']) {
                        $user_data['interests'] = explode(',', $user['interests']);
                    }
                }
            } catch (Exception $e) {
                error_log("خطأ في جلب بيانات المستخدم: " . $e->getMessage());
            }
        }
        
        // تحديد البلد من IP إذا لم يكن محدداً
        if (!$user_data['country'] || $user_data['country'] === 'SA') {
            $user_data['country'] = $this->getCountryFromIP();
        }
        
        return $user_data;
    }
    
    /**
     * جلب سياق الصفحة
     */
    private function getPageContext() {
        $context = [
            'page_type' => 'general',
            'content' => '',
            'keywords' => [],
            'category' => null
        ];
        
        // تحديد نوع الصفحة من الرابط
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($uri, '/topics/') !== false) {
            $context['page_type'] = 'topic';
        } elseif (strpos($uri, '/ads/') !== false) {
            $context['page_type'] = 'classified_ads';
        } elseif (strpos($uri, '/directory/') !== false) {
            $context['page_type'] = 'directory';
        } elseif (strpos($uri, '/courses/') !== false) {
            $context['page_type'] = 'courses';
        } elseif ($uri === '/' || $uri === '/home') {
            $context['page_type'] = 'homepage';
        }
        
        // جلب محتوى الصفحة والكلمات المفتاحية (يمكن تطويره أكثر)
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $context = $this->getPageContentById($_GET['id'], $context);
        }
        
        return $context;
    }
    
    /**
     * جلب محتوى الصفحة بالمعرف
     */
    private function getPageContentById($id, $context) {
        try {
            if ($context['page_type'] === 'topic') {
                $sql = "SELECT title, content, keywords, category_id 
                        FROM fmc_topics WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                $topic = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($topic) {
                    $context['content'] = $topic['title'] . ' ' . $topic['content'];
                    $context['keywords'] = explode(',', $topic['keywords'] ?? '');
                    $context['category'] = $topic['category_id'];
                }
            }
            // يمكن إضافة المزيد من أنواع الصفحات هنا
            
        } catch (Exception $e) {
            error_log("خطأ في جلب محتوى الصفحة: " . $e->getMessage());
        }
        
        return $context;
    }
    
    /**
     * تحديد البلد من عنوان IP
     */
    private function getCountryFromIP() {
        // تطبيق بسيط - يمكن استخدام خدمة GeoIP حقيقية
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // عناوين IP السعودية (مثال)
        $saudi_ranges = [
            '213.130.0.0/16',
            '212.26.0.0/16',
            '85.158.0.0/16'
        ];
        
        foreach ($saudi_ranges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return 'SA';
            }
        }
        
        return 'SA'; // افتراضي
    }
    
    /**
     * التحقق من وجود IP في نطاق معين
     */
    private function ipInRange($ip, $range) {
        list($subnet, $mask) = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
    }
    
    /**
     * جلب إعلانات للاختبار
     */
    public function getTestAds($placement = 'general', $limit = 3) {
        // بيانات تجريبية للاختبار
        return [
            [
                'id' => 1,
                'campaign_id' => 1,
                'type' => 'text',
                'title' => 'حلول تقنية متطورة',
                'description' => 'اكتشف أحدث الحلول التقنية لشركتك',
                'destination_url' => 'https://example.com/tech-solutions',
                'bid_strategy' => 'cpc',
                'max_cpc' => 1.50
            ],
            [
                'id' => 2,
                'campaign_id' => 2,
                'type' => 'image',
                'title' => 'خدمات مالية رقمية',
                'description' => 'البنك الرقمي الأول في المنطقة',
                'image_url' => '/assets/images/bank-ad.jpg',
                'destination_url' => 'https://example.com/digital-banking',
                'bid_strategy' => 'cpm',
                'max_cpm' => 5.00
            ]
        ];
    }
}
?>

