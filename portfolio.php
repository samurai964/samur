<?php
require_once __DIR__ . '/core/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: templates/frontend/auth/login.php');
    exit;
}

$current_user = getUserById($_SESSION['user_id']);

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_portfolio') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $project_url = trim($_POST['project_url'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        
        if (!empty($title) && !empty($description)) {
            // معالجة رفع الصور
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'assets/uploads/portfolio/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_extension, $allowed_extensions)) {
                            $new_filename = uniqid() . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                                $images[] = $upload_path;
                            }
                        }
                    }
                }
            }
            
            try {
                $pdo->beginTransaction();
                
                // إدراج العمل الجديد
                $stmt = $pdo->prepare("
                    INSERT INTO portfolio_items (user_id, title, description, category, project_url, technologies, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'published')
                ");
                $stmt->execute([
                    $_SESSION['user_id'], 
                    $title, 
                    $description, 
                    $category, 
                    $project_url, 
                    $technologies
                ]);
                
                $portfolio_id = $pdo->lastInsertId();
                
                // إدراج الصور
                if (!empty($images)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO portfolio_images (portfolio_id, image_path, is_primary) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($images as $index => $image_path) {
                        $is_primary = ($index === 0) ? 1 : 0;
                        $stmt->execute([$portfolio_id, $image_path, $is_primary]);
                    }
                }
                
                $pdo->commit();
                $success_message = 'تم إضافة العمل بنجاح';
                
                // تسجيل النشاط
                logActivity($_SESSION['user_id'], 'add_portfolio', "تم إضافة عمل جديد: $title");
                
            } catch (Exception $e) {
                $pdo->rollback();
                $error_message = 'حدث خطأ أثناء إضافة العمل';
            }
        } else {
            $error_message = 'يرجى ملء جميع الحقول المطلوبة';
        }
    }
    
    elseif ($action === 'delete_portfolio') {
        $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);
        
        if ($portfolio_id > 0) {
            // التحقق من ملكية العمل
            $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
            $portfolio = $stmt->fetch();
            
            if ($portfolio) {
                try {
                    $pdo->beginTransaction();
                    
                    // حذف الصور من الخادم
                    $stmt = $pdo->prepare("SELECT image_path FROM portfolio_images WHERE portfolio_id = ?");
                    $stmt->execute([$portfolio_id]);
                    $images = $stmt->fetchAll();
                    
                    foreach ($images as $image) {
                        if (file_exists($image['image_path'])) {
                            unlink($image['image_path']);
                        }
                    }
                    
                    // حذف الصور من قاعدة البيانات
                    $stmt = $pdo->prepare("DELETE FROM portfolio_images WHERE portfolio_id = ?");
                    $stmt->execute([$portfolio_id]);
                    
                    // حذف العمل
                    $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
                    $stmt->execute([$portfolio_id]);
                    
                    $pdo->commit();
                    $success_message = 'تم حذف العمل بنجاح';
                    
                    // تسجيل النشاط
                    logActivity($_SESSION['user_id'], 'delete_portfolio', "تم حذف العمل: {$portfolio['title']}");
                    
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error_message = 'حدث خطأ أثناء حذف العمل';
                }
            } else {
                $error_message = 'العمل غير موجود أو ليس لديك صلاحية لحذفه';
            }
        }
    }
    
    elseif ($action === 'like_portfolio') {
        $portfolio_id = (int)($_POST['portfolio_id'] ?? 0);
        
        if ($portfolio_id > 0) {
            // التحقق من وجود إعجاب سابق
            $stmt = $pdo->prepare("SELECT id FROM portfolio_likes WHERE portfolio_id = ? AND user_id = ?");
            $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
            $existing_like = $stmt->fetch();
            
            if ($existing_like) {
                // إزالة الإعجاب
                $stmt = $pdo->prepare("DELETE FROM portfolio_likes WHERE id = ?");
                $stmt->execute([$existing_like['id']]);
                $action_result = 'unliked';
            } else {
                // إضافة إعجاب
                $stmt = $pdo->prepare("INSERT INTO portfolio_likes (portfolio_id, user_id) VALUES (?, ?)");
                $stmt->execute([$portfolio_id, $_SESSION['user_id']]);
                $action_result = 'liked';
            }
            
            // إرجاع النتيجة كـ JSON للـ AJAX
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'action' => $action_result]);
                exit;
            }
        }
    }
}

// إعدادات البحث والفلترة
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$user_filter = $_GET['user'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// بناء استعلام البحث
$where_conditions = ["pi.status = 'published'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(pi.title LIKE ? OR pi.description LIKE ? OR pi.technologies LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category_filter)) {
    $where_conditions[] = "pi.category = ?";
    $params[] = $category_filter;
}

if (!empty($user_filter)) {
    $where_conditions[] = "pi.user_id = ?";
    $params[] = $user_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// جلب الأعمال
$stmt = $pdo->prepare("
    SELECT pi.*, u.username, u.first_name, u.last_name, u.avatar,
           (SELECT image_path FROM portfolio_images WHERE portfolio_id = pi.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id) as likes_count,
           (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id AND user_id = ?) as user_liked
    FROM portfolio_items pi
    JOIN users u ON pi.user_id = u.id
    $where_clause
    ORDER BY pi.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$params_with_user = array_merge([$_SESSION['user_id']], $params);
$stmt->execute($params_with_user);
$portfolio_items = $stmt->fetchAll();

// عدد الأعمال الإجمالي
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM portfolio_items pi 
    JOIN users u ON pi.user_id = u.id 
    $where_clause
");
$stmt->execute($params);
$total_items = $stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// جلب الفئات المتاحة
$categories = $pdo->query("
    SELECT DISTINCT category 
    FROM portfolio_items 
    WHERE status = 'published' AND category IS NOT NULL AND category != ''
    ORDER BY category
")->fetchAll(PDO::FETCH_COLUMN);

// جلب أعمال المستخدم الحالي
$user_portfolio = $pdo->prepare("
    SELECT pi.*, 
           (SELECT image_path FROM portfolio_images WHERE portfolio_id = pi.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM portfolio_likes WHERE portfolio_id = pi.id) as likes_count,
           (SELECT COUNT(*) FROM portfolio_views WHERE portfolio_id = pi.id) as views_count
    FROM portfolio_items pi
    WHERE pi.user_id = ?
    ORDER BY pi.created_at DESC
    LIMIT 6
");
$user_portfolio->execute([$_SESSION['user_id']]);
$my_portfolio = $user_portfolio->fetchAll();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معرض الأعمال - Final Max CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .portfolio-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .portfolio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .portfolio-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .portfolio-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portfolio-card:hover .portfolio-overlay {
            opacity: 1;
        }
        .portfolio-actions {
            display: flex;
            gap: 10px;
        }
        .portfolio-actions .btn {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portfolio-meta {
            padding: 1.5rem;
        }
        .portfolio-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }
        .portfolio-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .portfolio-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .portfolio-stats {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: #666;
        }
        .search-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .add-portfolio-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .add-portfolio-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .my-portfolio-section {
            background: #f8f9fa;
            padding: 2rem 0;
            margin-bottom: 3rem;
        }
        .category-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .like-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.1rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .like-btn.liked {
            color: #e74c3c;
        }
        .like-btn:hover {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- قسم البحث -->
    <section class="search-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h1><i class="fas fa-briefcase me-2"></i>معرض الأعمال</h1>
                    <p class="lead">اكتشف أعمال المبدعين وشارك إبداعاتك</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" class="form-control" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="البحث في الأعمال...">
                        <select class="form-select" name="category" style="max-width: 200px;">
                            <option value="">جميع الفئات</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                        <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-light">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- أعمالي -->
        <?php if (!empty($my_portfolio)): ?>
            <section class="my-portfolio-section">
                <div class="container">
                    <h3 class="mb-4"><i class="fas fa-user me-2"></i>أعمالي</h3>
                    <div class="row">
                        <?php foreach ($my_portfolio as $item): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="portfolio-card">
                                    <div class="portfolio-image" 
                                         style="background-image: url('<?php echo $item['primary_image'] ?: 'assets/images/default-portfolio.png'; ?>')">
                                        <div class="portfolio-overlay">
                                            <div class="portfolio-actions">
                                                <button class="btn btn-light" onclick="viewPortfolio(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning" onclick="editPortfolio(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_portfolio">
                                                    <input type="hidden" name="portfolio_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" 
                                                            onclick="return confirm('هل تريد حذف هذا العمل؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="portfolio-meta">
                                        <?php if (!empty($item['category'])): ?>
                                            <span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                                        <?php endif; ?>
                                        <h5 class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                        <p class="portfolio-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <div class="portfolio-stats">
                                            <span><i class="fas fa-heart me-1"></i><?php echo $item['likes_count']; ?></span>
                                            <span><i class="fas fa-eye me-1"></i><?php echo $item['views_count']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- جميع الأعمال -->
        <section>
            <h3 class="mb-4"><i class="fas fa-globe me-2"></i>جميع الأعمال</h3>
            
            <?php if (empty($portfolio_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-briefcase fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد أعمال حالياً</h4>
                    <p class="text-muted">كن أول من يشارك عمله في المعرض</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($portfolio_items as $item): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="portfolio-card">
                                <div class="portfolio-image" 
                                     style="background-image: url('<?php echo $item['primary_image'] ?: 'assets/images/default-portfolio.png'; ?>')">
                                    <div class="portfolio-overlay">
                                        <div class="portfolio-actions">
                                            <button class="btn btn-light" onclick="viewPortfolio(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!empty($item['project_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($item['project_url']); ?>" 
                                                   target="_blank" class="btn btn-info">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="portfolio-meta">
                                    <?php if (!empty($item['category'])): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                                    <?php endif; ?>
                                    <h5 class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <p class="portfolio-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                    
                                    <?php if (!empty($item['technologies'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-code me-1"></i>
                                                <?php echo htmlspecialchars($item['technologies']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="portfolio-footer">
                                        <div class="user-info">
                                            <img src="<?php echo $item['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                                 alt="الصورة الشخصية" class="user-avatar">
                                            <small><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></small>
                                        </div>
                                        <div class="portfolio-stats">
                                            <button class="like-btn <?php echo $item['user_liked'] ? 'liked' : ''; ?>" 
                                                    onclick="toggleLike(<?php echo $item['id']; ?>, this)">
                                                <i class="fas fa-heart"></i>
                                                <span class="like-count"><?php echo $item['likes_count']; ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- الترقيم -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="ترقيم الصفحات" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">السابق</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">التالي</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    
    <!-- زر إضافة عمل جديد -->
    <button class="add-portfolio-btn" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- مودال إضافة عمل جديد -->
    <div class="modal fade" id="addPortfolioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة عمل جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_portfolio">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">عنوان العمل *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة</label>
                                    <input type="text" class="form-control" name="category" 
                                           placeholder="مثل: تطوير ويب، تصميم جرافيك، إلخ">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">وصف العمل *</label>
                            <textarea class="form-control" name="description" rows="4" required
                                      placeholder="اكتب وصفاً مفصلاً عن العمل..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">رابط المشروع</label>
                                    <input type="url" class="form-control" name="project_url" 
                                           placeholder="https://example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">التقنيات المستخدمة</label>
                                    <input type="text" class="form-control" name="technologies" 
                                           placeholder="PHP, JavaScript, MySQL, إلخ">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">صور العمل</label>
                            <input type="file" class="form-control" name="images[]" multiple 
                                   accept="image/*">
                            <small class="form-text text-muted">
                                يمكنك رفع عدة صور. الصورة الأولى ستكون الصورة الرئيسية.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة العمل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // دالة الإعجاب
        function toggleLike(portfolioId, button) {
            fetch('portfolio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=like_portfolio&portfolio_id=${portfolioId}&ajax=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const likeCountSpan = button.querySelector('.like-count');
                    let currentCount = parseInt(likeCountSpan.textContent);
                    
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                        likeCountSpan.textContent = currentCount + 1;
                    } else {
                        button.classList.remove('liked');
                        likeCountSpan.textContent = Math.max(0, currentCount - 1);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // دالة عرض العمل
        function viewPortfolio(portfolioId) {
            // يمكن إضافة مودال لعرض تفاصيل العمل
            window.location.href = `portfolio_view.php?id=${portfolioId}`;
        }
        
        // دالة تعديل العمل
        function editPortfolio(portfolioId) {
            window.location.href = `portfolio_edit.php?id=${portfolioId}`;
        }
    </script>
</body>
</html>

