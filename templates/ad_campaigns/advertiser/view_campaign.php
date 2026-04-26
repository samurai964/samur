<?php
/**
 * صفحة عرض تفاصيل الحملة الإعلانية
 * واجهة متطورة لعرض إحصائيات وتفاصيل الحملة
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';

// التحقق من وجود معرف الحملة
$campaign_id = $_GET['id'] ?? 0;
if (!$campaign_id) {
    redirect('/ad_campaigns/dashboard');
}

// جلب بيانات الحملة (يجب استبدالها ببيانات حقيقية)
$campaign = [
    'id' => $campaign_id,
    'name' => 'حملة إعلانية تجريبية',
    'status' => 'active',
    'budget' => 1000.00,
    'budget_type' => 'total',
    'current_spend' => 350.75,
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'total_clicks' => 1250,
    'total_impressions' => 45000,
    'cpc_rate' => 0.50,
    'cpm_rate' => 2.00,
    'target_countries' => 'SA,AE,EG',
    'target_languages' => 'ar,en',
    'target_keywords' => 'تقنية، برمجة، تطوير',
    'created_at' => '2024-01-01 10:00:00'
];

// حساب الإحصائيات
$ctr = $campaign['total_impressions'] > 0 ? ($campaign['total_clicks'] / $campaign['total_impressions']) * 100 : 0;
$avg_cpc = $campaign['total_clicks'] > 0 ? $campaign['current_spend'] / $campaign['total_clicks'] : 0;
$budget_used_percent = $campaign['budget'] > 0 ? ($campaign['current_spend'] / $campaign['budget']) * 100 : 0;
?>

<div class="campaign-details-page">
    <!-- Header Section -->
    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/ad_campaigns/dashboard">لوحة التحكم</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>تفاصيل الحملة</span>
                </div>
                
                <div class="campaign-header-info">
                    <div class="campaign-title-section">
                        <h1 class="campaign-title">
                            <i class="fas fa-bullhorn"></i>
                            <?= htmlspecialchars($campaign['name']) ?>
                        </h1>
                        <span class="campaign-status status-<?= $campaign['status'] ?>">
                            <?php
                            $status_labels = [
                                'active' => 'نشطة',
                                'paused' => 'متوقفة',
                                'pending' => 'قيد المراجعة',
                                'rejected' => 'مرفوضة',
                                'completed' => 'مكتملة'
                            ];
                            echo $status_labels[$campaign['status']] ?? $campaign['status'];
                            ?>
                        </span>
                    </div>
                    
                    <div class="campaign-actions">
                        <a href="/ad_campaigns/edit_campaign/<?= $campaign['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i>
                            تعديل الحملة
                        </a>
                        <?php if ($campaign['status'] === 'active'): ?>
                            <a href="/ad_campaigns/pause_campaign/<?= $campaign['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-pause"></i>
                                إيقاف الحملة
                            </a>
                        <?php elseif ($campaign['status'] === 'paused'): ?>
                            <a href="/ad_campaigns/activate_campaign/<?= $campaign['id'] ?>" class="btn btn-success">
                                <i class="fas fa-play"></i>
                                تشغيل الحملة
                            </a>
                        <?php endif; ?>
                        <a href="/ad_campaigns/create_ad?campaign_id=<?= $campaign['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-plus"></i>
                            إضافة إعلان
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Performance Overview -->
            <div class="performance-overview">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i>
                    نظرة عامة على الأداء
                </h2>
                
                <div class="stats-grid">
                    <div class="stat-card impressions">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($campaign['total_impressions']) ?></div>
                            <div class="stat-label">إجمالي المشاهدات</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                +15.2% هذا الأسبوع
                            </div>
                        </div>
                    </div>

                    <div class="stat-card clicks">
                        <div class="stat-icon">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($campaign['total_clicks']) ?></div>
                            <div class="stat-label">إجمالي النقرات</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                +8.7% هذا الأسبوع
                            </div>
                        </div>
                    </div>

                    <div class="stat-card ctr">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($ctr, 2) ?>%</div>
                            <div class="stat-label">معدل النقر (CTR)</div>
                            <div class="stat-trend neutral">
                                <i class="fas fa-minus"></i>
                                0.0% هذا الأسبوع
                            </div>
                        </div>
                    </div>

                    <div class="stat-card spend">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">$<?= number_format($campaign['current_spend'], 2) ?></div>
                            <div class="stat-label">إجمالي الإنفاق</div>
                            <div class="stat-trend negative">
                                <i class="fas fa-arrow-down"></i>
                                -3.1% هذا الأسبوع
                            </div>
                        </div>
                    </div>

                    <div class="stat-card cpc">
                        <div class="stat-icon">
                            <i class="fas fa-hand-pointer"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number">$<?= number_format($avg_cpc, 2) ?></div>
                            <div class="stat-label">متوسط تكلفة النقرة</div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-down"></i>
                                -5.3% هذا الأسبوع
                            </div>
                        </div>
                    </div>

                    <div class="stat-card budget">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= number_format($budget_used_percent, 1) ?>%</div>
                            <div class="stat-label">الميزانية المستخدمة</div>
                            <div class="stat-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $budget_used_percent ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Campaign Details -->
                <div class="campaign-details-section">
                    <div class="details-card">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            تفاصيل الحملة
                        </h3>
                        
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">معرف الحملة:</span>
                                <span class="detail-value">#<?= $campaign['id'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ الإنشاء:</span>
                                <span class="detail-value"><?= date('Y-m-d', strtotime($campaign['created_at'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ البداية:</span>
                                <span class="detail-value"><?= $campaign['start_date'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ النهاية:</span>
                                <span class="detail-value"><?= $campaign['end_date'] ?: 'غير محدد' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">نوع الميزانية:</span>
                                <span class="detail-value"><?= $campaign['budget_type'] === 'daily' ? 'يومية' : 'إجمالية' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">الميزانية الإجمالية:</span>
                                <span class="detail-value">$<?= number_format($campaign['budget'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Targeting Details -->
                    <div class="targeting-card">
                        <h3 class="card-title">
                            <i class="fas fa-target"></i>
                            تفاصيل الاستهداف
                        </h3>
                        
                        <div class="targeting-info">
                            <div class="targeting-item">
                                <h4>البلدان المستهدفة</h4>
                                <div class="targeting-tags">
                                    <?php
                                    $countries = explode(',', $campaign['target_countries']);
                                    $country_names = [
                                        'SA' => 'السعودية',
                                        'AE' => 'الإمارات',
                                        'EG' => 'مصر'
                                    ];
                                    foreach ($countries as $country):
                                    ?>
                                        <span class="tag"><?= $country_names[$country] ?? $country ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="targeting-item">
                                <h4>اللغات المستهدفة</h4>
                                <div class="targeting-tags">
                                    <?php
                                    $languages = explode(',', $campaign['target_languages']);
                                    $language_names = [
                                        'ar' => 'العربية',
                                        'en' => 'الإنجليزية'
                                    ];
                                    foreach ($languages as $language):
                                    ?>
                                        <span class="tag"><?= $language_names[$language] ?? $language ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="targeting-item">
                                <h4>الكلمات المفتاحية</h4>
                                <div class="targeting-tags">
                                    <?php
                                    $keywords = explode('،', $campaign['target_keywords']);
                                    foreach ($keywords as $keyword):
                                    ?>
                                        <span class="tag"><?= trim($keyword) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Chart -->
                    <div class="chart-card">
                        <h3 class="card-title">
                            <i class="fas fa-chart-area"></i>
                            أداء الحملة خلال الوقت
                        </h3>
                        <div class="chart-container">
                            <canvas id="performanceChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Quick Stats -->
                    <div class="widget quick-stats">
                        <h3 class="widget-title">
                            <i class="fas fa-tachometer-alt"></i>
                            إحصائيات سريعة
                        </h3>
                        <div class="quick-stats-list">
                            <div class="quick-stat">
                                <span class="stat-label">أفضل يوم:</span>
                                <span class="stat-value">الأحد</span>
                            </div>
                            <div class="quick-stat">
                                <span class="stat-label">أفضل وقت:</span>
                                <span class="stat-value">8:00 PM</span>
                            </div>
                            <div class="quick-stat">
                                <span class="stat-label">أفضل جهاز:</span>
                                <span class="stat-value">الهاتف المحمول</span>
                            </div>
                            <div class="quick-stat">
                                <span class="stat-label">أفضل موقع:</span>
                                <span class="stat-value">الصفحة الرئيسية</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Ads -->
                    <div class="widget campaign-ads">
                        <h3 class="widget-title">
                            <i class="fas fa-images"></i>
                            إعلانات الحملة
                        </h3>
                        <div class="ads-list">
                            <!-- سيتم ملؤها بالإعلانات الفعلية -->
                            <div class="ad-item">
                                <div class="ad-preview">
                                    <img src="/assets/images/ad-placeholder.jpg" alt="إعلان تجريبي">
                                </div>
                                <div class="ad-info">
                                    <h4>إعلان نصي #1</h4>
                                    <p>نشط - 250 نقرة</p>
                                </div>
                                <div class="ad-actions">
                                    <a href="#" class="btn btn-sm btn-outline">عرض</a>
                                </div>
                            </div>
                            
                            <div class="ad-item">
                                <div class="ad-preview">
                                    <img src="/assets/images/ad-placeholder.jpg" alt="إعلان تجريبي">
                                </div>
                                <div class="ad-info">
                                    <h4>إعلان مصور #2</h4>
                                    <p>نشط - 180 نقرة</p>
                                </div>
                                <div class="ad-actions">
                                    <a href="#" class="btn btn-sm btn-outline">عرض</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="widget-footer">
                            <a href="/ad_campaigns/create_ad?campaign_id=<?= $campaign['id'] ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i>
                                إضافة إعلان جديد
                            </a>
                        </div>
                    </div>

                    <!-- Optimization Tips -->
                    <div class="widget optimization-tips">
                        <h3 class="widget-title">
                            <i class="fas fa-lightbulb"></i>
                            نصائح للتحسين
                        </h3>
                        <div class="tips-list">
                            <div class="tip-item">
                                <div class="tip-icon success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="tip-content">
                                    <h4>أداء جيد</h4>
                                    <p>معدل النقر أعلى من المتوسط</p>
                                </div>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon warning">
                                    <i class="fas fa-exclamation"></i>
                                </div>
                                <div class="tip-content">
                                    <h4>يمكن التحسين</h4>
                                    <p>جرب كلمات مفتاحية جديدة</p>
                                </div>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon info">
                                    <i class="fas fa-info"></i>
                                </div>
                                <div class="tip-content">
                                    <h4>اقتراح</h4>
                                    <p>زد الميزانية لزيادة الوصول</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Campaign Details Page Styles */
.campaign-details-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.page-header {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
    padding: 2rem 0;
    color: white;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.campaign-header-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.campaign-title-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.campaign-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.campaign-status {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-active {
    background: #10b981;
    color: white;
}

.status-paused {
    background: #f59e0b;
    color: white;
}

.status-pending {
    background: #3b82f6;
    color: white;
}

.status-rejected {
    background: #ef4444;
    color: white;
}

.status-completed {
    background: #8b5cf6;
    color: white;
}

.campaign-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.main-content {
    background: #f8fafc;
    border-radius: 30px 30px 0 0;
    padding: 3rem 0;
    margin-top: -1rem;
}

.performance-overview {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
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

.impressions .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.clicks .stat-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.ctr .stat-icon {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.spend .stat-icon {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.cpc .stat-icon {
    background: linear-gradient(135deg, #fa709a, #fee140);
}

.budget .stat-icon {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.stat-trend.positive {
    color: #10b981;
}

.stat-trend.negative {
    color: #ef4444;
}

.stat-trend.neutral {
    color: #6b7280;
}

.stat-progress {
    margin-top: 0.5rem;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 3rem;
}

.campaign-details-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.details-card,
.targeting-card,
.chart-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.card-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    color: #1f2937;
    font-weight: 600;
}

.targeting-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.targeting-item h4 {
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.targeting-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    background: #f3f4f6;
    color: #374151;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.chart-container {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border-radius: 10px;
    color: #6b7280;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.widget {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.widget-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-stats-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.quick-stat .stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.quick-stat .stat-value {
    color: #1f2937;
    font-weight: 600;
    font-size: 0.9rem;
}

.ads-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1rem;
}

.ad-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
}

.ad-preview {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    background: #e5e7eb;
}

.ad-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ad-info {
    flex: 1;
}

.ad-info h4 {
    font-size: 0.9rem;
    color: #374151;
    margin-bottom: 0.25rem;
}

.ad-info p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

.widget-footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.tips-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.tip-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.tip-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: white;
}

.tip-icon.success {
    background: #10b981;
}

.tip-icon.warning {
    background: #f59e0b;
}

.tip-icon.info {
    background: #3b82f6;
}

.tip-content h4 {
    font-size: 0.9rem;
    color: #374151;
    margin-bottom: 0.25rem;
}

.tip-content p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .campaign-header-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .campaign-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .campaign-title {
        font-size: 1.8rem;
    }
    
    .campaign-title-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<script>
// Performance Chart (placeholder)
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('performanceChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        
        // رسم مخطط بسيط (يمكن استبداله بمكتبة مخططات حقيقية)
        ctx.fillStyle = '#f3f4f6';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#6b7280';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('مخطط الأداء سيتم تحميله هنا', canvas.width / 2, canvas.height / 2);
    }
});
</script>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../frontend/partials/footer.php';
?>

