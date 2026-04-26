<?php
/**
 * صفحة إنشاء حملة إعلانية جديدة
 * واجهة متطورة لإنشاء حملات تنافس جوجل أدسنس
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';
?>

<div class="create-campaign-page">
    <!-- Header Section -->
    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/ad_campaigns/dashboard">لوحة التحكم</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>إنشاء حملة جديدة</span>
                </div>
                <h1 class="page-title">
                    <i class="fas fa-plus-circle"></i>
                    إنشاء حملة إعلانية جديدة
                </h1>
                <p class="page-subtitle">
                    أنشئ حملة إعلانية متقدمة للوصول إلى جمهورك المستهدف بفعالية
                </p>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="form-section">
        <div class="container">
            <form id="createCampaignForm" class="campaign-form" method="POST">
                <?= csrf_token_field() ?>
                
                <!-- Campaign Basic Info -->
                <div class="form-step active" data-step="1">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">1</span>
                            معلومات الحملة الأساسية
                        </h2>
                        <p class="step-description">ابدأ بتحديد اسم الحملة والمعلومات الأساسية</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="campaign_name" class="form-label">
                                <i class="fas fa-tag"></i>
                                اسم الحملة
                            </label>
                            <input type="text" id="campaign_name" name="name" class="form-input" 
                                   placeholder="أدخل اسماً وصفياً لحملتك الإعلانية" required>
                            <div class="form-hint">اختر اسماً يساعدك على تذكر هدف الحملة</div>
                        </div>

                        <div class="form-group full-width">
                            <label for="campaign_description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                وصف الحملة
                            </label>
                            <textarea id="campaign_description" name="description" class="form-textarea" 
                                      placeholder="اكتب وصفاً مختصراً لأهداف الحملة والجمهور المستهدف" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="start_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i>
                                تاريخ البداية
                            </label>
                            <input type="date" id="start_date" name="start_date" class="form-input" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="end_date" class="form-label">
                                <i class="fas fa-calendar-check"></i>
                                تاريخ النهاية
                            </label>
                            <input type="date" id="end_date" name="end_date" class="form-input">
                            <div class="form-hint">اتركه فارغاً للتشغيل المستمر</div>
                        </div>
                    </div>
                </div>

                <!-- Budget & Bidding -->
                <div class="form-step" data-step="2">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">2</span>
                            الميزانية والمزايدة
                        </h2>
                        <p class="step-description">حدد ميزانيتك واستراتيجية المزايدة</p>
                    </div>

                    <div class="budget-section">
                        <div class="budget-type-selector">
                            <h3>نوع الميزانية</h3>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="budget_type" value="daily" checked>
                                    <span class="radio-custom"></span>
                                    <div class="radio-content">
                                        <strong>ميزانية يومية</strong>
                                        <small>المبلغ الذي تريد إنفاقه يومياً</small>
                                    </div>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="budget_type" value="total">
                                    <span class="radio-custom"></span>
                                    <div class="radio-content">
                                        <strong>ميزانية إجمالية</strong>
                                        <small>المبلغ الإجمالي للحملة كاملة</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="budget" class="form-label">
                                    <i class="fas fa-dollar-sign"></i>
                                    الميزانية ($)
                                </label>
                                <input type="number" id="budget" name="budget" class="form-input" 
                                       min="10" step="0.01" placeholder="100.00" required>
                                <div class="form-hint">الحد الأدنى: $10</div>
                            </div>

                            <div class="form-group">
                                <label for="daily_budget" class="form-label">
                                    <i class="fas fa-calendar-day"></i>
                                    الميزانية اليومية ($)
                                </label>
                                <input type="number" id="daily_budget" name="daily_budget" class="form-input" 
                                       min="1" step="0.01" placeholder="10.00">
                                <div class="form-hint">اختياري - للتحكم في الإنفاق اليومي</div>
                            </div>
                        </div>

                        <div class="bidding-section">
                            <h3>استراتيجية المزايدة</h3>
                            <div class="bidding-options">
                                <label class="bidding-option">
                                    <input type="radio" name="bid_type" value="CPC" checked>
                                    <div class="option-card">
                                        <div class="option-icon">
                                            <i class="fas fa-mouse-pointer"></i>
                                        </div>
                                        <div class="option-content">
                                            <h4>التكلفة لكل نقرة (CPC)</h4>
                                            <p>ادفع فقط عندما ينقر شخص على إعلانك</p>
                                            <div class="bid-input">
                                                <input type="number" name="cpc_bid" class="form-input" 
                                                       min="0.01" step="0.01" placeholder="0.50">
                                                <span class="currency">$</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label class="bidding-option">
                                    <input type="radio" name="bid_type" value="CPM">
                                    <div class="option-card">
                                        <div class="option-icon">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="option-content">
                                            <h4>التكلفة لكل ألف ظهور (CPM)</h4>
                                            <p>ادفع مقابل عرض إعلانك 1000 مرة</p>
                                            <div class="bid-input">
                                                <input type="number" name="cpm_bid" class="form-input" 
                                                       min="0.10" step="0.01" placeholder="2.00">
                                                <span class="currency">$</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label class="bidding-option">
                                    <input type="radio" name="bid_type" value="CPA">
                                    <div class="option-card">
                                        <div class="option-icon">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                        <div class="option-content">
                                            <h4>التكلفة لكل إجراء (CPA)</h4>
                                            <p>ادفع فقط عند تحقيق هدف محدد</p>
                                            <div class="bid-input">
                                                <input type="number" name="cpa_bid" class="form-input" 
                                                       min="1.00" step="0.01" placeholder="10.00">
                                                <span class="currency">$</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Targeting Options -->
                <div class="form-step" data-step="3">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">3</span>
                            خيارات الاستهداف
                        </h2>
                        <p class="step-description">حدد جمهورك المستهدف بدقة</p>
                    </div>

                    <div class="targeting-section">
                        <div class="targeting-group">
                            <h3>
                                <i class="fas fa-globe"></i>
                                الاستهداف الجغرافي
                            </h3>
                            <div class="form-group">
                                <label for="target_countries" class="form-label">البلدان المستهدفة</label>
                                <select id="target_countries" name="target_countries[]" class="form-select" multiple>
                                    <option value="SA">السعودية</option>
                                    <option value="AE">الإمارات</option>
                                    <option value="EG">مصر</option>
                                    <option value="JO">الأردن</option>
                                    <option value="LB">لبنان</option>
                                    <option value="SY">سوريا</option>
                                    <option value="IQ">العراق</option>
                                    <option value="KW">الكويت</option>
                                    <option value="QA">قطر</option>
                                    <option value="BH">البحرين</option>
                                    <option value="OM">عمان</option>
                                    <option value="YE">اليمن</option>
                                    <option value="MA">المغرب</option>
                                    <option value="TN">تونس</option>
                                    <option value="DZ">الجزائر</option>
                                    <option value="LY">ليبيا</option>
                                    <option value="SD">السودان</option>
                                    <option value="US">الولايات المتحدة</option>
                                    <option value="GB">المملكة المتحدة</option>
                                    <option value="DE">ألمانيا</option>
                                    <option value="FR">فرنسا</option>
                                    <option value="CA">كندا</option>
                                    <option value="AU">أستراليا</option>
                                </select>
                                <div class="form-hint">اتركه فارغاً لاستهداف جميع البلدان</div>
                            </div>
                        </div>

                        <div class="targeting-group">
                            <h3>
                                <i class="fas fa-language"></i>
                                الاستهداف اللغوي
                            </h3>
                            <div class="form-group">
                                <label for="target_languages" class="form-label">اللغات المستهدفة</label>
                                <select id="target_languages" name="target_languages[]" class="form-select" multiple>
                                    <option value="ar">العربية</option>
                                    <option value="en">الإنجليزية</option>
                                    <option value="fr">الفرنسية</option>
                                    <option value="es">الإسبانية</option>
                                    <option value="de">الألمانية</option>
                                    <option value="it">الإيطالية</option>
                                    <option value="pt">البرتغالية</option>
                                    <option value="ru">الروسية</option>
                                    <option value="zh">الصينية</option>
                                    <option value="ja">اليابانية</option>
                                    <option value="ko">الكورية</option>
                                    <option value="hi">الهندية</option>
                                    <option value="tr">التركية</option>
                                    <option value="fa">الفارسية</option>
                                    <option value="ur">الأردية</option>
                                </select>
                            </div>
                        </div>

                        <div class="targeting-group">
                            <h3>
                                <i class="fas fa-tags"></i>
                                الكلمات المفتاحية
                            </h3>
                            <div class="form-group">
                                <label for="target_keywords" class="form-label">الكلمات المفتاحية المستهدفة</label>
                                <textarea id="target_keywords" name="target_keywords" class="form-textarea" 
                                          placeholder="أدخل الكلمات المفتاحية مفصولة بفواصل، مثل: تقنية، برمجة، تطوير، ذكي" rows="3"></textarea>
                                <div class="form-hint">استخدم كلمات مفتاحية ذات صلة بمنتجك أو خدمتك</div>
                            </div>
                        </div>

                        <div class="targeting-group">
                            <h3>
                                <i class="fas fa-users"></i>
                                الاستهداف الديموغرافي
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="age_min" class="form-label">العمر الأدنى</label>
                                    <select id="age_min" name="age_min" class="form-select">
                                        <option value="">غير محدد</option>
                                        <option value="18">18</option>
                                        <option value="25">25</option>
                                        <option value="35">35</option>
                                        <option value="45">45</option>
                                        <option value="55">55</option>
                                        <option value="65">65</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="age_max" class="form-label">العمر الأعلى</label>
                                    <select id="age_max" name="age_max" class="form-select">
                                        <option value="">غير محدد</option>
                                        <option value="24">24</option>
                                        <option value="34">34</option>
                                        <option value="44">44</option>
                                        <option value="54">54</option>
                                        <option value="64">64</option>
                                        <option value="100">65+</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="form-label">الجنس</label>
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review & Submit -->
                <div class="form-step" data-step="4">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">4</span>
                            مراجعة وإرسال
                        </h2>
                        <p class="step-description">راجع تفاصيل حملتك قبل الإرسال</p>
                    </div>

                    <div class="review-section">
                        <div class="review-card">
                            <h3>ملخص الحملة</h3>
                            <div id="campaign-summary">
                                <!-- سيتم ملؤها بـ JavaScript -->
                            </div>
                        </div>

                        <div class="terms-section">
                            <label class="checkbox-label">
                                <input type="checkbox" name="accept_terms" required>
                                <span class="checkbox-custom"></span>
                                أوافق على <a href="/terms" target="_blank">شروط الخدمة</a> و <a href="/privacy" target="_blank">سياسة الخصوصية</a>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation">
                    <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-arrow-right"></i>
                        السابق
                    </button>
                    <button type="button" id="nextBtn" class="btn btn-primary">
                        التالي
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button type="submit" id="submitBtn" class="btn btn-success" style="display: none;">
                        <i class="fas fa-rocket"></i>
                        إنشاء الحملة
                    </button>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 25%"></div>
                    </div>
                    <div class="progress-steps">
                        <div class="progress-step active">1</div>
                        <div class="progress-step">2</div>
                        <div class="progress-step">3</div>
                        <div class="progress-step">4</div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Create Campaign Page Styles */
.create-campaign-page {
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
    margin-bottom: 1rem;
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

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
}

.form-section {
    background: #f8fafc;
    padding: 3rem 0;
    min-height: calc(100vh - 200px);
}

.campaign-form {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
}

.form-step {
    display: none;
    padding: 3rem;
}

.form-step.active {
    display: block;
}

.step-header {
    text-align: center;
    margin-bottom: 3rem;
}

.step-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
}

.step-description {
    color: #6b7280;
    font-size: 1.1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.budget-section {
    background: #f8fafc;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.budget-type-selector h3 {
    margin-bottom: 1rem;
    color: #374151;
}

.radio-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.radio-option:hover {
    border-color: #667eea;
}

.radio-option input[type="radio"] {
    display: none;
}

.radio-option input[type="radio"]:checked + .radio-custom {
    background: #667eea;
    border-color: #667eea;
}

.radio-option input[type="radio"]:checked + .radio-custom::after {
    opacity: 1;
}

.radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

.radio-custom::after {
    content: '';
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.radio-content strong {
    display: block;
    color: #374151;
    margin-bottom: 0.25rem;
}

.radio-content small {
    color: #6b7280;
}

.bidding-section h3 {
    margin-bottom: 1.5rem;
    color: #374151;
}

.bidding-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.bidding-option {
    cursor: pointer;
}

.bidding-option input[type="radio"] {
    display: none;
}

.bidding-option input[type="radio"]:checked + .option-card {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.option-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 15px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    height: 100%;
}

.option-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.option-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.option-content h4 {
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.option-content p {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.bid-input {
    position: relative;
}

.bid-input .form-input {
    padding-right: 2rem;
}

.bid-input .currency {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-weight: 600;
}

.targeting-section {
    display: grid;
    gap: 2rem;
}

.targeting-group {
    background: #f8fafc;
    border-radius: 15px;
    padding: 1.5rem;
}

.targeting-group h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.review-section {
    display: grid;
    gap: 2rem;
}

.review-card {
    background: #f8fafc;
    border-radius: 15px;
    padding: 2rem;
}

.review-card h3 {
    color: #374151;
    margin-bottom: 1rem;
}

.terms-section {
    text-align: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: #374151;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: #667eea;
    border-color: #667eea;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '✓';
    color: white;
    font-size: 0.8rem;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.form-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 3rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.progress-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    padding: 1rem 3rem;
    border-top: 1px solid #e5e7eb;
}

.progress-bar {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-step {
    width: 30px;
    height: 30px;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #6b7280;
    background: white;
    transition: all 0.3s ease;
}

.progress-step.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.progress-step.completed {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-step {
        padding: 2rem 1.5rem;
    }
    
    .form-navigation {
        padding: 1.5rem;
    }
    
    .progress-indicator {
        padding: 1rem 1.5rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .bidding-options {
        grid-template-columns: 1fr;
    }
    
    .radio-group {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Multi-step form functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 4;
    
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('createCampaignForm');
    
    // Navigation functions
    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
        
        // Show current step
        document.querySelector(`[data-step="${step}"]`).classList.add('active');
        
        // Update progress
        updateProgress(step);
        
        // Update navigation buttons
        updateNavigation(step);
    }
    
    function updateProgress(step) {
        const progressFill = document.querySelector('.progress-fill');
        const progressSteps = document.querySelectorAll('.progress-step');
        
        // Update progress bar
        progressFill.style.width = `${(step / totalSteps) * 100}%`;
        
        // Update step indicators
        progressSteps.forEach((stepEl, index) => {
            stepEl.classList.remove('active', 'completed');
            if (index + 1 < step) {
                stepEl.classList.add('completed');
            } else if (index + 1 === step) {
                stepEl.classList.add('active');
            }
        });
    }
    
    function updateNavigation(step) {
        prevBtn.style.display = step > 1 ? 'block' : 'none';
        nextBtn.style.display = step < totalSteps ? 'block' : 'none';
        submitBtn.style.display = step === totalSteps ? 'block' : 'none';
    }
    
    function validateStep(step) {
        const currentStepEl = document.querySelector(`[data-step="${step}"]`);
        const requiredFields = currentStepEl.querySelectorAll('[required]');
        
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                field.focus();
                showNotification('يرجى ملء جميع الحقول المطلوبة', 'error');
                return false;
            }
        }
        
        // Additional validation for specific steps
        if (step === 2) {
            const budget = parseFloat(document.getElementById('budget').value);
            if (budget < 10) {
                showNotification('الحد الأدنى للميزانية هو $10', 'error');
                return false;
            }
            
            const bidType = document.querySelector('input[name="bid_type"]:checked').value;
            const bidInput = document.querySelector(`input[name="${bidType.toLowerCase()}_bid"]`);
            if (!bidInput.value || parseFloat(bidInput.value) <= 0) {
                showNotification('يرجى تحديد مبلغ المزايدة', 'error');
                return false;
            }
        }
        
        if (step === 4) {
            const acceptTerms = document.querySelector('input[name="accept_terms"]');
            if (!acceptTerms.checked) {
                showNotification('يجب الموافقة على الشروط والأحكام', 'error');
                return false;
            }
        }
        
        return true;
    }
    
    // Event listeners
    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep)) {
            if (currentStep === 3) {
                updateCampaignSummary();
            }
            currentStep++;
            showStep(currentStep);
        }
    });
    
    prevBtn.addEventListener('click', function() {
        currentStep--;
        showStep(currentStep);
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateStep(currentStep)) {
            return;
        }
        
        // Show loading
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإنشاء...';
        submitBtn.disabled = true;
        
        // Submit form data
        const formData = new FormData(form);
        
        fetch('/ad_campaigns/create_campaign', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم إنشاء الحملة بنجاح!', 'success');
                setTimeout(() => {
                    window.location.href = '/ad_campaigns/dashboard';
                }, 2000);
            } else {
                showNotification(data.message || 'حدث خطأ أثناء إنشاء الحملة', 'error');
                submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء الحملة';
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
            submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء الحملة';
            submitBtn.disabled = false;
        });
    });
    
    // Update campaign summary
    function updateCampaignSummary() {
        const summary = document.getElementById('campaign-summary');
        const name = document.getElementById('campaign_name').value;
        const budget = document.getElementById('budget').value;
        const budgetType = document.querySelector('input[name="budget_type"]:checked').value;
        const bidType = document.querySelector('input[name="bid_type"]:checked').value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        summary.innerHTML = `
            <div class="summary-item">
                <strong>اسم الحملة:</strong> ${name}
            </div>
            <div class="summary-item">
                <strong>الميزانية:</strong> $${budget} (${budgetType === 'daily' ? 'يومية' : 'إجمالية'})
            </div>
            <div class="summary-item">
                <strong>نوع المزايدة:</strong> ${bidType}
            </div>
            <div class="summary-item">
                <strong>تاريخ البداية:</strong> ${startDate}
            </div>
            ${endDate ? `<div class="summary-item"><strong>تاريخ النهاية:</strong> ${endDate}</div>` : ''}
        `;
    }
    
    // Bidding type change handler
    document.querySelectorAll('input[name="bid_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Enable/disable bid inputs based on selection
            document.querySelectorAll('.bid-input input').forEach(input => {
                input.disabled = true;
                input.required = false;
            });
            
            const selectedBidInput = document.querySelector(`input[name="${this.value.toLowerCase()}_bid"]`);
            if (selectedBidInput) {
                selectedBidInput.disabled = false;
                selectedBidInput.required = true;
            }
        });
    });
    
    // Initialize first step
    showStep(1);
    
    // Initialize bid inputs
    document.querySelector('input[name="bid_type"]:checked').dispatchEvent(new Event('change'));
});

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

.summary-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.summary-item:last-child {
    border-bottom: none;
}
</style>

<?php
// تضمين الفوتر
include_once __DIR__ . '/../../frontend/partials/footer.php';
?>

