<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المواضيع - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
</head>
<body>
    <?php 
    $currentPage = 'topics';
    include ROOT_PATH . '/templates/frontend/partials/header.php'; 
    ?>

    <main class="container">
        <!-- عرض الإعلانات أعلى المحتوى -->
        <?php displayInternalAds('content_top', 'topics'); ?>

        <div class="page-header fade-in">
            <h1>المواضيع والمنتديات</h1>
            <p class="page-subtitle">شارك في النقاشات واطرح الأسئلة وشارك خبراتك مع المجتمع</p>
        </div>

        <!-- فلاتر البحث المحسنة -->
        <div class="search-filters card">
            <form method="GET" action="/topics" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search">🔍 البحث</label>
                        <input type="text" id="search" name="search" placeholder="البحث في المواضيع..." 
                               value="<?php echo htmlspecialchars($data["currentSearch"] ?? ""); ?>">
                    </div>
                    <div class="form-group">
                        <label for="category">📂 القسم</label>
                        <select id="category" name="category">
                            <option value="">جميع الأقسام</option>
                            <?php if (isset($data["categories"])): ?>
                                <?php foreach ($data["categories"] as $category): ?>
                                    <option value="<?php echo $category["id"]; ?>" 
                                            <?php echo ($data["currentCategory"] == $category["id"]) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($category["name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort">🔄 الترتيب</label>
                        <select id="sort" name="sort">
                            <option value="latest">الأحدث</option>
                            <option value="popular">الأكثر شعبية</option>
                            <option value="most_viewed">الأكثر مشاهدة</option>
                            <option value="most_liked">الأكثر إعجاباً</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <span>🔍</span> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (isset($_SESSION["error_message"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error_message"]; unset($_SESSION["error_message"]); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-8">
                <?php if (empty($data["topics"])): ?>
                    <div class="empty-state card text-center">
                        <div class="empty-icon">📝</div>
                        <h3>لا توجد مواضيع حالياً</h3>
                        <p>كن أول من يبدأ النقاش وشارك موضوعاً جديداً</p>
                        <?php if (is_logged_in()): ?>
                            <a href="/topics/create" class="btn btn-primary">إضافة موضوع جديد</a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-primary">تسجيل الدخول للمشاركة</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="topics-header">
                        <div class="topics-stats">
                            <span class="stat-item">
                                <strong><?php echo count($data["topics"]); ?></strong> موضوع في هذه الصفحة
                            </span>
                            <?php if (isset($data['totalTopics'])): ?>
                                <span class="stat-item">
                                    <strong><?php echo $data['totalTopics']; ?></strong> إجمالي المواضيع
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (is_logged_in()): ?>
                            <a href="/topics/create" class="btn btn-primary">
                                <span>➕</span> موضوع جديد
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="topics-list">
                        <?php foreach ($data["topics"] as $index => $topic): ?>
                            <article class="topic-card fade-in">
                                <div class="topic-header">
                                    <h2>
                                        <a href="/topic/<?php echo $topic["id"]; ?>">
                                            <?php echo htmlspecialchars($topic["title"]); ?>
                                        </a>
                                    </h2>
                                    <div class="topic-meta">
                                        <span class="author">
                                            👤 <a href="/profile/<?php echo $topic['user_id'] ?? ''; ?>">
                                                <?php echo htmlspecialchars($topic["author_name"] ?? 'مستخدم'); ?>
                                            </a>
                                        </span>
                                        <span class="category">
                                            📂 <a href="/topics?category=<?php echo $topic["category_id"]; ?>">
                                                <?php echo htmlspecialchars($topic["category_name"] ?? 'عام'); ?>
                                            </a>
                                        </span>
                                        <span class="date">
                                            🕒 <?php echo date("Y-m-d H:i", strtotime($topic["created_at"])); ?>
                                        </span>
                                        <?php if (isset($topic['is_pinned']) && $topic['is_pinned']): ?>
                                            <span class="pinned-badge">📌 مثبت</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="topic-content">
                                    <?php if (!empty($topic["excerpt"])): ?>
                                        <p><?php echo htmlspecialchars($topic["excerpt"]); ?></p>
                                    <?php else: ?>
                                        <p><?php echo htmlspecialchars(substr(strip_tags($topic["content"] ?? ''), 0, 200)) . "..."; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="topic-footer">
                                    <div class="topic-stats">
                                        <span class="stat">👁️ <?php echo $topic["views"] ?? 0; ?> مشاهدة</span>
                                        <span class="stat">💬 <?php echo $topic["comments_count"] ?? 0; ?> تعليق</span>
                                        <span class="stat">👍 <?php echo $topic["likes"] ?? 0; ?> إعجاب</span>
                                    </div>
                                    <a href="/topic/<?php echo $topic["id"]; ?>" class="read-more">
                                        اقرأ المزيد ←
                                    </a>
                                </div>
                            </article>

                            <!-- عرض إعلان بين المواضيع كل 3 مواضيع -->
                            <?php if (($index + 1) % 3 == 0): ?>
                                <?php displayInternalAds('content_middle', 'topics'); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- الترقيم المحسن -->
                    <?php if (isset($data["totalPages"]) && $data["totalPages"] > 1): ?>
                        <div class="pagination">
                            <?php if ($data["currentPage"] > 1): ?>
                                <a href="?page=<?php echo $data["currentPage"] - 1; ?><?php echo isset($data["currentCategory"]) && $data["currentCategory"] ? "&category=" . $data["currentCategory"] : ""; ?><?php echo isset($data["currentSearch"]) && $data["currentSearch"] ? "&search=" . urlencode($data["currentSearch"]) : ""; ?>" class="btn btn-secondary">← السابق</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $data["totalPages"]; $i++): ?>
                                <?php if ($i == $data["currentPage"]): ?>
                                    <span class="current-page"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo isset($data["currentCategory"]) && $data["currentCategory"] ? "&category=" . $data["currentCategory"] : ""; ?><?php echo isset($data["currentSearch"]) && $data["currentSearch"] ? "&search=" . urlencode($data["currentSearch"]) : ""; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($data["currentPage"] < $data["totalPages"]): ?>
                                <a href="?page=<?php echo $data["currentPage"] + 1; ?><?php echo isset($data["currentCategory"]) && $data["currentCategory"] ? "&category=" . $data["currentCategory"] : ""; ?><?php echo isset($data["currentSearch"]) && $data["currentSearch"] ? "&search=" . urlencode($data["currentSearch"]) : ""; ?>" class="btn btn-secondary">التالي →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="col-4">
                <aside class="sidebar">
                    <!-- عرض الإعلانات في الشريط الجانبي -->
                    <?php displayInternalAds('sidebar', 'topics'); ?>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">الأقسام الشائعة</h3>
                        <div class="categories-list">
                            <?php if (isset($data['popularCategories'])): ?>
                                <?php foreach ($data['popularCategories'] as $category): ?>
                                    <a href="/topics?category=<?php echo $category['id']; ?>" class="category-link">
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo $category['topics_count'] ?? 0; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">المواضيع الشائعة</h3>
                        <div class="popular-topics">
                            <?php if (isset($data['popularTopics'])): ?>
                                <?php foreach ($data['popularTopics'] as $topic): ?>
                                    <div class="popular-topic">
                                        <a href="/topic/<?php echo $topic['id']; ?>">
                                            <?php echo htmlspecialchars($topic['title']); ?>
                                        </a>
                                        <div class="topic-mini-stats">
                                            👁️ <?php echo $topic['views'] ?? 0; ?> | 💬 <?php echo $topic['comments_count'] ?? 0; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (is_logged_in()): ?>
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">إحصائياتك</h3>
                            <div class="user-stats">
                                <div class="stat-item">
                                    <span class="stat-label">مواضيعك</span>
                                    <span class="stat-value"><?php echo $data['userStats']['topics'] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">تعليقاتك</span>
                                    <span class="stat-value"><?php echo $data['userStats']['comments'] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">نقاطك</span>
                                    <span class="stat-value"><?php echo $data['userStats']['points'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>

        <!-- عرض الإعلانات أسفل المحتوى -->
        <?php displayInternalAds('content_bottom', 'topics'); ?>
    </main>

    <?php include ROOT_PATH . '/templates/frontend/partials/footer.php'; ?>

    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    <script>
        // تأثيرات بصرية للصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // إضافة تأثير الظهور التدريجي
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, observerOptions);

            // مراقبة جميع بطاقات المواضيع
            document.querySelectorAll('.topic-card').forEach(card => {
                observer.observe(card);
            });

            // تأثير hover للإحصائيات
            document.querySelectorAll('.topic-stats .stat').forEach(stat => {
                stat.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.color = 'var(--primary-color)';
                });
                
                stat.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.color = '';
                });
            });

            // تحسين تجربة البحث
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            }
        });
    </script>
</body>
</html>

