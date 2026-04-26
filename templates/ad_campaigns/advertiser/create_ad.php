<?php
/**
 * صفحة إنشاء إعلان جديد
 * واجهة متطورة لإنشاء إعلانات متنوعة (نصية، مصورة، HTML)
 */

// تضمين الهيدر
include_once __DIR__ . '/../../frontend/partials/header.php';

// جلب معرف الحملة من الرابط
$campaign_id = $_GET['campaign_id'] ?? 0;
?>

<div class="create-ad-page">
    <!-- Header Section -->
    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="/ad_campaigns/dashboard">لوحة التحكم</a>
                    <i class="fas fa-chevron-left"></i>
                    <a href="/ad_campaigns/view_campaign/<?= $campaign_id ?>">تفاصيل الحملة</a>
                    <i class="fas fa-chevron-left"></i>
                    <span>إنشاء إعلان جديد</span>
                </div>
                <h1 class="page-title">
                    <i class="fas fa-plus-circle"></i>
                    إنشاء إعلان جديد
                </h1>
                <p class="page-subtitle">
                    أنشئ إعلاناً جذاباً وفعالاً لحملتك الإعلانية
                </p>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="form-section">
        <div class="container">
            <form id="createAdForm" class="ad-form" method="POST" enctype="multipart/form-data">
                <?= csrf_token_field() ?>
                <input type="hidden" name="campaign_id" value="<?= $campaign_id ?>">
                
                <!-- Ad Type Selection -->
                <div class="form-step active" data-step="1">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">1</span>
                            اختر نوع الإعلان
                        </h2>
                        <p class="step-description">حدد نوع الإعلان الذي تريد إنشاؤه</p>
                    </div>

                    <div class="ad-types-grid">
                        <label class="ad-type-option">
                            <input type="radio" name="ad_type" value="text" checked>
                            <div class="ad-type-card">
                                <div class="ad-type-icon">
                                    <i class="fas fa-font"></i>
                                </div>
                                <div class="ad-type-content">
                                    <h3>إعلان نصي</h3>
                                    <p>إعلان بسيط يحتوي على عنوان ووصف ورابط</p>
                                    <ul class="features-list">
                                        <li>سهل الإنشاء والتعديل</li>
                                        <li>تحميل سريع</li>
                                        <li>مناسب لجميع الأجهزة</li>
                                        <li>تكلفة منخفضة</li>
                                    </ul>
                                </div>
                            </div>
                        </label>

                        <label class="ad-type-option">
                            <input type="radio" name="ad_type" value="image">
                            <div class="ad-type-card">
                                <div class="ad-type-icon">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div class="ad-type-content">
                                    <h3>إعلان مصور</h3>
                                    <p>إعلان يحتوي على صورة جذابة مع نص</p>
                                    <ul class="features-list">
                                        <li>جذب بصري عالي</li>
                                        <li>معدل نقر أفضل</li>
                                        <li>مناسب للعلامات التجارية</li>
                                        <li>تأثير قوي على الجمهور</li>
                                    </ul>
                                </div>
                            </div>
                        </label>

                        <label class="ad-type-option">
                            <input type="radio" name="ad_type" value="html">
                            <div class="ad-type-card">
                                <div class="ad-type-icon">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="ad-type-content">
                                    <h3>إعلان HTML</h3>
                                    <p>إعلان متقدم بكود HTML مخصص</p>
                                    <ul class="features-list">
                                        <li>مرونة كاملة في التصميم</li>
                                        <li>تفاعل متقدم</li>
                                        <li>رسوم متحركة</li>
                                        <li>تخصيص كامل</li>
                                    </ul>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Text Ad Form -->
                <div class="form-step" data-step="2" data-type="text">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">2</span>
                            تفاصيل الإعلان النصي
                        </h2>
                        <p class="step-description">أدخل محتوى إعلانك النصي</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="text_title" class="form-label">
                                <i class="fas fa-heading"></i>
                                عنوان الإعلان
                            </label>
                            <input type="text" id="text_title" name="title" class="form-input" 
                                   placeholder="اكتب عنواناً جذاباً ومختصراً" maxlength="60">
                            <div class="form-hint">الحد الأقصى: 60 حرف</div>
                            <div class="char-counter">
                                <span id="title-counter">0</span>/60
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="text_description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                وصف الإعلان
                            </label>
                            <textarea id="text_description" name="description" class="form-textarea" 
                                      placeholder="اكتب وصفاً مقنعاً لمنتجك أو خدمتك" rows="4" maxlength="150"></textarea>
                            <div class="form-hint">الحد الأقصى: 150 حرف</div>
                            <div class="char-counter">
                                <span id="description-counter">0</span>/150
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="destination_url" class="form-label">
                                <i class="fas fa-link"></i>
                                رابط الوجهة
                            </label>
                            <input type="url" id="destination_url" name="destination_url" class="form-input" 
                                   placeholder="https://example.com" required>
                            <div class="form-hint">الرابط الذي سيتم توجيه المستخدمين إليه عند النقر</div>
                        </div>
                    </div>

                    <!-- Text Ad Preview -->
                    <div class="ad-preview-section">
                        <h3>معاينة الإعلان</h3>
                        <div class="text-ad-preview">
                            <div class="ad-preview-container">
                                <div class="ad-title-preview" id="title-preview">عنوان الإعلان سيظهر هنا</div>
                                <div class="ad-description-preview" id="description-preview">وصف الإعلان سيظهر هنا</div>
                                <div class="ad-url-preview" id="url-preview">example.com</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Ad Form -->
                <div class="form-step" data-step="2" data-type="image">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">2</span>
                            تفاصيل الإعلان المصور
                        </h2>
                        <p class="step-description">أدخل محتوى إعلانك المصور</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="image_title" class="form-label">
                                <i class="fas fa-heading"></i>
                                عنوان الإعلان
                            </label>
                            <input type="text" id="image_title" name="title" class="form-input" 
                                   placeholder="عنوان مختصر وجذاب" maxlength="40">
                            <div class="form-hint">الحد الأقصى: 40 حرف</div>
                        </div>

                        <div class="form-group">
                            <label for="image_description" class="form-label">
                                <i class="fas fa-align-left"></i>
                                وصف مختصر
                            </label>
                            <textarea id="image_description" name="description" class="form-textarea" 
                                      placeholder="وصف مختصر" rows="3" maxlength="100"></textarea>
                            <div class="form-hint">الحد الأقصى: 100 حرف</div>
                        </div>

                        <div class="form-group full-width">
                            <label for="ad_image" class="form-label">
                                <i class="fas fa-upload"></i>
                                صورة الإعلان
                            </label>
                            <div class="file-upload-area" id="imageUploadArea">
                                <input type="file" id="ad_image" name="ad_image" accept="image/*" class="file-input">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>اسحب الصورة هنا أو انقر للاختيار</p>
                                    <small>PNG, JPG, GIF - الحد الأقصى: 2MB</small>
                                </div>
                            </div>
                            <div class="image-preview" id="imagePreview" style="display: none;">
                                <img id="previewImg" src="" alt="معاينة الصورة">
                                <button type="button" class="remove-image" id="removeImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="image_destination_url" class="form-label">
                                <i class="fas fa-link"></i>
                                رابط الوجهة
                            </label>
                            <input type="url" id="image_destination_url" name="destination_url" class="form-input" 
                                   placeholder="https://example.com" required>
                        </div>
                    </div>

                    <!-- Image Ad Preview -->
                    <div class="ad-preview-section">
                        <h3>معاينة الإعلان</h3>
                        <div class="image-ad-preview">
                            <div class="ad-preview-container">
                                <div class="ad-image-container" id="adImageContainer">
                                    <div class="placeholder-image">
                                        <i class="fas fa-image"></i>
                                        <span>صورة الإعلان</span>
                                    </div>
                                </div>
                                <div class="ad-content">
                                    <div class="ad-title-preview" id="imageTitle-preview">عنوان الإعلان</div>
                                    <div class="ad-description-preview" id="imageDescription-preview">وصف الإعلان</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HTML Ad Form -->
                <div class="form-step" data-step="2" data-type="html">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">2</span>
                            تفاصيل الإعلان HTML
                        </h2>
                        <p class="step-description">أدخل كود HTML المخصص لإعلانك</p>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="html_title" class="form-label">
                                <i class="fas fa-heading"></i>
                                عنوان الإعلان (للإدارة)
                            </label>
                            <input type="text" id="html_title" name="title" class="form-input" 
                                   placeholder="اسم تعريفي للإعلان">
                        </div>

                        <div class="form-group">
                            <label for="html_destination_url" class="form-label">
                                <i class="fas fa-link"></i>
                                رابط الوجهة
                            </label>
                            <input type="url" id="html_destination_url" name="destination_url" class="form-input" 
                                   placeholder="https://example.com" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="html_content" class="form-label">
                                <i class="fas fa-code"></i>
                                كود HTML
                            </label>
                            <div class="code-editor-container">
                                <textarea id="html_content" name="html_content" class="code-editor" 
                                          placeholder="أدخل كود HTML هنا..." rows="15"></textarea>
                                <div class="code-editor-toolbar">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="formatCode()">
                                        <i class="fas fa-indent"></i>
                                        تنسيق الكود
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="validateHTML()">
                                        <i class="fas fa-check"></i>
                                        التحقق من الكود
                                    </button>
                                </div>
                            </div>
                            <div class="form-hint">
                                <strong>ملاحظة:</strong> تأكد من أن الكود آمن ولا يحتوي على سكريبت ضار
                            </div>
                        </div>
                    </div>

                    <!-- HTML Ad Preview -->
                    <div class="ad-preview-section">
                        <h3>معاينة الإعلان</h3>
                        <div class="html-ad-preview">
                            <div class="preview-toolbar">
                                <button type="button" class="btn btn-sm btn-primary" onclick="previewHTML()">
                                    <i class="fas fa-eye"></i>
                                    معاينة
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="clearPreview()">
                                    <i class="fas fa-times"></i>
                                    مسح
                                </button>
                            </div>
                            <div class="html-preview-container" id="htmlPreviewContainer">
                                <div class="preview-placeholder">
                                    <i class="fas fa-code"></i>
                                    <span>انقر على "معاينة" لرؤية الإعلان</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review & Submit -->
                <div class="form-step" data-step="3">
                    <div class="step-header">
                        <h2 class="step-title">
                            <span class="step-number">3</span>
                            مراجعة وإرسال
                        </h2>
                        <p class="step-description">راجع تفاصيل إعلانك قبل الإرسال</p>
                    </div>

                    <div class="review-section">
                        <div class="review-card">
                            <h3>ملخص الإعلان</h3>
                            <div id="ad-summary">
                                <!-- سيتم ملؤها بـ JavaScript -->
                            </div>
                        </div>

                        <div class="final-preview">
                            <h3>المعاينة النهائية</h3>
                            <div id="finalPreview">
                                <!-- سيتم ملؤها بـ JavaScript -->
                            </div>
                        </div>

                        <div class="terms-section">
                            <label class="checkbox-label">
                                <input type="checkbox" name="accept_ad_terms" required>
                                <span class="checkbox-custom"></span>
                                أوافق على <a href="/ad-terms" target="_blank">شروط الإعلانات</a> وأؤكد أن المحتوى لا ينتهك القوانين
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
                        إنشاء الإعلان
                    </button>
                </div>

                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 33%"></div>
                    </div>
                    <div class="progress-steps">
                        <div class="progress-step active">1</div>
                        <div class="progress-step">2</div>
                        <div class="progress-step">3</div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Create Ad Page Styles */
.create-ad-page {
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

.ad-form {
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

.ad-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.ad-type-option {
    cursor: pointer;
}

.ad-type-option input[type="radio"] {
    display: none;
}

.ad-type-option input[type="radio"]:checked + .ad-type-card {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(102, 126, 234, 0.2);
}

.ad-type-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 15px;
    padding: 2rem;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.ad-type-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.ad-type-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
}

.ad-type-content h3 {
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.ad-type-content p {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.features-list li {
    color: #374151;
    padding: 0.5rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.features-list li::before {
    content: '✓';
    color: #10b981;
    font-weight: bold;
    position: absolute;
    left: 0;
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
.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.char-counter {
    font-size: 0.8rem;
    color: #6b7280;
    text-align: left;
    margin-top: 0.25rem;
}

.file-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.file-upload-area:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.file-upload-area.dragover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-content i {
    font-size: 2rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.upload-content p {
    color: #374151;
    margin-bottom: 0.5rem;
}

.upload-content small {
    color: #6b7280;
}

.image-preview {
    position: relative;
    margin-top: 1rem;
    border-radius: 10px;
    overflow: hidden;
    max-width: 300px;
}

.image-preview img {
    width: 100%;
    height: auto;
    display: block;
}

.remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(239, 68, 68, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.3s ease;
}

.remove-image:hover {
    background: #ef4444;
}

.code-editor-container {
    position: relative;
}

.code-editor {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    line-height: 1.5;
    resize: vertical;
    min-height: 300px;
}

.code-editor:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.code-editor-toolbar {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.ad-preview-section {
    background: #f8fafc;
    border-radius: 15px;
    padding: 2rem;
    margin-top: 2rem;
}

.ad-preview-section h3 {
    color: #374151;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.text-ad-preview,
.image-ad-preview,
.html-ad-preview {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.ad-preview-container {
    max-width: 400px;
}

.ad-title-preview {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.ad-description-preview {
    color: #6b7280;
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.ad-url-preview {
    color: #10b981;
    font-size: 0.9rem;
}

.ad-image-container {
    margin-bottom: 1rem;
}

.placeholder-image {
    width: 100%;
    height: 200px;
    background: #f3f4f6;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.placeholder-image i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.preview-toolbar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.html-preview-container {
    min-height: 200px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background: white;
}

.preview-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.preview-placeholder i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.review-section {
    display: grid;
    gap: 2rem;
}

.review-card,
.final-preview {
    background: #f8fafc;
    border-radius: 15px;
    padding: 2rem;
}

.review-card h3,
.final-preview h3 {
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
    
    .ad-types-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Multi-step form functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    let currentAdType = 'text';
    const totalSteps = 3;
    
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('createAdForm');
    
    // Ad type selection
    document.querySelectorAll('input[name="ad_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            currentAdType = this.value;
            updateStepVisibility();
        });
    });
    
    // Navigation functions
    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
        
        // Show current step
        if (step === 1) {
            document.querySelector(`[data-step="${step}"]`).classList.add('active');
        } else if (step === 2) {
            document.querySelector(`[data-step="${step}"][data-type="${currentAdType}"]`).classList.add('active');
        } else {
            document.querySelector(`[data-step="${step}"]`).classList.add('active');
        }
        
        // Update progress
        updateProgress(step);
        
        // Update navigation buttons
        updateNavigation(step);
    }
    
    function updateStepVisibility() {
        // Hide all step 2 variants
        document.querySelectorAll('[data-step="2"]').forEach(step => {
            step.classList.remove('active');
        });
        
        // Show the correct step 2 variant if we're on step 2
        if (currentStep === 2) {
            document.querySelector(`[data-step="2"][data-type="${currentAdType}"]`).classList.add('active');
        }
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
    
    // Event listeners
    nextBtn.addEventListener('click', function() {
        if (currentStep === 2) {
            updateAdSummary();
        }
        currentStep++;
        showStep(currentStep);
    });
    
    prevBtn.addEventListener('click', function() {
        currentStep--;
        showStep(currentStep);
    });
    
    // Character counters
    function setupCharCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        
        if (input && counter) {
            input.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = length;
                
                if (length > maxLength * 0.9) {
                    counter.style.color = '#ef4444';
                } else if (length > maxLength * 0.7) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#6b7280';
                }
            });
        }
    }
    
    setupCharCounter('text_title', 'title-counter', 60);
    setupCharCounter('text_description', 'description-counter', 150);
    
    // Live preview for text ads
    function setupTextAdPreview() {
        const titleInput = document.getElementById('text_title');
        const descInput = document.getElementById('text_description');
        const urlInput = document.getElementById('destination_url');
        
        const titlePreview = document.getElementById('title-preview');
        const descPreview = document.getElementById('description-preview');
        const urlPreview = document.getElementById('url-preview');
        
        if (titleInput && titlePreview) {
            titleInput.addEventListener('input', function() {
                titlePreview.textContent = this.value || 'عنوان الإعلان سيظهر هنا';
            });
        }
        
        if (descInput && descPreview) {
            descInput.addEventListener('input', function() {
                descPreview.textContent = this.value || 'وصف الإعلان سيظهر هنا';
            });
        }
        
        if (urlInput && urlPreview) {
            urlInput.addEventListener('input', function() {
                try {
                    const url = new URL(this.value);
                    urlPreview.textContent = url.hostname;
                } catch {
                    urlPreview.textContent = this.value || 'example.com';
                }
            });
        }
    }
    
    // Image upload handling
    function setupImageUpload() {
        const uploadArea = document.getElementById('imageUploadArea');
        const fileInput = document.getElementById('ad_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeBtn = document.getElementById('removeImage');
        
        if (!uploadArea || !fileInput) return;
        
        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageFile(files[0]);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleImageFile(this.files[0]);
            }
        });
        
        // Remove image
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                imagePreview.style.display = 'none';
                uploadArea.style.display = 'block';
            });
        }
        
        function handleImageFile(file) {
            if (!file.type.startsWith('image/')) {
                showNotification('يرجى اختيار ملف صورة صحيح', 'error');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                showNotification('حجم الصورة يجب أن يكون أقل من 2MB', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadArea.style.display = 'none';
                
                // Update ad preview
                updateImageAdPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Image ad preview
    function setupImageAdPreview() {
        const titleInput = document.getElementById('image_title');
        const descInput = document.getElementById('image_description');
        
        const titlePreview = document.getElementById('imageTitle-preview');
        const descPreview = document.getElementById('imageDescription-preview');
        
        if (titleInput && titlePreview) {
            titleInput.addEventListener('input', function() {
                titlePreview.textContent = this.value || 'عنوان الإعلان';
            });
        }
        
        if (descInput && descPreview) {
            descInput.addEventListener('input', function() {
                descPreview.textContent = this.value || 'وصف الإعلان';
            });
        }
    }
    
    function updateImageAdPreview(imageSrc) {
        const container = document.getElementById('adImageContainer');
        if (container && imageSrc) {
            container.innerHTML = `<img src="${imageSrc}" alt="صورة الإعلان" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">`;
        }
    }
    
    // HTML preview functions
    window.previewHTML = function() {
        const htmlContent = document.getElementById('html_content').value;
        const previewContainer = document.getElementById('htmlPreviewContainer');
        
        if (!htmlContent.trim()) {
            showNotification('يرجى إدخال كود HTML أولاً', 'warning');
            return;
        }
        
        // Basic security check (remove script tags)
        const sanitizedHTML = htmlContent.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        
        previewContainer.innerHTML = sanitizedHTML;
    };
    
    window.clearPreview = function() {
        const previewContainer = document.getElementById('htmlPreviewContainer');
        previewContainer.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-code"></i>
                <span>انقر على "معاينة" لرؤية الإعلان</span>
            </div>
        `;
    };
    
    window.formatCode = function() {
        const textarea = document.getElementById('html_content');
        // Basic HTML formatting (simplified)
        let html = textarea.value;
        html = html.replace(/></g, '>\n<');
        textarea.value = html;
    };
    
    window.validateHTML = function() {
        const htmlContent = document.getElementById('html_content').value;
        
        // Basic validation
        const openTags = (htmlContent.match(/</g) || []).length;
        const closeTags = (htmlContent.match(/>/g) || []).length;
        
        if (openTags === closeTags) {
            showNotification('الكود يبدو صحيحاً', 'success');
        } else {
            showNotification('قد يحتوي الكود على أخطاء في العلامات', 'warning');
        }
    };
    
    // Update ad summary
    function updateAdSummary() {
        const summary = document.getElementById('ad-summary');
        const finalPreview = document.getElementById('finalPreview');
        
        let summaryHTML = '';
        let previewHTML = '';
        
        if (currentAdType === 'text') {
            const title = document.getElementById('text_title').value;
            const description = document.getElementById('text_description').value;
            const url = document.getElementById('destination_url').value;
            
            summaryHTML = `
                <div class="summary-item"><strong>نوع الإعلان:</strong> نصي</div>
                <div class="summary-item"><strong>العنوان:</strong> ${title}</div>
                <div class="summary-item"><strong>الوصف:</strong> ${description}</div>
                <div class="summary-item"><strong>الرابط:</strong> ${url}</div>
            `;
            
            previewHTML = `
                <div class="text-ad-preview">
                    <div class="ad-preview-container">
                        <div class="ad-title-preview">${title}</div>
                        <div class="ad-description-preview">${description}</div>
                        <div class="ad-url-preview">${url}</div>
                    </div>
                </div>
            `;
        } else if (currentAdType === 'image') {
            const title = document.getElementById('image_title').value;
            const description = document.getElementById('image_description').value;
            const url = document.getElementById('image_destination_url').value;
            
            summaryHTML = `
                <div class="summary-item"><strong>نوع الإعلان:</strong> مصور</div>
                <div class="summary-item"><strong>العنوان:</strong> ${title}</div>
                <div class="summary-item"><strong>الوصف:</strong> ${description}</div>
                <div class="summary-item"><strong>الرابط:</strong> ${url}</div>
                <div class="summary-item"><strong>الصورة:</strong> تم رفعها</div>
            `;
            
            const previewImg = document.getElementById('previewImg');
            const imageSrc = previewImg ? previewImg.src : '';
            
            previewHTML = `
                <div class="image-ad-preview">
                    <div class="ad-preview-container">
                        <div class="ad-image-container">
                            ${imageSrc ? `<img src="${imageSrc}" alt="صورة الإعلان" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">` : '<div class="placeholder-image"><i class="fas fa-image"></i><span>صورة الإعلان</span></div>'}
                        </div>
                        <div class="ad-content">
                            <div class="ad-title-preview">${title}</div>
                            <div class="ad-description-preview">${description}</div>
                        </div>
                    </div>
                </div>
            `;
        } else if (currentAdType === 'html') {
            const title = document.getElementById('html_title').value;
            const url = document.getElementById('html_destination_url').value;
            
            summaryHTML = `
                <div class="summary-item"><strong>نوع الإعلان:</strong> HTML</div>
                <div class="summary-item"><strong>العنوان:</strong> ${title}</div>
                <div class="summary-item"><strong>الرابط:</strong> ${url}</div>
                <div class="summary-item"><strong>كود HTML:</strong> تم إدخاله</div>
            `;
            
            previewHTML = `
                <div class="html-ad-preview">
                    <div class="html-preview-container">
                        <p><strong>معاينة HTML:</strong> استخدم زر المعاينة في الخطوة السابقة</p>
                    </div>
                </div>
            `;
        }
        
        summary.innerHTML = summaryHTML;
        finalPreview.innerHTML = previewHTML;
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإنشاء...';
        submitBtn.disabled = true;
        
        // Submit form data
        const formData = new FormData(form);
        
        fetch('/ad_campaigns/create_ad', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم إنشاء الإعلان بنجاح!', 'success');
                setTimeout(() => {
                    window.location.href = `/ad_campaigns/view_campaign/${campaign_id}`;
                }, 2000);
            } else {
                showNotification(data.message || 'حدث خطأ أثناء إنشاء الإعلان', 'error');
                submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء الإعلان';
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('حدث خطأ في الاتصال', 'error');
            submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء الإعلان';
            submitBtn.disabled = false;
        });
    });
    
    // Initialize
    showStep(1);
    setupTextAdPreview();
    setupImageAdPreview();
    setupImageUpload();
});

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

.notification-warning {
    border-left: 4px solid #f59e0b;
    color: #92400e;
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

