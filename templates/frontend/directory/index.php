<?php include '../templates/frontend/partials/header.php'; ?>

<div class="directory-page">
    <!-- Hero Section -->
    <section class="directory-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">🌐 دليل المواقع</h1>
                <p class="hero-subtitle">اكتشف أفضل المواقع العربية في جميع المجالات</p>
                
                <!-- Search Bar -->
                <div class="search-section">
                    <form method="GET" class="search-form">
                        <div class="search-input-group">
                            <input type="text" name="search" placeholder="ابحث عن موقع..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="search-input">
                            <select name="category" class="search-select">
                                <option value="">جميع الفئات</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" 
                                            <?= ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?> (<?= $cat['websites_count'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="search-btn">🔍 بحث</button>
                        </div>
                    </form>
                </div>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?= format_number($stats['total_websites']) ?></span>
                        <span class="stat-label">موقع</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= format_number($stats['total_categories']) ?></span>
                        <span class="stat-label">فئة</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= format_number($stats['total_reviews']) ?></span>
                        <span class="stat-label">تقييم</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= format_number($stats['total_views']) ?></span>
                        <span class="stat-label">مشاهدة</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Websites -->
    <?php if (!empty($featured_websites)): ?>
    <section class="featured-section">
        <div class="container">
            <h2 class="section-title">⭐ المواقع المميزة</h2>
            <div class="featured-grid">
                <?php foreach ($featured_websites as $website): ?>
                    <div class="featured-card">
                        <div class="featured-image">
                            <?php if ($website['screenshot']): ?>
                                <img src="/assets/uploads/<?= htmlspecialchars($website['screenshot']) ?>" 
                                     alt="<?= htmlspecialchars($website['title']) ?>">
                            <?php else: ?>
                                <div class="no-image">🌐</div>
                            <?php endif; ?>
                            <div class="featured-badge">مميز</div>
                        </div>
                        <div class="featured-content">
                            <h3 class="featured-title">
                                <a href="/directory/<?= htmlspecialchars($website['slug']) ?>">
                                    <?= htmlspecialchars($website['title']) ?>
                                </a>
                            </h3>
                            <p class="featured-description">
                                <?= truncate_text($website['description'], 100) ?>
                            </p>
                            <div class="featured-meta">
                                <span class="category">📂 <?= htmlspecialchars($website['category_name']) ?></span>
                                <span class="rating">⭐ <?= $website['rating'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <div class="content-layout">
                <!-- Sidebar -->
                <aside class="sidebar">
                    <!-- Categories -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">📂 الفئات</h3>
                        <ul class="categories-list">
                            <li class="<?= empty($_GET['category']) ? 'active' : '' ?>">
                                <a href="/directory">جميع الفئات</a>
                                <span class="count"><?= $stats['total_websites'] ?></span>
                            </li>
                            <?php foreach ($categories as $category): ?>
                                <li class="<?= ($_GET['category'] ?? '') == $category['id'] ? 'active' : '' ?>">
                                    <a href="/directory/category/<?= htmlspecialchars($category['slug']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                    <span class="count"><?= $category['websites_count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Add Website Button -->
                    <div class="sidebar-widget">
                        <a href="/directory/add" class="add-website-btn">
                            ➕ أضف موقعك
                        </a>
                    </div>

                    <!-- Top Rated -->
                    <?php if (!empty($stats['top_rated'])): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">🏆 الأعلى تقييماً</h3>
                        <ul class="top-rated-list">
                            <?php foreach ($stats['top_rated'] as $website): ?>
                                <li>
                                    <a href="/directory/<?= htmlspecialchars($website['slug']) ?>">
                                        <?= htmlspecialchars($website['title']) ?>
                                    </a>
                                    <div class="rating">⭐ <?= $website['rating'] ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </aside>

                <!-- Websites List -->
                <main class="websites-content">
                    <!-- Filters -->
                    <div class="filters-bar">
                        <div class="filters-left">
                            <span class="results-count">
                                <?= format_number($websites['total']) ?> موقع
                            </span>
                        </div>
                        <div class="filters-right">
                            <select name="sort" onchange="updateSort(this.value)" class="sort-select">
                                <option value="latest" <?= ($_GET['sort'] ?? 'latest') === 'latest' ? 'selected' : '' ?>>
                                    الأحدث
                                </option>
                                <option value="popular" <?= ($_GET['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>
                                    الأكثر شعبية
                                </option>
                                <option value="rating" <?= ($_GET['sort'] ?? '') === 'rating' ? 'selected' : '' ?>>
                                    الأعلى تقييماً
                                </option>
                                <option value="alphabetical" <?= ($_GET['sort'] ?? '') === 'alphabetical' ? 'selected' : '' ?>>
                                    أبجدياً
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Websites Grid -->
                    <?php if (!empty($websites['websites'])): ?>
                        <div class="websites-grid">
                            <?php foreach ($websites['websites'] as $website): ?>
                                <div class="website-card" data-aos="fade-up">
                                    <div class="website-image">
                                        <?php if ($website['screenshot']): ?>
                                            <img src="/assets/uploads/<?= htmlspecialchars($website['screenshot']) ?>" 
                                                 alt="<?= htmlspecialchars($website['title']) ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="no-image">🌐</div>
                                        <?php endif; ?>
                                        
                                        <?php if ($website['is_free']): ?>
                                            <div class="free-badge">مجاني</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="website-content">
                                        <h3 class="website-title">
                                            <a href="/directory/<?= htmlspecialchars($website['slug']) ?>">
                                                <?= htmlspecialchars($website['title']) ?>
                                            </a>
                                        </h3>
                                        
                                        <p class="website-description">
                                            <?= truncate_text($website['description'], 120) ?>
                                        </p>
                                        
                                        <div class="website-meta">
                                            <span class="category">
                                                📂 <a href="/directory/category/<?= htmlspecialchars($website['category_slug']) ?>">
                                                    <?= htmlspecialchars($website['category_name']) ?>
                                                </a>
                                            </span>
                                            <span class="author">👤 <?= htmlspecialchars($website['username']) ?></span>
                                        </div>
                                        
                                        <div class="website-stats">
                                            <span class="rating">
                                                ⭐ <?= $website['rating'] ?: 'غير مقيم' ?>
                                                <?php if ($website['reviews_count'] > 0): ?>
                                                    (<?= $website['reviews_count'] ?>)
                                                <?php endif; ?>
                                            </span>
                                            <span class="views">👁️ <?= format_number($website['views']) ?></span>
                                            <span class="date">📅 <?= time_ago($website['created_at']) ?></span>
                                        </div>
                                        
                                        <div class="website-actions">
                                            <a href="/directory/<?= htmlspecialchars($website['slug']) ?>" 
                                               class="btn btn-primary">عرض التفاصيل</a>
                                            <a href="<?= htmlspecialchars($website['url']) ?>" 
                                               target="_blank" rel="noopener" class="btn btn-outline">زيارة الموقع</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($websites['pages'] > 1): ?>
                            <div class="pagination-wrapper">
                                <?php
                                $current_page = $websites['current_page'];
                                $total_pages = $websites['pages'];
                                $query_params = $_GET;
                                ?>
                                
                                <nav class="pagination">
                                    <?php if ($current_page > 1): ?>
                                        <?php
                                        $query_params['page'] = $current_page - 1;
                                        $prev_url = '/directory?' . http_build_query($query_params);
                                        ?>
                                        <a href="<?= $prev_url ?>" class="pagination-link">« السابق</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                        <?php
                                        $query_params['page'] = $i;
                                        $page_url = '/directory?' . http_build_query($query_params);
                                        ?>
                                        <a href="<?= $page_url ?>" 
                                           class="pagination-link <?= $i === $current_page ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <?php
                                        $query_params['page'] = $current_page + 1;
                                        $next_url = '/directory?' . http_build_query($query_params);
                                        ?>
                                        <a href="<?= $next_url ?>" class="pagination-link">التالي »</a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">🔍</div>
                            <h3>لا توجد مواقع</h3>
                            <p>لم يتم العثور على مواقع تطابق معايير البحث الخاصة بك.</p>
                            <a href="/directory" class="btn btn-primary">عرض جميع المواقع</a>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </section>
</div>

<!-- Internal Ads Display -->
<?php include '../templates/frontend/partials/ads_display.php'; ?>

<style>
.directory-page {
    min-height: 100vh;
}

.directory-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
}

.hero-title {
    font-size: 3rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.search-section {
    max-width: 800px;
    margin: 0 auto 3rem;
}

.search-input-group {
    display: flex;
    gap: 10px;
    background: white;
    padding: 10px;
    border-radius: 50px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.search-input {
    flex: 1;
    border: none;
    padding: 15px 20px;
    border-radius: 25px;
    font-size: 1rem;
}

.search-select {
    border: none;
    padding: 15px 20px;
    border-radius: 25px;
    background: #f8f9fa;
    min-width: 150px;
}

.search-btn {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 2rem;
    max-width: 600px;
    margin: 0 auto;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.featured-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: #2c3e50;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.featured-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
}

.featured-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.featured-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    font-size: 3rem;
}

.featured-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.featured-content {
    padding: 1.5rem;
}

.featured-title a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.2rem;
    font-weight: 600;
}

.featured-description {
    color: #666;
    margin: 1rem 0;
    line-height: 1.6;
}

.featured-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    color: #888;
}

.main-content {
    padding: 60px 0;
}

.content-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 3rem;
}

.sidebar {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    height: fit-content;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.sidebar-widget {
    margin-bottom: 2rem;
}

.widget-title {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #2c3e50;
    border-bottom: 2px solid #667eea;
    padding-bottom: 0.5rem;
}

.categories-list {
    list-style: none;
    padding: 0;
}

.categories-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.categories-list li.active a {
    color: #667eea;
    font-weight: 600;
}

.categories-list a {
    color: #333;
    text-decoration: none;
}

.count {
    background: #f8f9fa;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.8rem;
}

.add-website-btn {
    display: block;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    text-decoration: none;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
}

.add-website-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.top-rated-list {
    list-style: none;
    padding: 0;
}

.top-rated-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.top-rated-list a {
    color: #333;
    text-decoration: none;
    font-size: 0.9rem;
}

.rating {
    font-size: 0.8rem;
    color: #ff6b6b;
    margin-top: 0.2rem;
}

.filters-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.results-count {
    font-weight: 600;
    color: #2c3e50;
}

.sort-select {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
}

.websites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.website-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.website-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.website-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.website-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.free-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #27ae60;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
}

.website-content {
    padding: 1.5rem;
}

.website-title a {
    color: #2c3e50;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
}

.website-description {
    color: #666;
    margin: 1rem 0;
    line-height: 1.6;
}

.website-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.website-meta a {
    color: #667eea;
    text-decoration: none;
}

.website-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.8rem;
    color: #888;
}

.website-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-outline {
    border: 1px solid #667eea;
    color: #667eea;
    background: transparent;
}

.btn:hover {
    transform: translateY(-1px);
}

.no-results {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.pagination-wrapper {
    margin-top: 3rem;
    text-align: center;
}

.pagination {
    display: inline-flex;
    gap: 0.5rem;
}

.pagination-link {
    padding: 0.5rem 1rem;
    background: white;
    color: #667eea;
    text-decoration: none;
    border-radius: 5px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.pagination-link:hover,
.pagination-link.active {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .content-layout {
        grid-template-columns: 1fr;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .featured-grid {
        grid-template-columns: 1fr;
    }
    
    .websites-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-bar {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
function updateSort(value) {
    const url = new URL(window.location);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}

// Initialize AOS
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true
        });
    }
});
</script>

<?php include '../templates/frontend/partials/footer.php'; ?>

