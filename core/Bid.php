<?php

class Bid {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($projectId, $userId, $amount, $message) {

        // 🔒 منع التقديم على مشروعك
        $stmt = $this->db->prepare("SELECT user_id FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $owner = $stmt->fetchColumn();

        if ($owner == $userId) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO bids (project_id, user_id, amount, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$projectId, $userId, $amount, $message]);
    }

    public function getByProject($projectId) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.username 
            FROM bids b
            LEFT JOIN users u ON u.id = b.user_id
            WHERE b.project_id = ?
            ORDER BY b.id DESC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acceptBid($bidId) {

        // جلب العرض
        $stmt = $this->db->prepare("SELECT * FROM bids WHERE id = ?");
        $stmt->execute([$bidId]);
        $bid = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bid) return;

        // جلب المشروع
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$bid['project_id']]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) return;

        // 💰 التحقق من رصيد صاحب المشروع
        $stmt = $this->db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$project['user_id']]);
        $balance = $stmt->fetchColumn();

        if ($balance < $bid['amount']) {
            return false; // لا يوجد رصيد
        }

        // 💰 العمولة
        $commission = $bid['amount'] * 0.10;
        $freelancerAmount = $bid['amount'] - $commission;

        require_once __DIR__ . '/Wallet.php';
        $wallet = new Wallet($this->db);

        // 💰 خصم من صاحب المشروع
        $wallet->deduct(
            $project['user_id'],
            $bid['amount'],
            "دفع مشروع رقم " . $project['id']
        );

        // 💰 إضافة للمستقل
        $wallet->add(
            $bid['user_id'],
            $freelancerAmount,
            "أرباح مشروع رقم " . $project['id']
        );

        // تحديث المشروع
        $stmt = $this->db->prepare("
            UPDATE projects 
            SET selected_bid_id = ?, 
                assigned_user_id = ?, 
                status = 'in_progress'
            WHERE id = ?
        ");

        $stmt->execute([
            $bidId,
            $bid['user_id'],
            $bid['project_id']
        ]);
    }
}
?>
