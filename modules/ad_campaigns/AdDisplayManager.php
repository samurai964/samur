<?php
/**
 * مدير عرض الإعلانات
 * يدير عرض الإعلانات في مختلف مواقع الموقع
 */

require_once __DIR__ . '/AdTargetingEngine.php';

class AdDisplayManager {
    private $db;
    private $targeting_engine;
    private $settings;
    
    public function __construct($database) {
        $this->db = $database;
        $this->targeting_engine = new AdTargetingEngine($database);
        $this->settings = $this->loadSettings();
    }
    
    /**
     * عرض الإعلانات في موقع محدد
     * @param string $placement موقع العرض
     * @param array $options خيارات العرض
     * @return string HTML للإعلانات
     */
    public function displayAds($placement, $options = []) {
        // التحقق من تفعيل النظام
        if (!$this->isSystemEnabled()) {
            return '';
        }
        
        // التحقق من السماح بعرض الإعلانات في هذا الموقع
        if (!$this->isPlacementAllowed($placement)) {
            return '';
        }
        
        // الحصول على الإعلانات المناسبة
        $limit = $options['limit'] ?? $this->getPlacementLimit($placement);
        $ads = $this->targeting_engine->getTargetedAds($placement, $limit);
        
        if (empty($ads)) {
            return $this->displayFallbackAds($placement);
        }
        
        // تحديد نمط العرض
        $display_style = $options['style'] ?? $this->getPlacementStyle($placement);
        
        return $this->renderAds($ads, $placement, $display_style, $options);
    }
    
    /**
     * عرض إعلان واحد
     * @param string $placement موقع العرض
     * @param array $options خيارات العرض
     * @return string HTML للإعلان
     */
    public function displaySingleAd($placement, $options = []) {
        $options['limit'] = 1;
        return $this->displayAds($placement, $options);
    }
    
    /**
     * عرض شريط إعلانات
     * @param string $placement موقع العرض
     * @param array $options خيارات العرض
     * @return string HTML لشريط الإعلانات
     */
    public function displayAdBanner($placement, $options = []) {
        $options['style'] = 'banner';
        $options['limit'] = $options['limit'] ?? 1;
        return $this->displayAds($placement, $options);
    }
    
    /**
     * عرض إعلانات جانبية
     * @param string $placement موقع العرض
     * @param array $options خيارات العرض
     * @return string HTML للإعلانات الجانبية
     */
    public function displaySidebarAds($placement, $options = []) {
        $options['style'] = 'sidebar';
        $options['limit'] = $options['limit'] ?? 3;
        return $this->displayAds($placement, $options);
    }
    
    /**
     * عرض إعلانات مدمجة في المحتوى
     * @param string $content المحتوى الأصلي
     * @param string $placement موقع العرض
     * @return string المحتوى مع الإعلانات المدمجة
     */
    public function injectInlineAds($content, $placement = 'inline') {
        if (!$this->isSystemEnabled() || !$this->isPlacementAllowed($placement)) {
            return $content;
        }
        
        // تقسيم المحتوى إلى فقرات
        $paragraphs = explode('</p>', $content);
        $total_paragraphs = count($paragraphs);
        
        if ($total_paragraphs < 3) {
            return $content; // محتوى قصير جداً
        }
        
        // تحديد مواقع إدراج الإعلانات
        $ad_positions = $this->calculateInlineAdPositions($total_paragraphs);
        
        $result = '';
        $ad_count = 0;
        
        foreach ($paragraphs as $index => $paragraph) {
            $result .= $paragraph;
            
            if ($index < $total_paragraphs - 1) {
                $result .= '</p>';
                
                // إدراج إعلان في المواقع المحددة
                if (in_array($index + 1, $ad_positions) && $ad_count < 2) {
                    $inline_ad = $this->displaySingleAd($placement, [
                        'style' => 'inline',
                        'class' => 'inline-ad-' . ($ad_count + 1)
                    ]);
                    
                    if ($inline_ad) {
                        $result .= $inline_ad;
                        $ad_count++;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * معالجة نقرة على الإعلان
     * @param int $ad_id معرف الإعلان
     * @return string|false رابط الوجهة أو false في حالة الخطأ
     */
    public function handleAdClick($ad_id) {
        // التحقق من صحة معرف الإعلان
        if (!is_numeric($ad_id) || $ad_id <= 0) {
            return false;
        }
        
        // تسجيل النقرة والحصول على رابط الوجهة
        $destination_url = $this->targeting_engine->logAdClick($ad_id);
        
        if ($destination_url) {
            // إضافة معاملات التتبع إذا لزم الأمر
            $destination_url = $this->addTrackingParameters($destination_url, $ad_id);
        }
        
        return $destination_url;
    }
    
    /**
     * رندر الإعلانات
     * @param array $ads قائمة الإعلانات
     * @param string $placement موقع العرض
     * @param string $style نمط العرض
     * @param array $options خيارات إضافية
     * @return string HTML للإعلانات
     */
    private function renderAds($ads, $placement, $style, $options = []) {
        if (empty($ads)) {
            return '';
        }
        
        $html = '';
        $container_class = $options['class'] ?? "ad-container ad-{$placement} ad-{$style}";
        
        // بداية الحاوي
        $html .= "<div class=\"{$container_class}\" data-placement=\"{$placement}\">\n";
        
        // إضافة تسمية "إعلان" إذا كانت مطلوبة
        if ($this->settings['show_ad_label'] ?? true) {
            $html .= "<div class=\"ad-label\">إعلان</div>\n";
        }
        
        foreach ($ads as $index => $ad) {
            $html .= $this->renderSingleAd($ad, $style, $index);
        }
        
        // نهاية الحاوي
        $html .= "</div>\n";
        
        // إضافة CSS و JavaScript إذا لزم الأمر
        if ($options['include_assets'] ?? true) {
            $html .= $this->getAdAssets($style);
        }
        
        return $html;
    }
    
    /**
     * رندر إعلان واحد
     * @param array $ad بيانات الإعلان
     * @param string $style نمط العرض
     * @param int $index فهرس الإعلان
     * @return string HTML للإعلان
     */
    private function renderSingleAd($ad, $style, $index = 0) {
        $ad_id = $ad['id'];
        $ad_type = $ad['type'];
        $click_url = "/ad_campaigns/click/{$ad_id}";
        
        $html = "<div class=\"ad-item ad-{$ad_type} ad-{$style}\" data-ad-id=\"{$ad_id}\">\n";
        
        switch ($ad_type) {
            case 'text':
                $html .= $this->renderTextAd($ad, $click_url, $style);
                break;
                
            case 'image':
                $html .= $this->renderImageAd($ad, $click_url, $style);
                break;
                
            case 'html':
                $html .= $this->renderHtmlAd($ad, $click_url, $style);
                break;
                
            default:
                $html .= $this->renderTextAd($ad, $click_url, $style);
        }
        
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * رندر إعلان نصي
     */
    private function renderTextAd($ad, $click_url, $style) {
        $title = htmlspecialchars($ad['title']);
        $description = htmlspecialchars($ad['description']);
        $display_url = parse_url($ad['destination_url'], PHP_URL_HOST);
        
        $html = "<a href=\"{$click_url}\" class=\"ad-link text-ad-link\" target=\"_blank\" rel=\"nofollow\">\n";
        $html .= "  <div class=\"ad-content\">\n";
        $html .= "    <h3 class=\"ad-title\">{$title}</h3>\n";
        $html .= "    <p class=\"ad-description\">{$description}</p>\n";
        $html .= "    <span class=\"ad-url\">{$display_url}</span>\n";
        $html .= "  </div>\n";
        $html .= "</a>\n";
        
        return $html;
    }
    
    /**
     * رندر إعلان مصور
     */
    private function renderImageAd($ad, $click_url, $style) {
        $title = htmlspecialchars($ad['title']);
        $description = htmlspecialchars($ad['description']);
        $image_url = htmlspecialchars($ad['image_url']);
        $alt_text = htmlspecialchars($ad['title']);
        
        $html = "<a href=\"{$click_url}\" class=\"ad-link image-ad-link\" target=\"_blank\" rel=\"nofollow\">\n";
        $html .= "  <div class=\"ad-image-container\">\n";
        $html .= "    <img src=\"{$image_url}\" alt=\"{$alt_text}\" class=\"ad-image\" loading=\"lazy\">\n";
        $html .= "  </div>\n";
        
        if ($style !== 'banner') {
            $html .= "  <div class=\"ad-content\">\n";
            $html .= "    <h3 class=\"ad-title\">{$title}</h3>\n";
            if ($description) {
                $html .= "    <p class=\"ad-description\">{$description}</p>\n";
            }
            $html .= "  </div>\n";
        }
        
        $html .= "</a>\n";
        
        return $html;
    }
    
    /**
     * رندر إعلان HTML
     */
    private function renderHtmlAd($ad, $click_url, $style) {
        $html_content = $ad['html_content'];
        
        // تنظيف وتأمين محتوى HTML
        $html_content = $this->sanitizeHtmlContent($html_content);
        
        // إضافة رابط النقرة إذا لم يكن موجوداً
        if (strpos($html_content, 'href=') === false) {
            $html_content = "<a href=\"{$click_url}\" target=\"_blank\" rel=\"nofollow\">{$html_content}</a>";
        }
        
        return $html_content;
    }
    
    /**
     * عرض إعلانات احتياطية
     */
    private function displayFallbackAds($placement) {
        // يمكن عرض إعلانات داخلية أو رسالة ترويجية
        if ($this->settings['show_fallback_ads'] ?? false) {
            return $this->renderFallbackContent($placement);
        }
        
        return '';
    }
    
    /**
     * رندر محتوى احتياطي
     */
    private function renderFallbackContent($placement) {
        $fallback_content = $this->settings['fallback_content'] ?? [
            'title' => 'انضم إلى مجتمعنا',
            'description' => 'اكتشف المزيد من المحتوى المفيد والمثير للاهتمام',
            'button_text' => 'تصفح المزيد',
            'button_url' => '/'
        ];
        
        $html = "<div class=\"ad-container ad-fallback ad-{$placement}\">\n";
        $html .= "  <div class=\"ad-item fallback-ad\">\n";
        $html .= "    <div class=\"ad-content\">\n";
        $html .= "      <h3 class=\"ad-title\">{$fallback_content['title']}</h3>\n";
        $html .= "      <p class=\"ad-description\">{$fallback_content['description']}</p>\n";
        $html .= "      <a href=\"{$fallback_content['button_url']}\" class=\"ad-button\">{$fallback_content['button_text']}</a>\n";
        $html .= "    </div>\n";
        $html .= "  </div>\n";
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * حساب مواقع الإعلانات المدمجة
     */
    private function calculateInlineAdPositions($total_paragraphs) {
        $positions = [];
        
        if ($total_paragraphs >= 6) {
            // إعلان بعد الفقرة الثالثة
            $positions[] = 3;
            
            if ($total_paragraphs >= 10) {
                // إعلان آخر في المنتصف تقريباً
                $positions[] = intval($total_paragraphs * 0.6);
            }
        }
        
        return $positions;
    }
    
    /**
     * إضافة معاملات التتبع
     */
    private function addTrackingParameters($url, $ad_id) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . "utm_source=finalmax&utm_medium=ad&utm_campaign=ad_{$ad_id}";
    }
    
    /**
     * تنظيف محتوى HTML
     */
    private function sanitizeHtmlContent($html) {
        // إزالة العلامات الخطيرة
        $dangerous_tags = ['script', 'iframe', 'object', 'embed', 'form'];
        
        foreach ($dangerous_tags as $tag) {
            $html = preg_replace("/<{$tag}\b[^>]*>(.*?)<\/{$tag}>/is", '', $html);
        }
        
        // إزالة الأحداث JavaScript
        $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        return $html;
    }
    
    /**
     * الحصول على أصول CSS و JavaScript
     */
    private function getAdAssets($style) {
        static $assets_loaded = [];
        
        if (in_array($style, $assets_loaded)) {
            return '';
        }
        
        $assets_loaded[] = $style;
        
        $css = $this->getAdCSS($style);
        $js = $this->getAdJavaScript();
        
        return $css . $js;
    }
    
    /**
     * الحصول على CSS للإعلانات
     */
    private function getAdCSS($style) {
        return "
        <style>
        /* Ad Display Styles */
        .ad-container {
            margin: 1rem 0;
            position: relative;
        }
        
        .ad-label {
            font-size: 0.7rem;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .ad-item {
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .ad-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .ad-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        
        /* Text Ad Styles */
        .text-ad-link {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .ad-title {
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .ad-description {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        
        .ad-url {
            color: #10b981;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Image Ad Styles */
        .image-ad-link {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .ad-image-container {
            position: relative;
            overflow: hidden;
        }
        
        .ad-image {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }
        
        .image-ad-link:hover .ad-image {
            transform: scale(1.05);
        }
        
        .image-ad-link .ad-content {
            padding: 1rem;
        }
        
        /* Banner Styles */
        .ad-banner {
            text-align: center;
            margin: 2rem 0;
        }
        
        .ad-banner .ad-image {
            max-height: 200px;
            object-fit: cover;
        }
        
        /* Sidebar Styles */
        .ad-sidebar .ad-item {
            margin-bottom: 1rem;
        }
        
        .ad-sidebar .ad-image {
            max-height: 150px;
            object-fit: cover;
        }
        
        /* Inline Styles */
        .ad-inline {
            margin: 2rem auto;
            max-width: 600px;
            text-align: center;
        }
        
        .ad-inline .ad-item {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        /* Fallback Ad Styles */
        .fallback-ad {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .fallback-ad .ad-title {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        .fallback-ad .ad-description {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
        }
        
        .ad-button {
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        .ad-button:hover {
            transform: translateY(-2px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .ad-container {
                margin: 0.5rem 0;
            }
            
            .ad-item {
                font-size: 0.9rem;
            }
            
            .ad-title {
                font-size: 1rem;
            }
            
            .ad-banner .ad-image {
                max-height: 150px;
            }
        }
        </style>
        ";
    }
    
    /**
     * الحصول على JavaScript للإعلانات
     */
    private function getAdJavaScript() {
        return "
        <script>
        // Ad Display JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // تتبع مشاهدة الإعلانات
            const adItems = document.querySelectorAll('.ad-item[data-ad-id]');
            
            // Intersection Observer لتتبع ظهور الإعلانات
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const adId = entry.target.dataset.adId;
                            if (adId && !entry.target.dataset.viewed) {
                                entry.target.dataset.viewed = 'true';
                                // يمكن إرسال طلب AJAX لتسجيل المشاهدة هنا
                            }
                        }
                    });
                }, {
                    threshold: 0.5,
                    rootMargin: '0px 0px -50px 0px'
                });
                
                adItems.forEach(function(item) {
                    observer.observe(item);
                });
            }
            
            // تتبع النقرات
            adItems.forEach(function(item) {
                const adLink = item.querySelector('.ad-link');
                if (adLink) {
                    adLink.addEventListener('click', function(e) {
                        const adId = item.dataset.adId;
                        
                        // إرسال إحصائية النقرة
                        if (navigator.sendBeacon) {
                            navigator.sendBeacon('/ad_campaigns/track_click', 
                                JSON.stringify({ad_id: adId}));
                        }
                    });
                }
            });
        });
        </script>
        ";
    }
    
    /**
     * تحميل إعدادات النظام
     */
    private function loadSettings() {
        try {
            $sql = "SELECT setting_key, setting_value FROM fmc_ad_settings";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = json_decode($row['setting_value'], true) ?: $row['setting_value'];
            }
            
            return $settings;
            
        } catch (Exception $e) {
            error_log("خطأ في تحميل إعدادات الإعلانات: " . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }
    
    /**
     * الحصول على الإعدادات الافتراضية
     */
    private function getDefaultSettings() {
        return [
            'system_enabled' => true,
            'show_ad_label' => true,
            'show_fallback_ads' => true,
            'allowed_placements' => [
                'homepage', 'sidebar', 'header', 'footer', 'inline', 
                'topic_detail', 'category', 'search_results'
            ],
            'placement_limits' => [
                'homepage' => 3,
                'sidebar' => 3,
                'header' => 1,
                'footer' => 2,
                'inline' => 2,
                'topic_detail' => 2
            ],
            'placement_styles' => [
                'homepage' => 'grid',
                'sidebar' => 'sidebar',
                'header' => 'banner',
                'footer' => 'banner',
                'inline' => 'inline'
            ]
        ];
    }
    
    /**
     * التحقق من تفعيل النظام
     */
    private function isSystemEnabled() {
        return $this->settings['system_enabled'] ?? true;
    }
    
    /**
     * التحقق من السماح بالعرض في موقع معين
     */
    private function isPlacementAllowed($placement) {
        $allowed = $this->settings['allowed_placements'] ?? [];
        return in_array($placement, $allowed);
    }
    
    /**
     * الحصول على حد العرض لموقع معين
     */
    private function getPlacementLimit($placement) {
        $limits = $this->settings['placement_limits'] ?? [];
        return $limits[$placement] ?? 3;
    }
    
    /**
     * الحصول على نمط العرض لموقع معين
     */
    private function getPlacementStyle($placement) {
        $styles = $this->settings['placement_styles'] ?? [];
        return $styles[$placement] ?? 'default';
    }
}
?>

