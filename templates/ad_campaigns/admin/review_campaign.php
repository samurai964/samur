<?php
/**
 * صفحة مراجعة الحملة الإعلانية - المشرفين
 * واجهة متطورة لمراجعة تفاصيل الحملة والموافقة عليها أو رفضها
 */

// تضمين الهيدر
include_once __DIR__ . '/../../admin/header.php';

// التحقق من صلاحيات المدير
if (!Auth::hasPermission('manage_ad_campaigns')) {
    redirect('/admin/dashboard.php');
}

// جلب معرف الحملة
$campaign_id = $_GET['id'] ?? 0;
if (!$campaign_id) {
    redirect('/admin/ad_campaigns/dashboard');
}

// بيانات تجريبية للحملة (يجب استبدالها ببيانات حقيقية)
$campaign = [
    'id' => $campaign_id,
    'name' => 'حملة تسويق المنتجات التقنية المتطورة',
    'description' => 'حملة إعلانية شاملة لتسويق أحدث المنتجات التقنية والحلول الرقمية المبتكرة',
    'advertiser_id' => 15,
    'advertiser_name' => 'شركة التقنية المتقدمة',
    'advertiser_email' => 'contact@techadvanced.com',
    'advertiser_phone' => '+966501234567',
    'status' => 'pending',
    'budget_type' => 'total',
    'budget' => 5000.00,
    'daily_budget' => 200.00,
    'bid_strategy' => 'cpc',
    'max_cpc' => 1.50,
    'max_cpm' => 5.00,
    'start_date' => '2024-02-01',
    'end_date' => '2024-03-31',
    'target_countries' => 'SA,AE,EG,KW,QA,BH,OM',
    'target_languages' => 'ar,en',
    'target_age_min' => 25,
    'target_age_max' => 45,
    'target_gender' => 'all',
    'target_interests' => 'تقنية، برمجة، تطوير، ذكي اصطناعي، تجارة إلكترونية',
    'target_keywords' => 'تقنية، برمجة، تطوير، حلول رقمية، ذكاء اصطناعي',
    'website_url' => 'https://techadvanced.com',
    'created_at' => '2024-01-15 10:30:00',
    'submitted_at' => '2024-01-15 11:00:00'
];

// إعلانات الحملة
$campaign_ads = [
    [
        'id' => 1,
        'type' => 'text',
        'title' => 'حلول تقنية متطورة لشركتك',
        'description' => 'اكتشف أحدث الحلول التقنية التي تساعد شركتك على النمو والتطور في العصر الرقمي',
        'destination_url' => 'https://techadvanced.com/solutions',
        'status' => 'pending'
    ],
    [
        'id' => 2,
        'type' => 'image',
        'title' => 'منتجات ذكية للمستقبل',
        'description' => 'تقنيات الذكاء الاصطناعي في خدمة أعمالك',
        'image_url' => '/uploads/ads/tech-products.jpg',
        'destination_url' => 'https://techadvanced.com/products',
        'status' => 'pending'
    ]
];

// سجل المراجعات السابقة
$review_history = [
    [
        'reviewer' => 'أحمد محمد',
        'action' => 'طلب تعديل',
        'comment' => 'يرجى تعديل الكلمات المفتاحية لتكون أكثر تحديداً',
        'date' => '2024-01-14 15:30:00'
    ]
];
?>

<div class="review-campaign-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="breadcrumb">
                <a href="/admin/ad_campaigns/dashboard">لوحة التحكم</a>
                <i class="fas fa-chevron-left"></i>
                <a href="/admin/ad_campaigns/pending">الحملات قيد المراجعة</a>
                <i class="fas fa-chevron-left"></i>
                <span>مراجعة الحملة</span>
            </div>
            
            <div class="title-section">
                <h1 class="page-title">
                    <i class="fas fa-search"></i>
                    مراجعة الحملة الإعلانية
                </h1>
                <div class="campaign-meta">
                    <span class="campaign-id">#<?= $campaign['id'] ?></span>
                    <span class="status-badge status-<?= $campaign['status'] ?>">
                        قيد المراجعة
                    </span>
                    <span class="submission-date">
                        <i class="fas fa-clock"></i>
                        مُرسلة في <?= date('Y-m-d H:i', strtotime($campaign['submitted_at'])) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-grid">
            <!-- Campaign Details -->
            <div class="campaign-details-section">
                <!-- Basic Information -->
                <div class="details-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            معلومات الحملة الأساسية
                        </h2>
                        <div class="review-score">
                            <span class="score-label">نقاط المراجعة:</span>
                            <div class="score-value">
                                <span class="score">85</span>
                                <span class="score-max">/100</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="details-grid">
                        <div class="detail-group">
                            <h3>تفاصيل الحملة</h3>
                            <div class="detail-item">
                                <span class="detail-label">اسم الحملة:</span>
                                <span class="detail-value"><?= htmlspecialchars($campaign['name']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">الوصف:</span>
                                <span class="detail-value"><?= htmlspecialchars($campaign['description']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">موقع الويب:</span>
                                <span class="detail-value">
                                    <a href="<?= $campaign['website_url'] ?>" target="_blank" class="website-link">
                                        <?= $campaign['website_url'] ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        <div class="detail-group">
                            <h3>الميزانية والمزايدة</h3>
                            <div class="detail-item">
                                <span class="detail-label">نوع الميزانية:</span>
                                <span class="detail-value"><?= $campaign['budget_type'] === 'daily' ? 'يومية' : 'إجمالية' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">الميزانية الإجمالية:</span>
                                <span class="detail-value budget-amount">$<?= number_format($campaign['budget'], 2) ?></span>
                            </div>
                            <?php if ($campaign['budget_type'] === 'daily'): ?>
                            <div class="detail-item">
                                <span class="detail-label">الميزانية اليومية:</span>
                                <span class="detail-value">$<?= number_format($campaign['daily_budget'], 2) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">استراتيجية المزايدة:</span>
                                <span class="detail-value">
                                    <?= $campaign['bid_strategy'] === 'cpc' ? 'تكلفة النقرة (CPC)' : 'تكلفة الألف مشاهدة (CPM)' ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">الحد الأقصى للمزايدة:</span>
                                <span class="detail-value">
                                    $<?= $campaign['bid_strategy'] === 'cpc' ? number_format($campaign['max_cpc'], 2) : number_format($campaign['max_cpm'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-group">
                            <h3>التوقيت</h3>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ البداية:</span>
                                <span class="detail-value"><?= $campaign['start_date'] ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ النهاية:</span>
                                <span class="detail-value"><?= $campaign['end_date'] ?: 'غير محدد' ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">مدة الحملة:</span>
                                <span class="detail-value">
                                    <?php
                                    $start = new DateTime($campaign['start_date']);
                                    $end = new DateTime($campaign['end_date']);
                                    $diff = $start->diff($end);
                                    echo $diff->days . ' يوم';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Targeting Details -->
                <div class="targeting-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-target"></i>
                            تفاصيل الاستهداف
                        </h2>
                        <div class="targeting-score">
                            <span class="score-indicator good">
                                <i class="fas fa-check-circle"></i>
                                استهداف جيد
                            </span>
                        </div>
                    </div>
                    
                    <div class="targeting-grid">
                        <div class="targeting-group">
                            <h3>الاستهداف الجغرافي</h3>
                            <div class="targeting-tags">
                                <?php
                                $countries = explode(',', $campaign['target_countries']);
                                $country_names = [
                                    'SA' => 'السعودية',
                                    'AE' => 'الإمارات',
                                    'EG' => 'مصر',
                                    'KW' => 'الكويت',
                                    'QA' => 'قطر',
                                    'BH' => 'البحرين',
                                    'OM' => 'عُمان'
                                ];
                                foreach ($countries as $country):
                                ?>
                                    <span class="tag country-tag"><?= $country_names[$country] ?? $country ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="targeting-group">
                            <h3>اللغات المستهدفة</h3>
                            <div class="targeting-tags">
                                <?php
                                $languages = explode(',', $campaign['target_languages']);
                                $language_names = [
                                    'ar' => 'العربية',
                                    'en' => 'الإنجليزية'
                                ];
                                foreach ($languages as $language):
                                ?>
                                    <span class="tag language-tag"><?= $language_names[$language] ?? $language ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="targeting-group">
                            <h3>الفئة العمرية والجنس</h3>
                            <div class="demographic-info">
                                <div class="demo-item">
                                    <span class="demo-label">العمر:</span>
                                    <span class="demo-value"><?= $campaign['target_age_min'] ?> - <?= $campaign['target_age_max'] ?> سنة</span>
                                </div>
                                <div class="demo-item">
                                    <span class="demo-label">الجنس:</span>
                                    <span class="demo-value">
                                        <?php
                                        $gender_labels = [
                                            'all' => 'الجميع',
                                            'male' => 'ذكور',
                                            'female' => 'إناث'
                                        ];
                                        echo $gender_labels[$campaign['target_gender']] ?? $campaign['target_gender'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="targeting-group">
                            <h3>الاهتمامات</h3>
                            <div class="targeting-tags">
                                <?php
                                $interests = explode('،', $campaign['target_interests']);
                                foreach ($interests as $interest):
                                ?>
                                    <span class="tag interest-tag"><?= trim($interest) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="targeting-group">
                            <h3>الكلمات المفتاحية</h3>
                            <div class="targeting-tags">
                                <?php
                                $keywords = explode('،', $campaign['target_keywords']);
                                foreach ($keywords as $keyword):
                                ?>
                                    <span class="tag keyword-tag"><?= trim($keyword) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaign Ads -->
                <div class="ads-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-images"></i>
                            إعلانات الحملة
                        </h2>
                        <div class="ads-count">
                            <span><?= count($campaign_ads) ?> إعلان</span>
                        </div>
                    </div>
                    
                    <div class="ads-list">
                        <?php foreach ($campaign_ads as $ad): ?>
                            <div class="ad-item">
                                <div class="ad-preview">
                                    <?php if ($ad['type'] === 'text'): ?>
                                        <div class="text-ad-preview">
                                            <div class="ad-title"><?= htmlspecialchars($ad['title']) ?></div>
                                            <div class="ad-description"><?= htmlspecialchars($ad['description']) ?></div>
                                            <div class="ad-url"><?= parse_url($ad['destination_url'], PHP_URL_HOST) ?></div>
                                        </div>
                                    <?php elseif ($ad['type'] === 'image'): ?>
                                        <div class="image-ad-preview">
                                            <div class="ad-image">
                                                <img src="<?= $ad['image_url'] ?>" alt="صورة الإعلان" onerror="this.src='/assets/images/ad-placeholder.jpg'">
                                            </div>
                                            <div class="ad-content">
                                                <div class="ad-title"><?= htmlspecialchars($ad['title']) ?></div>
                                                <div class="ad-description"><?= htmlspecialchars($ad['description']) ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ad-info">
                                    <div class="ad-type">
                                        <i class="fas fa-<?= $ad['type'] === 'text' ? 'font' : 'image' ?>"></i>
                                        <?= $ad['type'] === 'text' ? 'إعلان نصي' : 'إعلان مصور' ?>
                                    </div>
                                    <div class="ad-status">
                                        <span class="status-badge status-<?= $ad['status'] ?>">
                                            قيد المراجعة
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="ad-actions">
                                    <button class="btn btn-sm btn-success" onclick="approveAd(<?= $ad['id'] ?>)">
                                        <i class="fas fa-check"></i>
                                        موافقة
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectAd(<?= $ad['id'] ?>)">
                                        <i class="fas fa-times"></i>
                                        رفض
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Advertiser Information -->
                <div class="widget advertiser-info">
                    <h3 class="widget-title">
                        <i class="fas fa-user-tie"></i>
                        معلومات المعلن
                    </h3>
                    <div class="advertiser-details">
                        <div class="advertiser-avatar">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="advertiser-data">
                            <h4 class="advertiser-name"><?= htmlspecialchars($campaign['advertiser_name']) ?></h4>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?= $campaign['advertiser_email'] ?>"><?= $campaign['advertiser_email'] ?></a>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?= $campaign['advertiser_phone'] ?>"><?= $campaign['advertiser_phone'] ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="advertiser-stats">
                        <div class="stat-item">
                            <span class="stat-label">الحملات السابقة:</span>
                            <span class="stat-value">8</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">معدل الموافقة:</span>
                            <span class="stat-value">92%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">إجمالي الإنفاق:</span>
                            <span class="stat-value">$15,000</span>
                        </div>
                    </div>
                </div>

                <!-- Review Actions -->
                <div class="widget review-actions">
                    <h3 class="widget-title">
                        <i class="fas fa-gavel"></i>
                        إجراءات المراجعة
                    </h3>
                    
                    <form id="reviewForm" class="review-form">
                        <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                        
                        <div class="form-group">
                            <label for="review_action" class="form-label">الإجراء:</label>
                            <select id="review_action" name="action" class="form-select" required>
                                <option value="">اختر الإجراء</option>
                                <option value="approve">موافقة</option>
                                <option value="reject">رفض</option>
                                <option value="request_changes">طلب تعديلات</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="review_comment" class="form-label">ملاحظات المراجعة:</label>
                            <textarea id="review_comment" name="comment" class="form-textarea" 
                                      rows="4" placeholder="أضف ملاحظاتك هنا..."></textarea>
                        </div>
                        
                        <div class="form-group" id="rejection_reason_group" style="display: none;">
                            <label for="rejection_reason" class="form-label">سبب الرفض:</label>
                            <select id="rejection_reason" name="rejection_reason" class="form-select">
                                <option value="">اختر السبب</option>
                                <option value="inappropriate_content">محتوى غير مناسب</option>
                                <option value="misleading_claims">ادعاءات مضللة</option>
                                <option value="poor_quality">جودة منخفضة</option>
                                <option value="policy_violation">انتهاك السياسات</option>
                                <option value="technical_issues">مشاكل تقنية</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane"></i>
                                إرسال المراجعة
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Review History -->
                <?php if (!empty($review_history)): ?>
                <div class="widget review-history">
                    <h3 class="widget-title">
                        <i class="fas fa-history"></i>
                        سجل المراجعات
                    </h3>
                    <div class="history-list">
                        <?php foreach ($review_history as $review): ?>
                            <div class="history-item">
                                <div class="history-header">
                                    <span class="reviewer-name"><?= htmlspecialchars($review['reviewer']) ?></span>
                                    <span class="review-date"><?= date('Y-m-d H:i', strtotime($review['date'])) ?></span>
                                </div>
                                <div class="review-action">
                                    <span class="action-badge"><?= $review['action'] ?></span>
                                </div>
                                <div class="review-comment">
                                    <?= htmlspecialchars($review['comment']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Compliance Check -->
                <div class="widget compliance-check">
                    <h3 class="widget-title">
                        <i class="fas fa-shield-alt"></i>
                        فحص الامتثال
                    </h3>
                    <div class="compliance-list">
                        <div class="compliance-item passed">
                            <i class="fas fa-check-circle"></i>
                            <span>محتوى مناسب</span>
                        </div>
                        <div class="compliance-item passed">
                            <i class="fas fa-check-circle"></i>
                            <span>لا يحتوي على محتوى مضلل</span>
                        </div>
                        <div class="compliance-item passed">
                            <i class="fas fa-check-circle"></i>
                            <span>يتوافق مع السياسات</span>
                        </div>
                        <div class="compliance-item warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>يحتاج مراجعة الكلمات المفتاحية</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Review Campaign Page Styles */
.review-campaign-page {
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

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title i {
    color: #667eea;
}

.campaign-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.campaign-id {
    font-weight: 600;
    color: #6b7280;
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

.submission-date {
    color: #6b7280;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.main-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

.campaign-details-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.details-card,
.targeting-card,
.ads-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.card-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.review-score {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.score-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.score-value {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.score {
    font-size: 1.5rem;
    font-weight: 700;
    color: #10b981;
}

.score-max {
    color: #6b7280;
    font-size: 0.9rem;
}

.targeting-score {
    display: flex;
    align-items: center;
}

.score-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.score-indicator.good {
    background: #dcfce7;
    color: #166534;
}

.details-grid {
    display: grid;
    gap: 2rem;
}

.detail-group {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.5rem;
}

.detail-group h3 {
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
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
    text-align: left;
}

.budget-amount {
    color: #10b981;
    font-size: 1.1rem;
}

.website-link {
    color: #667eea;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.website-link:hover {
    text-decoration: underline;
}

.targeting-grid {
    display: grid;
    gap: 1.5rem;
}

.targeting-group {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.5rem;
}

.targeting-group h3 {
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.targeting-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.country-tag {
    background: #dbeafe;
    color: #1e40af;
}

.language-tag {
    background: #fef3c7;
    color: #92400e;
}

.interest-tag {
    background: #f3e8ff;
    color: #7c3aed;
}

.keyword-tag {
    background: #dcfce7;
    color: #166534;
}

.demographic-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.demo-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.demo-label {
    color: #6b7280;
    font-weight: 500;
}

.demo-value {
    color: #1f2937;
    font-weight: 600;
}

.ads-count {
    background: #f3f4f6;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #374151;
    font-weight: 600;
}

.ads-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.ad-item {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
}

.ad-preview {
    margin-bottom: 1rem;
}

.text-ad-preview {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
}

.image-ad-preview {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
}

.ad-image {
    width: 120px;
    height: 80px;
    border-radius: 6px;
    overflow: hidden;
}

.ad-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ad-content {
    flex: 1;
}

.ad-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.ad-description {
    color: #6b7280;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.ad-url {
    color: #10b981;
    font-size: 0.9rem;
}

.ad-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.ad-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.ad-actions {
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

.advertiser-details {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.advertiser-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.advertiser-data {
    flex: 1;
}

.advertiser-name {
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}

.contact-item a {
    color: #667eea;
    text-decoration: none;
}

.contact-item a:hover {
    text-decoration: underline;
}

.advertiser-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
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
    color: #1f2937;
    font-weight: 600;
    font-size: 0.9rem;
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    color: #374151;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-select,
.form-textarea {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
}

.action-buttons {
    margin-top: 1rem;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.history-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.reviewer-name {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.review-date {
    color: #6b7280;
    font-size: 0.8rem;
}

.action-badge {
    background: #e0e7ff;
    color: #3730a3;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.review-comment {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-top: 0.5rem;
}

.compliance-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.compliance-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}

.compliance-item.passed {
    color: #166534;
}

.compliance-item.warning {
    color: #92400e;
}

.compliance-item.failed {
    color: #dc2626;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .main-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .review-campaign-page {
        padding: 1rem;
    }
    
    .page-header {
        padding: 1.5rem;
    }
    
    .details-card,
    .targeting-card,
    .ads-card {
        padding: 1.5rem;
    }
    
    .campaign-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .image-ad-preview {
        flex-direction: column;
    }
    
    .ad-image {
        width: 100%;
        height: 120px;
    }
}
</style>

<script>
// Review form functionality
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.getElementById('reviewForm');
    const actionSelect = document.getElementById('review_action');
    const rejectionReasonGroup = document.getElementById('rejection_reason_group');
    
    // Show/hide rejection reason based on action
    actionSelect.addEventListener('change', function() {
        if (this.value === 'reject') {
            rejectionReasonGroup.style.display = 'block';
            document.getElementById('rejection_reason').required = true;
        } else {
            rejectionReasonGroup.style.display = 'none';
            document.getElementById('rejection_reason').required = false;
        }
    });
    
    // Form submission
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = formData.get('action');
        
        if (!action) {
            showNotification('يرجى اختيار الإجراء', 'error');
            return;
        }
        
        // Confirm action
        let confirmMessage = '';
        switch (action) {
            case 'approve':
                confirmMessage = 'هل أنت متأكد من الموافقة على هذه الحملة؟';
                break;
            case 'reject':
                confirmMessage = 'هل أنت متأكد من رفض هذه الحملة؟';
                break;
            case 'request_changes':
                confirmMessage = 'هل أنت متأكد من طلب تعديلات على هذه الحملة؟';
                break;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Submit review
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
        submitBtn.disabled = true;
        
        fetch('/admin/ad_campaigns/review', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم إرسال المراجعة بنجاح', 'success');
                setTimeout(() => {
                    window.location.href = '/admin/ad_campaigns/pending';
                }, 2000);
            } else {
                showNotification(data.message || 'حدث خطأ أثناء إرسال المراجعة', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

// Ad approval/rejection functions
function approveAd(adId) {
    if (confirm('هل أنت متأكد من الموافقة على هذا الإعلان؟')) {
        fetch(`/admin/ad_campaigns/approve_ad/${adId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم الموافقة على الإعلان', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'حدث خطأ', 'error');
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
        });
    }
}

function rejectAd(adId) {
    const reason = prompt('يرجى إدخال سبب رفض الإعلان:');
    if (reason && reason.trim()) {
        fetch(`/admin/ad_campaigns/reject_ad/${adId}`, {
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
                showNotification('تم رفض الإعلان', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'حدث خطأ', 'error');
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

