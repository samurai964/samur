<?php
/**
 * مكون اختيار اللغة للمستخدمين
 * Final Max CMS - Language Selector Component
 */

// التأكد من تحميل محرك اللغات
if (!isset($language_engine)) {
    require_once __DIR__ . '/../../../core/LanguageEngine.php';
    $language_engine = new LanguageEngine($db);
}

// الحصول على اللغات النشطة
$active_languages = $language_engine->getActiveLanguages();
$current_language = $language_engine->getCurrentLanguageInfo();

// إعدادات العرض
$show_flags = true;
$show_native_names = true;
$dropdown_style = $_GET['style'] ?? 'modern'; // modern, classic, minimal
$position = $_GET['position'] ?? 'header'; // header, footer, sidebar
?>

<div class="language-selector-container" data-style="<?= $dropdown_style ?>" data-position="<?= $position ?>">
    <!-- Language Selector Button -->
    <div class="language-selector-trigger" onclick="toggleLanguageDropdown()">
        <div class="current-language">
            <?php if ($show_flags && $current_language): ?>
                <div class="language-flag">
                    <i class="flag-icon <?= $current_language['flag_icon'] ?>"></i>
                </div>
            <?php endif; ?>
            
            <div class="language-info">
                <?php if ($current_language): ?>
                    <span class="language-name">
                        <?= $show_native_names ? htmlspecialchars($current_language['native_name']) : htmlspecialchars($current_language['name']) ?>
                    </span>
                    <span class="language-code"><?= strtoupper($current_language['code']) ?></span>
                <?php else: ?>
                    <span class="language-name">اختر اللغة</span>
                <?php endif; ?>
            </div>
            
            <div class="dropdown-arrow">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </div>

    <!-- Language Dropdown -->
    <div class="language-dropdown" id="languageDropdown">
        <div class="dropdown-header">
            <h4>اختر لغتك المفضلة</h4>
            <p>Choose your preferred language</p>
        </div>
        
        <div class="languages-list">
            <?php foreach ($active_languages as $language): ?>
                <div class="language-option <?= $current_language && $language['code'] === $current_language['code'] ? 'active' : '' ?>" 
                     onclick="changeLanguage('<?= $language['code'] ?>')">
                    
                    <?php if ($show_flags): ?>
                        <div class="language-flag">
                            <i class="flag-icon <?= $language['flag_icon'] ?>"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="language-details">
                        <div class="language-name">
                            <?= htmlspecialchars($language['native_name']) ?>
                        </div>
                        <div class="language-english">
                            <?= htmlspecialchars($language['name']) ?>
                        </div>
                    </div>
                    
                    <div class="language-meta">
                        <span class="language-code"><?= strtoupper($language['code']) ?></span>
                        <?php if ($language['completion_percentage'] < 100): ?>
                            <div class="completion-indicator">
                                <div class="completion-bar">
                                    <div class="completion-fill" style="width: <?= $language['completion_percentage'] ?>%"></div>
                                </div>
                                <span class="completion-text"><?= round($language['completion_percentage']) ?>%</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($current_language && $language['code'] === $current_language['code']): ?>
                        <div class="active-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="dropdown-footer">
            <div class="language-stats">
                <span><?= count($active_languages) ?> لغة متاحة</span>
                <span>•</span>
                <span><?= count($active_languages) ?> languages available</span>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-preferences">
                    <a href="/user/language-preferences" class="preferences-link">
                        <i class="fas fa-cog"></i>
                        إعدادات اللغة
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="language-loading" id="languageLoading">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <div class="loading-text">جاري تغيير اللغة...</div>
    </div>
</div>

<!-- Compact Language Selector (Alternative Style) -->
<div class="language-selector-compact" style="display: none;">
    <select class="language-select" onchange="changeLanguage(this.value)">
        <?php foreach ($active_languages as $language): ?>
            <option value="<?= $language['code'] ?>" 
                    <?= $current_language && $language['code'] === $current_language['code'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($language['native_name']) ?> (<?= strtoupper($language['code']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Language Flags Bar (Alternative Style) -->
<div class="language-flags-bar" style="display: none;">
    <?php foreach ($active_languages as $language): ?>
        <div class="flag-option <?= $current_language && $language['code'] === $current_language['code'] ? 'active' : '' ?>" 
             onclick="changeLanguage('<?= $language['code'] ?>')"
             title="<?= htmlspecialchars($language['native_name']) ?>">
            <i class="flag-icon <?= $language['flag_icon'] ?>"></i>
        </div>
    <?php endforeach; ?>
</div>

<style>
/* Language Selector Styles */
.language-selector-container {
    position: relative;
    display: inline-block;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    z-index: 1000;
}

.language-selector-trigger {
    cursor: pointer;
    user-select: none;
    transition: all 0.3s ease;
}

.current-language {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.3s ease;
    min-width: 120px;
}

.current-language:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.language-flag {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    overflow: hidden;
}

.language-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.language-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
    line-height: 1;
}

.language-code {
    font-size: 0.7rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
}

.dropdown-arrow {
    color: #6b7280;
    transition: transform 0.3s ease;
    font-size: 0.8rem;
}

.language-selector-container.open .dropdown-arrow {
    transform: rotate(180deg);
}

/* Language Dropdown */
.language-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    min-width: 300px;
    max-height: 400px;
    overflow: hidden;
    z-index: 1001;
}

.language-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 20px 20px 15px;
    border-bottom: 1px solid #f3f4f6;
    text-align: center;
}

.dropdown-header h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.dropdown-header p {
    margin: 0;
    font-size: 0.8rem;
    color: #6b7280;
}

.languages-list {
    max-height: 250px;
    overflow-y: auto;
    padding: 10px 0;
}

.language-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.language-option:hover {
    background: #f8fafc;
}

.language-option.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.language-option.active .language-name,
.language-option.active .language-english,
.language-option.active .language-code {
    color: white;
}

.language-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.language-details .language-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1f2937;
}

.language-details .language-english {
    font-size: 0.8rem;
    color: #6b7280;
}

.language-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.completion-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
}

.completion-bar {
    width: 40px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.completion-fill {
    height: 100%;
    background: #10b981;
    transition: width 0.3s ease;
}

.completion-text {
    font-size: 0.7rem;
    color: #6b7280;
    font-weight: 500;
}

.active-indicator {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: white;
    font-size: 0.9rem;
}

.dropdown-footer {
    padding: 15px 20px;
    border-top: 1px solid #f3f4f6;
    background: #f9fafb;
}

.language-stats {
    text-align: center;
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 10px;
}

.preferences-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.preferences-link:hover {
    border-color: #667eea;
    color: #667eea;
}

/* Loading Overlay */
.language-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 10px;
    border-radius: 10px;
    z-index: 1002;
}

.language-loading.show {
    display: flex;
}

.loading-spinner {
    font-size: 1.5rem;
    color: #667eea;
}

.loading-text {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

/* Alternative Styles */

/* Compact Selector */
.language-selector-compact {
    display: inline-block;
}

.language-select {
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.3s ease;
}

.language-select:hover,
.language-select:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Flags Bar */
.language-flags-bar {
    display: flex;
    gap: 8px;
    align-items: center;
}

.flag-option {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    font-size: 1.2rem;
}

.flag-option:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.flag-option.active {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .language-dropdown {
        min-width: 280px;
        left: 50%;
        transform: translateX(-50%) translateY(-10px);
    }
    
    .language-dropdown.show {
        transform: translateX(-50%) translateY(0);
    }
    
    .language-option {
        padding: 15px 20px;
    }
    
    .current-language {
        min-width: 100px;
    }
    
    .language-info {
        display: none;
    }
    
    .language-selector-container[data-position="header"] .current-language {
        padding: 6px 10px;
    }
}

/* Position-specific styles */
.language-selector-container[data-position="header"] {
    margin: 0 10px;
}

.language-selector-container[data-position="footer"] .current-language {
    background: transparent;
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.language-selector-container[data-position="footer"] .current-language:hover {
    border-color: rgba(255, 255, 255, 0.6);
}

.language-selector-container[data-position="sidebar"] {
    width: 100%;
}

.language-selector-container[data-position="sidebar"] .current-language {
    width: 100%;
    justify-content: space-between;
}

/* Style variations */
.language-selector-container[data-style="minimal"] .current-language {
    border: none;
    background: transparent;
    padding: 4px 8px;
}

.language-selector-container[data-style="minimal"] .language-dropdown {
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.language-selector-container[data-style="classic"] .current-language {
    border-radius: 4px;
    background: #f8fafc;
}

.language-selector-container[data-style="classic"] .language-dropdown {
    border-radius: 4px;
}

/* Animation for language change */
@keyframes languageChange {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

.language-changing {
    animation: languageChange 0.5s ease-in-out;
}

/* RTL Support */
[dir="rtl"] .language-dropdown {
    left: auto;
    right: 0;
}

[dir="rtl"] .language-meta {
    align-items: flex-start;
}

[dir="rtl"] .active-indicator {
    right: auto;
    left: 15px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .current-language {
        background: #374151;
        border-color: #4b5563;
        color: white;
    }
    
    .language-dropdown {
        background: #374151;
        border-color: #4b5563;
    }
    
    .dropdown-header h4 {
        color: white;
    }
    
    .language-option:hover {
        background: #4b5563;
    }
    
    .language-details .language-name {
        color: white;
    }
    
    .dropdown-footer {
        background: #4b5563;
        border-color: #6b7280;
    }
    
    .preferences-link {
        background: #4b5563;
        border-color: #6b7280;
        color: white;
    }
}
</style>

<script>
// Language Selector JavaScript
let isDropdownOpen = false;
let isChangingLanguage = false;

function toggleLanguageDropdown() {
    if (isChangingLanguage) return;
    
    const container = document.querySelector('.language-selector-container');
    const dropdown = document.getElementById('languageDropdown');
    
    if (isDropdownOpen) {
        closeLanguageDropdown();
    } else {
        openLanguageDropdown();
    }
}

function openLanguageDropdown() {
    const container = document.querySelector('.language-selector-container');
    const dropdown = document.getElementById('languageDropdown');
    
    container.classList.add('open');
    dropdown.classList.add('show');
    isDropdownOpen = true;
    
    // Add click outside listener
    setTimeout(() => {
        document.addEventListener('click', handleClickOutside);
    }, 100);
    
    // Track analytics
    trackLanguageSelectorOpen();
}

function closeLanguageDropdown() {
    const container = document.querySelector('.language-selector-container');
    const dropdown = document.getElementById('languageDropdown');
    
    container.classList.remove('open');
    dropdown.classList.remove('show');
    isDropdownOpen = false;
    
    // Remove click outside listener
    document.removeEventListener('click', handleClickOutside);
}

function handleClickOutside(event) {
    const container = document.querySelector('.language-selector-container');
    
    if (!container.contains(event.target)) {
        closeLanguageDropdown();
    }
}

function changeLanguage(languageCode) {
    if (isChangingLanguage) return;
    
    // Get current language
    const currentLang = getCurrentLanguageCode();
    if (languageCode === currentLang) {
        closeLanguageDropdown();
        return;
    }
    
    isChangingLanguage = true;
    
    // Show loading state
    showLanguageLoading();
    
    // Close dropdown
    closeLanguageDropdown();
    
    // Track analytics
    trackLanguageChange(currentLang, languageCode);
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'change_language';
    input.value = languageCode;
    
    form.appendChild(input);
    document.body.appendChild(form);
    
    // Add animation class
    document.body.classList.add('language-changing');
    
    // Submit form after short delay for better UX
    setTimeout(() => {
        form.submit();
    }, 500);
}

function showLanguageLoading() {
    const loading = document.getElementById('languageLoading');
    if (loading) {
        loading.classList.add('show');
    }
}

function hideLanguageLoading() {
    const loading = document.getElementById('languageLoading');
    if (loading) {
        loading.classList.remove('show');
    }
    isChangingLanguage = false;
}

function getCurrentLanguageCode() {
    const activeOption = document.querySelector('.language-option.active');
    return activeOption ? activeOption.getAttribute('onclick').match(/'([^']+)'/)[1] : 'ar';
}

// Analytics functions
function trackLanguageSelectorOpen() {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'language_selector_open', {
            'event_category': 'Language',
            'event_label': getCurrentLanguageCode()
        });
    }
}

function trackLanguageChange(fromLang, toLang) {
    if (typeof gtag !== 'undefined') {
        gtag('event', 'language_change', {
            'event_category': 'Language',
            'event_label': `${fromLang}_to_${toLang}`,
            'custom_parameter_1': fromLang,
            'custom_parameter_2': toLang
        });
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(event) {
    if (!isDropdownOpen) return;
    
    const options = document.querySelectorAll('.language-option');
    let currentIndex = -1;
    
    // Find currently focused option
    options.forEach((option, index) => {
        if (option.classList.contains('keyboard-focus')) {
            currentIndex = index;
        }
    });
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            currentIndex = (currentIndex + 1) % options.length;
            updateKeyboardFocus(options, currentIndex);
            break;
            
        case 'ArrowUp':
            event.preventDefault();
            currentIndex = currentIndex <= 0 ? options.length - 1 : currentIndex - 1;
            updateKeyboardFocus(options, currentIndex);
            break;
            
        case 'Enter':
            event.preventDefault();
            if (currentIndex >= 0) {
                const selectedOption = options[currentIndex];
                const languageCode = selectedOption.getAttribute('onclick').match(/'([^']+)'/)[1];
                changeLanguage(languageCode);
            }
            break;
            
        case 'Escape':
            event.preventDefault();
            closeLanguageDropdown();
            break;
    }
});

function updateKeyboardFocus(options, focusIndex) {
    options.forEach((option, index) => {
        option.classList.toggle('keyboard-focus', index === focusIndex);
    });
}

// Auto-hide loading on page load
window.addEventListener('load', function() {
    hideLanguageLoading();
});

// Handle browser back/forward
window.addEventListener('popstate', function() {
    hideLanguageLoading();
});

// Responsive behavior
function handleResponsiveChanges() {
    const container = document.querySelector('.language-selector-container');
    const compact = document.querySelector('.language-selector-compact');
    const flagsBar = document.querySelector('.language-flags-bar');
    
    if (window.innerWidth <= 768) {
        // Mobile: show compact version in some cases
        if (container.dataset.position === 'header') {
            // Keep normal selector but adjust styles
        }
    }
}

window.addEventListener('resize', handleResponsiveChanges);
window.addEventListener('load', handleResponsiveChanges);

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Set initial state
    hideLanguageLoading();
    
    // Add hover effects
    const options = document.querySelectorAll('.language-option');
    options.forEach(option => {
        option.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        option.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
    
    // Preload flag icons
    preloadFlagIcons();
});

function preloadFlagIcons() {
    const flags = document.querySelectorAll('.flag-icon');
    flags.forEach(flag => {
        const img = new Image();
        img.src = flag.style.backgroundImage || '';
    });
}

// Alternative selector functions
function showCompactSelector() {
    document.querySelector('.language-selector-container').style.display = 'none';
    document.querySelector('.language-selector-compact').style.display = 'inline-block';
}

function showFlagsBar() {
    document.querySelector('.language-selector-container').style.display = 'none';
    document.querySelector('.language-flags-bar').style.display = 'flex';
}

function showNormalSelector() {
    document.querySelector('.language-selector-container').style.display = 'inline-block';
    document.querySelector('.language-selector-compact').style.display = 'none';
    document.querySelector('.language-flags-bar').style.display = 'none';
}
</script>

<?php
// Handle language change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_language'])) {
    $new_language = $_POST['change_language'];
    
    try {
        $language_engine->setLanguage($new_language);
        
        // Redirect to prevent form resubmission
        $redirect_url = $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url");
        exit;
    } catch (Exception $e) {
        error_log("Language change error: " . $e->getMessage());
    }
}
?>

