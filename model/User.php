<?php
class User {
    private $conn;
    private $table = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByUsername($username) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username AND status = 'aktif' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_user = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePassword($id, $password) {
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id_user = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function registerUser($data) {
        // Cek apakah username sudah ada
        $checkQuery = "SELECT id_user FROM " . $this->table . " WHERE username = :username";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":username", $data['username']);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return "Username sudah terdaftar!";
        }

        $query = "INSERT INTO " . $this->table . " (nama_user, username, password, level) 
                  VALUES (:nama_user, :username, :password, :level)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nama_user", $data['nama_user']);
        $stmt->bindParam(":username", $data['username']);
        $stmt->bindParam(":password", $data['password']);
        $stmt->bindParam(":level", $data['level']);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}