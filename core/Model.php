<?php
// Final Max CMS - Core Model

class Model {
    protected $db;
    protected $table;
    protected $prefix;

    public function __construct($pdo, $prefix = '') {
$this->db = $pdo;
$this->prefix = $prefix;
}


    /**
     * Get all records from the table.
     * @return array All records.
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM " . $this->prefix . $this->table);
        return $stmt->fetchAll();
    }

    /**
     * Find a record by ID.
     * @param int $id The ID of the record.
     * @return array|false The record or false if not found.
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM " . $this->prefix . $this->table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new record.
     * @param array $data The data to insert.
     * @return int The ID of the newly created record.
     */
    public function create($data) {
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $stmt = $this->db->prepare("INSERT INTO " . $this->prefix . $this->table . " ($fields) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    /**
     * Update an existing record.
     * @param int $id The ID of the record to update.
     * @param array $data The data to update.
     * @return int The number of affected rows.
     */
    public function update($id, $data) {
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "{$field} = ?";
        }
        $set = implode(", ", $set);
        $stmt = $this->db->prepare("UPDATE " . $this->prefix . $this->table . " SET {$set} WHERE id = ?");
        $values = array_values($data);
        $values[] = $id;
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    /**
     * Delete a record.
     * @param int $id The ID of the record to delete.
     * @return int The number of affected rows.
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM " . $this->prefix . $this->table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    /**
     * Execute a custom query.
     * @param string $sql The SQL query.
     * @param array $params Parameters for the query.
     * @return PDOStatement The PDOStatement object.
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

?>

