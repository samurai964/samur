<?php

// ===== التحقق من التفعيل =====
if (!function_exists('setting') || !setting('ticker_enabled')) {
    return;
}

// ===== الإعدادات =====
$bg        = setting('ticker_bg', '#0f172a');
$color     = setting('ticker_color', '#ffffff');
$speed     = (int) setting('ticker_speed', 20);
$sections  = setting('ticker_sections');
$direction = setting('ticker_direction', 'rtl'); // rtl أو ltr

// ===== تحديد اتجاه الحركة =====
$animationDirection = ($direction === 'ltr') ? 'tickerLTR' : 'tickerRTL';

if (!function_exists('displayEnhancedTicker')) {

    function displayEnhancedTicker() {

        global $pdo, $prefix, $sections;

        if (!isset($pdo)) return;
        if (!isset($prefix)) $prefix = '';

        $filter = '';
        if (!empty($sections)) {
            $filter = "AND category_id IN ($sections)";
        }

        $tickerItems = [];

        try {

            // ===== مواضيع =====
            $stmt = $pdo->query("
                SELECT title FROM `{$prefix}topics`
                WHERE status='active' $filter
                ORDER BY id DESC
                LIMIT 10
            ");

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tickerItems[] = '📌 ' . $row['title'];
            }

        } catch (Exception $e) {
            // لا نوقف النظام
        }

        // ===== fallback =====
        if (empty($tickerItems)) {
            $tickerItems[] = '🔥 مرحباً بك في الموقع';
        }

        // ===== تكرار (مهم جدًا للاستمرارية) =====
        $tickerItems = array_merge($tickerItems, $tickerItems);

        // ===== CSS ديناميكي =====
        global $bg, $color, $speed, $animationDirection;

        echo "<style>
        .enhanced-ticker {
            background: {$bg};
            color: {$color};
        }

        .enhanced-ticker-track {
            animation: {$animationDirection} {$speed}s linear infinite;
        }
        </style>";

        // ===== العرض =====
        echo '<div class="enhanced-ticker">';
        echo '<div class="enhanced-ticker-track">';

        // تكرار مزدوج لمنع الفراغ
        foreach ($tickerItems as $text) {
            echo '<div class="enhanced-ticker-item">' . htmlspecialchars($text) . '</div>';
        }

        foreach ($tickerItems as $text) {
            echo '<div class="enhanced-ticker-item">' . htmlspecialchars($text) . '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}

displayEnhancedTicker();

?>
