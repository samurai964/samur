
<?php

class CoursesModel {
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getCourses($filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT c.*, u.username as instructor_username, u.avatar as instructor_avatar FROM `{$this->prefix}courses` c JOIN `{$this->prefix}users` u ON c.instructor_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters["category_id"])) {
            $sql .= " AND c.category_id = ?";
            $params[] = $filters["category_id"];
        }
        if (!empty($filters["level"])) {
            $sql .= " AND c.level = ?";
            $params[] = $filters["level"];
        }
        if (!empty($filters["price_type"])) {
            $sql .= " AND c.price_type = ?";
            $params[] = $filters["price_type"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countCourses($filters = []) {
        $sql = "SELECT COUNT(*) FROM `{$this->prefix}courses` c WHERE 1=1";
        $params = [];

        if (!empty($filters["category_id"])) {
            $sql .= " AND c.category_id = ?";
            $params[] = $filters["category_id"];
        }
        if (!empty($filters["level"])) {
            $sql .= " AND c.level = ?";
            $params[] = $filters["level"];
        }
        if (!empty($filters["price_type"])) {
            $sql .= " AND c.price_type = ?";
            $params[] = $filters["price_type"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getCourseById($courseId) {
        $stmt = $this->pdo->prepare("SELECT c.*, u.username as instructor_username, u.avatar as instructor_avatar FROM `{$this->prefix}courses` c JOIN `{$this->prefix}users` u ON c.instructor_id = u.id WHERE c.id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCourse($data) {
        $sql = "INSERT INTO `{$this->prefix}courses` (instructor_id, title, description, price, price_type, category_id, level, duration, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["instructor_id"],
            $data["title"],
            $data["description"],
            $data["price"],
            $data["price_type"],
            $data["category_id"],
            $data["level"],
            $data["duration"],
            $data["image"],
            $data["status"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateCourse($courseId, $data) {
        $sql = "UPDATE `{$this->prefix}courses` SET title = ?, description = ?, price = ?, price_type = ?, category_id = ?, level = ?, duration = ?, image = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["title"],
            $data["description"],
            $data["price"],
            $data["price_type"],
            $data["category_id"],
            $data["level"],
            $data["duration"],
            $data["image"],
            $data["status"],
            $courseId
        ]);
        return $stmt->rowCount();
    }

    public function deleteCourse($courseId) {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}courses` WHERE id = ?");
        $stmt->execute([$courseId]);
        return $stmt->rowCount();
    }

    public function getLessonsByCourseId($courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}course_lessons` WHERE course_id = ? ORDER BY lesson_order ASC");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLessonById($lessonId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}course_lessons` WHERE id = ?");
        $stmt->execute([$lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createLesson($data) {
        $sql = "INSERT INTO `{$this->prefix}course_lessons` (course_id, title, content, video_url, lesson_order) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["course_id"],
            $data["title"],
            $data["content"],
            $data["video_url"],
            $data["lesson_order"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateLesson($lessonId, $data) {
        $sql = "UPDATE `{$this->prefix}course_lessons` SET title = ?, content = ?, video_url = ?, lesson_order = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["title"],
            $data["content"],
            $data["video_url"],
            $data["lesson_order"],
            $lessonId
        ]);
        return $stmt->rowCount();
    }

    public function deleteLesson($lessonId) {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}course_lessons` WHERE id = ?");
        $stmt->execute([$lessonId]);
        return $stmt->rowCount();
    }

    public function enrollUserInCourse($userId, $courseId) {
        $sql = "INSERT INTO `{$this->prefix}course_enrollments` (user_id, course_id, enrollment_date, status) VALUES (?, ?, NOW(), ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $courseId, 'enrolled']);
        return $this->pdo->lastInsertId();
    }

    public function isUserEnrolled($userId, $courseId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}course_enrollments` WHERE user_id = ? AND course_id = ? AND status = 'enrolled'");
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getEnrollment($userId, $courseId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}course_enrollments` WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$userId, $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateLessonProgress($enrollmentId, $lessonId, $status) {
        $sql = "INSERT INTO `{$this->prefix}lesson_progress` (enrollment_id, lesson_id, status, completion_date) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status), completion_date = VALUES(completion_date)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$enrollmentId, $lessonId, $status]);
        return $stmt->rowCount();
    }

    public function getLessonProgress($enrollmentId, $lessonId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}lesson_progress` WHERE enrollment_id = ? AND lesson_id = ?");
        $stmt->execute([$enrollmentId, $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCourseReviews($courseId) {
        $sql = "SELECT cr.*, u.username, u.avatar FROM `{$this->prefix}course_reviews` cr JOIN `{$this->prefix}users` u ON cr.user_id = u.id WHERE course_id = ? ORDER BY cr.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCourseReview($data) {
        $sql = "INSERT INTO `{$this->prefix}course_reviews` (course_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["course_id"],
            $data["user_id"],
            $data["rating"],
            $data["review_text"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getAverageRating($courseId) {
        $stmt = $this->pdo->prepare("SELECT AVG(rating) FROM `{$this->prefix}course_reviews` WHERE course_id = ?");
        $stmt->execute([$courseId]);
        return $stmt->fetchColumn() ?: 0;
    }

    public function getUserEnrollments($userId) {
        $sql = "SELECT ce.*, c.title, c.image, c.instructor_id, u.username as instructor_username FROM `{$this->prefix}course_enrollments` ce JOIN `{$this->prefix}courses` c ON ce.course_id = c.id JOIN `{$this->prefix}users` u ON c.instructor_id = u.id WHERE ce.user_id = ? ORDER BY ce.enrollment_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInstructorCourses($instructorId) {
        $sql = "SELECT c.*, COUNT(ce.id) as total_enrollments FROM `{$this->prefix}courses` c LEFT JOIN `{$this->prefix}course_enrollments` ce ON c.id = ce.course_id WHERE c.instructor_id = ? GROUP BY c.id ORDER BY c.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$instructorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>

