<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data["topic"]["title"]); ?> - Final Max CMS</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($data["topic"]["content"]), 0, 160)); ?>">
</head>
<body>
    <?php 
    $currentPage = 'topic_detail';
    include ROOT_PATH . '/templates/frontend/partials/header.php'; 
    ?>

    <main class="container">
        <!-- عرض الإعلانات أعلى المحتوى -->
        <?php displayInternalAds('content_top', 'topic_detail'); ?>

        <div class="row">
            <div class="col-8">
                <article class="topic-detail card fade-in">
                    <header class="topic-header">
                        <h1 class="topic-title"><?php echo htmlspecialchars($data["topic"]["title"]); ?></h1>
                        
                        <div class="topic-meta">
                            <div class="meta-row">
                                <div class="author-info">
                                    <img src="<?php echo $data["topic"]["author_avatar"] ?? '/assets/images/default-avatar.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($data["topic"]["author_name"]); ?>" 
                                         class="author-avatar">
                                    <div class="author-details">
                                        <span class="author-name">
                                            👤 <a href="/profile/<?php echo $data["topic"]["user_id"]; ?>">
                                                <?php echo htmlspecialchars($data["topic"]["author_name"]); ?>
                                            </a>
                                        </span>
                                        <span class="author-role"><?php echo $data["topic"]["author_role"] ?? 'عضو'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="topic-actions">
                                    <?php if (is_logged_in()): ?>
                                        <button class="btn btn-sm btn-outline like-btn" data-topic-id="<?php echo $data["topic"]["id"]; ?>">
                                            <span class="like-icon">👍</span>
                                            <span class="like-count"><?php echo $data["topic"]["likes"]; ?></span>
                                        </button>
                                        <button class="btn btn-sm btn-outline bookmark-btn" data-topic-id="<?php echo $data["topic"]["id"]; ?>">
                                            <span>🔖</span> حفظ
                                        </button>
                                        <button class="btn btn-sm btn-outline share-btn" data-topic-id="<?php echo $data["topic"]["id"]; ?>">
                                            <span>📤</span> مشاركة
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="meta-row">
                                <span class="category">
                                    📂 <a href="/topics?category=<?php echo $data["topic"]["category_id"]; ?>">
                                        <?php echo htmlspecialchars($data["topic"]["category_name"]); ?>
                                    </a>
                                </span>
                                <span class="date">
                                    🕒 <?php echo date("Y-m-d H:i", strtotime($data["topic"]["created_at"])); ?>
                                </span>
                                <?php if ($data["topic"]["updated_at"] != $data["topic"]["created_at"]): ?>
                                    <span class="updated">
                                        ✏️ آخر تحديث: <?php echo date("Y-m-d H:i", strtotime($data["topic"]["updated_at"])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="topic-stats">
                                <span class="stat">👁️ <?php echo number_format($data["topic"]["views"]); ?> مشاهدة</span>
                                <span class="stat">👍 <?php echo number_format($data["topic"]["likes"]); ?> إعجاب</span>
                                <span class="stat">💬 <?php echo count($data["comments"] ?? []); ?> تعليق</span>
                            </div>
                        </div>
                    </header>
                    
                    <div class="topic-content">
                        <?php echo nl2br(htmlspecialchars($data["topic"]["content"])); ?>
                    </div>
                    
                    <?php if (!empty($data["topic"]["tags"])): ?>
                        <div class="topic-tags">
                            <h4>🏷️ العلامات:</h4>
                            <div class="tags-list">
                                <?php 
                                $tags = explode(",", $data["topic"]["tags"]);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <a href="/topics?tag=<?php echo urlencode($tag); ?>" class="tag">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- عرض الإعلانات وسط المحتوى -->
                    <?php displayInternalAds('content_middle', 'topic_detail'); ?>
                </article>

                <!-- قسم التعليقات -->
                <section class="comments-section card">
                    <h3 class="comments-title">💬 التعليقات (<?php echo count($data["comments"] ?? []); ?>)</h3>
                    
                    <?php if (is_logged_in()): ?>
                        <form class="comment-form" method="POST" action="/topic/<?php echo $data["topic"]["id"]; ?>/comment">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                            <div class="form-group">
                                <label for="comment">إضافة تعليق:</label>
                                <textarea id="comment" name="comment" rows="4" placeholder="شارك رأيك أو تعليقك..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <span>💬</span> إضافة تعليق
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="login-prompt">
                            <p>يجب <a href="/login">تسجيل الدخول</a> لإضافة تعليق.</p>
                        </div>
                    <?php endif; ?>

                    <div class="comments-list">
                        <?php if (empty($data["comments"])): ?>
                            <div class="no-comments">
                                <p>لا توجد تعليقات بعد. كن أول من يعلق!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data["comments"] as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <div class="commenter-info">
                                            <img src="<?php echo $comment["author_avatar"] ?? '/assets/images/default-avatar.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($comment["author_name"]); ?>" 
                                                 class="commenter-avatar">
                                            <div class="commenter-details">
                                                <span class="commenter-name">
                                                    <a href="/profile/<?php echo $comment["user_id"]; ?>">
                                                        <?php echo htmlspecialchars($comment["author_name"]); ?>
                                                    </a>
                                                </span>
                                                <span class="comment-date">
                                                    <?php echo date("Y-m-d H:i", strtotime($comment["created_at"])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <?php if (is_logged_in()): ?>
                                            <div class="comment-actions">
                                                <button class="btn btn-sm btn-outline like-comment-btn" data-comment-id="<?php echo $comment["id"]; ?>">
                                                    <span>👍</span> <?php echo $comment["likes"] ?? 0; ?>
                                                </button>
                                                <button class="btn btn-sm btn-outline reply-btn" data-comment-id="<?php echo $comment["id"]; ?>">
                                                    <span>↩️</span> رد
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="comment-content">
                                        <?php echo nl2br(htmlspecialchars($comment["content"])); ?>
                                    </div>
                                    
                                    <?php if (isset($comment["replies"]) && !empty($comment["replies"])): ?>
                                        <div class="comment-replies">
                                            <?php foreach ($comment["replies"] as $reply): ?>
                                                <div class="reply-item">
                                                    <div class="reply-header">
                                                        <span class="replier-name">
                                                            <a href="/profile/<?php echo $reply["user_id"]; ?>">
                                                                <?php echo htmlspecialchars($reply["author_name"]); ?>
                                                            </a>
                                                        </span>
                                                        <span class="reply-date">
                                                            <?php echo date("Y-m-d H:i", strtotime($reply["created_at"])); ?>
                                                        </span>
                                                    </div>
                                                    <div class="reply-content">
                                                        <?php echo nl2br(htmlspecialchars($reply["content"])); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="col-4">
                <aside class="sidebar">
                    <!-- عرض الإعلانات في الشريط الجانبي -->
                    <?php displayInternalAds('sidebar', 'topic_detail'); ?>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">معلومات الكاتب</h3>
                        <div class="author-profile">
                            <img src="<?php echo $data["topic"]["author_avatar"] ?? '/assets/images/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($data["topic"]["author_name"]); ?>" 
                                 class="author-profile-avatar">
                            <h4><?php echo htmlspecialchars($data["topic"]["author_name"]); ?></h4>
                            <p class="author-bio"><?php echo htmlspecialchars($data["topic"]["author_bio"] ?? 'لا توجد معلومات إضافية'); ?></p>
                            <div class="author-stats">
                                <div class="stat-item">
                                    <span class="stat-label">المواضيع</span>
                                    <span class="stat-value"><?php echo $data["authorStats"]["topics"] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">التعليقات</span>
                                    <span class="stat-value"><?php echo $data["authorStats"]["comments"] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">النقاط</span>
                                    <span class="stat-value"><?php echo $data["authorStats"]["points"] ?? 0; ?></span>
                                </div>
                            </div>
                            <a href="/profile/<?php echo $data["topic"]["user_id"]; ?>" class="btn btn-primary btn-sm">
                                عرض الملف الشخصي
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">مواضيع ذات صلة</h3>
                        <div class="related-topics">
                            <?php if (isset($data['relatedTopics']) && !empty($data['relatedTopics'])): ?>
                                <?php foreach ($data['relatedTopics'] as $relatedTopic): ?>
                                    <div class="related-topic">
                                        <a href="/topic/<?php echo $relatedTopic['id']; ?>">
                                            <?php echo htmlspecialchars($relatedTopic['title']); ?>
                                        </a>
                                        <div class="related-topic-stats">
                                            👁️ <?php echo $relatedTopic['views']; ?> | 💬 <?php echo $relatedTopic['comments_count']; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>لا توجد مواضيع ذات صلة.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">إحصائيات الموضوع</h3>
                        <div class="topic-detailed-stats">
                            <div class="stat-item">
                                <span class="stat-icon">👁️</span>
                                <div class="stat-info">
                                    <span class="stat-value"><?php echo number_format($data["topic"]["views"]); ?></span>
                                    <span class="stat-label">مشاهدة</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">👍</span>
                                <div class="stat-info">
                                    <span class="stat-value"><?php echo number_format($data["topic"]["likes"]); ?></span>
                                    <span class="stat-label">إعجاب</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">💬</span>
                                <div class="stat-info">
                                    <span class="stat-value"><?php echo count($data["comments"] ?? []); ?></span>
                                    <span class="stat-label">تعليق</span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-icon">📅</span>
                                <div class="stat-info">
                                    <span class="stat-value"><?php echo date("d/m/Y", strtotime($data["topic"]["created_at"])); ?></span>
                                    <span class="stat-label">تاريخ النشر</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- عرض الإعلانات أسفل المحتوى -->
        <?php displayInternalAds('content_bottom', 'topic_detail'); ?>
    </main>

    <?php include ROOT_PATH . '/templates/frontend/partials/footer.php'; ?>

    <script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تأثير الظهور التدريجي
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, observerOptions);

            // مراقبة العناصر
            document.querySelectorAll('.topic-detail, .comments-section, .sidebar-section').forEach(element => {
                observer.observe(element);
            });

            // وظائف التفاعل
            const likeBtn = document.querySelector('.like-btn');
            if (likeBtn) {
                likeBtn.addEventListener('click', function() {
                    const topicId = this.dataset.topicId;
                    // إرسال طلب AJAX للإعجاب
                    fetch('/api/topic/' + topicId + '/like', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const countSpan = this.querySelector('.like-count');
                            countSpan.textContent = data.likes;
                            this.classList.toggle('liked');
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            }

            // وظيفة المشاركة
            const shareBtn = document.querySelector('.share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', function() {
                    if (navigator.share) {
                        navigator.share({
                            title: document.title,
                            url: window.location.href
                        });
                    } else {
                        // نسخ الرابط للحافظة
                        navigator.clipboard.writeText(window.location.href);
                        alert('تم نسخ رابط الموضوع!');
                    }
                });
            }

            // تأثيرات hover للتعليقات
            document.querySelectorAll('.comment-item').forEach(comment => {
                comment.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(-5px)';
                });
                
                comment.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>

