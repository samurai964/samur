<?php
// ملف عرض الإعلانات الداخلية
// يتم استدعاؤه في جميع صفحات الموقع لعرض الإعلانات حسب الموضع

if (!function_exists('displayInternalAds')) {
    function displayInternalAds($position, $page = '') {
        global $pdo, $prefix;
        
        if (!isset($pdo) || !isset($prefix)) {
            return;
        }
        
        try {
            // إنشاء كائن InternalAdsModel
            require_once ROOT_PATH . '/modules/internal_ads/InternalAdsModel.php';
            $internalAdsModel = new InternalAdsModel($pdo, $prefix);
            
            // جلب الإعلانات للموضع والصفحة المحددة
            $ads = $internalAdsModel->getActiveAdsForPage($page, $position);
            
            if (!empty($ads)) {
                foreach ($ads as $ad) {
                    echo '<div class="internal-ad position-' . htmlspecialchars($position) . '" data-ad-id="' . $ad['id'] . '">';
                    
                    if (!empty($ad['image_url'])) {
                        echo '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" style="max-width: 100%; height: auto; margin-bottom: 1rem; border-radius: 8px;">';
                    }
                    
                    echo '<h3>' . htmlspecialchars($ad['title']) . '</h3>';
                    echo '<p>' . htmlspecialchars($ad['content']) . '</p>';
                    
                    if (!empty($ad['link_url'])) {
                        echo '<a href="' . htmlspecialchars($ad['link_url']) . '" class="btn btn-primary" target="_blank" onclick="recordAdClick(' . $ad['id'] . ')">اعرف المزيد</a>';
                    }
                    
                    echo '</div>';
                    
                    // تسجيل مشاهدة الإعلان
                    echo '<script>recordAdView(' . $ad['id'] . ');</script>';
                }
            }
        } catch (Exception $e) {
            // في حالة حدوث خطأ، لا نعرض شيئًا
            return;
        }
    }
}

// دالة لتسجيل مشاهدة الإعلان
if (!function_exists('getAdTrackingScript')) {
    function getAdTrackingScript() {
        return '
        <script>
        function recordAdView(adId) {
            fetch("/api/internal-ads/record-view", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "ad_id=" + adId
            }).catch(function(error) {
                console.log("Ad view tracking error:", error);
            });
        }
        
        function recordAdClick(adId) {
            fetch("/api/internal-ads/record-click", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "ad_id=" + adId
            }).catch(function(error) {
                console.log("Ad click tracking error:", error);
            });
        }
        </script>';
    }
}

// إضافة سكريبت التتبع في نهاية الصفحة
echo getAdTrackingScript();
?>

