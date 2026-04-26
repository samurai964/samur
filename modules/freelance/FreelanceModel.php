
<?php

class FreelanceModel {
    private $pdo;
    private $prefix;

    public function __construct($pdo, $prefix) {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    public function getProjects($filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT p.*, u.username as client_username, u.avatar as client_avatar FROM `{$this->prefix}freelance_projects` p JOIN `{$this->prefix}users` u ON p.client_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters["category_id"])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters["category_id"];
        }
        if (!empty($filters["status"])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters["status"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countProjects($filters = []) {
        $sql = "SELECT COUNT(*) FROM `{$this->prefix}freelance_projects` p WHERE 1=1";
        $params = [];

        if (!empty($filters["category_id"])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters["category_id"];
        }
        if (!empty($filters["status"])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters["status"];
        }
        if (!empty($filters["search"])) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $params[] = "%" . $filters["search"] . "%";
            $params[] = "%" . $filters["search"] . "%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getProjectById($projectId) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username as client_username, u.avatar as client_avatar FROM `{$this->prefix}freelance_projects` p JOIN `{$this->prefix}users` u ON p.client_id = u.id WHERE p.id = ?");
        $stmt->execute([$projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createProject($data) {
        $sql = "INSERT INTO `{$this->prefix}freelance_projects` (client_id, title, description, budget, deadline, category_id, skills_required, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["client_id"],
            $data["title"],
            $data["description"],
            $data["budget"],
            $data["deadline"],
            $data["category_id"],
            $data["skills_required"],
            $data["status"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateProject($projectId, $data) {
        $sql = "UPDATE `{$this->prefix}freelance_projects` SET title = ?, description = ?, budget = ?, deadline = ?, category_id = ?, skills_required = ?, status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["title"],
            $data["description"],
            $data["budget"],
            $data["deadline"],
            $data["category_id"],
            $data["skills_required"],
            $data["status"],
            $projectId
        ]);
        return $stmt->rowCount();
    }

    public function deleteProject($projectId) {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}freelance_projects` WHERE id = ?");
        $stmt->execute([$projectId]);
        return $stmt->rowCount();
    }

    public function getProposalsForProject($projectId) {
        $sql = "SELECT fp.*, u.username as freelancer_username, u.avatar as freelancer_avatar FROM `{$this->prefix}freelance_proposals` fp JOIN `{$this->prefix}users` u ON fp.freelancer_id = u.id WHERE fp.project_id = ? ORDER BY fp.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProposalById($proposalId) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->prefix}freelance_proposals` WHERE id = ?");
        $stmt->execute([$proposalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createProposal($data) {
        $sql = "INSERT INTO `{$this->prefix}freelance_proposals` (project_id, freelancer_id, proposal_text, bid_amount, delivery_time, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["project_id"],
            $data["freelancer_id"],
            $data["proposal_text"],
            $data["bid_amount"],
            $data["delivery_time"],
            $data["status"]
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateProposalStatus($proposalId, $status) {
        $sql = "UPDATE `{$this->prefix}freelance_proposals` SET status = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$status, $proposalId]);
        return $stmt->rowCount();
    }

    public function getFreelancerProposals($freelancerId) {
        $sql = "SELECT fp.*, p.title as project_title, p.description as project_description FROM `{$this->prefix}freelance_proposals` fp JOIN `{$this->prefix}freelance_projects` p ON fp.project_id = p.id WHERE fp.freelancer_id = ? ORDER BY fp.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$freelancerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientProjects($clientId) {
        $sql = "SELECT * FROM `{$this->prefix}freelance_projects` WHERE client_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectCategories() {
        $stmt = $this->pdo->query("SELECT * FROM `{$this->prefix}categories` WHERE type = 'freelance' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSkills() {
        $stmt = $this->pdo->query("SELECT * FROM `{$this->prefix}skills` ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectByProposalId($proposalId) {
        $sql = "SELECT p.* FROM `{$this->prefix}freelance_projects` p JOIN `{$this->prefix}freelance_proposals` fp ON p.id = fp.project_id WHERE fp.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$proposalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hasUserProposed($projectId, $freelancerId) {
        $sql = "SELECT COUNT(*) FROM `{$this->prefix}freelance_proposals` WHERE project_id = ? AND freelancer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$projectId, $freelancerId]);
        return $stmt->fetchColumn() > 0;
    }
}

?>

