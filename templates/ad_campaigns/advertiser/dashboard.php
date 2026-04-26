<?php
/**
 * لوحة تحكم المعلنين - نظام الحملات الإعلانية
 * واجهة متطورة تنافس جوجل أدسنس
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';
?>

<div class="advertiser-dashboard">
    <!-- Hero Section -->
    <div class="dashboard-hero">
        <div class="container">
            <div class="hero-content">
                <div class="welcome-section">
                    <h1 class="hero-title">
                        <i class="fas fa-chart-line"></i>
                        مرحباً بك في لوحة تحكم المعلن
                    </h1>
                    <p class="hero-subtitle">
                        أدر حملاتك الإعلانية بكفاءة وراقب أداءها في الوقت الفعلي
                    </p>
                    <div class="company-info">
                        <span class="company-name"><?= htmlspecialchars($advertiser['company_name']) ?></span>
                        <span class="account-status status-<?= $advertiser['status'] ?>">
                            <?= $advertiser['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                        </span>
                    </div>
                </div>
                
                <div class="balance-card">
                    <div class="balance-header">
                        <i class="fas fa-wallet"></i>
                        <span>رصيد الحساب</span>
                    </div>
                    <div class="balance-amount">
                        $<?= number_format($advertiser['balance'], 2) ?>
                    </div>
                    <div class="balance-actions">
                        <a href="/ad_campaigns/deposit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            إضافة رصيد
                        </a>
                        <a href="/ad_campaigns/transactions" class="btn btn-outline">
                            <i class="fas fa-history"></i>
                            المعاملات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card impressions">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_impressions'] ?? 0) ?></div>
                        <div class="stat-label">إجمالي المشاهدات</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            +12.5%
                        </div>
                    </div>
                </div>

                <div class="stat-card clicks">
                    <div class="stat-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= number_format($stats['total_clicks'] ?? 0) ?></div>
                        <div class="stat-label">إجمالي النقرات</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            +8.3%
                        </div>
                    </div>
                </div>

                <div class="stat-card ctr">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?= $stats['total_impressions'] > 0 ? number_format(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2) : 0 ?>%
                        </div>
                        <div class="stat-label">معدل النقر (CTR)</div>
                        <div class="stat-change neutral">
                            <i class="fas fa-minus"></i>
                            0.0%
                        </div>
                    </div>
                </div>

                <div class="stat-card spend">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">$<?= number_format($stats['total_spend'] ?? 0, 2) ?></div>
                        <div class="stat-label">إجمالي الإنفاق</div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            -5.2%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="content-grid">
                <!-- Campaigns Section -->
                <div class="campaigns-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-bullhorn"></i>
                            حملاتك الإعلانية
                        </h2>
                        <div class="section-actions">
                            <a href="/ad_campaigns/create_campaign" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                إنشاء حملة جديدة
                            </a>
                        </div>
                    </div>

                    <div class="campaigns-grid">
                        <?php if (!empty($campaigns)): ?>
                            <?php foreach ($campaigns as $campaign): ?>
                                <div class="campaign-card">
                                    <div class="campaign-header">
                                        <h3 class="campaign-name"><?= htmlspecialchars($campaign['name']) ?></h3>
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

                                    <div class="campaign-stats">
                                        <div class="stat-item">
                                            <span class="stat-label">الميزانية:</span>
                                            <span class="stat-value">$<?= number_format($campaign['budget'], 2) ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">الإنفاق:</span>
                                            <span class="stat-value">$<?= number_format($campaign['current_spend'], 2) ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">النقرات:</span>
                                            <span class="stat-value"><?= number_format($campaign['total_clicks']) ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">المشاهدات:</span>
                                            <span class="stat-value"><?= number_format($campaign['total_impressions']) ?></span>
                                        </div>
                                    </div>

                                    <div class="campaign-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $campaign['budget'] > 0 ? ($campaign['current_spend'] / $campaign['budget']) * 100 : 0 ?>%"></div>
                                        </div>
                                        <span class="progress-text">
                                            <?= $campaign['budget'] > 0 ? number_format(($campaign['current_spend'] / $campaign['budget']) * 100, 1) : 0 ?>% من الميزانية
                                        </span>
                                    </div>

                                    <div class="campaign-actions">
                                        <a href="/ad_campaigns/view_campaign/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-eye"></i>
                                            عرض
                                        </a>
                                        <a href="/ad_campaigns/edit_campaign/<?= $campaign['id'] ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                            تعديل
                                        </a>
                                        <?php if ($campaign['status'] === 'active'): ?>
                                            <a href="/ad_campaigns/pause_campaign/<?= $campaign['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-pause"></i>
                                                إيقاف
                                            </a>
                                        <?php elseif ($campaign['status'] === 'paused'): ?>
                                            <a href="/ad_campaigns/activate_campaign/<?= $campaign['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-play"></i>
                                                تشغيل
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h3>لا توجد حملات إعلانية بعد</h3>
                                <p>ابدأ بإنشاء حملتك الإعلانية الأولى لتصل إلى جمهورك المستهدف</p>
                                <a href="/ad_campaigns/create_campaign" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    إنشاء حملة جديدة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Quick Actions -->
                    <div class="widget quick-actions">
                        <h3 class="widget-title">
                            <i class="fas fa-bolt"></i>
                            إجراءات سريعة
                        </h3>
                        <div class="action-list">
                            <a href="/ad_campaigns/create_campaign" class="action-item">
                                <i class="fas fa-plus-circle"></i>
                                <span>إنشاء حملة جديدة</span>
                            </a>
                            <a href="/ad_campaigns/create_ad" class="action-item">
                                <i class="fas fa-image"></i>
                                <span>إنشاء إعلان جديد</span>
                            </a>
                            <a href="/ad_campaigns/deposit" class="action-item">
                                <i class="fas fa-credit-card"></i>
                                <span>إضافة رصيد</span>
                            </a>
                            <a href="/ad_campaigns/reports" class="action-item">
                                <i class="fas fa-chart-bar"></i>
                                <span>عرض التقارير</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="widget recent-activities">
                        <h3 class="widget-title">
                            <i class="fas fa-clock"></i>
                            الأنشطة الأخيرة
                        </h3>
                        <div class="activity-list">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?= $activity['icon'] ?? 'info-circle' ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text"><?= htmlspecialchars($activity['description']) ?></div>
                                            <div class="activity-time"><?= date('Y-m-d H:i', strtotime($activity['created_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-activities">
                                    <i class="fas fa-info-circle"></i>
                                    <span>لا توجد أنشطة حديثة</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Tips -->
                    <div class="widget performance-tips">
                        <h3 class="widget-title">
                            <i class="fas fa-lightbulb"></i>
                            نصائح لتحسين الأداء
                        </h3>
                        <div class="tips-list">
                            <div class="tip-item">
                                <i class="fas fa-target"></i>
                                <span>استهدف جمهوراً محدداً لتحسين معدل النقر</span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-image"></i>
                                <span>استخدم صوراً عالية الجودة وجذابة</span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-clock"></i>
                                <span>اختبر أوقات مختلفة لعرض إعلاناتك</span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-chart-line"></i>
                                <span>راقب الأداء وعدل الاستراتيجية حسب الحاجة</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Advertiser Dashboard Styles */
.advertiser-dashboard {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding-bottom: 2rem;
}

.dashboard-hero {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
    padding: 3rem 0;
    color: white;
}

.hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.welcome-section {
    flex: 1;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.hero-title i {
    color: #ffd700;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.company-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.company-name {
    font-size: 1.1rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 25px;
}

.account-status {
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.9rem;
    font-weight: 600;
}

.status-active {
    background: #10b981;
    color: white;
}

.status-inactive {
    background: #ef4444;
    color: white;
}

.balance-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    min-width: 300px;
}

.balance-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.balance-amount {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #ffd700;
}

.balance-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.stats-section {
    margin-top: -2rem;
    position: relative;
    z-index: 10;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
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

.main-content {
    background: #f8fafc;
    border-radius: 30px 30px 0 0;
    padding: 3rem 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.campaigns-grid {
    display: grid;
    gap: 1.5rem;
}

.campaign-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.campaign-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
}

.campaign-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.campaign-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
}

.campaign-status {
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

.status-pending {
    background: #dbeafe;
    color: #1e40af;
}

.status-rejected {
    background: #fee2e2;
    color: #dc2626;
}

.status-completed {
    background: #f3e8ff;
    color: #7c3aed;
}

.campaign-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
}

.campaign-progress {
    margin-bottom: 1rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.8rem;
    color: #6b7280;
}

.campaign-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #374151;
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

.action-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.action-item:hover {
    background: #f3f4f6;
    color: #667eea;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    gap: 0.75rem;
}

.activity-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 0.9rem;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 0.9rem;
    color: #374151;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.8rem;
    color: #6b7280;
}

.no-activities {
    text-align: center;
    color: #6b7280;
    padding: 1rem;
}

.tips-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #374151;
}

.tip-item i {
    color: #667eea;
    margin-top: 0.1rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .balance-card {
        min-width: auto;
        width: 100%;
        max-width: 400px;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .campaign-stats {
        grid-template-columns: 1fr;
    }
    
    .campaign-actions {
        justify-content: center;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .balance-amount {
        font-size: 2.5rem;
    }
}
</style>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../frontend/partials/footer.php';
?>

