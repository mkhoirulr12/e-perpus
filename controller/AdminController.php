<?php
session_start();
require_once "../config/database.php";

class AdminController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function handle() {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'update_profile_full': $this->updateProfileFull(); break;
            case 'save_buku_full': $this->saveBukuFull(); break;
            case 'save_siswa_full': $this->saveSiswaFull(); break;
            case 'save_pustakawan_full': $this->savePustakawanFull(); break;
            case 'delete_buku': $this->deleteBuku(); break;
            case 'delete_siswa': $this->deleteSiswa(); break;
            case 'delete_user': $this->deleteUser(); break;
            case 'get_book_status': $this->getBookStatus(); break;
            case 'update_tentang': $this->updateTentang(); break;
            case 'update_featured_books': $this->updateFeaturedBooks(); break;
            case 'update_tentang_visual': $this->updateTentangVisual(); break;
        }
    }

    private function updateProfileFull() {
        $user_id = $_SESSION['user_id'];
        $nama_user = $_POST['nama_user'];
        $password = $_POST['password'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $jabatan = $_POST['jabatan_guru'];
        $no_telp = $_POST['no_telp'];
        $alamat = $_POST['alamat'];

        try {
            $this->db->beginTransaction();
            // Update User Table
            if (!empty($password)) {
                $stmt = $this->db->prepare("UPDATE user SET nama_user=?, password=? WHERE id_user=?");
                $stmt->execute([$nama_user, $password, $user_id]);
            } else {
                $stmt = $this->db->prepare("UPDATE user SET nama_user=? WHERE id_user=?");
                $stmt->execute([$nama_user, $user_id]);
            }
            $_SESSION['nama'] = $nama_user;

            // Handle Photo
            $poto_profil = null;
            if (isset($_FILES['poto_profil']) && $_FILES['poto_profil']['error'] == 0) {
                $target_dir = "../assets/img/profil/";
                $ext = pathinfo($_FILES["poto_profil"]["name"], PATHINFO_EXTENSION);
                $poto_profil = "admin_" . $user_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES["poto_profil"]["tmp_name"], $target_dir . $poto_profil);
            }

            // Update Admin Table
            $check = $this->db->prepare("SELECT id_admin FROM admin WHERE id_user = ?");
            $check->execute([$user_id]);
            if ($check->fetch()) {
                $sql = "UPDATE admin SET nama_lengkap=?, jabatan_guru=?, no_telp=?, alamat=?";
                $params = [$nama_lengkap, $jabatan, $no_telp, $alamat];
                if ($poto_profil) { $sql .= ", poto_profil=?"; $params[] = $poto_profil; }
                $sql .= " WHERE id_user=?"; $params[] = $user_id;
                $this->db->prepare($sql)->execute($params);
            } else {
                $poto = $poto_profil ?? 'default.jpg';
                $this->db->prepare("INSERT INTO admin (id_user, nama_lengkap, jabatan_guru, no_telp, alamat, poto_profil) VALUES (?, ?, ?, ?, ?, ?)")->execute([$user_id, $nama_lengkap, $jabatan, $no_telp, $alamat, $poto]);
            }
            $this->db->commit();
            $_SESSION['success'] = "Seluruh data profil admin berhasil diperbarui!";
        } catch (Exception $e) { $this->db->rollBack(); $_SESSION['error'] = $e->getMessage(); }
        header("Location: ../view/admin/dashboard.php?page=pengaturan"); exit;
    }

    private function saveBukuFull() {
        $id     = $_POST['id_buku']      ?? null;
        $kode   = $_POST['kode_buku']    ?? '';
        $judul  = $_POST['judul_buku']   ?? '';
        $penerbit = $_POST['penerbit']   ?? '';
        $tahun  = $_POST['tahun_terbit'] ?? null;
        $id_kat = $_POST['id_kategori']  ?? null;
        $id_tema = $_POST['id_tema']     ?? null;
        $stok   = $_POST['jumlah_buku']  ?? 0;
        $rak    = $_POST['lokasi_rak']   ?? '';

        // Handle Gambar Buku
        $gambar = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $target_dir = "../assets/img/buku/";
            $ext = pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION);
            $gambar = "book_" . time() . "." . $ext;
            move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $gambar);
        }

        if (!$id_kat) {
            $_SESSION['error'] = "Kategori buku harus dipilih!";
            header("Location: ../view/admin/dashboard.php?page=buku"); exit;
        }

        if ($id) {
            $sql = "UPDATE buku SET kode_buku=?, judul_buku=?, penerbit=?, tahun_terbit=?, id_kategori=?, id_tema=?, jumlah_buku=?, lokasi_rak=?";
            $params = [$kode, $judul, $penerbit, $tahun, $id_kat, $id_tema, $stok, $rak];
            if ($gambar) { $sql .= ", gambar=?"; $params[] = $gambar; }
            $sql .= " WHERE id_buku=?"; $params[] = $id;
            $this->db->prepare($sql)->execute($params);
        } else {
            $sql = "INSERT INTO buku (kode_buku, judul_buku, penerbit, tahun_terbit, id_kategori, id_tema, jumlah_buku, lokasi_rak, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->prepare($sql)->execute([$kode, $judul, $penerbit, $tahun, $id_kat, $id_tema, $stok, $rak, $gambar]);
        }
        $_SESSION['success'] = "Data buku berhasil disimpan!";
        header("Location: ../view/admin/dashboard.php?page=buku"); exit;
    }

    private function saveSiswaFull() {
        $id = $_POST['id_siswa'];
        $nis = $_POST['nis'];
        $nama = $_POST['nama_siswa'];
        $jk = $_POST['jenis_kelamin'];
        $kelas = $_POST['kelas'];
        $alamat = $_POST['alamat'];
        $hp = $_POST['no_hp'];
        $ortu = $_POST['nama_orangtua'];
        $pass = $_POST['password'];

        // Check for duplicate NIS
        if ($id) {
            $stmtCheck = $this->db->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND id_siswa != ?");
            $stmtCheck->execute([$nis, $id]);
        } else {
            $stmtCheck = $this->db->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
            $stmtCheck->execute([$nis]);
        }

        if ($stmtCheck->fetch()) {
            $_SESSION['error'] = "Gagal: NIS sudah terdaftar!";
            header("Location: ../view/admin/dashboard.php?page=siswa");
            exit;
        }

        if ($id) {
            $sql = "UPDATE siswa SET nis=?, nama_siswa=?, jenis_kelamin=?, kelas=?, alamat=?, no_hp=?, nama_orangtua=?";
            $params = [$nis, $nama, $jk, $kelas, $alamat, $hp, $ortu];
            if (!empty($pass)) { $sql .= ", password=?"; $params[] = $pass; }
            $sql .= " WHERE id_siswa=?"; $params[] = $id;
            $this->db->prepare($sql)->execute($params);
        } else {
            $sql = "INSERT INTO siswa (nis, nama_siswa, jenis_kelamin, kelas, alamat, no_hp, nama_orangtua, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->prepare($sql)->execute([$nis, $nama, $jk, $kelas, $alamat, $hp, $ortu, $pass]);
        }
        $_SESSION['success'] = "Data siswa berhasil disimpan!";
        header("Location: ../view/admin/dashboard.php?page=siswa"); exit;
    }

    private function savePustakawanFull() {
        $id_user = $_POST['id_user'];
        $user_login = $_POST['username'];
        $nama_pust = $_POST['nama_user'];
        $pass = $_POST['password'];
        $guru = $_POST['guru'];
        $hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];

        try {
            $this->db->beginTransaction();
            if ($id_user) {
                // Update User
                if (!empty($pass)) {
                    $this->db->prepare("UPDATE user SET username=?, nama_user=?, password=? WHERE id_user=?")->execute([$user_login, $nama_pust, $pass, $id_user]);
                } else {
                    $this->db->prepare("UPDATE user SET username=?, nama_user=? WHERE id_user=?")->execute([$user_login, $nama_pust, $id_user]);
                }
                // Update Pustakawan Details
                $this->db->prepare("UPDATE pustakawan SET guru=?, no_hp=?, alamat=? WHERE id_user=?")->execute([$guru, $hp, $alamat, $id_user]);
            } else {
                // Insert User
                $this->db->prepare("INSERT INTO user (username, nama_user, password, level) VALUES (?, ?, ?, 'pustakawan')")->execute([$user_login, $nama_pust, $pass]);
                $new_id = $this->db->lastInsertId();
                // Insert Pustakawan Details
                $this->db->prepare("INSERT INTO pustakawan (id_user, guru, no_hp, alamat) VALUES (?, ?, ?, ?)")->execute([$new_id, $guru, $hp, $alamat]);
            }
            $this->db->commit();
            $_SESSION['success'] = "Data pustakawan berhasil disimpan!";
        } catch (Exception $e) { $this->db->rollBack(); $_SESSION['error'] = $e->getMessage(); }
        header("Location: ../view/admin/dashboard.php?page=pustakawan"); exit;
    }

    private function deleteBuku() {
        $this->db->prepare("DELETE FROM buku WHERE id_buku = ?")->execute([$_GET['id']]);
        $_SESSION['success'] = "Buku dihapus!";
        header("Location: ../view/admin/dashboard.php?page=buku"); exit;
    }
    private function deleteSiswa() {
        $this->db->prepare("DELETE FROM siswa WHERE id_siswa = ?")->execute([$_GET['id']]);
        $_SESSION['success'] = "Siswa dihapus!";
        header("Location: ../view/admin/dashboard.php?page=siswa"); exit;
    }
    private function deleteUser() {
        $this->db->prepare("DELETE FROM user WHERE id_user = ?")->execute([$_GET['id']]);
        $_SESSION['success'] = "User dihapus!";
        header("Location: ../view/admin/dashboard.php?page=pustakawan"); exit;
    }
    private function getBookStatus() {
        $id = $_GET['id'] ?? 0;
        
        // Get book stock
        $stmtBook = $this->db->prepare("SELECT judul_buku, jumlah_buku FROM buku WHERE id_buku = ?");
        $stmtBook->execute([$id]);
        $book = $stmtBook->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            echo json_encode(['error' => 'Buku tidak ditemukan']);
            exit;
        }

        // Get active borrowers
        $stmtBorrowers = $this->db->prepare("
            SELECT s.nama_siswa, s.kelas, p.tanggal_pinjam, p.tanggal_jatuh_tempo 
            FROM peminjaman p 
            JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
            JOIN siswa s ON p.id_siswa = s.id_siswa 
            WHERE dp.id_buku = ? AND p.status = 'dipinjam'
        ");
        $stmtBorrowers->execute([$id]);
        $borrowers = $stmtBorrowers->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'stok' => $book['jumlah_buku'],
            'borrowers' => $borrowers
        ]);
        exit;
    }
    private function updateTentang() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $judul = $_POST['tentang_judul'] ?? '';
            $deskripsi = $_POST['tentang_deskripsi'] ?? '';

            try {
                $stmt1 = $this->db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('tentang_judul', ?) ON DUPLICATE KEY UPDATE nilai = ?");
                $stmt1->execute([$judul, $judul]);

                $stmt2 = $this->db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('tentang_deskripsi', ?) ON DUPLICATE KEY UPDATE nilai = ?");
                $stmt2->execute([$deskripsi, $deskripsi]);

                $_SESSION['success'] = "Berhasil memperbarui informasi Tentang Perpustakaan!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal memperbarui: " . $e->getMessage();
            }
            header("Location: ../view/admin/dashboard.php?page=pengaturan_beranda");
            exit;
        }
    }

    private function updateFeaturedBooks() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $featured_ids = $_POST['featured_ids'] ?? [];

            try {
                $this->db->beginTransaction();
                
                // Reset all featured flags
                $this->db->query("UPDATE buku SET is_featured = 0");

                // Set featured flag for selected IDs
                if (!empty($featured_ids)) {
                    $placeholders = implode(',', array_fill(0, count($featured_ids), '?'));
                    $stmt = $this->db->prepare("UPDATE buku SET is_featured = 1 WHERE id_buku IN ($placeholders)");
                    $stmt->execute($featured_ids);
                }

                $this->db->commit();
                $_SESSION['success'] = "Berhasil memperbarui daftar buku unggulan!";
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error'] = "Gagal memperbarui: " . $e->getMessage();
            }
            header("Location: ../view/admin/dashboard.php?page=pengaturan_beranda");
            exit;
        }
    }
    private function updateTentangVisual() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $_POST['visual_ids'] ?? [];
            // Remove empty values
            $ids = array_filter($ids);
            $ids_string = implode(',', $ids);

            try {
                $stmt = $this->db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('tentang_buku_ids', ?) ON DUPLICATE KEY UPDATE nilai = ?");
                $stmt->execute([$ids_string, $ids_string]);

                $_SESSION['success'] = "Berhasil memperbarui visual Tentang Perpustakaan!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal memperbarui: " . $e->getMessage();
            }
            header("Location: ../view/admin/dashboard.php?page=pengaturan_beranda");
            exit;
        }
    }
}

$controller = new AdminController();
$controller->handle();
?>
