/**
 * English Language JavaScript Support
 * Final Max CMS - English Language Pack
 */

// Language configuration
const EnglishLanguageConfig = {
    code: 'en',
    name: 'English',
    nativeName: 'English',
    direction: 'ltr',
    dateFormat: 'MM/DD/YYYY',
    timeFormat: '12', // 12-hour format
    currency: {
        symbol: '$',
        position: 'before', // before or after
        decimal: '.',
        thousands: ','
    },
    numbers: {
        decimal: '.',
        thousands: ','
    }
};

// Date and time formatting functions
const EnglishDateFormatter = {
    // Format date according to English locale
    formatDate: function(date, format = 'MM/DD/YYYY') {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        const options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };
        
        return date.toLocaleDateString('en-US', options);
    },
    
    // Format time according to English locale
    formatTime: function(date, format = '12') {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        const options = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: format === '12'
        };
        
        return date.toLocaleTimeString('en-US', options);
    },
    
    // Format datetime
    formatDateTime: function(date) {
        return this.formatDate(date) + ' ' + this.formatTime(date);
    },
    
    // Get relative time (e.g., "2 hours ago")
    getRelativeTime: function(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'just now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        } else if (diffInSeconds < 2592000) {
            const days = Math.floor(diffInSeconds / 86400);
            return days === 1 ? '1 day ago' : `${days} days ago`;
        } else if (diffInSeconds < 31536000) {
            const months = Math.floor(diffInSeconds / 2592000);
            return months === 1 ? '1 month ago' : `${months} months ago`;
        } else {
            const years = Math.floor(diffInSeconds / 31536000);
            return years === 1 ? '1 year ago' : `${years} years ago`;
        }
    },
    
    // Get day names
    getDayNames: function(format = 'long') {
        const days = {
            long: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            short: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            narrow: ['S', 'M', 'T', 'W', 'T', 'F', 'S']
        };
        return days[format] || days.long;
    },
    
    // Get month names
    getMonthNames: function(format = 'long') {
        const months = {
            long: ['January', 'February', 'March', 'April', 'May', 'June',
                   'July', 'August', 'September', 'October', 'November', 'December'],
            short: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            narrow: ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D']
        };
        return months[format] || months.long;
    }
};

// Number formatting functions
const EnglishNumberFormatter = {
    // Format number with thousands separator
    formatNumber: function(number, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },
    
    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    // Format percentage
    formatPercentage: function(number, decimals = 1) {
        return new Intl.NumberFormat('en-US', {
            style: 'percent',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number / 100);
    },
    
    // Parse number from string
    parseNumber: function(str) {
        return parseFloat(str.replace(/,/g, ''));
    }
};

// Text formatting functions
const EnglishTextFormatter = {
    // Capitalize first letter
    capitalize: function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },
    
    // Title case
    titleCase: function(str) {
        return str.replace(/\w\S*/g, (txt) => {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    },
    
    // Pluralize words
    pluralize: function(count, singular, plural = null) {
        if (count === 1) {
            return singular;
        }
        
        if (plural) {
            return plural;
        }
        
        // Simple pluralization rules for English
        if (singular.endsWith('y')) {
            return singular.slice(0, -1) + 'ies';
        } else if (singular.endsWith('s') || singular.endsWith('sh') || 
                   singular.endsWith('ch') || singular.endsWith('x') || 
                   singular.endsWith('z')) {
            return singular + 'es';
        } else {
            return singular + 's';
        }
    },
    
    // Truncate text
    truncate: function(str, length, suffix = '...') {
        if (str.length <= length) {
            return str;
        }
        return str.substring(0, length - suffix.length) + suffix;
    },
    
    // Word count
    wordCount: function(str) {
        return str.trim().split(/\s+/).length;
    },
    
    // Reading time estimation (average 200 words per minute)
    readingTime: function(str) {
        const words = this.wordCount(str);
        const minutes = Math.ceil(words / 200);
        return minutes === 1 ? '1 minute read' : `${minutes} minutes read`;
    }
};

// Validation functions for English
const EnglishValidator = {
    // Validate email
    isValidEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    // Validate phone number (US format)
    isValidPhone: function(phone) {
        const regex = /^[\+]?[1-9][\d]{0,15}$/;
        return regex.test(phone.replace(/[\s\-\(\)]/g, ''));
    },
    
    // Validate URL
    isValidUrl: function(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    },
    
    // Validate password strength
    validatePassword: function(password) {
        const result = {
            isValid: false,
            score: 0,
            feedback: []
        };
        
        if (password.length < 8) {
            result.feedback.push('Password must be at least 8 characters long');
        } else {
            result.score += 1;
        }
        
        if (!/[a-z]/.test(password)) {
            result.feedback.push('Password must contain at least one lowercase letter');
        } else {
            result.score += 1;
        }
        
        if (!/[A-Z]/.test(password)) {
            result.feedback.push('Password must contain at least one uppercase letter');
        } else {
            result.score += 1;
        }
        
        if (!/\d/.test(password)) {
            result.feedback.push('Password must contain at least one number');
        } else {
            result.score += 1;
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            result.feedback.push('Password must contain at least one special character');
        } else {
            result.score += 1;
        }
        
        result.isValid = result.score >= 4;
        return result;
    }
};

// Search and filter functions
const EnglishSearchHelper = {
    // Normalize text for search
    normalizeText: function(text) {
        return text.toLowerCase()
                  .replace(/[^\w\s]/g, '')
                  .trim();
    },
    
    // Highlight search terms
    highlightSearchTerms: function(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    },
    
    // Extract keywords from text
    extractKeywords: function(text, maxKeywords = 10) {
        const commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 
                           'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were',
                           'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did',
                           'will', 'would', 'could', 'should', 'may', 'might', 'can',
                           'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she',
                           'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them'];
        
        const words = this.normalizeText(text)
                         .split(/\s+/)
                         .filter(word => word.length > 2 && !commonWords.includes(word));
        
        const wordCount = {};
        words.forEach(word => {
            wordCount[word] = (wordCount[word] || 0) + 1;
        });
        
        return Object.entries(wordCount)
                    .sort(([,a], [,b]) => b - a)
                    .slice(0, maxKeywords)
                    .map(([word]) => word);
    }
};

// UI Helper functions
const EnglishUIHelper = {
    // Generate breadcrumb text
    generateBreadcrumb: function(items) {
        return items.join(' > ');
    },
    
    // Generate pagination text
    generatePaginationText: function(current, total) {
        return `Page ${current} of ${total}`;
    },
    
    // Generate results count text
    generateResultsText: function(start, end, total) {
        if (total === 0) {
            return 'No results found';
        } else if (total === 1) {
            return '1 result';
        } else if (start === end) {
            return `Result ${start} of ${total}`;
        } else {
            return `Results ${start}-${end} of ${total}`;
        }
    },
    
    // Generate file size text
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Generate loading text
    getLoadingText: function() {
        const messages = [
            'Loading...',
            'Please wait...',
            'Processing...',
            'Almost there...',
            'Just a moment...'
        ];
        return messages[Math.floor(Math.random() * messages.length)];
    }
};

// Form helper functions
const EnglishFormHelper = {
    // Generate form validation messages
    getValidationMessage: function(field, rule, value = null) {
        const messages = {
            required: `${field} is required.`,
            email: `Please enter a valid email address.`,
            minLength: `${field} must be at least ${value} characters long.`,
            maxLength: `${field} cannot exceed ${value} characters.`,
            numeric: `${field} must be a number.`,
            url: `Please enter a valid URL.`,
            phone: `Please enter a valid phone number.`,
            date: `Please enter a valid date.`,
            time: `Please enter a valid time.`,
            match: `${field} does not match.`,
            unique: `${field} already exists.`,
            fileSize: `File size cannot exceed ${value}.`,
            fileType: `Invalid file type. Please select a valid file.`
        };
        
        return messages[rule] || `${field} is invalid.`;
    },
    
    // Generate confirmation messages
    getConfirmationMessage: function(action, item = null) {
        const messages = {
            delete: `Are you sure you want to delete${item ? ' ' + item : ' this item'}?`,
            remove: `Are you sure you want to remove${item ? ' ' + item : ' this item'}?`,
            cancel: `Are you sure you want to cancel? Any unsaved changes will be lost.`,
            reset: `Are you sure you want to reset the form? All data will be lost.`,
            logout: `Are you sure you want to log out?`,
            leave: `Are you sure you want to leave this page? Any unsaved changes will be lost.`
        };
        
        return messages[action] || `Are you sure you want to ${action}?`;
    }
};

// Initialize English language support
function initializeEnglishLanguage() {
    // Set document direction
    document.documentElement.setAttribute('dir', 'ltr');
    document.documentElement.setAttribute('lang', 'en');
    
    // Update date pickers
    updateDatePickers();
    
    // Update number inputs
    updateNumberInputs();
    
    // Update form validation
    updateFormValidation();
    
    // Update UI text
    updateUIText();
}

function updateDatePickers() {
    const datePickers = document.querySelectorAll('input[type="date"], .datepicker');
    datePickers.forEach(picker => {
        if (picker.flatpickr) {
            picker.flatpickr.set('locale', 'en');
        }
    });
}

function updateNumberInputs() {
    const numberInputs = document.querySelectorAll('input[type="number"], .number-input');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Format number as user types
            const value = this.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                this.value = EnglishNumberFormatter.formatNumber(parseFloat(value));
            }
        });
    });
}

function updateFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        const rules = input.dataset.rules ? input.dataset.rules.split('|') : [];
        const fieldName = input.dataset.label || input.name || 'Field';
        
        rules.forEach(rule => {
            const [ruleName, ruleValue] = rule.split(':');
            
            if (!validateField(input, ruleName, ruleValue)) {
                showValidationError(input, EnglishFormHelper.getValidationMessage(fieldName, ruleName, ruleValue));
                isValid = false;
            } else {
                clearValidationError(input);
            }
        });
    });
    
    return isValid;
}

function validateField(input, rule, value) {
    const inputValue = input.value.trim();
    
    switch (rule) {
        case 'required':
            return inputValue !== '';
        case 'email':
            return EnglishValidator.isValidEmail(inputValue);
        case 'minLength':
            return inputValue.length >= parseInt(value);
        case 'maxLength':
            return inputValue.length <= parseInt(value);
        case 'numeric':
            return !isNaN(inputValue) && inputValue !== '';
        case 'url':
            return EnglishValidator.isValidUrl(inputValue);
        case 'phone':
            return EnglishValidator.isValidPhone(inputValue);
        default:
            return true;
    }
}

function showValidationError(input, message) {
    clearValidationError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error';
    errorDiv.textContent = message;
    
    input.classList.add('is-invalid');
    input.parentNode.appendChild(errorDiv);
}

function clearValidationError(input) {
    input.classList.remove('is-invalid');
    const existingError = input.parentNode.querySelector('.validation-error');
    if (existingError) {
        existingError.remove();
    }
}

function updateUIText() {
    // Update any dynamic UI text that needs localization
    const loadingElements = document.querySelectorAll('.loading-text');
    loadingElements.forEach(element => {
        if (!element.textContent.trim()) {
            element.textContent = EnglishUIHelper.getLoadingText();
        }
    });
}

// Export functions for global use
window.EnglishLanguageConfig = EnglishLanguageConfig;
window.EnglishDateFormatter = EnglishDateFormatter;
window.EnglishNumberFormatter = EnglishNumberFormatter;
window.EnglishTextFormatter = EnglishTextFormatter;
window.EnglishValidator = EnglishValidator;
window.EnglishSearchHelper = EnglishSearchHelper;
window.EnglishUIHelper = EnglishUIHelper;
window.EnglishFormHelper = EnglishFormHelper;

// Auto-initialize if this is the active language
document.addEventListener('DOMContentLoaded', function() {
    const currentLang = document.documentElement.getAttribute('lang');
    if (currentLang === 'en' || !currentLang) {
        initializeEnglishLanguage();
    }
});

// Listen for language change events
document.addEventListener('languageChanged', function(e) {
    if (e.detail.language === 'en') {
        initializeEnglishLanguage();
    }
});

