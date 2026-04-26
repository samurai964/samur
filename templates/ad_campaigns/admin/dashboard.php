<?php
/**
 * لوحة تحكم المشرفين - نظام الحملات الإعلانية
 * واجهة متطورة لإدارة ومراقبة جميع الحملات الإعلانية
 */

// تضمين الهيدر
include_once __DIR__ . '/../../admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_ad_campaigns')) {
    redirect('/admin/dashboard.php');
}

// بيانات تجريبية (يجب استبدالها ببيانات حقيقية من قاعدة البيانات)
$stats = [
    'total_campaigns' => 45,
    'active_campaigns' => 28,
    'pending_campaigns' => 8,
    'total_advertisers' => 15,
    'total_revenue' => 12500.75,
    'total_clicks' => 125000,
    'total_impressions' => 2500000,
    'avg_ctr' => 5.0
];

$recent_campaigns = [
    [
        'id' => 1,
        'name' => 'حملة تسويق المنتجات التقنية',
        'advertiser' => 'شركة التقنية المتقدمة',
        'status' => 'pending',
        'budget' => 2000.00,
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'name' => 'إعلانات الخدمات المالية',
        'advertiser' => 'البنك الرقمي',
        'status' => 'active',
        'budget' => 5000.00,
        'created_at' => '2024-01-14 14:20:00'
    ],
    [
        'id' => 3,
        'name' => 'حملة التجارة الإلكترونية',
        'advertiser' => 'متجر الإلكترونيات',
        'status' => 'rejected',
        'budget' => 1500.00,
        'created_at' => '2024-01-13 09:15:00'
    ]
];

$top_advertisers = [
    [
        'name' => 'شركة التقنية المتقدمة',
        'campaigns' => 8,
        'total_spend' => 15000.00,
        'avg_ctr' => 6.2
    ],
    [
        'name' => 'البنك الرقمي',
        'campaigns' => 5,
        'total_spend' => 12000.00,
        'avg_ctr' => 4.8
    ],
    [
        'name' => 'متجر الإلكترونيات',
        'campaigns' => 6,
        'total_spend' => 8500.00,
        'avg_ctr' => 5.5
    ]
];
?>

<div class="admin-campaigns-dashboard">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="title-section">
                <h1 class="page-title">
                    <i class="fas fa-bullhorn"></i>
                    إدارة الحملات الإعلانية
                </h1>
                <p class="page-subtitle">
                    مراقبة وإدارة جميع الحملات الإعلانية والمعلنين
                </p>
            </div>
            
            <div class="header-actions">
                <a href="/admin/ad_campaigns/settings" class="btn btn-outline">
                    <i class="fas fa-cog"></i>
                    إعدادات النظام
                </a>
                <a href="/admin/ad_campaigns/reports" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i>
                    التقارير المفصلة
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card campaigns">
                <div class="stat-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($stats['total_campaigns']) ?></div>
                    <div class="stat-label">إجمالي الحملات</div>
                    <div class="stat-breakdown">
                        <span class="active"><?= $stats['active_campaigns'] ?> نشطة</span>
                        <span class="pending"><?= $stats['pending_campaigns'] ?> قيد المراجعة</span>
                    </div>
                </div>
            </div>

            <div class="stat-card advertisers">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($stats['total_advertisers']) ?></div>
                    <div class="stat-label">المعلنون النشطون</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        +12% هذا الشهر
                    </div>
                </div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number">$<?= number_format($stats['total_revenue'], 2) ?></div>
                    <div class="stat-label">إجمالي الإيرادات</div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        +25% هذا الشهر
                    </div>
                </div>
            </div>

            <div class="stat-card performance">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?= number_format($stats['avg_ctr'], 1) ?>%</div>
                    <div class="stat-label">متوسط معدل النقر</div>
                    <div class="stat-details">
                        <small><?= number_format($stats['total_clicks']) ?> نقرة من <?= number_format($stats['total_impressions']) ?> مشاهدة</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-grid">
            <!-- Recent Campaigns -->
            <div class="campaigns-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i>
                        الحملات الأخيرة
                    </h2>
                    <div class="section-actions">
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="all">الكل</button>
                            <button class="filter-tab" data-filter="pending">قيد المراجعة</button>
                            <button class="filter-tab" data-filter="active">نشطة</button>
                            <button class="filter-tab" data-filter="rejected">مرفوضة</button>
                        </div>
                        <a href="/admin/ad_campaigns/all" class="btn btn-outline btn-sm">
                            عرض الكل
                        </a>
                    </div>
                </div>

                <div class="campaigns-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الحملة</th>
                                <th>المعلن</th>
                                <th>الحالة</th>
                                <th>الميزانية</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_campaigns as $campaign): ?>
                                <tr class="campaign-row" data-status="<?= $campaign['status'] ?>">
                                    <td>
                                        <div class="campaign-info">
                                            <h4 class="campaign-name"><?= htmlspecialchars($campaign['name']) ?></h4>
                                            <span class="campaign-id">#<?= $campaign['id'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="advertiser-info">
                                            <span class="advertiser-name"><?= htmlspecialchars($campaign['advertiser']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $campaign['status'] ?>">
                                            <?php
                                            $status_labels = [
                                                'pending' => 'قيد المراجعة',
                                                'active' => 'نشطة',
                                                'rejected' => 'مرفوضة',
                                                'paused' => 'متوقفة',
                                                'completed' => 'مكتملة'
                                            ];
                                            echo $status_labels[$campaign['status']] ?? $campaign['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="budget-amount">$<?= number_format($campaign['budget'], 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="date-created"><?= date('Y-m-d H:i', strtotime($campaign['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="/admin/ad_campaigns/view/<?= $campaign['id'] ?>" class="btn btn-sm btn-outline" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($campaign['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="approveCampaign(<?= $campaign['id'] ?>)" title="موافقة">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectCampaign(<?= $campaign['id'] ?>)" title="رفض">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($campaign['status'] === 'active'): ?>
                                                <button class="btn btn-sm btn-warning" onclick="pauseCampaign(<?= $campaign['id'] ?>)" title="إيقاف">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                        <a href="/admin/ad_campaigns/pending" class="action-item">
                            <div class="action-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">مراجعة الحملات</span>
                                <span class="action-count"><?= $stats['pending_campaigns'] ?> حملة</span>
                            </div>
                        </a>
                        
                        <a href="/admin/ad_campaigns/advertisers" class="action-item">
                            <div class="action-icon advertisers">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">إدارة المعلنين</span>
                                <span class="action-count"><?= $stats['total_advertisers'] ?> معلن</span>
                            </div>
                        </a>
                        
                        <a href="/admin/ad_campaigns/reports" class="action-item">
                            <div class="action-icon reports">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">التقارير</span>
                                <span class="action-count">عرض مفصل</span>
                            </div>
                        </a>
                        
                        <a href="/admin/ad_campaigns/settings" class="action-item">
                            <div class="action-icon settings">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">الإعدادات</span>
                                <span class="action-count">تكوين النظام</span>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Top Advertisers -->
                <div class="widget top-advertisers">
                    <h3 class="widget-title">
                        <i class="fas fa-trophy"></i>
                        أفضل المعلنين
                    </h3>
                    <div class="advertisers-list">
                        <?php foreach ($top_advertisers as $index => $advertiser): ?>
                            <div class="advertiser-item">
                                <div class="advertiser-rank">
                                    <span class="rank-number"><?= $index + 1 ?></span>
                                </div>
                                <div class="advertiser-info">
                                    <h4 class="advertiser-name"><?= htmlspecialchars($advertiser['name']) ?></h4>
                                    <div class="advertiser-stats">
                                        <span class="stat"><?= $advertiser['campaigns'] ?> حملة</span>
                                        <span class="stat">$<?= number_format($advertiser['total_spend']) ?></span>
                                        <span class="stat"><?= $advertiser['avg_ctr'] ?>% CTR</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- System Health -->
                <div class="widget system-health">
                    <h3 class="widget-title">
                        <i class="fas fa-heartbeat"></i>
                        حالة النظام
                    </h3>
                    <div class="health-metrics">
                        <div class="health-item">
                            <div class="health-indicator good"></div>
                            <div class="health-info">
                                <span class="health-label">أداء الخادم</span>
                                <span class="health-value">ممتاز</span>
                            </div>
                        </div>
                        
                        <div class="health-item">
                            <div class="health-indicator good"></div>
                            <div class="health-info">
                                <span class="health-label">قاعدة البيانات</span>
                                <span class="health-value">مستقرة</span>
                            </div>
                        </div>
                        
                        <div class="health-item">
                            <div class="health-indicator warning"></div>
                            <div class="health-info">
                                <span class="health-label">مساحة التخزين</span>
                                <span class="health-value">75% مستخدمة</span>
                            </div>
                        </div>
                        
                        <div class="health-item">
                            <div class="health-indicator good"></div>
                            <div class="health-info">
                                <span class="health-label">عرض الإعلانات</span>
                                <span class="health-value">يعمل بشكل طبيعي</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="widget recent-activities">
                    <h3 class="widget-title">
                        <i class="fas fa-history"></i>
                        الأنشطة الأخيرة
                    </h3>
                    <div class="activities-list">
                        <div class="activity-item">
                            <div class="activity-icon approved">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="activity-content">
                                <p>تم الموافقة على حملة "التسويق الرقمي"</p>
                                <span class="activity-time">منذ 5 دقائق</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon new">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="activity-content">
                                <p>حملة جديدة تحتاج للمراجعة</p>
                                <span class="activity-time">منذ 15 دقيقة</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon rejected">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="activity-content">
                                <p>تم رفض حملة "الإعلانات المضللة"</p>
                                <span class="activity-time">منذ 30 دقيقة</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Campaigns Dashboard Styles */
.admin-campaigns-dashboard {
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
    font-size: 1.1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.stats-section {
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
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

.campaigns .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.advertisers .stat-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.revenue .stat-icon {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
}

.performance .stat-icon {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
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

.stat-breakdown {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
}

.stat-breakdown .active {
    color: #10b981;
}

.stat-breakdown .pending {
    color: #f59e0b;
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

.stat-details {
    font-size: 0.8rem;
    color: #6b7280;
}

.main-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

.campaigns-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 2rem;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-tabs {
    display: flex;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 0.25rem;
}

.filter-tab {
    padding: 0.5rem 1rem;
    border: none;
    background: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-tab.active,
.filter-tab:hover {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: right;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.campaign-info h4 {
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.campaign-id {
    color: #6b7280;
    font-size: 0.8rem;
}

.advertiser-name {
    color: #374151;
    font-weight: 500;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-active {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #dc2626;
}

.status-paused {
    background: #e0e7ff;
    color: #3730a3;
}

.status-completed {
    background: #f3e8ff;
    color: #7c3aed;
}

.budget-amount {
    font-weight: 600;
    color: #1f2937;
}

.date-created {
    color: #6b7280;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
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
    gap: 0.75rem;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.action-item:hover {
    background: #f3f4f6;
    transform: translateX(-3px);
}

.action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.action-icon.pending {
    background: #f59e0b;
}

.action-icon.advertisers {
    background: #8b5cf6;
}

.action-icon.reports {
    background: #06b6d4;
}

.action-icon.settings {
    background: #6b7280;
}

.action-content {
    flex: 1;
}

.action-title {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
}

.action-count {
    font-size: 0.8rem;
    color: #6b7280;
}

.advertisers-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.advertiser-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 10px;
}

.advertiser-rank {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.advertiser-info {
    flex: 1;
}

.advertiser-name {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.advertiser-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #6b7280;
}

.health-metrics {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.health-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.health-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.health-indicator.good {
    background: #10b981;
}

.health-indicator.warning {
    background: #f59e0b;
}

.health-indicator.error {
    background: #ef4444;
}

.health-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.health-label {
    color: #374151;
    font-size: 0.9rem;
}

.health-value {
    color: #6b7280;
    font-size: 0.8rem;
}

.activities-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.activity-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: white;
}

.activity-icon.approved {
    background: #10b981;
}

.activity-icon.new {
    background: #3b82f6;
}

.activity-icon.rejected {
    background: #ef4444;
}

.activity-content {
    flex: 1;
}

.activity-content p {
    color: #374151;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.activity-time {
    color: #6b7280;
    font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .main-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

@media (max-width: 768px) {
    .admin-campaigns-dashboard {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .campaigns-section {
        padding: 1.5rem;
    }
    
    .data-table {
        font-size: 0.8rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<script>
// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Filter tabs functionality
    const filterTabs = document.querySelectorAll('.filter-tab');
    const campaignRows = document.querySelectorAll('.campaign-row');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter campaigns
            const filter = this.dataset.filter;
            campaignRows.forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

// Campaign management functions
function approveCampaign(campaignId) {
    if (confirm('هل أنت متأكد من الموافقة على هذه الحملة؟')) {
        fetch(`/admin/ad_campaigns/approve/${campaignId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم الموافقة على الحملة بنجاح', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'حدث خطأ أثناء الموافقة', 'error');
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
        });
    }
}

function rejectCampaign(campaignId) {
    const reason = prompt('يرجى إدخال سبب الرفض:');
    if (reason && reason.trim()) {
        fetch(`/admin/ad_campaigns/reject/${campaignId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم رفض الحملة', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'حدث خطأ أثناء الرفض', 'error');
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
        });
    }
}

function pauseCampaign(campaignId) {
    if (confirm('هل أنت متأكد من إيقاف هذه الحملة؟')) {
        fetch(`/admin/ad_campaigns/pause/${campaignId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم إيقاف الحملة', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'حدث خطأ أثناء الإيقاف', 'error');
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
        });
    }
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}
</script>

<style>
/* Notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 400px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #10b981;
    color: #065f46;
}

.notification-error {
    border-left: 4px solid #ef4444;
    color: #991b1b;
}

.notification-info {
    border-left: 4px solid #3b82f6;
    color: #1e40af;
}
</style>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../admin/footer.php';
?>

