<?php

class Database {

    private $host = "localhost";
    private $db   = "perpustakaan_sd";
    private $user = "root";
    private $pass = "";
    public $conn;

    public function connect() {

        $this->conn = null;

        try {

            $this->conn = new PDO(
                "mysql:host=" . $this->host . 
                ";dbname=" . $this->db,
                $this->user,
                $this->pass
            );

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            // Auto-sync schema (Replacing separate migrate files)
            $this->ensureSchema();

        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }

    private function ensureSchema() {
        try {
            // Update status peminjaman
            $this->conn->exec("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('diajukan', 'dipinjam', 'dikembalikan') DEFAULT 'diajukan'");
            
            // Tambahkan kolom id_tema ke tabel buku jika belum ada
            $checkColumn = $this->conn->query("SHOW COLUMNS FROM buku LIKE 'id_tema'")->fetch();
            if (!$checkColumn) {
                $this->conn->exec("ALTER TABLE buku ADD COLUMN id_tema INT(11) DEFAULT NULL AFTER id_kategori");
            }

            // Pastikan data kategori ada
            $countKat = $this->conn->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
            if ($countKat == 0) {
                $this->conn->exec("INSERT INTO kategori (id_kategori, nama_kategori) VALUES (1, 'Umum'), (2, 'Pembelajaran'), (3, 'Islami')");
            }

            // Pastikan data tema ada
            $countTema = $this->conn->query("SELECT COUNT(*) FROM tema")->fetchColumn();
            if ($countTema == 0) {
                $this->conn->exec("INSERT INTO tema (id_tema, nama_tema) VALUES (1, 'Fiksi'), (2, 'Non-Fiksi'), (3, 'Sains'), (4, 'Sejarah'), (5, 'Religi')");
            }
            // Tambahkan kolom gambar ke tabel buku jika belum ada
            $checkGambar = $this->conn->query("SHOW COLUMNS FROM buku LIKE 'gambar'")->fetch();
            if (!$checkGambar) {
                $this->conn->exec("ALTER TABLE buku ADD COLUMN gambar VARCHAR(255) DEFAULT NULL AFTER lokasi_rak");
            }
            // Tambahkan kolom foto ke tabel siswa jika belum ada
            $checkFoto = $this->conn->query("SHOW COLUMNS FROM siswa LIKE 'foto'")->fetch();
            if (!$checkFoto) {
                $this->conn->exec("ALTER TABLE siswa ADD COLUMN foto VARCHAR(255) DEFAULT NULL");
            }

        } catch (Exception $e) {
            // Silently fail or log
        }
    }
}