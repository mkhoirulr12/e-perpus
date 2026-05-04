<?php
session_start();
require_once "../config/database.php";

class PustakawanController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function handle() {
        $action = $_GET['action'] ?? '';

        if ($action === 'tambah_buku') {
            $this->tambahBuku();
        } elseif ($action === 'export_template') {
            $this->exportTemplate();
        } elseif ($action === 'update_profile') {
            $this->updateProfile();
        } elseif ($action === 'siswa_add') {
            $this->siswaAdd();
        } elseif ($action === 'siswa_edit') {
            $this->siswaEdit();
        } elseif ($action === 'siswa_delete') {
            $this->siswaDelete();
        } elseif ($action === 'pinjam_cepat') {
            $this->pinjamCepat();
        } elseif ($action === 'acc_pinjam') {
            $this->accPinjam();
        } elseif ($action === 'tolak_pinjam') {
            $this->tolakPinjam();
        } elseif ($action === 'delete_buku') {
            $this->hapusBuku();
        }
    }

    private function pinjamCepat() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nis = $_POST['nis'];
            $kode_buku = $_POST['kode_buku'];
            $user_id = $_SESSION['user_id'];

            try {
                $this->db->beginTransaction();

                // 1. Cek Siswa
                $stmtSiswa = $this->db->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND status = 'aktif'");
                $stmtSiswa->execute([$nis]);
                $siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

                if (!$siswa) throw new Exception("NIS Siswa tidak ditemukan atau tidak aktif!");

                // 2. Cek Buku
                $stmtBuku = $this->db->prepare("SELECT id_buku, jumlah_buku FROM buku WHERE kode_buku = ?");
                $stmtBuku->execute([$kode_buku]);
                $buku = $stmtBuku->fetch(PDO::FETCH_ASSOC);

                if (!$buku) throw new Exception("Kode Buku tidak ditemukan!");
                if ($buku['jumlah_buku'] <= 0) throw new Exception("Stok buku sedang habis!");

                // 3. Buat Peminjaman
                $kode_pinjam = "PJ-" . time();
                $tgl_pinjam = date('Y-m-d');
                $tgl_tempo = date('Y-m-d', strtotime('+7 days'));

                $stmtP = $this->db->prepare("INSERT INTO peminjaman (kode_peminjaman, id_siswa, id_user, tanggal_pinjam, tanggal_jatuh_tempo, status) VALUES (?, ?, ?, ?, ?, 'dipinjam')");
                $stmtP->execute([$kode_pinjam, $siswa['id_siswa'], $user_id, $tgl_pinjam, $tgl_tempo]);
                $id_pinjam = $this->db->lastInsertId();

                // 4. Detail Peminjaman
                $stmtD = $this->db->prepare("INSERT INTO detail_peminjaman (id_peminjaman, id_buku, jumlah) VALUES (?, ?, 1)");
                $stmtD->execute([$id_pinjam, $buku['id_buku']]);

                // 5. Kurangi Stok
                $stmtU = $this->db->prepare("UPDATE buku SET jumlah_buku = jumlah_buku - 1 WHERE id_buku = ?");
                $stmtU->execute([$buku['id_buku']]);

                $this->db->commit();
                $_SESSION['success'] = "Peminjaman berhasil diproses!";
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error'] = $e->getMessage();
            }

            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=dashboard");
            exit;
        }
    }

    private function updateProfile() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $user_id = $_SESSION['user_id'];
            $nama = $_POST['nama_user'];
            $guru = $_POST['guru'];
            $alamat = $_POST['alamat'];
            $no_hp = $_POST['no_hp'];
            $password = $_POST['password'];

            try {
                $this->db->beginTransaction();

                // Update User Table (Nama & Password)
                if (!empty($password)) {
                    $stmtUser = $this->db->prepare("UPDATE user SET nama_user = ?, password = ? WHERE id_user = ?");
                    $stmtUser->execute([$nama, $password, $user_id]);
                } else {
                    $stmtUser = $this->db->prepare("UPDATE user SET nama_user = ? WHERE id_user = ?");
                    $stmtUser->execute([$nama, $user_id]);
                }

                // Handle Photo Upload
                $poto_profil = 'default.jpg';
                if (isset($_FILES['poto_profil']) && $_FILES['poto_profil']['error'] == 0) {
                    $target_dir = "../assets/img/profil/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $ext = pathinfo($_FILES["poto_profil"]["name"], PATHINFO_EXTENSION);
                    $poto_profil = "pustakawan_" . $user_id . "_" . time() . "." . $ext;
                    move_uploaded_file($_FILES["poto_profil"]["tmp_name"], $target_dir . $poto_profil);
                }

                // Update Pustakawan Table
                // Check if exists first
                $check = $this->db->prepare("SELECT id_pustakawan FROM pustakawan WHERE id_user = ?");
                $check->execute([$user_id]);
                if ($check->fetch()) {
                    $sqlP = "UPDATE pustakawan SET guru = ?, alamat = ?, no_hp = ?";
                    $params = [$guru, $alamat, $no_hp];
                    if ($poto_profil !== 'default.jpg') {
                        $sqlP .= ", poto_profil = ?";
                        $params[] = $poto_profil;
                    }
                    $sqlP .= " WHERE id_user = ?";
                    $params[] = $user_id;
                    $stmtP = $this->db->prepare($sqlP);
                    $stmtP->execute($params);
                } else {
                    $stmtP = $this->db->prepare("INSERT INTO pustakawan (id_user, guru, alamat, no_hp, poto_profil) VALUES (?, ?, ?, ?, ?)");
                    $stmtP->execute([$user_id, $guru, $alamat, $no_hp, $poto_profil]);
                }

                $this->db->commit();
                $_SESSION['nama'] = $nama;
                $_SESSION['success'] = "Profil berhasil diperbarui!";
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error'] = "Gagal memperbarui profil: " . $e->getMessage();
            }

            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=pengaturan");
            exit;
        }
    }

    private function siswaAdd() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nis = $_POST['nis'];
            $nama = $_POST['nama_siswa'];
            $pw = $_POST['password'];
            $jk = $_POST['jenis_kelamin'];
            $kelas = $_POST['kelas'];
            $alamat = $_POST['alamat'];
            $no_hp = $_POST['no_hp'];
            $ortu = $_POST['nama_orangtua'];

            // Check for duplicate NIS
            $stmtCheck = $this->db->prepare("SELECT id_siswa FROM siswa WHERE nis = ?");
            $stmtCheck->execute([$nis]);
            if ($stmtCheck->fetch()) {
                $_SESSION['error'] = "Gagal menambah siswa: NIS sudah terdaftar!";
                header("Location: ../view/pustakawan/PustakawanDashboard.php?page=siswa");
                exit;
            }

            $sql = "INSERT INTO siswa (nis, password, nama_siswa, jenis_kelamin, kelas, alamat, no_hp, nama_orangtua, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')";
            $stmt = $this->db->prepare($sql);
            try {
                $stmt->execute([$nis, $pw, $nama, $jk, $kelas, $alamat, $no_hp, $ortu]);
                $_SESSION['success'] = "Data siswa berhasil ditambahkan!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal menambah siswa: " . $e->getMessage();
            }
            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=siswa");
            exit;
        }
    }

    private function siswaEdit() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id = $_POST['id_siswa'];
            $nis = $_POST['nis'];
            $nama = $_POST['nama_siswa'];
            $jk = $_POST['jenis_kelamin'];
            $kelas = $_POST['kelas'];
            $alamat = $_POST['alamat'];
            $no_hp = $_POST['no_hp'];
            $ortu = $_POST['nama_orangtua'];

            // Check for duplicate NIS
            $stmtCheck = $this->db->prepare("SELECT id_siswa FROM siswa WHERE nis = ? AND id_siswa != ?");
            $stmtCheck->execute([$nis, $id]);
            if ($stmtCheck->fetch()) {
                $_SESSION['error'] = "Gagal memperbarui siswa: NIS sudah digunakan oleh siswa lain!";
                header("Location: ../view/pustakawan/PustakawanDashboard.php?page=siswa");
                exit;
            }

            $sql = "UPDATE siswa SET nis = ?, nama_siswa = ?, jenis_kelamin = ?, kelas = ?, alamat = ?, no_hp = ?, nama_orangtua = ? WHERE id_siswa = ?";
            $stmt = $this->db->prepare($sql);
            try {
                $stmt->execute([$nis, $nama, $jk, $kelas, $alamat, $no_hp, $ortu, $id]);
                $_SESSION['success'] = "Data siswa berhasil diperbarui!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal memperbarui siswa: " . $e->getMessage();
            }
            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=siswa");
            exit;
        }
    }

    private function siswaDelete() {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $stmt = $this->db->prepare("DELETE FROM siswa WHERE id_siswa = ?");
            try {
                $stmt->execute([$id]);
                $_SESSION['success'] = "Data siswa berhasil dihapus!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal menghapus siswa: " . $e->getMessage();
            }
        }
        header("Location: ../view/pustakawan/PustakawanDashboard.php?page=siswa");
        exit;
    }

    private function tambahBuku() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $judul = $_POST['judul_buku'];
            $id_kategori = $_POST['id_kategori'];
            $penerbit = $_POST['penerbit'];
            $tahun = $_POST['tahun_terbit'];
            $id_tema = $_POST['id_tema'] ?? null;
            $jumlah = $_POST['jumlah_buku'];
            $lokasi = $_POST['lokasi_rak'];

            // Handle Gambar Buku
            $gambar = null;
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                $target_dir = "../assets/img/buku/";
                $ext = pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION);
                $gambar = "book_" . time() . "." . $ext;
                move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $gambar);
            }

            $kode_buku = $_POST['kode_buku'] ?? '';
            $id_buku = $_POST['id_buku'] ?? null;

            if ($id_buku) {
                $query = "UPDATE buku SET kode_buku=?, judul_buku=?, penerbit=?, tahun_terbit=?, id_kategori=?, id_tema=?, jumlah_buku=?, lokasi_rak=?";
                $params = [$kode_buku, $judul, $penerbit, $tahun, $id_kategori, $id_tema, $jumlah, $lokasi];
                if ($gambar) {
                    $query .= ", gambar=?";
                    $params[] = $gambar;
                }
                $query .= " WHERE id_buku=?";
                $params[] = $id_buku;
                
                $stmt = $this->db->prepare($query);
                try {
                    $stmt->execute($params);
                    $_SESSION['success'] = "Buku berhasil diperbarui!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Gagal memperbarui buku: " . $e->getMessage();
                }
            } else {
                $query = "INSERT INTO buku (kode_buku, judul_buku, penerbit, tahun_terbit, id_kategori, id_tema, jumlah_buku, lokasi_rak, gambar, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'tersedia')";
                $stmt = $this->db->prepare($query);
                
                try {
                    $stmt->execute([$kode_buku, $judul, $penerbit, $tahun, $id_kategori, $id_tema, $jumlah, $lokasi, $gambar]);
                    $_SESSION['success'] = "Buku berhasil ditambahkan dengan kode: $kode_buku";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Gagal menambah buku: " . $e->getMessage();
                }
            }

            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=buku");
            exit;
        }
    }

    private function hapusBuku() {
        $id = $_GET['id'] ?? '';
        if ($id) {
            $stmt = $this->db->prepare("DELETE FROM buku WHERE id_buku = ?");
            try {
                $stmt->execute([$id]);
                $_SESSION['success'] = "Koleksi buku berhasil dihapus!";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal menghapus buku: " . $e->getMessage();
            }
        }
        header("Location: ../view/pustakawan/PustakawanDashboard.php?page=buku");
        exit;
    }

    private function exportTemplate() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=format_upload_buku.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['judul_buku', 'id_kategori', 'penerbit', 'tahun_terbit', 'jumlah_buku', 'lokasi_rak']);
        fputcsv($output, ['Laskar Pelangi', '1', 'Bentang Pustaka', '2005', '5', 'Rak A-1']);
        fclose($output);
        exit;
    }

    private function accPinjam() {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            header("Location: ../view/pustakawan/PustakawanDashboard.php?page=dashboard");
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Ambil detail buku dari peminjaman
            $stmtDetail = $this->db->prepare("SELECT id_buku FROM detail_peminjaman WHERE id_peminjaman = ?");
            $stmtDetail->execute([$id]);
            $detail = $stmtDetail->fetch(PDO::FETCH_ASSOC);

            if (!$detail) throw new Exception("Data detail peminjaman tidak ditemukan!");

            // 2. Cek Stok Buku
            $stmtBuku = $this->db->prepare("SELECT jumlah_buku FROM buku WHERE id_buku = ?");
            $stmtBuku->execute([$detail['id_buku']]);
            $buku = $stmtBuku->fetch(PDO::FETCH_ASSOC);

            if ($buku['jumlah_buku'] <= 0) throw new Exception("Stok buku sudah habis, tidak bisa ACC!");

            // 3. Update Status Peminjaman (dan set tanggal jatuh tempo)
            $tgl_pinjam = date('Y-m-d');
            $tgl_tempo = date('Y-m-d', strtotime('+7 days'));
            $user_id = $_SESSION['user_id'];

            $stmtUpdate = $this->db->prepare("UPDATE peminjaman SET status = 'dipinjam', tanggal_pinjam = ?, tanggal_jatuh_tempo = ?, id_user = ? WHERE id_peminjaman = ?");
            $stmtUpdate->execute([$tgl_pinjam, $tgl_tempo, $user_id, $id]);

            // 4. Kurangi Stok
            $stmtStok = $this->db->prepare("UPDATE buku SET jumlah_buku = jumlah_buku - 1 WHERE id_buku = ?");
            $stmtStok->execute([$detail['id_buku']]);

            $this->db->commit();
            $_SESSION['success'] = "Permohonan pinjam berhasil di-ACC!";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ../view/pustakawan/PustakawanDashboard.php?page=dashboard");
        exit;
    }

    private function tolakPinjam() {
        $id = $_GET['id'] ?? '';
        if ($id) {
            try {
                $this->db->beginTransaction();
                // Hapus detail dan peminjaman
                $stmtD = $this->db->prepare("DELETE FROM detail_peminjaman WHERE id_peminjaman = ?");
                $stmtD->execute([$id]);
                
                $stmtP = $this->db->prepare("DELETE FROM peminjaman WHERE id_peminjaman = ?");
                $stmtP->execute([$id]);

                $this->db->commit();
                $_SESSION['success'] = "Permohonan pinjam telah ditolak.";
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error'] = "Gagal menolak permohonan.";
            }
        }
        header("Location: ../view/pustakawan/PustakawanDashboard.php?page=dashboard");
        exit;
    }
}

$controller = new PustakawanController();
$controller->handle();
