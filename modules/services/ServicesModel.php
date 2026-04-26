<?php

require_once ROOT_PATH . '/core/Model.php';

class ServicesModel extends Model {
    
    // إنشاء خدمة جديدة
    public function createService($data, $sellerId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}services` 
                (`title`, `description`, `category_id`, `seller_id`, `price`, `delivery_time`, 
                 `image`, `gallery`, `tags`, `requirements`, `status`, `featured`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['category_id'],
                $sellerId,
                $data['price'],
                $data['delivery_time'],
                $data['image'] ?? '',
                $data['gallery'] ?? '',
                $data['tags'] ?? '',
                $data['requirements'] ?? '',
                $data['status'] ?? 'pending',
                $data['featured'] ?? 0
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء الخدمة: ' . $e->getMessage()];
        }
    }

    // تحديث خدمة
    public function updateService($id, $data, $sellerId = null) {
        try {
            $setSql = [];
            $params = [];
            
            if (isset($data['title'])) { $setSql[] = '`title` = ?'; $params[] = $data['title']; }
            if (isset($data['description'])) { $setSql[] = '`description` = ?'; $params[] = $data['description']; }
            if (isset($data['category_id'])) { $setSql[] = '`category_id` = ?'; $params[] = $data['category_id']; }
            if (isset($data['price'])) { $setSql[] = '`price` = ?'; $params[] = $data['price']; }
            if (isset($data['delivery_time'])) { $setSql[] = '`delivery_time` = ?'; $params[] = $data['delivery_time']; }
            if (isset($data['image'])) { $setSql[] = '`image` = ?'; $params[] = $data['image']; }
            if (isset($data['gallery'])) { $setSql[] = '`gallery` = ?'; $params[] = $data['gallery']; }
            if (isset($data['tags'])) { $setSql[] = '`tags` = ?'; $params[] = $data['tags']; }
            if (isset($data['requirements'])) { $setSql[] = '`requirements` = ?'; $params[] = $data['requirements']; }
            if (isset($data['status'])) { $setSql[] = '`status` = ?'; $params[] = $data['status']; }
            if (isset($data['featured'])) { $setSql[] = '`featured` = ?'; $params[] = $data['featured']; }
            
            $setSql[] = '`updated_at` = NOW()';
            $params[] = $id;

            $whereClause = "WHERE `id` = ?";
            if ($sellerId) {
                $whereClause .= " AND `seller_id` = ?";
                $params[] = $sellerId;
            }

            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}services` SET " . implode(', ', $setSql) . " $whereClause");
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث الخدمة: ' . $e->getMessage()];
        }
    }

    // حذف خدمة
    public function deleteService($id, $sellerId = null) {
        try {
            $whereClause = "WHERE `id` = ?";
            $params = [$id];
            
            if ($sellerId) {
                $whereClause .= " AND `seller_id` = ?";
                $params[] = $sellerId;
            }

            // حذف الباقات المرتبطة
            $this->pdo->prepare("DELETE FROM `{$this->prefix}service_packages` WHERE `service_id` = ?")->execute([$id]);
            
            // حذف المراجعات المرتبطة
            $this->pdo->prepare("DELETE FROM `{$this->prefix}service_reviews` WHERE `service_id` = ?")->execute([$id]);
            
            // حذف الطلبات المرتبطة (أو تحديث حالتها)
            $this->pdo->prepare("UPDATE `{$this->prefix}service_orders` SET `status` = 'cancelled' WHERE `service_id` = ? AND `status` IN ('pending', 'in_progress')")->execute([$id]);

            // حذف الخدمة
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}services` $whereClause");
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في حذف الخدمة: ' . $e->getMessage()];
        }
    }

    // إنشاء باقة خدمة
    public function createServicePackage($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}service_packages` 
                (`service_id`, `name`, `description`, `price`, `delivery_time`, `features`) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['service_id'],
                $data['name'],
                $data['description'],
                $data['price'],
                $data['delivery_time'],
                $data['features'] ?? ''
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء الباقة: ' . $e->getMessage()];
        }
    }

    // إنشاء فئة خدمة
    public function createServiceCategory($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}service_categories` 
                (`name`, `description`, `parent_id`, `icon`, `sort_order`) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['parent_id'] ?? null,
                $data['icon'] ?? '',
                $data['sort_order'] ?? 0
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء الفئة: ' . $e->getMessage()];
        }
    }

    // إنشاء طلب خدمة
    public function createOrder($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}service_orders` 
                (`service_id`, `package_id`, `buyer_id`, `seller_id`, `requirements`, 
                 `quantity`, `total_price`, `delivery_time`, `status`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['service_id'],
                $data['package_id'] ?? null,
                $data['buyer_id'],
                $data['seller_id'],
                $data['requirements'],
                $data['quantity'] ?? 1,
                $data['total_price'],
                $data['delivery_time'],
                $data['status'] ?? 'pending'
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إنشاء الطلب: ' . $e->getMessage()];
        }
    }

    // تحديث حالة طلب
    public function updateOrderStatus($id, $status, $userId = null, $userRole = null) {
        try {
            $whereClause = "WHERE `id` = ?";
            $params = [$status, $id];
            
            if ($userId && $userRole) {
                if ($userRole === 'buyer') {
                    $whereClause .= " AND `buyer_id` = ?";
                } elseif ($userRole === 'seller') {
                    $whereClause .= " AND `seller_id` = ?";
                }
                $params[] = $userId;
            }

            $stmt = $this->pdo->prepare("UPDATE `{$this->prefix}service_orders` SET `status` = ?, `updated_at` = NOW() $whereClause");
            $stmt->execute($params);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في تحديث حالة الطلب: ' . $e->getMessage()];
        }
    }

    // إضافة رسالة للطلب
    public function addOrderMessage($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}order_messages` 
                (`order_id`, `sender_id`, `message`, `attachment`) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['order_id'],
                $data['sender_id'],
                $data['message'],
                $data['attachment'] ?? ''
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة الرسالة: ' . $e->getMessage()];
        }
    }

    // إضافة مراجعة
    public function addReview($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO `{$this->prefix}service_reviews` 
                (`service_id`, `buyer_id`, `order_id`, `rating`, `review`) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['service_id'],
                $data['buyer_id'],
                $data['order_id'],
                $data['rating'],
                $data['review']
            ]);
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'خطأ في إضافة المراجعة: ' . $e->getMessage()];
        }
    }

    // جلب خدمة بالمعرف
    public function getServiceById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.username as seller_name, c.name as category_name,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM `{$this->prefix}services` s 
                LEFT JOIN `{$this->prefix}users` u ON s.seller_id = u.id 
                LEFT JOIN `{$this->prefix}service_categories` c ON s.category_id = c.id 
                LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                WHERE s.id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب طلب بالمعرف
    public function getOrderById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT o.*, s.title as service_title, sp.name as package_name,
                       buyer.username as buyer_name, seller.username as seller_name
                FROM `{$this->prefix}service_orders` o
                JOIN `{$this->prefix}services` s ON o.service_id = s.id
                LEFT JOIN `{$this->prefix}service_packages` sp ON o.package_id = sp.id
                JOIN `{$this->prefix}users` buyer ON o.buyer_id = buyer.id
                JOIN `{$this->prefix}users` seller ON o.seller_id = seller.id
                WHERE o.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    // جلب جميع فئات الخدمات
    public function getAllServiceCategories() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM `{$this->prefix}service_categories` ORDER BY `sort_order`, `name`");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب خدمات البائع
    public function getSellerServices($sellerId, $limit = null) {
        try {
            $sql = "SELECT s.*, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                    FROM `{$this->prefix}services` s 
                    LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                    WHERE s.seller_id = ?
                    GROUP BY s.id
                    ORDER BY s.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sellerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب طلبات البائع
    public function getSellerOrders($sellerId, $status = null, $limit = null) {
        try {
            $whereClause = "WHERE o.seller_id = ?";
            $params = [$sellerId];
            
            if ($status) {
                $whereClause .= " AND o.status = ?";
                $params[] = $status;
            }
            
            $sql = "SELECT o.*, s.title as service_title, u.username as buyer_name
                    FROM `{$this->prefix}service_orders` o
                    JOIN `{$this->prefix}services` s ON o.service_id = s.id
                    JOIN `{$this->prefix}users` u ON o.buyer_id = u.id
                    $whereClause
                    ORDER BY o.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // جلب طلبات المشتري
    public function getBuyerOrders($buyerId, $status = null, $limit = null) {
        try {
            $whereClause = "WHERE o.buyer_id = ?";
            $params = [$buyerId];
            
            if ($status) {
                $whereClause .= " AND o.status = ?";
                $params[] = $status;
            }
            
            $sql = "SELECT o.*, s.title as service_title, u.username as seller_name
                    FROM `{$this->prefix}service_orders` o
                    JOIN `{$this->prefix}services` s ON o.service_id = s.id
                    JOIN `{$this->prefix}users` u ON o.seller_id = u.id
                    $whereClause
                    ORDER BY o.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // إحصائيات الخدمات
    public function getServicesStats() {
        try {
            $stats = [];
            
            // عدد الخدمات
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}services`");
            $stats['total_services'] = $stmt->fetchColumn();
            
            // عدد الخدمات النشطة
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}services` WHERE `status` = 'active'");
            $stats['active_services'] = $stmt->fetchColumn();
            
            // عدد الطلبات
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}service_orders`");
            $stats['total_orders'] = $stmt->fetchColumn();
            
            // عدد الطلبات المكتملة
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}service_orders` WHERE `status` = 'completed'");
            $stats['completed_orders'] = $stmt->fetchColumn();
            
            // إجمالي الأرباح
            $stmt = $this->pdo->query("SELECT SUM(`total_price`) FROM `{$this->prefix}service_orders` WHERE `status` = 'completed'");
            $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
            
            // عدد البائعين النشطين
            $stmt = $this->pdo->query("SELECT COUNT(DISTINCT `seller_id`) FROM `{$this->prefix}services` WHERE `status` = 'active'");
            $stats['active_sellers'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }

    // البحث في الخدمات
    public function searchServices($query, $filters = [], $limit = 20, $offset = 0) {
        try {
            $whereClause = "WHERE s.status = 'active'";
            $params = [];
            
            if ($query) {
                $whereClause .= " AND (s.title LIKE ? OR s.description LIKE ? OR s.tags LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }
            
            if (isset($filters['category']) && $filters['category']) {
                $whereClause .= " AND s.category_id = ?";
                $params[] = $filters['category'];
            }
            
            if (isset($filters['min_price']) && $filters['min_price'] !== null) {
                $whereClause .= " AND s.price >= ?";
                $params[] = $filters['min_price'];
            }
            
            if (isset($filters['max_price']) && $filters['max_price'] !== null) {
                $whereClause .= " AND s.price <= ?";
                $params[] = $filters['max_price'];
            }

            $sql = "SELECT s.*, u.username as seller_name, c.name as category_name,
                           AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                    FROM `{$this->prefix}services` s 
                    LEFT JOIN `{$this->prefix}users` u ON s.seller_id = u.id 
                    LEFT JOIN `{$this->prefix}service_categories` c ON s.category_id = c.id 
                    LEFT JOIN `{$this->prefix}service_reviews` r ON s.id = r.service_id
                    $whereClause 
                    GROUP BY s.id
                    ORDER BY s.featured DESC, s.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

?>

