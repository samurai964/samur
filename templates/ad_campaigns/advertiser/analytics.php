<?php
/**
 * صفحة التحليلات للمعلنين
 * تعرض إحصائيات مفصلة وتفاعلية للحملات الإعلانية
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';

// التحقق من تسجيل الدخول
if (!Auth::isLoggedIn()) {
    redirect('/auth/login');
}

require_once __DIR__ . '/../../../modules/ad_campaigns/AnalyticsEngine.php';

// إنشاء محرك التحليلات
$analytics_engine = new AnalyticsEngine($database->getConnection());

// الحصول على معرف المعلن
$advertiser_id = $_SESSION['user_id'];

// الحصول على الفترة المحددة
$period = $_GET['period'] ?? 'month';
$campaign_id = $_GET['campaign_id'] ?? null;

// جلب التحليلات
if ($campaign_id) {
    $analytics = $analytics_engine->getCampaignAnalytics($campaign_id, $period);
    $page_title = "تحليلات الحملة";
} else {
    $analytics = $analytics_engine->getAdvertiserAnalytics($advertiser_id, $period);
    $page_title = "تحليلات المعلن";
}

// جلب قائمة الحملات للفلترة
$campaigns_sql = "SELECT id, name FROM fmc_ad_campaigns WHERE advertiser_id = ? ORDER BY created_at DESC";
$campaigns_stmt = $database->getConnection()->prepare($campaigns_sql);
$campaigns_stmt->execute([$advertiser_id]);
$campaigns = $campaigns_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="analytics-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <h1 class="page-title">
                    <i class="fas fa-chart-line"></i>
                    <?= $page_title ?>
                </h1>
                <p class="page-subtitle">
                    <?= $campaign_id ? 'تحليلات مفصلة للحملة الإعلانية' : 'نظرة شاملة على أداء حملاتك الإعلانية' ?>
                </p>
            </div>
            
            <div class="header-controls">
                <!-- Period Filter -->
                <div class="filter-group">
                    <label for="period-filter">الفترة الزمنية:</label>
                    <select id="period-filter" class="form-select">
                        <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>اليوم</option>
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>الأسبوع الماضي</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>الشهر الماضي</option>
                        <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>الربع الماضي</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>السنة الماضية</option>
                        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>جميع الفترات</option>
                    </select>
                </div>
                
                <!-- Campaign Filter -->
                <?php if (!$campaign_id): ?>
                <div class="filter-group">
                    <label for="campaign-filter">الحملة:</label>
                    <select id="campaign-filter" class="form-select">
                        <option value="">جميع الحملات</option>
                        <?php foreach ($campaigns as $campaign): ?>
                            <option value="<?= $campaign['id'] ?>"><?= htmlspecialchars($campaign['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Export Button -->
                <button class="btn btn-outline-primary" onclick="exportAnalytics()">
                    <i class="fas fa-download"></i>
                    تصدير التقرير
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Content -->
    <div class="analytics-content">
        <?php if ($campaign_id && !empty($analytics)): ?>
            <!-- Campaign Analytics -->
            <?php include 'analytics_campaign.php'; ?>
        <?php elseif (!$campaign_id && !empty($analytics)): ?>
            <!-- Advertiser Analytics -->
            <?php include 'analytics_advertiser.php'; ?>
        <?php else: ?>
            <!-- No Data -->
            <div class="no-data-message">
                <div class="no-data-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>لا توجد بيانات متاحة</h3>
                <p>لم يتم العثور على بيانات تحليلية للفترة المحددة.</p>
                <a href="/ad_campaigns/advertiser/create_campaign" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    إنشاء حملة جديدة
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Campaign Analytics Template -->
<?php if ($campaign_id && !empty($analytics)): ?>
<div id="campaign-analytics-template" style="display: none;">
    <!-- Overview Cards -->
    <div class="overview-cards">
        <div class="stats-grid">
            <div class="stat-card impressions">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($analytics['basic_stats']['total_impressions'] ?? 0) ?></h3>
                    <p class="stat-label">إجمالي المشاهدات</p>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12.5%</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card clicks">
                <div class="stat-icon">
                    <i class="fas fa-mouse-pointer"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($analytics['basic_stats']['total_clicks'] ?? 0) ?></h3>
                    <p class="stat-label">إجمالي النقرات</p>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8.3%</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card ctr">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($analytics['basic_stats']['ctr'] ?? 0, 2) ?>%</h3>
                    <p class="stat-label">معدل النقر (CTR)</p>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>-2.1%</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card spend">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value">$<?= number_format($analytics['basic_stats']['current_spend'] ?? 0, 2) ?></h3>
                    <p class="stat-label">إجمالي الإنفاق</p>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+15.7%</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card cpc">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value">$<?= number_format($analytics['basic_stats']['avg_cpc'] ?? 0, 2) ?></h3>
                    <p class="stat-label">متوسط تكلفة النقرة</p>
                    <div class="stat-change neutral">
                        <i class="fas fa-minus"></i>
                        <span>0.0%</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card budget">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($analytics['basic_stats']['budget_used_percent'] ?? 0, 1) ?>%</h3>
                    <p class="stat-label">استخدام الميزانية</p>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: <?= $analytics['basic_stats']['budget_used_percent'] ?? 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="charts-grid">
            <!-- Performance Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-area"></i>
                        الأداء عبر الوقت
                    </h3>
                    <div class="chart-controls">
                        <button class="chart-toggle active" data-metric="impressions">المشاهدات</button>
                        <button class="chart-toggle" data-metric="clicks">النقرات</button>
                        <button class="chart-toggle" data-metric="ctr">معدل النقر</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="performanceChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Device Stats -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-devices"></i>
                        إحصائيات الأجهزة
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="deviceChart" width="300" height="300"></canvas>
                </div>
                <div class="device-stats-list">
                    <?php foreach ($analytics['device_stats'] as $device): ?>
                        <div class="device-stat-item">
                            <span class="device-name"><?= $device['device_type'] ?></span>
                            <span class="device-impressions"><?= number_format($device['impressions']) ?> مشاهدة</span>
                            <span class="device-ctr"><?= number_format($device['ctr'], 2) ?>% CTR</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Targeting Performance -->
        <div class="targeting-performance">
            <div class="performance-grid">
                <!-- Geographic Performance -->
                <div class="performance-card">
                    <div class="performance-header">
                        <h3 class="performance-title">
                            <i class="fas fa-globe"></i>
                            الأداء الجغرافي
                        </h3>
                    </div>
                    <div class="performance-list">
                        <?php foreach (array_slice($analytics['targeting_stats']['country_stats'], 0, 5) as $country): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <span class="performance-name"><?= $country['country'] ?></span>
                                    <span class="performance-metrics">
                                        <?= number_format($country['impressions']) ?> مشاهدة • 
                                        <?= number_format($country['clicks']) ?> نقرة
                                    </span>
                                </div>
                                <div class="performance-ctr">
                                    <span class="ctr-value"><?= number_format($country['ctr'], 2) ?>%</span>
                                    <div class="ctr-bar">
                                        <div class="ctr-fill" style="width: <?= min($country['ctr'] * 10, 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Age Group Performance -->
                <div class="performance-card">
                    <div class="performance-header">
                        <h3 class="performance-title">
                            <i class="fas fa-users"></i>
                            الأداء حسب العمر
                        </h3>
                    </div>
                    <div class="performance-list">
                        <?php foreach ($analytics['targeting_stats']['age_stats'] as $age): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <span class="performance-name"><?= $age['age_group'] ?></span>
                                    <span class="performance-metrics">
                                        <?= number_format($age['impressions']) ?> مشاهدة • 
                                        <?= number_format($age['clicks']) ?> نقرة
                                    </span>
                                </div>
                                <div class="performance-percentage">
                                    <?php 
                                    $total_impressions = array_sum(array_column($analytics['targeting_stats']['age_stats'], 'impressions'));
                                    $percentage = $total_impressions > 0 ? ($age['impressions'] / $total_impressions) * 100 : 0;
                                    ?>
                                    <span class="percentage-value"><?= number_format($percentage, 1) ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Placement Performance -->
                <div class="performance-card">
                    <div class="performance-header">
                        <h3 class="performance-title">
                            <i class="fas fa-map-marker-alt"></i>
                            أداء مواقع العرض
                        </h3>
                    </div>
                    <div class="performance-list">
                        <?php foreach ($analytics['placement_stats'] as $placement): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <span class="performance-name"><?= $placement['placement'] ?></span>
                                    <span class="performance-metrics">
                                        <?= number_format($placement['impressions']) ?> مشاهدة • 
                                        <?= number_format($placement['clicks']) ?> نقرة
                                    </span>
                                </div>
                                <div class="performance-ctr">
                                    <span class="ctr-value"><?= number_format($placement['ctr'], 2) ?>%</span>
                                    <div class="ctr-bar">
                                        <div class="ctr-fill" style="width: <?= min($placement['ctr'] * 10, 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Time Analysis -->
        <div class="time-analysis">
            <div class="time-grid">
                <!-- Hourly Performance -->
                <div class="time-card">
                    <div class="time-header">
                        <h3 class="time-title">
                            <i class="fas fa-clock"></i>
                            الأداء الساعي
                        </h3>
                    </div>
                    <div class="time-chart">
                        <canvas id="hourlyChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Daily Performance -->
                <div class="time-card">
                    <div class="time-header">
                        <h3 class="time-title">
                            <i class="fas fa-calendar-week"></i>
                            الأداء اليومي
                        </h3>
                    </div>
                    <div class="time-chart">
                        <canvas id="dailyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Advertiser Analytics Template -->
<?php if (!$campaign_id && !empty($analytics)): ?>
<div id="advertiser-analytics-template" style="display: none;">
    <!-- Overview Section -->
    <div class="advertiser-overview">
        <div class="overview-grid">
            <div class="overview-card campaigns">
                <div class="overview-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="overview-content">
                    <h3 class="overview-value"><?= $analytics['overview']['total_campaigns'] ?? 0 ?></h3>
                    <p class="overview-label">إجمالي الحملات</p>
                    <div class="overview-breakdown">
                        <span class="breakdown-item active">
                            <?= $analytics['overview']['active_campaigns'] ?? 0 ?> نشطة
                        </span>
                        <span class="breakdown-item paused">
                            <?= $analytics['overview']['paused_campaigns'] ?? 0 ?> متوقفة
                        </span>
                        <span class="breakdown-item completed">
                            <?= $analytics['overview']['completed_campaigns'] ?? 0 ?> مكتملة
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card performance">
                <div class="overview-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="overview-content">
                    <h3 class="overview-value"><?= number_format($analytics['overview']['total_impressions'] ?? 0) ?></h3>
                    <p class="overview-label">إجمالي المشاهدات</p>
                    <div class="overview-secondary">
                        <span><?= number_format($analytics['overview']['total_clicks'] ?? 0) ?> نقرة</span>
                        <span><?= number_format($analytics['overview']['avg_ctr'] ?? 0, 2) ?>% CTR</span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card budget">
                <div class="overview-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="overview-content">
                    <h3 class="overview-value">$<?= number_format($analytics['overview']['total_budget'] ?? 0, 2) ?></h3>
                    <p class="overview-label">إجمالي الميزانية</p>
                    <div class="overview-secondary">
                        <span>$<?= number_format($analytics['overview']['total_spend'] ?? 0, 2) ?> مُنفق</span>
                        <span>$<?= number_format($analytics['overview']['avg_cpc'] ?? 0, 2) ?> متوسط CPC</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Campaigns -->
    <div class="top-campaigns">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fas fa-trophy"></i>
                أفضل الحملات أداءً
            </h3>
            <a href="/ad_campaigns/advertiser/campaigns" class="section-link">
                عرض جميع الحملات
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
        
        <div class="campaigns-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>اسم الحملة</th>
                        <th>الحالة</th>
                        <th>المشاهدات</th>
                        <th>النقرات</th>
                        <th>معدل النقر</th>
                        <th>الإنفاق</th>
                        <th>متوسط CPC</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($analytics['top_campaigns'], 0, 5) as $campaign): ?>
                        <tr>
                            <td>
                                <div class="campaign-name">
                                    <a href="/ad_campaigns/advertiser/analytics?campaign_id=<?= $campaign['id'] ?>">
                                        <?= htmlspecialchars($campaign['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $campaign['status'] ?>">
                                    <?= ucfirst($campaign['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($campaign['total_impressions']) ?></td>
                            <td><?= number_format($campaign['total_clicks']) ?></td>
                            <td><?= number_format($campaign['ctr'], 2) ?>%</td>
                            <td>$<?= number_format($campaign['current_spend'], 2) ?></td>
                            <td>$<?= number_format($campaign['avg_cpc'], 2) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/ad_campaigns/advertiser/view_campaign?id=<?= $campaign['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/ad_campaigns/advertiser/analytics?campaign_id=<?= $campaign['id'] ?>" 
                                       class="btn btn-sm btn-outline-success" title="تحليلات">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Performance Charts -->
    <div class="performance-charts">
        <div class="charts-grid">
            <!-- Daily Spend Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        الإنفاق اليومي
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="dailySpendChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Monthly Performance -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-area"></i>
                        الأداء الشهري
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyPerformanceChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Analytics Page Styles */
.analytics-page {
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
}

.page-header {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title i {
    color: #667eea;
}

.page-subtitle {
    color: #6b7280;
    margin: 0;
}

.header-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

.form-select {
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    font-size: 0.9rem;
    min-width: 150px;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
}

/* Overview Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-card.impressions .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-card.clicks .stat-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.stat-card.ctr .stat-icon {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.stat-card.spend .stat-icon {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.stat-card.cpc .stat-icon {
    background: linear-gradient(135deg, #fa709a, #fee140);
}

.stat-card.budget .stat-icon {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6b7280;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

.stat-change.neutral {
    color: #6b7280;
}

.stat-progress {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

/* Charts Section */
.charts-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.chart-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.chart-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-controls {
    display: flex;
    gap: 0.5rem;
}

.chart-toggle {
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 20px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chart-toggle.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.chart-container {
    position: relative;
    height: 300px;
}

.device-stats-list {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.device-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
}

.device-name {
    font-weight: 600;
    color: #374151;
}

.device-impressions {
    color: #6b7280;
    font-size: 0.9rem;
}

.device-ctr {
    color: #10b981;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Performance Cards */
.targeting-performance {
    margin-top: 2rem;
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.performance-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.performance-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.performance-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.performance-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
}

.performance-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.performance-name {
    font-weight: 600;
    color: #374151;
}

.performance-metrics {
    color: #6b7280;
    font-size: 0.8rem;
}

.performance-ctr {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.ctr-value {
    font-weight: 600;
    color: #10b981;
}

.ctr-bar {
    width: 60px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.ctr-fill {
    height: 100%;
    background: #10b981;
    transition: width 0.3s ease;
}

.performance-percentage {
    display: flex;
    align-items: center;
}

.percentage-value {
    font-weight: 600;
    color: #667eea;
}

/* Time Analysis */
.time-analysis {
    margin-top: 2rem;
}

.time-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.time-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.time-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.time-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.time-chart {
    height: 250px;
}

/* Advertiser Overview */
.advertiser-overview {
    margin-bottom: 2rem;
}

.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.overview-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.overview-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.overview-card.campaigns .overview-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.overview-card.performance .overview-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.overview-card.budget .overview-icon {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.overview-content {
    flex: 1;
}

.overview-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.overview-label {
    color: #6b7280;
    margin-bottom: 1rem;
    font-size: 1rem;
}

.overview-breakdown {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.breakdown-item {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.breakdown-item.active {
    background: #dcfce7;
    color: #166534;
}

.breakdown-item.paused {
    background: #fef3c7;
    color: #92400e;
}

.breakdown-item.completed {
    background: #e0e7ff;
    color: #3730a3;
}

.overview-secondary {
    display: flex;
    gap: 1rem;
    color: #6b7280;
    font-size: 0.9rem;
}

/* Top Campaigns */
.top-campaigns {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-link:hover {
    text-decoration: underline;
}

.campaigns-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: right;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.campaign-name a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.campaign-name a:hover {
    text-decoration: underline;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-paused {
    background: #fef3c7;
    color: #92400e;
}

.status-completed {
    background: #e0e7ff;
    color: #3730a3;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

/* No Data Message */
.no-data-message {
    background: white;
    border-radius: 15px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.no-data-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.no-data-message h3 {
    color: #374151;
    margin-bottom: 1rem;
}

.no-data-message p {
    color: #6b7280;
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .time-grid {
        grid-template-columns: 1fr;
    }
    
    .performance-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .analytics-page {
        padding: 1rem;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-controls {
        width: 100%;
        justify-content: space-between;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .overview-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-card {
        padding: 1.5rem;
    }
    
    .table {
        font-size: 0.8rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Analytics JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initializeCharts();
    
    // Handle filter changes
    document.getElementById('period-filter').addEventListener('change', function() {
        updateAnalytics();
    });
    
    const campaignFilter = document.getElementById('campaign-filter');
    if (campaignFilter) {
        campaignFilter.addEventListener('change', function() {
            updateAnalytics();
        });
    }
    
    // Chart toggle functionality
    const chartToggles = document.querySelectorAll('.chart-toggle');
    chartToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            chartToggles.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            updatePerformanceChart(this.dataset.metric);
        });
    });
});

function initializeCharts() {
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart');
    if (performanceCtx) {
        const performanceChart = new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($analytics['performance_stats']['daily_performance'] ?? [], 'date')) ?>,
                datasets: [{
                    label: 'المشاهدات',
                    data: <?= json_encode(array_column($analytics['performance_stats']['daily_performance'] ?? [], 'impressions')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            color: '#f3f4f6'
                        }
                    }
                }
            }
        });
    }
    
    // Device Chart
    const deviceCtx = document.getElementById('deviceChart');
    if (deviceCtx) {
        const deviceData = <?= json_encode($analytics['device_stats'] ?? []) ?>;
        const deviceChart = new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(d => d.device_type),
                datasets: [{
                    data: deviceData.map(d => d.impressions),
                    backgroundColor: [
                        '#667eea',
                        '#f093fb',
                        '#4facfe'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
    
    // Hourly Chart
    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        const hourlyChart = new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['time_stats']['hour_stats'] ?? [], 'hour')) ?>,
                datasets: [{
                    label: 'المشاهدات',
                    data: <?= json_encode(array_column($analytics['time_stats']['hour_stats'] ?? [], 'impressions')) ?>,
                    backgroundColor: '#667eea',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Daily Chart
    const dailyCtx = document.getElementById('dailyChart');
    if (dailyCtx) {
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($analytics['time_stats']['day_stats'] ?? [], 'day_name')) ?>,
                datasets: [{
                    label: 'المشاهدات',
                    data: <?= json_encode(array_column($analytics['time_stats']['day_stats'] ?? [], 'impressions')) ?>,
                    borderColor: '#f093fb',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            color: '#f3f4f6'
                        }
                    }
                }
            }
        });
    }
    
    // Daily Spend Chart (for advertiser analytics)
    const dailySpendCtx = document.getElementById('dailySpendChart');
    if (dailySpendCtx) {
        const dailySpendChart = new Chart(dailySpendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['daily_spend'] ?? [], 'date')) ?>,
                datasets: [{
                    label: 'الإنفاق اليومي',
                    data: <?= json_encode(array_column($analytics['daily_spend'] ?? [], 'daily_spend')) ?>,
                    backgroundColor: '#43e97b',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Monthly Performance Chart
    const monthlyCtx = document.getElementById('monthlyPerformanceChart');
    if (monthlyCtx) {
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($analytics['monthly_performance'] ?? [], 'month')) ?>,
                datasets: [
                    {
                        label: 'المشاهدات',
                        data: <?= json_encode(array_column($analytics['monthly_performance'] ?? [], 'impressions')) ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'الإنفاق',
                        data: <?= json_encode(array_column($analytics['monthly_performance'] ?? [], 'spend')) ?>,
                        borderColor: '#43e97b',
                        backgroundColor: 'rgba(67, 233, 123, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: '#f3f4f6'
                        }
                    }
                }
            }
        });
    }
}

function updateAnalytics() {
    const period = document.getElementById('period-filter').value;
    const campaignFilter = document.getElementById('campaign-filter');
    const campaignId = campaignFilter ? campaignFilter.value : '';
    
    let url = window.location.pathname + '?period=' + period;
    if (campaignId) {
        url += '&campaign_id=' + campaignId;
    }
    
    window.location.href = url;
}

function updatePerformanceChart(metric) {
    // This would update the performance chart based on the selected metric
    // Implementation depends on having the chart instance available
    console.log('Updating chart for metric:', metric);
}

function exportAnalytics() {
    const period = document.getElementById('period-filter').value;
    const campaignFilter = document.getElementById('campaign-filter');
    const campaignId = campaignFilter ? campaignFilter.value : '';
    
    let url = '/ad_campaigns/export_analytics?period=' + period;
    if (campaignId) {
        url += '&campaign_id=' + campaignId;
    }
    
    window.open(url, '_blank');
}
</script>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../frontend/partials/footer.php';
?>

