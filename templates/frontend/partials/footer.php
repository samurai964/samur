    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-3">
                        <div class="footer-section">
                            <h3 class="footer-title">Final Max CMS</h3>
                            <p class="footer-description">
                                نظام إدارة محتوى متطور يجمع بين قوة المنتديات ومرونة منصات الخدمات المهنية. 
                                انضم إلى مجتمعنا واستفد من خدماتنا المتنوعة.
                            </p>
                            <div class="social-links">
                                <a href="#" class="social-link" data-tooltip="فيسبوك">
                                    <span>📘</span>
                                </a>
                                <a href="#" class="social-link" data-tooltip="تويتر">
                                    <span>🐦</span>
                                </a>
                                <a href="#" class="social-link" data-tooltip="لينكد إن">
                                    <span>💼</span>
                                </a>
                                <a href="#" class="social-link" data-tooltip="يوتيوب">
                                    <span>📺</span>
                                </a>
                                <a href="#" class="social-link" data-tooltip="إنستغرام">
                                    <span>📷</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="footer-section">
                            <h4 class="footer-section-title">الأقسام الرئيسية</h4>
                            <ul class="footer-links">
                                <li><a href="/topics">📝 المواضيع والمنتديات</a></li>
                                <li><a href="/categories">📂 الأقسام</a></li>
                                <li><a href="/services">⚡ الخدمات المصغرة</a></li>
                                <li><a href="/courses">🎓 الدورات التدريبية</a></li>
                                <li><a href="/ads">📢 الإعلانات المبوبة</a></li>
                                <li><a href="/freelance">💼 العمل الحر</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="footer-section">
                            <h4 class="footer-section-title">خدمات الأعضاء</h4>
                            <ul class="footer-links">
                                <li><a href="/profile">👤 الملف الشخصي</a></li>
                                <li><a href="/wallet">💰 المحفظة</a></li>
                                <li><a href="/points">⭐ نظام النقاط</a></li>
                                <li><a href="/notifications">🔔 الإشعارات</a></li>
                                <li><a href="/messages">💬 الرسائل</a></li>
                                <li><a href="/bookmarks">🔖 المحفوظات</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="footer-section">
                            <h4 class="footer-section-title">معلومات مهمة</h4>
                            <ul class="footer-links">
                                <li><a href="/about">ℹ️ عن الموقع</a></li>
                                <li><a href="/contact">📞 اتصل بنا</a></li>
                                <li><a href="/privacy">🔒 سياسة الخصوصية</a></li>
                                <li><a href="/terms">📋 شروط الاستخدام</a></li>
                                <li><a href="/help">❓ المساعدة</a></li>
                                <li><a href="/sitemap">🗺️ خريطة الموقع</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-stats">
                <div class="row">
                    <div class="col-12">
                        <div class="stats-container">
                            <div class="stat-item">
                                <span class="stat-icon">👥</span>
                                <div class="stat-info">
                                    <span class="stat-number" data-count="<?php echo $siteStats['users'] ?? 0; ?>">0</span>
                                    <span class="stat-label">عضو مسجل</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">📝</span>
                                <div class="stat-info">
                                    <span class="stat-number" data-count="<?php echo $siteStats['topics'] ?? 0; ?>">0</span>
                                    <span class="stat-label">موضوع</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">💬</span>
                                <div class="stat-info">
                                    <span class="stat-number" data-count="<?php echo $siteStats['comments'] ?? 0; ?>">0</span>
                                    <span class="stat-label">تعليق</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">⚡</span>
                                <div class="stat-info">
                                    <span class="stat-number" data-count="<?php echo $siteStats['services'] ?? 0; ?>">0</span>
                                    <span class="stat-label">خدمة</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">🎓</span>
                                <div class="stat-info">
                                    <span class="stat-number" data-count="<?php echo $siteStats['courses'] ?? 0; ?>">0</span>
                                    <span class="stat-label">دورة</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row">
                    <div class="col-6">
                        <div class="copyright">
                            <p>
                                &copy; <?php echo date('Y'); ?> Final Max CMS. 
                                جميع الحقوق محفوظة. 
                                <span class="version">الإصدار 2.0</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="footer-actions">
                            <button id="theme-toggle" class="theme-toggle" data-tooltip="تبديل الثيم">
                                <span class="theme-icon">🌙</span>
                            </button>
                            <button id="scroll-to-top" class="scroll-to-top" data-tooltip="العودة للأعلى">
                                <span>⬆️</span>
                            </button>
                            <div class="language-selector">
                                <div class="language-selector">
    <?php if (function_exists('get_active_languages')): ?>
        <?php foreach (get_active_languages() as $lang): ?>
            <a href="/admin/change-lang?lang=<?= $lang['code'] ?>">
                <?= $lang['native_name'] ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
                                    <option value="ar" selected>العربية</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- عرض الإعلانات في الفوتر -->
        <?php
if (function_exists('displayInternalAds')) {
    displayInternalAds('footer', $currentPage ?? 'general');
}
?>

    </footer>

    <!-- نافذة منبثقة للإشعارات -->
    <div id="notification-container" class="notification-container"></div>

    <!-- نافذة منبثقة للتأكيد -->
    <div id="confirm-modal" class="modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">تأكيد العملية</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirm-message">هل أنت متأكد من هذا الإجراء؟</p>
            </div>
            <div class="modal-footer">
                <button id="confirm-yes" class="btn btn-primary">نعم</button>
                <button id="confirm-no" class="btn btn-secondary">لا</button>
            </div>
        </div>
    </div>

    <!-- بيانات المستخدم للـ JavaScript -->
    <?php if (is_logged_in()): ?>
        <script id="user-data" type="application/json">
            <?php echo json_encode([
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['user_role'] ?? 'user',
                'points' => $_SESSION['user_points'] ?? 0,
                'notifications_count' => $_SESSION['notifications_count'] ?? 0
            ]); ?>
        </script>
    <?php endif; ?>

    <!-- إعدادات الموقع للـ JavaScript -->
    <script id="site-config" type="application/json">
        <?php echo json_encode([
            'site_name' => 'Final Max CMS',
            'site_url' => BASE_URL,
            'api_url' => BASE_URL . '/api',
            'assets_url' => ASSETS_URL,
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'language' => get_current_language(),
            'timezone' => 'Asia/Riyadh'
        ]); ?>
    </script>

    <script>
        // تأثيرات الفوتر
        document.addEventListener('DOMContentLoaded', function() {
            // تحريك الأرقام في الإحصائيات
            const statNumbers = document.querySelectorAll('.stat-number');
            const observerOptions = {
                threshold: 0.5
            };

            const statsObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const finalCount = parseInt(target.dataset.count);
                        animateNumber(target, 0, finalCount, 2000);
                        statsObserver.unobserve(target);
                    }
                });
            }, observerOptions);

            statNumbers.forEach(number => {
                statsObserver.observe(number);
            });

            // زر العودة للأعلى
            const scrollToTopBtn = document.getElementById('scroll-to-top');
            if (scrollToTopBtn) {
                scrollToTopBtn.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                // إظهار/إخفاء زر العودة للأعلى
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        scrollToTopBtn.style.display = 'block';
                    } else {
                        scrollToTopBtn.style.display = 'none';
                    }
                });
            }

            // تبديل الثيم
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    
                    const icon = themeToggle.querySelector('.theme-icon');
                    icon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
                });
            }

            // تطبيق الثيم المحفوظ
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            const themeIcon = document.querySelector('.theme-icon');
            if (themeIcon) {
                themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
            }
        });

        // دالة تحريك الأرقام
        function animateNumber(element, start, end, duration) {
            const startTime = performance.now();
            const difference = end - start;

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = Math.floor(start + (difference * progress));
                element.textContent = current.toLocaleString('ar-SA');

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }

            requestAnimationFrame(updateNumber);
        }
    </script>
</body>
</html>