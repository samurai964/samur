/**
 * Final Max CMS - JavaScript الرئيسي
 * نظام إدارة المحتوى المتقدم
 */

// متغيرات عامة
const FinalMaxCMS = {
    config: {
        apiUrl: '/api',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        currentUser: null,
        theme: localStorage.getItem('theme') || 'light'
    },
    
    // تهيئة النظام
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.loadUserData();
        this.applyTheme();
        this.initializeAnimations();
    },

    // إعداد مستمعات الأحداث
    setupEventListeners: function() {
        // تبديل الثيم
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // القائمة المنسدلة للموبايل
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('active');
            });
        }

        // البحث المباشر
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performLiveSearch(e.target.value);
                }, 300);
            });
        }

        // أزرار الإعجاب
        document.addEventListener('click', (e) => {
            if (e.target.matches('.like-btn') || e.target.closest('.like-btn')) {
                e.preventDefault();
                this.handleLike(e.target.closest('.like-btn'));
            }
        });

        // أزرار المشاركة
        document.addEventListener('click', (e) => {
            if (e.target.matches('.share-btn') || e.target.closest('.share-btn')) {
                e.preventDefault();
                this.handleShare(e.target.closest('.share-btn'));
            }
        });

        // أزرار الحفظ
        document.addEventListener('click', (e) => {
            if (e.target.matches('.bookmark-btn') || e.target.closest('.bookmark-btn')) {
                e.preventDefault();
                this.handleBookmark(e.target.closest('.bookmark-btn'));
            }
        });

        // تأكيد الحذف
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-btn') || e.target.closest('.delete-btn')) {
                if (!confirm('هل أنت متأكد من الحذف؟')) {
                    e.preventDefault();
                }
            }
        });
    },

    // تهيئة المكونات
    initializeComponents: function() {
        this.initializeCarousels();
        this.initializeModals();
        this.initializeTooltips();
        this.initializeFormValidation();
        this.initializeTicker();
    },

    // تحميل بيانات المستخدم
    loadUserData: function() {
        const userDataElement = document.getElementById('user-data');
        if (userDataElement) {
            try {
                this.config.currentUser = JSON.parse(userDataElement.textContent);
            } catch (e) {
                console.warn('خطأ في تحميل بيانات المستخدم:', e);
            }
        }
    },

    // تطبيق الثيم
    applyTheme: function() {
        document.documentElement.setAttribute('data-theme', this.config.theme);
    },

    // تبديل الثيم
    toggleTheme: function() {
        this.config.theme = this.config.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.config.theme);
        this.applyTheme();
    },

    // البحث المباشر
    performLiveSearch: function(query) {
        if (query.length < 2) return;

        const searchResults = document.getElementById('search-results');
        if (!searchResults) return;

        searchResults.innerHTML = '<div class="loading">جاري البحث...</div>';

        fetch(`${this.config.apiUrl}/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-CSRF-Token': this.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            this.displaySearchResults(data.results);
        })
        .catch(error => {
            console.error('خطأ في البحث:', error);
            searchResults.innerHTML = '<div class="error">حدث خطأ في البحث</div>';
        });
    },

    // عرض نتائج البحث
    displaySearchResults: function(results) {
        const searchResults = document.getElementById('search-results');
        if (!searchResults) return;

        if (results.length === 0) {
            searchResults.innerHTML = '<div class="no-results">لا توجد نتائج</div>';
            return;
        }

        let html = '<div class="search-results-list">';
        results.forEach(result => {
            html += `
                <div class="search-result-item">
                    <h4><a href="${result.url}">${result.title}</a></h4>
                    <p>${result.excerpt}</p>
                    <small>${result.type} - ${result.date}</small>
                </div>
            `;
        });
        html += '</div>';

        searchResults.innerHTML = html;
    },

    // معالجة الإعجاب
    handleLike: function(button) {
        const itemId = button.dataset.itemId || button.dataset.topicId || button.dataset.commentId;
        const itemType = button.dataset.itemType || 'topic';
        
        if (!itemId) return;

        button.disabled = true;
        
        fetch(`${this.config.apiUrl}/${itemType}/${itemId}/like`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const countElement = button.querySelector('.like-count');
                if (countElement) {
                    countElement.textContent = data.likes;
                }
                button.classList.toggle('liked', data.liked);
                this.showNotification('تم تسجيل إعجابك!', 'success');
            } else {
                this.showNotification(data.message || 'حدث خطأ', 'error');
            }
        })
        .catch(error => {
            console.error('خطأ في الإعجاب:', error);
            this.showNotification('حدث خطأ في الشبكة', 'error');
        })
        .finally(() => {
            button.disabled = false;
        });
    },

    // معالجة المشاركة
    handleShare: function(button) {
        const url = button.dataset.url || window.location.href;
        const title = button.dataset.title || document.title;

        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            }).then(() => {
                this.showNotification('تم المشاركة بنجاح!', 'success');
            }).catch(error => {
                console.log('خطأ في المشاركة:', error);
                this.fallbackShare(url);
            });
        } else {
            this.fallbackShare(url);
        }
    },

    // مشاركة احتياطية
    fallbackShare: function(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                this.showNotification('تم نسخ الرابط!', 'success');
            });
        } else {
            // طريقة قديمة للنسخ
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showNotification('تم نسخ الرابط!', 'success');
        }
    },

    // معالجة الحفظ
    handleBookmark: function(button) {
        const itemId = button.dataset.itemId || button.dataset.topicId;
        const itemType = button.dataset.itemType || 'topic';
        
        if (!itemId) return;

        button.disabled = true;
        
        fetch(`${this.config.apiUrl}/${itemType}/${itemId}/bookmark`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.toggle('bookmarked', data.bookmarked);
                const message = data.bookmarked ? 'تم الحفظ!' : 'تم إلغاء الحفظ!';
                this.showNotification(message, 'success');
            } else {
                this.showNotification(data.message || 'حدث خطأ', 'error');
            }
        })
        .catch(error => {
            console.error('خطأ في الحفظ:', error);
            this.showNotification('حدث خطأ في الشبكة', 'error');
        })
        .finally(() => {
            button.disabled = false;
        });
    },

    // تهيئة الشرائح المتحركة
    initializeCarousels: function() {
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            this.setupCarousel(carousel);
        });
    },

    // إعداد شريحة متحركة
    setupCarousel: function(carousel) {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        let currentSlide = 0;

        if (slides.length === 0) return;

        const showSlide = (index) => {
            slides.forEach((slide, i) => {
                slide.style.display = i === index ? 'block' : 'none';
            });
        };

        const nextSlide = () => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        };

        const prevSlide = () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(currentSlide);
        };

        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);

        // تشغيل تلقائي
        setInterval(nextSlide, 5000);
        
        // عرض الشريحة الأولى
        showSlide(0);
    },

    // تهيئة النوافذ المنبثقة
    initializeModals: function() {
        const modalTriggers = document.querySelectorAll('[data-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.modal;
                this.openModal(modalId);
            });
        });

        // إغلاق النوافذ المنبثقة
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal-close') || e.target.matches('.modal-backdrop')) {
                this.closeModal(e.target.closest('.modal'));
            }
        });

        // إغلاق بمفتاح Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.active');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    },

    // فتح نافذة منبثقة
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    // إغلاق نافذة منبثقة
    closeModal: function(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // تهيئة التلميحات
    initializeTooltips: function() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });
            
            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    },

    // عرض تلميح
    showTooltip: function(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
        
        setTimeout(() => tooltip.classList.add('visible'), 10);
    },

    // إخفاء التلميح
    hideTooltip: function() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    },

    // تهيئة التحقق من النماذج
    initializeFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    },

    // التحقق من النموذج
    validateForm: function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'هذا الحقل مطلوب');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        // التحقق من البريد الإلكتروني
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'البريد الإلكتروني غير صحيح');
                isValid = false;
            }
        });

        return isValid;
    },

    // عرض خطأ الحقل
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        
        field.classList.add('error');
        field.parentNode.appendChild(error);
    },

    // مسح خطأ الحقل
    clearFieldError: function(field) {
        field.classList.remove('error');
        const error = field.parentNode.querySelector('.field-error');
        if (error) {
            error.remove();
        }
    },

    // التحقق من صحة البريد الإلكتروني
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // تهيئة الشريط المتحرك
    initializeTicker: function() {
        const ticker = document.querySelector('.enhanced-ticker');
        if (!ticker) return;

        const content = ticker.querySelector('.ticker-content');
        if (!content) return;

        // تكرار المحتوى للحركة المستمرة
        const originalContent = content.innerHTML;
        content.innerHTML = originalContent + originalContent;

        // إيقاف الحركة عند التمرير
        ticker.addEventListener('mouseenter', () => {
            content.style.animationPlayState = 'paused';
        });

        ticker.addEventListener('mouseleave', () => {
            content.style.animationPlayState = 'running';
        });
    },

    // تهيئة الرسوم المتحركة
    initializeAnimations: function() {
        // Intersection Observer للرسوم المتحركة
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // مراقبة العناصر القابلة للحركة
        const animatedElements = document.querySelectorAll('.fade-in, .slide-in, .scale-in');
        animatedElements.forEach(element => {
            observer.observe(element);
        });
    },

    // عرض إشعار
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        `;

        document.body.appendChild(notification);

        // إضافة مستمع لإغلاق الإشعار
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.hideNotification(notification);
        });

        // عرض الإشعار
        setTimeout(() => notification.classList.add('show'), 100);

        // إخفاء تلقائي بعد 5 ثوانٍ
        setTimeout(() => this.hideNotification(notification), 5000);
    },

    // إخفاء إشعار
    hideNotification: function(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    },

    // تحميل المزيد من المحتوى
    loadMore: function(url, container) {
        const loadMoreBtn = document.querySelector('.load-more-btn');
        if (loadMoreBtn) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = 'جاري التحميل...';
        }

        fetch(url, {
            headers: {
                'X-CSRF-Token': this.config.csrfToken
            }
        })
        .then(response => response.text())
        .then(html => {
            const containerElement = document.querySelector(container);
            if (containerElement) {
                containerElement.insertAdjacentHTML('beforeend', html);
            }
        })
        .catch(error => {
            console.error('خطأ في تحميل المحتوى:', error);
            this.showNotification('خطأ في تحميل المحتوى', 'error');
        })
        .finally(() => {
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                loadMoreBtn.textContent = 'تحميل المزيد';
            }
        });
    }
};

// تهيئة النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    FinalMaxCMS.init();
});

// تصدير للاستخدام العام
window.FinalMaxCMS = FinalMaxCMS;

