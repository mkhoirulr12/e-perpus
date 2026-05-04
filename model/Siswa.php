<?php
class Siswa {
    private $conn;
    private $table = "siswa";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByNis($nis) {
        $query = "SELECT * FROM " . $this->table . " WHERE nis = :nis AND status = 'aktif' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nis", $nis);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registerSiswa($data) {
        // Cek apakah NIS sudah ada
        $checkQuery = "SELECT id_siswa FROM " . $this->table . " WHERE nis = :nis";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":nis", $data['nis']);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return "NIS sudah terdaftar!";
        }

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table . " (nis, nama_siswa, password, jenis_kelamin, kelas, username) 
                      VALUES (:nis, :nama_siswa, :password, :jenis_kelamin, :kelas, :username)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nis", $data['nis']);
            $stmt->bindParam(":nama_siswa", $data['nama_siswa']);
            $stmt->bindParam(":password", $data['password']);
            $stmt->bindParam(":jenis_kelamin", $data['jenis_kelamin']);
            $stmt->bindParam(":kelas", $data['kelas']);
            $stmt->bindParam(":username", $data['username']);
            
            $stmt->execute();
            $id_siswa = $this->conn->lastInsertId();

            // Insert into user table for unified login
            $userQuery = "INSERT INTO user (nama_user, username, password, level, id_siswa) 
                          VALUES (:nama_user, :username, :password, 'siswa', :id_siswa)";
            $stmtUser = $this->conn->prepare($userQuery);
            $stmtUser->bindParam(":nama_user", $data['nama_siswa']);
            $stmtUser->bindParam(":username", $data['nis']); // Use NIS as username
            $stmtUser->bindParam(":password", $data['password']);
            $stmtUser->bindParam(":id_siswa", $id_siswa);
            $stmtUser->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return "Gagal mendaftar: " . $e->getMessage();
        }
    }

    public function updatePassword($id, $password) {
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id_siswa = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function login($nis, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE nis = :nis AND password = :password AND status = 'aktif' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nis", $nis);
        $stmt->bindParam(":password", $password);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}