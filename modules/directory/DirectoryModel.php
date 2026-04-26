<?php
/**
 * Final Max CMS - Directory Model
 * نموذج دليل المواقع
 */

require_once '../core/Model.php';

class DirectoryModel extends Model {
    
    /**
     * الحصول على قائمة المواقع
     */
    public function getWebsites($params = []) {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $category_id = $params['category_id'] ?? null;
        $search = $params['search'] ?? '';
        $sort = $params['sort'] ?? 'latest';
        $status = $params['status'] ?? 'approved';
        $user_id = $params['user_id'] ?? null;
        
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["w.status = ?"];
        $params_array = [$status];
        
        if ($category_id) {
            $where_conditions[] = "w.category_id = ?";
            $params_array[] = $category_id;
        }
        
        if ($user_id) {
            $where_conditions[] = "w.user_id = ?";
            $params_array[] = $user_id;
        }
        
        if ($search) {
            $where_conditions[] = "(w.title LIKE ? OR w.description LIKE ? OR w.tags LIKE ?)";
            $search_term = "%{$search}%";
            $params_array[] = $search_term;
            $params_array[] = $search_term;
            $params_array[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $order_clause = match($sort) {
            'popular' => 'ORDER BY w.views DESC',
            'rating' => 'ORDER BY w.rating DESC, w.reviews_count DESC',
            'alphabetical' => 'ORDER BY w.title ASC',
            'oldest' => 'ORDER BY w.created_at ASC',
            default => 'ORDER BY w.created_at DESC'
        };
        
        $sql = "
            SELECT w.*, c.name as category_name, c.slug as category_slug,
                   u.username, u.avatar,
                   COUNT(r.id) as total_reviews
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            LEFT JOIN directory_reviews r ON w.id = r.website_id AND r.status = 'approved'
            WHERE {$where_clause}
            GROUP BY w.id
            {$order_clause}
            LIMIT ? OFFSET ?
        ";
        
        $params_array[] = $limit;
        $params_array[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params_array);
        $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // الحصول على العدد الإجمالي
        $count_params = array_slice($params_array, 0, -2);
        $count_sql = "
            SELECT COUNT(DISTINCT w.id)
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE {$where_clause}
        ";
        
        $stmt = $this->db->prepare($count_sql);
        $stmt->execute($count_params);
        $total = $stmt->fetchColumn();
        
        return [
            'websites' => $websites,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * الحصول على موقع بواسطة ID
     */
    public function getWebsiteById($id) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, c.slug as category_slug,
                   u.username, u.avatar, u.email as user_email
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على موقع بواسطة Slug
     */
    public function getWebsiteBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, c.slug as category_slug,
                   u.username, u.avatar, u.email as user_email
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.slug = ?
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * إضافة موقع جديد
     */
    public function addWebsite($data) {
        $stmt = $this->db->prepare("
            INSERT INTO directory_websites (
                user_id, category_id, title, slug, url, description, 
                screenshot, tags, contact_email, language, is_free, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['slug'],
            $data['url'],
            $data['description'],
            $data['screenshot'] ?? null,
            $data['tags'],
            $data['contact_email'],
            $data['language'],
            $data['is_free'],
            $data['status']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * تحديث موقع
     */
    public function updateWebsite($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        $params[] = $id;
        
        $sql = "UPDATE directory_websites SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * حذف موقع
     */
    public function deleteWebsite($id) {
        $stmt = $this->db->prepare("DELETE FROM directory_websites WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * زيادة عدد المشاهدات
     */
    public function incrementViews($id) {
        $stmt = $this->db->prepare("UPDATE directory_websites SET views = views + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * التحقق من وجود موقع
     */
    public function websiteExists($url) {
        $stmt = $this->db->prepare("SELECT id FROM directory_websites WHERE url = ?");
        $stmt->execute([$url]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * إنشاء slug فريد
     */
    public function generateSlug($title) {
        $slug = create_slug($title);
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * التحقق من وجود slug
     */
    private function slugExists($slug) {
        $stmt = $this->db->prepare("SELECT id FROM directory_websites WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * الحصول على الفئات
     */
    public function getCategories() {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(w.id) as websites_count
            FROM directory_categories c
            LEFT JOIN directory_websites w ON c.id = w.category_id AND w.status = 'approved'
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على فئة بواسطة Slug
     */
    public function getCategoryBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM directory_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على المواقع المميزة
     */
    public function getFeaturedWebsites($limit = 6) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, c.slug as category_slug,
                   u.username
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.status = 'approved' AND w.is_featured = 1
            ORDER BY w.featured_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على المواقع ذات الصلة
     */
    public function getRelatedWebsites($website_id, $category_id, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, u.username
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.status = 'approved' AND w.id != ? AND w.category_id = ?
            ORDER BY w.rating DESC, w.views DESC
            LIMIT ?
        ");
        $stmt->execute([$website_id, $category_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * البحث في المواقع
     */
    public function searchWebsites($query, $category_id = null, $limit = 10) {
        $where_conditions = ["w.status = 'approved'"];
        $params = [];
        
        $where_conditions[] = "(w.title LIKE ? OR w.description LIKE ? OR w.tags LIKE ?)";
        $search_term = "%{$query}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        
        if ($category_id) {
            $where_conditions[] = "w.category_id = ?";
            $params[] = $category_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $params[] = $limit;
        
        $stmt = $this->db->prepare("
            SELECT w.id, w.title, w.slug, w.url, w.description, w.screenshot,
                   w.rating, w.views, c.name as category_name
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            WHERE {$where_clause}
            ORDER BY w.rating DESC, w.views DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إضافة تقييم
     */
    public function addReview($data) {
        $stmt = $this->db->prepare("
            INSERT INTO directory_reviews (
                website_id, user_id, rating, comment, status, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $data['website_id'],
            $data['user_id'],
            $data['rating'],
            $data['comment'],
            $data['status']
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * الحصول على تقييمات موقع
     */
    public function getWebsiteReviews($website_id, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.avatar
            FROM directory_reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.website_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$website_id, $limit, $offset]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // الحصول على العدد الإجمالي
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM directory_reviews 
            WHERE website_id = ? AND status = 'approved'
        ");
        $stmt->execute([$website_id]);
        $total = $stmt->fetchColumn();
        
        return [
            'reviews' => $reviews,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * الحصول على تقييم المستخدم
     */
    public function getUserReview($website_id, $user_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM directory_reviews 
            WHERE website_id = ? AND user_id = ?
        ");
        $stmt->execute([$website_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديث تقييم الموقع
     */
    public function updateWebsiteRating($website_id) {
        $stmt = $this->db->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as reviews_count
            FROM directory_reviews 
            WHERE website_id = ? AND status = 'approved'
        ");
        $stmt->execute([$website_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $avg_rating = round($result['avg_rating'], 1);
        $reviews_count = $result['reviews_count'];
        
        $stmt = $this->db->prepare("
            UPDATE directory_websites 
            SET rating = ?, reviews_count = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$avg_rating, $reviews_count, $website_id]);
    }
    
    /**
     * الحصول على إحصائيات الدليل
     */
    public function getStats() {
        $stats = [];
        
        // إجمالي المواقع
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM directory_websites WHERE status = 'approved'");
        $stmt->execute();
        $stats['total_websites'] = $stmt->fetchColumn();
        
        // إجمالي الفئات
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM directory_categories");
        $stmt->execute();
        $stats['total_categories'] = $stmt->fetchColumn();
        
        // إجمالي التقييمات
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM directory_reviews WHERE status = 'approved'");
        $stmt->execute();
        $stats['total_reviews'] = $stmt->fetchColumn();
        
        // إجمالي المشاهدات
        $stmt = $this->db->prepare("SELECT SUM(views) FROM directory_websites WHERE status = 'approved'");
        $stmt->execute();
        $stats['total_views'] = $stmt->fetchColumn() ?: 0;
        
        // أحدث المواقع
        $stmt = $this->db->prepare("
            SELECT w.title, w.slug, w.created_at, c.name as category_name
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            WHERE w.status = 'approved'
            ORDER BY w.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['recent_websites'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // أعلى المواقع تقييماً
        $stmt = $this->db->prepare("
            SELECT w.title, w.slug, w.rating, w.reviews_count, c.name as category_name
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            WHERE w.status = 'approved' AND w.reviews_count > 0
            ORDER BY w.rating DESC, w.reviews_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_rated'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * الحصول على المواقع الشائعة
     */
    public function getPopularWebsites($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, u.username
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.status = 'approved'
            ORDER BY w.views DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على أحدث المواقع
     */
    public function getLatestWebsites($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT w.*, c.name as category_name, u.username
            FROM directory_websites w
            LEFT JOIN directory_categories c ON w.category_id = c.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.status = 'approved'
            ORDER BY w.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

