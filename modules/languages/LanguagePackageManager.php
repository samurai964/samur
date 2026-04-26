<?php

class LanguagePackageManager {
    
    private $db;
    private $language_engine;
    private $packages_dir;
    private $temp_dir;
    private $backup_dir;
    private $repository_url;
    private $max_download_size;
    private $allowed_extensions;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->language_engine = new LanguageEngine($database_connection);
        
        $this->packages_dir = __DIR__ . '/../../storage/language_packages/';
        $this->temp_dir = __DIR__ . '/../../storage/temp/';
        $this->backup_dir = __DIR__ . '/../../storage/backups/languages/';
        
        // 🔥 إصلاح مهم
        $this->repository_url = ''; // بدل null أو غير معرف
        
        $this->max_download_size = 10485760;
        $this->allowed_extensions = ['zip'];

        $this->createDirectories();
    }

    private function createDirectories() {
        foreach ([$this->packages_dir, $this->temp_dir, $this->backup_dir] as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
    }

    private function safeFileGet($url, $context = null) {
        try {
            if (!$url || strpos($url, 'http') === false) return false;
            return file_get_contents($url, false, $context);
        } catch (Exception $e) {
            return false;
        }
    }

    public function searchAvailablePackages($search_term = '', $language_code = '') {
        try {
            $external_packages = $this->searchExternalRepository($search_term, $language_code);
            $local_packages = $this->searchLocalPackages($search_term, $language_code);
            return array_merge($external_packages, $local_packages);
        } catch (Exception $e) {
            return [];
        }
    }

    private function searchExternalRepository($search_term, $language_code) {

        // 🔥 تعطيل بدون حذف
        if (empty($this->repository_url)) {
            return [];
        }

        try {
            $url = $this->repository_url . 'search.php';

            $params = [
                'q' => $search_term,
                'lang' => $language_code,
                'format' => 'json'
            ];

            $full_url = $url . '?' . http_build_query($params);

            $response = $this->safeFileGet($full_url);

            if (!$response) return [];

            $data = json_decode($response, true);
            if (!$data || !isset($data['packages'])) return [];

            return $data['packages'];

        } catch (Exception $e) {
            return [];
        }
    }

    private function searchLocalPackages($search_term, $language_code) {
        try {
            $stmt = $this->db->query("SELECT * FROM fmc_language_packages");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    private function isPackageInstalled($package_name) {
        try {
            $stmt = $this->db->prepare("SELECT is_installed FROM fmc_language_packages WHERE package_name=?");
            $stmt->execute([$package_name]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * تحميل حزمة لغة
     */
    public function downloadPackage($package_name, $download_url = null) {
        try {
            // بدء عملية التحميل
            $this->updatePackageStatus($package_name, 'downloading');
            $this->logPackageAction($package_name, 'download', 'started');
            
            // الحصول على معلومات الحزمة
            $package_info = $this->getPackageInfo($package_name);
            if (!$package_info) {
                throw new Exception("Package not found: {$package_name}");
            }
            
            $download_url = $download_url ?: $package_info['download_url'];
            if (!$download_url) {
                throw new Exception("No download URL available for package: {$package_name}");
            }
            
            // تحديد مسار الملف المؤقت
            $temp_file = $this->temp_dir . $package_name . '_' . time() . '.tmp';
            
            // تحميل الملف
            $downloaded_file = $this->downloadFile($download_url, $temp_file);
            if (!$downloaded_file) {
                throw new Exception("Failed to download package: {$package_name}");
            }
            
            // التحقق من صحة الملف
            if (!$this->validateDownloadedFile($downloaded_file, $package_info)) {
                unlink($downloaded_file);
                throw new Exception("Downloaded file validation failed for package: {$package_name}");
            }
            
            // نقل الملف إلى مجلد الحزم
            $final_path = $this->packages_dir . $package_name . '.zip';
            if (!rename($downloaded_file, $final_path)) {
                unlink($downloaded_file);
                throw new Exception("Failed to move downloaded file for package: {$package_name}");
            }
            
            // تحديث معلومات الحزمة
            $this->updatePackageInfo($package_name, [
                'file_path' => $final_path,
                'file_size' => filesize($final_path),
                'status' => 'available',
                'last_check' => date('Y-m-d H:i:s')
            ]);
            
            $this->logPackageAction($package_name, 'download', 'completed');
            
            return [
                'success' => true,
                'message' => 'Package downloaded successfully',
                'file_path' => $final_path
            ];
            
        } catch (Exception $e) {
            $this->updatePackageStatus($package_name, 'failed', $e->getMessage());
            $this->logPackageAction($package_name, 'download', 'failed', $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * تحميل ملف من URL
     */
    private function downloadFile($url, $destination) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 300, // 5 دقائق
                    'user_agent' => 'FinalMaxCMS-LanguageManager/1.0',
                    'follow_location' => true,
                    'max_redirects' => 5
                ]
            ]);
            
            $file_content = file_get_contents($url, false, $context);
            if ($file_content === false) {
                return false;
            }
            
            // التحقق من حجم الملف
            if (strlen($file_content) > $this->max_download_size) {
                throw new Exception("File size exceeds maximum allowed size");
            }
            
            if (file_put_contents($destination, $file_content) === false) {
                return false;
            }
            
            return $destination;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error downloading file - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من صحة الملف المحمل
     */
    private function validateDownloadedFile($file_path, $package_info) {
        try {
            // التحقق من وجود الملف
            if (!file_exists($file_path)) {
                return false;
            }
            
            // التحقق من حجم الملف
            $file_size = filesize($file_path);
            if (isset($package_info['file_size']) && $package_info['file_size'] > 0) {
                if (abs($file_size - $package_info['file_size']) > 1024) { // تسامح 1KB
                    return false;
                }
            }
            
            // التحقق من hash الملف
            if (isset($package_info['file_hash']) && !empty($package_info['file_hash'])) {
                $file_hash = hash_file('sha256', $file_path);
                if ($file_hash !== $package_info['file_hash']) {
                    return false;
                }
            }
            
            // التحقق من امتداد الملف
            $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if (!in_array($file_extension, $this->allowed_extensions)) {
                // محاولة تحديد النوع من محتوى الملف
                $file_type = $this->detectFileType($file_path);
                if (!$file_type) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error validating file - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * كشف نوع الملف
     */
    private function detectFileType($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_types = [
            'application/zip',
            'application/x-gzip',
            'application/gzip',
            'application/x-tar'
        ];
        
        return in_array($mime_type, $allowed_types);
    }
    
    /**
     * تنصيب حزمة لغة
     */
    public function installPackage($package_name) {
        try {
            // بدء عملية التنصيب
            $this->updatePackageStatus($package_name, 'installing');
            $this->logPackageAction($package_name, 'install', 'started');
            
            // الحصول على معلومات الحزمة
            $package_info = $this->getPackageInfo($package_name);
            if (!$package_info) {
                throw new Exception("Package not found: {$package_name}");
            }
            
            // التحقق من وجود ملف الحزمة
            if (!file_exists($package_info['file_path'])) {
                throw new Exception("Package file not found: {$package_info['file_path']}");
            }
            
            // إنشاء نسخة احتياطية إذا كانت اللغة منصبة مسبقاً
            if ($package_info['is_installed']) {
                $this->createLanguageBackup($package_info['language_code']);
            }
            
            // استخراج الحزمة
            $extracted_path = $this->extractPackage($package_info['file_path'], $package_name);
            if (!$extracted_path) {
                throw new Exception("Failed to extract package: {$package_name}");
            }
            
            // قراءة ملف معلومات الحزمة
            $package_manifest = $this->readPackageManifest($extracted_path);
            if (!$package_manifest) {
                throw new Exception("Invalid package manifest: {$package_name}");
            }
            
            // التحقق من توافق الحزمة
            if (!$this->validatePackageCompatibility($package_manifest)) {
                throw new Exception("Package is not compatible with current system version");
            }
            
            // تنصيب الترجمات
            $this->installTranslations($package_manifest, $extracted_path);
            
            // تحديث معلومات اللغة
            $this->updateLanguageInfo($package_manifest);
            
            // تنظيف الملفات المؤقتة
            $this->cleanupTempFiles($extracted_path);
            
            // تحديث حالة الحزمة
            $this->updatePackageInfo($package_name, [
                'is_installed' => 1,
                'install_date' => date('Y-m-d H:i:s'),
                'status' => 'installed'
            ]);
            
            $this->logPackageAction($package_name, 'install', 'completed');
            
            return [
                'success' => true,
                'message' => 'Package installed successfully',
                'language_code' => $package_manifest['language_code']
            ];
            
        } catch (Exception $e) {
            $this->updatePackageStatus($package_name, 'failed', $e->getMessage());
            $this->logPackageAction($package_name, 'install', 'failed', $e->getMessage());
            
            // تنظيف الملفات المؤقتة في حالة الفشل
            if (isset($extracted_path)) {
                $this->cleanupTempFiles($extracted_path);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * استخراج حزمة اللغة
     */
    private function extractPackage($package_file, $package_name) {
        try {
            $extract_path = $this->temp_dir . $package_name . '_extract_' . time() . '/';
            
            if (!mkdir($extract_path, 0755, true)) {
                return false;
            }
            
            $zip = new ZipArchive();
            $result = $zip->open($package_file);
            
            if ($result !== TRUE) {
                rmdir($extract_path);
                return false;
            }
            
            $zip->extractTo($extract_path);
            $zip->close();
            
            return $extract_path;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error extracting package - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * قراءة ملف معلومات الحزمة
     */
    private function readPackageManifest($extracted_path) {
        try {
            $manifest_file = $extracted_path . 'manifest.json';
            
            if (!file_exists($manifest_file)) {
                // البحث عن ملفات بديلة
                $alternative_files = ['package.json', 'info.json', 'language.json'];
                foreach ($alternative_files as $alt_file) {
                    if (file_exists($extracted_path . $alt_file)) {
                        $manifest_file = $extracted_path . $alt_file;
                        break;
                    }
                }
            }
            
            if (!file_exists($manifest_file)) {
                return false;
            }
            
            $manifest_content = file_get_contents($manifest_file);
            $manifest = json_decode($manifest_content, true);
            
            if (!$manifest) {
                return false;
            }
            
            // التحقق من الحقول المطلوبة
            $required_fields = ['language_code', 'name', 'version', 'translations'];
            foreach ($required_fields as $field) {
                if (!isset($manifest[$field])) {
                    return false;
                }
            }
            
            return $manifest;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error reading package manifest - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من توافق الحزمة
     */
    private function validatePackageCompatibility($manifest) {
        try {
            // التحقق من إصدار النظام
            if (isset($manifest['min_cms_version'])) {
                $current_version = defined('FMC_VERSION') ? FMC_VERSION : '1.0.0';
                if (version_compare($current_version, $manifest['min_cms_version'], '<')) {
                    return false;
                }
            }
            
            // التحقق من إصدار PHP
            if (isset($manifest['min_php_version'])) {
                if (version_compare(PHP_VERSION, $manifest['min_php_version'], '<')) {
                    return false;
                }
            }
            
            // التحقق من الامتدادات المطلوبة
            if (isset($manifest['required_extensions'])) {
                foreach ($manifest['required_extensions'] as $extension) {
                    if (!extension_loaded($extension)) {
                        return false;
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error validating compatibility - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تنصيب الترجمات
     */
    private function installTranslations($manifest, $extracted_path) {
        try {
            $language_code = $manifest['language_code'];
            
            // الحصول على معرف اللغة أو إنشاؤها
            $language_id = $this->getOrCreateLanguage($manifest);
            
            // تنصيب مجموعات الترجمة
            foreach ($manifest['translations'] as $group_key => $translations) {
                // الحصول على معرف المجموعة أو إنشاؤها
                $group_id = $this->getOrCreateTranslationGroup($group_key, $manifest);
                
                // تنصيب مفاتيح الترجمة
                foreach ($translations as $key_name => $translation_data) {
                    $this->installTranslationKey($group_id, $key_name, $translation_data, $language_id);
                }
            }
            
            // تنصيب ملفات إضافية إذا وجدت
            $this->installAdditionalFiles($manifest, $extracted_path);
            
            return true;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error installing translations - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * الحصول على معرف اللغة أو إنشاؤها
     */
    private function getOrCreateLanguage($manifest) {
        try {
            $language_code = $manifest['language_code'];
            
            // البحث عن اللغة الموجودة
            $stmt = $this->db->prepare("SELECT id FROM fmc_languages WHERE code = ?");
            $stmt->execute([$language_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            // إنشاء لغة جديدة
            $stmt = $this->db->prepare("
                INSERT INTO fmc_languages 
                (code, name, native_name, flag_icon, direction, is_active, is_installed, version, author, description)
                VALUES (?, ?, ?, ?, ?, 1, 1, ?, ?, ?)
            ");
            
            $stmt->execute([
                $language_code,
                $manifest['name'],
                $manifest['native_name'] ?? $manifest['name'],
                $manifest['flag_icon'] ?? 'flag-' . $language_code,
                $manifest['direction'] ?? 'ltr',
                $manifest['version'],
                $manifest['author'] ?? '',
                $manifest['description'] ?? ''
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error creating language - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * الحصول على معرف مجموعة الترجمة أو إنشاؤها
     */
    private function getOrCreateTranslationGroup($group_key, $manifest) {
        try {
            // البحث عن المجموعة الموجودة
            $stmt = $this->db->prepare("SELECT id FROM fmc_translation_groups WHERE group_key = ?");
            $stmt->execute([$group_key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            // إنشاء مجموعة جديدة
            $group_info = $manifest['groups'][$group_key] ?? [];
            
            $stmt = $this->db->prepare("
                INSERT INTO fmc_translation_groups 
                (group_key, name, description, module, is_system)
                VALUES (?, ?, ?, ?, 0)
            ");
            
            $stmt->execute([
                $group_key,
                $group_info['name'] ?? ucfirst($group_key),
                $group_info['description'] ?? '',
                $group_info['module'] ?? 'custom'
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error creating translation group - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * تنصيب مفتاح ترجمة
     */
    private function installTranslationKey($group_id, $key_name, $translation_data, $language_id) {
        try {
            // الحصول على معرف المفتاح أو إنشاؤه
            $stmt = $this->db->prepare("SELECT id FROM fmc_translation_keys WHERE key_name = ? AND group_id = ?");
            $stmt->execute([$key_name, $group_id]);
            $key_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$key_result) {
                // إنشاء مفتاح جديد
                $stmt = $this->db->prepare("
                    INSERT INTO fmc_translation_keys 
                    (key_name, group_id, default_value, description, context, is_system)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                
                $default_value = is_array($translation_data) ? $translation_data['default'] ?? $key_name : $key_name;
                $description = is_array($translation_data) ? $translation_data['description'] ?? '' : '';
                $context = is_array($translation_data) ? $translation_data['context'] ?? '' : '';
                
                $stmt->execute([$key_name, $group_id, $default_value, $description, $context]);
                $key_id = $this->db->lastInsertId();
            } else {
                $key_id = $key_result['id'];
            }
            
            // إضافة أو تحديث الترجمة
            $translation_value = is_array($translation_data) ? $translation_data['value'] : $translation_data;
            
            $stmt = $this->db->prepare("
                INSERT INTO fmc_translations 
                (key_id, language_id, translated_value, is_approved)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                translated_value = VALUES(translated_value),
                is_approved = 1,
                updated_at = NOW()
            ");
            
            $stmt->execute([$key_id, $language_id, $translation_value]);
            
            return true;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error installing translation key - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * تنصيب ملفات إضافية
     */
    private function installAdditionalFiles($manifest, $extracted_path) {
        try {
            if (!isset($manifest['additional_files'])) {
                return;
            }
            
            foreach ($manifest['additional_files'] as $file_info) {
                $source_file = $extracted_path . $file_info['source'];
                $destination_file = __DIR__ . '/../../' . $file_info['destination'];
                
                if (file_exists($source_file)) {
                    $destination_dir = dirname($destination_file);
                    if (!is_dir($destination_dir)) {
                        mkdir($destination_dir, 0755, true);
                    }
                    
                    copy($source_file, $destination_file);
                }
            }
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error installing additional files - " . $e->getMessage());
        }
    }
    
    /**
     * تحديث معلومات اللغة
     */
    private function updateLanguageInfo($manifest) {
        try {
            $stmt = $this->db->prepare("
                UPDATE fmc_languages 
                SET name = ?, native_name = ?, flag_icon = ?, direction = ?, 
                    version = ?, author = ?, description = ?, last_updated = NOW()
                WHERE code = ?
            ");
            
            $stmt->execute([
                $manifest['name'],
                $manifest['native_name'] ?? $manifest['name'],
                $manifest['flag_icon'] ?? 'flag-' . $manifest['language_code'],
                $manifest['direction'] ?? 'ltr',
                $manifest['version'],
                $manifest['author'] ?? '',
                $manifest['description'] ?? '',
                $manifest['language_code']
            ]);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error updating language info - " . $e->getMessage());
        }
    }
    
    /**
     * إلغاء تنصيب حزمة لغة
     */
    public function uninstallPackage($package_name) {
        try {
            // بدء عملية إلغاء التنصيب
            $this->logPackageAction($package_name, 'uninstall', 'started');
            
            // الحصول على معلومات الحزمة
            $package_info = $this->getPackageInfo($package_name);
            if (!$package_info) {
                throw new Exception("Package not found: {$package_name}");
            }
            
            // إنشاء نسخة احتياطية
            $this->createLanguageBackup($package_info['language_code']);
            
            // حذف الترجمات
            $this->removeLanguageTranslations($package_info['language_code']);
            
            // تعطيل اللغة
            $this->deactivateLanguage($package_info['language_code']);
            
            // تحديث حالة الحزمة
            $this->updatePackageInfo($package_name, [
                'is_installed' => 0,
                'status' => 'available'
            ]);
            
            $this->logPackageAction($package_name, 'uninstall', 'completed');
            
            return [
                'success' => true,
                'message' => 'Package uninstalled successfully'
            ];
            
        } catch (Exception $e) {
            $this->logPackageAction($package_name, 'uninstall', 'failed', $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * إنشاء نسخة احتياطية للغة
     */
    private function createLanguageBackup($language_code) {
        try {
            if (!$this->getSetting('backup_before_update', true)) {
                return;
            }
            
            $backup_file = $this->backup_dir . $language_code . '_backup_' . date('Y-m-d_H-i-s') . '.json';
            
            // تصدير الترجمات
            $translations = $this->language_engine->exportLanguageTranslations($language_code, 'json');
            
            if ($translations) {
                file_put_contents($backup_file, $translations);
            }
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error creating backup - " . $e->getMessage());
        }
    }
    
    /**
     * حذف ترجمات اللغة
     */
    private function removeLanguageTranslations($language_code) {
        try {
            $stmt = $this->db->prepare("
                DELETE t FROM fmc_translations t
                JOIN fmc_languages l ON t.language_id = l.id
                WHERE l.code = ?
            ");
            $stmt->execute([$language_code]);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error removing translations - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * تعطيل اللغة
     */
    private function deactivateLanguage($language_code) {
        try {
            $stmt = $this->db->prepare("
                UPDATE fmc_languages 
                SET is_active = 0, is_installed = 0 
                WHERE code = ?
            ");
            $stmt->execute([$language_code]);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error deactivating language - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * تنظيف الملفات المؤقتة
     */
    private function cleanupTempFiles($path) {
        try {
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            }
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error cleaning temp files - " . $e->getMessage());
        }
    }
    
    /**
     * حذف مجلد بالكامل
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * الحصول على معلومات الحزمة
     */
    private function getPackageInfo($package_name) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM fmc_language_packages WHERE package_name = ?");
            $stmt->execute([$package_name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error getting package info - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * تحديث معلومات الحزمة
     */
    private function updatePackageInfo($package_name, $data) {
        try {
            $set_clauses = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $set_clauses[] = "{$key} = ?";
                $params[] = $value;
            }
            
            $params[] = $package_name;
            
            $sql = "UPDATE fmc_language_packages SET " . implode(', ', $set_clauses) . " WHERE package_name = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error updating package info - " . $e->getMessage());
        }
    }
    
    /**
     * تحديث حالة الحزمة
     */
    private function updatePackageStatus($package_name, $status, $error_message = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE fmc_language_packages 
                SET status = ?, error_message = ?, updated_at = NOW()
                WHERE package_name = ?
            ");
            $stmt->execute([$status, $error_message, $package_name]);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error updating package status - " . $e->getMessage());
        }
    }
    
    /**
     * تسجيل إجراء الحزمة
     */
    private function logPackageAction($package_name, $action, $status, $error_message = null) {
        try {
            $package_info = $this->getPackageInfo($package_name);
            $package_id = $package_info ? $package_info['id'] : null;
            
            $language_id = null;
            if ($package_info && $package_info['language_code']) {
                $stmt = $this->db->prepare("SELECT id FROM fmc_languages WHERE code = ?");
                $stmt->execute([$package_info['language_code']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $language_id = $result ? $result['id'] : null;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO fmc_language_update_log 
                (language_id, package_id, action, status, error_message, performed_by, started_at, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            $completed_at = ($status === 'completed' || $status === 'failed') ? date('Y-m-d H:i:s') : null;
            $user_id = $_SESSION['user_id'] ?? null;
            
            $stmt->execute([
                $language_id,
                $package_id,
                $action,
                $status,
                $error_message,
                $user_id,
                $completed_at
            ]);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error logging package action - " . $e->getMessage());
        }
    }
    
    /**
     * الحصول على قائمة الحزم المنصبة
     */
    public function getInstalledPackages() {
        try {
            $stmt = $this->db->prepare("
                SELECT lp.*, l.name as language_name, l.native_name, l.is_active
                FROM fmc_language_packages lp
                LEFT JOIN fmc_languages l ON lp.language_code = l.code
                WHERE lp.is_installed = 1
                ORDER BY l.is_active DESC, lp.package_name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error getting installed packages - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * التحقق من وجود تحديثات
     */
    public function checkForUpdates() {
        try {
            $installed_packages = $this->getInstalledPackages();
            $updates_available = [];
            
            foreach ($installed_packages as $package) {
                $latest_version = $this->getLatestPackageVersion($package['package_name']);
                
                if ($latest_version && version_compare($latest_version, $package['version'], '>')) {
                    $updates_available[] = [
                        'package_name' => $package['package_name'],
                        'current_version' => $package['version'],
                        'latest_version' => $latest_version,
                        'language_code' => $package['language_code']
                    ];
                }
            }
            
            return $updates_available;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error checking for updates - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على أحدث إصدار للحزمة
     */
    private function getLatestPackageVersion($package_name) {
        try {
            $url = $this->repository_url . 'version.php?package=' . urlencode($package_name);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'FinalMaxCMS-LanguageManager/1.0'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            return $data && isset($data['version']) ? $data['version'] : null;
        } catch (Exception $e) {
            error_log("LanguagePackageManager: Error getting latest version - " . $e->getMessage());
            return null;
        }
    }
}
?>

