<?php

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * إنشاء مستخدم جديد
     * @param string $username
     * @param string $email
     * @param string $password_hash
     * @param string $role
     * @return bool
     */
    public function create($username, $email, $password_hash, $role = 'user') {
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $password_hash, $role]);
    }

    /**
     * الحصول على مستخدم بواسطة اسم المستخدم
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على مستخدم بواسطة البريد الإلكتروني
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على مستخدم بواسطة ID
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث بيانات المستخدم
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $set_clause = [];
        $params = [];
        foreach ($data as $key => $value) {
            $set_clause[] = "`{$key}` = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $set_clause) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * حذف مستخدم
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * الحصول على جميع المستخدمين
     * @return array
     */
    public function getAllUsers() {
        $stmt = $this->db->query("SELECT * FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * حظر مستخدم
     * @param int $user_id
     * @return bool
     */
    public function banUser($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * إلغاء حظر مستخدم
     * @param int $user_id
     * @return bool
     */
    public function unbanUser($user_id) {
        $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        return $stmt->execute([$user_id]);
    }
}

?>

