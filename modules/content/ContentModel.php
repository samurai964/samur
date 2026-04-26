<?php

require_once ROOT_PATH . '/core/Model.php';

class ContentModel extends Model {
    
    // إنشاء قسم جديد
    public function createCategory($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}categories` 
                (`name`, `description`, `parent_id`, `sort_order`, `meta_title`, `meta_description`, `slug`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['parent_id'] ?? null,
                $data['sort_order'] ?? 0,
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? '',
                $data['slug'] ?? $this->generateSlug($data['name'])
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء القسم: ' . $e->getMessage()];
        }
    }

    // تحديث قسم
    public function updateCategory($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE `{$this->prefix}categories` 
                SET `name` = ?, `description` = ?, `parent_id` = ?, `sort_order` = ?, 
                    `meta_title` = ?, `meta_description` = ?, `slug` = ?
                WHERE `id` = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['parent_id'] ?? null,
                $data['sort_order'] ?? 0,
                $data['meta_title'] ?? $data['name'],
                $data['meta_description'] ?? '',
                $data['slug'] ?? $this->generateSlug($data['name']),
                $id
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث القسم: ' . $e->getMessage()];
        }
    }

    // حذف قسم
    public function deleteCategory($id) {
        try {
            // فحص إذا كان القسم يحتوي على مواضيع
            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}topics` WHERE `category_id` = ?");
            $checkStmt->execute([$id]);
            $topicCount = $checkStmt->fetchColumn();

            if ($topicCount > 0) {
                return ['success' => false, 'message' => 'لا يمكن حذف القسم لأنه يحتوي على مواضيع.'];
            }

            // فحص إذا كان القسم يحتوي على أقسام فرعية
            $checkSubStmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}categories` WHERE `parent_id` = ?");
            $checkSubStmt->execute([$id]);
            $subCategoryCount = $checkSubStmt->fetchColumn();

            if ($subCategoryCount > 0) {
                return ['success' => false, 'message' => 'لا يمكن حذف القسم لأنه يحتوي على أقسام فرعية.'];
            }

            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}categories` WHERE `id` = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف القسم: ' . $e->getMessage()];
        }
    }

    // إنشاء موضوع جديد
    public function createTopic($data, $authorId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}topics` 
                (`title`, `content`, `excerpt`, `category_id`, `author_id`, `status`, `featured`, 
                 `meta_title`, `meta_description`, `meta_keywords`, `slug`, `tags`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $data['content'],
                $data['excerpt'] ?? $this->generateExcerpt($data['content']),
                $data['category_id'],
                $authorId,
                $data['status'] ?? 'draft',
                $data['featured'] ?? 0,
                $data['meta_title'] ?? $data['title'],
                $data['meta_description'] ?? '',
                $data['meta_keywords'] ?? '',
                $data['slug'] ?? $this->generateSlug($data['title']),
                $data['tags'] ?? ''
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء الموضوع: ' . $e->getMessage()];
        }
    }

    // تحديث موضوع
    public function updateTopic($id, $data, $authorId = null) {
        try {
            $setSql = [];
            $params = [];
            
            if (isset($data['title'])) { $setSql[] = '`title` = ?'; $params[] = $data['title']; }
            if (isset($data['content'])) { $setSql[] = '`content` = ?'; $params[] = $data['content']; }
            if (isset($data['excerpt'])) { $setSql[] = '`excerpt` = ?'; $params[] = $data['excerpt']; }
            if (isset($data['category_id'])) { $setSql[] = '`category_id` = ?'; $params[] = $data['category_id']; }
            if (isset($data['status'])) { $setSql[] = '`status` = ?'; $params[] = $data['status']; }
            if (isset($data['featured'])) { $setSql[] = '`featured` = ?'; $params[] = $data['featured']; }
            if (isset($data['meta_title'])) { $setSql[] = '`meta_title` = ?'; $params[] = $data['meta_title']; }
            if (isset($data['meta_description'])) { $setSql[] = '`meta_description` = ?'; $params[] = $data['meta_description']; }
            if (isset($data['meta_keywords'])) { $setSql[] = '`meta_keywords` = ?'; $params[] = $data['meta_keywords']; }
            if (isset($data['slug'])) { $setSql[] = '`slug` = ?'; $params[] = $data['slug']; }
            if (isset($data['tags'])) { $setSql[] = '`tags` = ?'; $params[] = $data['tags']; }
            
            $setSql[] = '`updated_at` = NOW()';
            $params[] = $id;

            $whereClause = "WHERE `id` = ?";
            if ($authorId) {
                $whereClause .= " AND `author_id` = ?";
                $params[] = $authorId;
            }

            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}topics` SET " . implode(', ', $setSql) . " $whereClause");
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث الموضوع: ' . $e->getMessage()];
        }
    }

    // حذف موضوع
    public function deleteTopic($id, $authorId = null) {
        try {
            $whereClause = "WHERE `id` = ?";
            $params = [$id];
            
            if ($authorId) {
                $whereClause .= " AND `author_id` = ?";
                $params[] = $authorId;
            }

            // حذف التعليقات المرتبطة
            $this->pdo->prepare("DELETE FROM `{$this->prefix}comments` WHERE `topic_id` = ?")->execute([$id]);
            
            // حذف الإعجابات المرتبطة
            $this->pdo->prepare("DELETE FROM `{$this->prefix}topic_likes` WHERE `topic_id` = ?")->execute([$id]);

            // حذف الموضوع
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}topics` $whereClause");
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف الموضوع: ' . $e->getMessage()];
        }
    }

    // جلب موضوع بالمعرف
    public function getTopicById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, u.username as author_name, c.name as category_name 
                FROM `{$this->prefix}topics` t 
                LEFT JOIN `{$this->prefix}users` u ON t.author_id = u.id 
                LEFT JOIN `{$this->prefix}categories` c ON t.category_id = c.id 
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب قسم بالمعرف
    public function getCategoryById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}categories` WHERE `id` = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب جميع الأقسام
    public function getAllCategories() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM `{$this->prefix}categories` ORDER BY `sort_order`, `name`");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // إنشاء تعليق
    public function createComment($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}comments` 
                (`topic_id`, `user_id`, `content`, `parent_id`, `status`) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['topic_id'],
                $data['user_id'],
                $data['content'],
                $data['parent_id'] ?? null,
                $data['status'] ?? 'pending'
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء التعليق: ' . $e->getMessage()];
        }
    }

    // تحديث حالة تعليق
    public function updateCommentStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}comments` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث التعليق: ' . $e->getMessage()];
        }
    }

    // حذف تعليق
    public function deleteComment($id) {
        try {
            // حذف الردود أولاً
            $this->pdo->prepare("DELETE FROM `{$this->prefix}comments` WHERE `parent_id` = ?")->execute([$id]);
            
            // حذف التعليق الأصلي
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}comments` WHERE `id` = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف التعليق: ' . $e->getMessage()];
        }
    }

    // توليد slug من النص
    private function generateSlug($text) {
        // تحويل النص العربي إلى slug
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\u0600-\u06FF\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // إضافة timestamp إذا كان الـ slug فارغاً
        if (empty($slug)) {
            $slug = 'topic-' . time();
        }
        
        return $slug;
    }

    // توليد مقتطف من المحتوى
    private function generateExcerpt($content, $length = 200) {
        $excerpt = strip_tags($content);
        if (strlen($excerpt) > $length) {
            $excerpt = substr($excerpt, 0, $length) . '...';
        }
        return $excerpt;
    }

    // إحصائيات المحتوى
    public function getContentStats() {
        try {
            $stats = [];
            
            // عدد المواضيع
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}topics`");
            $stats['total_topics'] = $stmt->fetchColumn();
            
            // عدد المواضيع المنشورة
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}topics` WHERE `status` = 'published'");
            $stats['published_topics'] = $stmt->fetchColumn();
            
            // عدد الأقسام
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}categories`");
            $stats['total_categories'] = $stmt->fetchColumn();
            
            // عدد التعليقات
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}comments`");
            $stats['total_comments'] = $stmt->fetchColumn();
            
            // عدد التعليقات المعتمدة
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}comments` WHERE `status` = 'approved'");
            $stats['approved_comments'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }
}

?>

