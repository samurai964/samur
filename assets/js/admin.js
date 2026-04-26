/**
 * Final Max CMS - Admin JavaScript
 * ملف JavaScript خاص بلوحة التحكم الإدارية
 */

const AdminPanel = {
    // تهيئة لوحة التحكم
    init: function() {
        this.setupEventListeners();
        this.initializeCharts();
        this.setupDataTables();
        this.initializeModals();
        this.setupFormValidation();
    },

    // إعداد مستمعات الأحداث
    setupEventListeners: function() {
        // تأكيد الحذف
        document.addEventListener('click', (e) => {
            if (e.target.matches('.delete-btn') || e.target.closest('.delete-btn')) {
                e.preventDefault();
                this.confirmDelete(e.target.closest('.delete-btn'));
            }
        });

        // تأكيد الحظر
        document.addEventListener('click', (e) => {
            if (e.target.matches('.ban-btn') || e.target.closest('.ban-btn')) {
                e.preventDefault();
                this.confirmBan(e.target.closest('.ban-btn'));
            }
        });

        // تحديد الكل
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', this.toggleSelectAll);
        }

        // الإجراءات المجمعة
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        if (bulkActionBtn) {
            bulkActionBtn.addEventListener('click', this.handleBulkAction);
        }

        // البحث المباشر
        const searchInputs = document.querySelectorAll('.admin-search');
        searchInputs.forEach(input => {
            let searchTimeout;
            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performAdminSearch(e.target.value, e.target.dataset.searchType);
                }, 300);
            });
        });

        // تبديل الحالة
        document.addEventListener('change', (e) => {
            if (e.target.matches('.status-toggle')) {
                this.toggleStatus(e.target);
            }
        });
    },

    // تأكيد الحذف
    confirmDelete: function(button) {
        const itemType = button.dataset.itemType || 'العنصر';
        const itemName = button.dataset.itemName || '';
        
        const message = itemName 
            ? `هل أنت متأكد من حذف ${itemType}: "${itemName}"؟`
            : `هل أنت متأكد من حذف هذا ${itemType}؟`;
            
        if (confirm(message + '\n\nهذا الإجراء لا يمكن التراجع عنه!')) {
            const form = button.closest('form');
            if (form) {
                form.submit();
            } else {
                window.location.href = button.href;
            }
        }
    },

    // تأكيد الحظر
    confirmBan: function(button) {
        const username = button.dataset.username || 'المستخدم';
        
        if (confirm(`هل أنت متأكد من حظر المستخدم: "${username}"؟\n\nسيتم منعه من الوصول للموقع.`)) {
            const form = button.closest('form');
            if (form) {
                form.submit();
            } else {
                window.location.href = button.href;
            }
        }
    },

    // تحديد الكل
    toggleSelectAll: function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        AdminPanel.updateBulkActionButton();
    },

    // تحديث زر الإجراءات المجمعة
    updateBulkActionButton: function() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked');
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        const bulkActionSelect = document.getElementById('bulk-action-select');
        
        if (bulkActionBtn && bulkActionSelect) {
            if (selectedItems.length > 0) {
                bulkActionBtn.disabled = false;
                bulkActionBtn.textContent = `تطبيق على ${selectedItems.length} عنصر`;
            } else {
                bulkActionBtn.disabled = true;
                bulkActionBtn.textContent = 'اختر عناصر أولاً';
            }
        }
    },

    // معالجة الإجراءات المجمعة
    handleBulkAction: function() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked');
        const actionSelect = document.getElementById('bulk-action-select');
        
        if (selectedItems.length === 0) {
            alert('يرجى اختيار عنصر واحد على الأقل');
            return;
        }
        
        if (!actionSelect.value) {
            alert('يرجى اختيار إجراء');
            return;
        }
        
        const action = actionSelect.value;
        const count = selectedItems.length;
        
        let confirmMessage = '';
        switch (action) {
            case 'delete':
                confirmMessage = `هل أنت متأكد من حذف ${count} عنصر؟`;
                break;
            case 'activate':
                confirmMessage = `هل أنت متأكد من تفعيل ${count} عنصر؟`;
                break;
            case 'deactivate':
                confirmMessage = `هل أنت متأكد من إلغاء تفعيل ${count} عنصر؟`;
                break;
            default:
                confirmMessage = `هل أنت متأكد من تطبيق هذا الإجراء على ${count} عنصر؟`;
        }
        
        if (confirm(confirmMessage)) {
            const form = document.getElementById('bulk-action-form');
            if (form) {
                form.submit();
            }
        }
    },

    // البحث الإداري
    performAdminSearch: function(query, searchType) {
        if (query.length < 2) return;

        const resultsContainer = document.getElementById(`${searchType}-search-results`);
        if (!resultsContainer) return;

        resultsContainer.innerHTML = '<div class="loading">جاري البحث...</div>';

        fetch(`/admin/api/search?type=${searchType}&q=${encodeURIComponent(query)}`, {
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            this.displayAdminSearchResults(data.results, resultsContainer, searchType);
        })
        .catch(error => {
            console.error('خطأ في البحث:', error);
            resultsContainer.innerHTML = '<div class="error">حدث خطأ في البحث</div>';
        });
    },

    // عرض نتائج البحث الإداري
    displayAdminSearchResults: function(results, container, searchType) {
        if (results.length === 0) {
            container.innerHTML = '<div class="no-results">لا توجد نتائج</div>';
            return;
        }

        let html = '<div class="admin-search-results">';
        results.forEach(result => {
            html += `
                <div class="admin-search-result">
                    <div class="result-info">
                        <strong>${result.title}</strong>
                        <small>${result.type} - ${result.date}</small>
                    </div>
                    <div class="result-actions">
                        <a href="${result.edit_url}" class="btn btn-sm btn-primary">تعديل</a>
                        <a href="${result.view_url}" class="btn btn-sm btn-secondary">عرض</a>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        container.innerHTML = html;
    },

    // تبديل الحالة
    toggleStatus: function(toggle) {
        const itemId = toggle.dataset.itemId;
        const itemType = toggle.dataset.itemType;
        const newStatus = toggle.checked ? 'active' : 'inactive';

        fetch(`/admin/api/${itemType}/${itemId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('تم تحديث الحالة بنجاح', 'success');
            } else {
                toggle.checked = !toggle.checked; // إعادة الحالة السابقة
                this.showNotification(data.message || 'حدث خطأ', 'error');
            }
        })
        .catch(error => {
            console.error('خطأ في تحديث الحالة:', error);
            toggle.checked = !toggle.checked; // إعادة الحالة السابقة
            this.showNotification('حدث خطأ في الشبكة', 'error');
        });
    },

    // تهيئة الرسوم البيانية
    initializeCharts: function() {
        // رسم بياني للإحصائيات
        const statsChart = document.getElementById('stats-chart');
        if (statsChart) {
            this.createStatsChart(statsChart);
        }

        // رسم بياني للإيرادات
        const revenueChart = document.getElementById('revenue-chart');
        if (revenueChart) {
            this.createRevenueChart(revenueChart);
        }
    },

    // إنشاء رسم بياني للإحصائيات
    createStatsChart: function(canvas) {
        // هنا يمكن استخدام مكتبة Chart.js أو أي مكتبة أخرى
        // مثال بسيط بدون مكتبة خارجية
        const ctx = canvas.getContext('2d');
        
        // بيانات تجريبية
        const data = [10, 20, 30, 25, 35, 40, 45];
        const labels = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
        
        this.drawSimpleChart(ctx, data, labels, canvas.width, canvas.height);
    },

    // رسم بياني بسيط
    drawSimpleChart: function(ctx, data, labels, width, height) {
        const padding = 40;
        const chartWidth = width - (padding * 2);
        const chartHeight = height - (padding * 2);
        
        const maxValue = Math.max(...data);
        const stepX = chartWidth / (data.length - 1);
        const stepY = chartHeight / maxValue;
        
        // مسح الكانفاس
        ctx.clearRect(0, 0, width, height);
        
        // رسم الخطوط
        ctx.strokeStyle = '#3498db';
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        data.forEach((value, index) => {
            const x = padding + (index * stepX);
            const y = height - padding - (value * stepY);
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // رسم النقاط
        ctx.fillStyle = '#3498db';
        data.forEach((value, index) => {
            const x = padding + (index * stepX);
            const y = height - padding - (value * stepY);
            
            ctx.beginPath();
            ctx.arc(x, y, 5, 0, 2 * Math.PI);
            ctx.fill();
        });
    },

    // تهيئة جداول البيانات
    setupDataTables: function() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            this.enhanceTable(table);
        });
    },

    // تحسين الجداول
    enhanceTable: function(table) {
        // إضافة فرز للأعمدة
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header);
            });
        });

        // إضافة فلترة
        const filterInputs = table.querySelectorAll('.column-filter');
        filterInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.filterTable(table, input);
            });
        });
    },

    // فرز الجدول
    sortTable: function(table, header) {
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isAscending = header.classList.contains('sort-asc');
        
        // إزالة فئات الفرز من جميع الأعمدة
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        // إضافة فئة الفرز للعمود الحالي
        header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
        
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            // محاولة تحويل إلى رقم
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAscending ? bNum - aNum : aNum - bNum;
            } else {
                return isAscending 
                    ? bValue.localeCompare(aValue, 'ar')
                    : aValue.localeCompare(bValue, 'ar');
            }
        });
        
        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
    },

    // فلترة الجدول
    filterTable: function(table, input) {
        const columnIndex = input.dataset.columnIndex;
        const filterValue = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const cellValue = row.children[columnIndex].textContent.toLowerCase();
            row.style.display = cellValue.includes(filterValue) ? '' : 'none';
        });
    },

    // تهيئة النوافذ المنبثقة
    initializeModals: function() {
        const modalTriggers = document.querySelectorAll('[data-admin-modal]');
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const modalId = trigger.dataset.adminModal;
                this.openAdminModal(modalId);
            });
        });

        // إغلاق النوافذ المنبثقة
        document.addEventListener('click', (e) => {
            if (e.target.matches('.admin-modal-close') || e.target.matches('.admin-modal-backdrop')) {
                this.closeAdminModal(e.target.closest('.admin-modal'));
            }
        });
    },

    // فتح نافذة منبثقة إدارية
    openAdminModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    // إغلاق نافذة منبثقة إدارية
    closeAdminModal: function(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // تهيئة التحقق من النماذج
    setupFormValidation: function() {
        const forms = document.querySelectorAll('form[data-admin-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateAdminForm(form)) {
                    e.preventDefault();
                }
            });
        });
    },

    // التحقق من النماذج الإدارية
    validateAdminForm: function(form) {
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

        return isValid;
    },

    // عرض خطأ الحقل
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const error = document.createElement('div');
        error.className = 'admin-field-error';
        error.textContent = message;
        
        field.classList.add('error');
        field.parentNode.appendChild(error);
    },

    // مسح خطأ الحقل
    clearFieldError: function(field) {
        field.classList.remove('error');
        const error = field.parentNode.querySelector('.admin-field-error');
        if (error) {
            error.remove();
        }
    },

    // عرض إشعار إداري
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `admin-notification admin-notification-${type}`;
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

    // إخفاء إشعار إداري
    hideNotification: function(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
};

// تهيئة لوحة التحكم عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    AdminPanel.init();
});

// تصدير للاستخدام العام
window.AdminPanel = AdminPanel;

