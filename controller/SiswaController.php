<?php
session_start();
if (!isset($_SESSION['siswa_id']) || $_SESSION['level'] !== 'siswa') {
    header("Location: ../view/auth/Login.php");
    exit;
}

require_once "../config/database.php";
$database = new Database();
$conn = $database->connect();

$action = $_GET['action'] ?? '';

if ($action === 'export_riwayat') {
    $siswa_id = $_SESSION['siswa_id'];
    
    // Fetch data
    $stmt = $conn->prepare("
        SELECT b.judul_buku, b.penerbit, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.status, pg.tanggal_kembali
        FROM peminjaman p 
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
        JOIN buku b ON dp.id_buku = b.id_buku 
        LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman 
        WHERE p.id_siswa = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$siswa_id]);
    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Riwayat_Peminjaman_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Judul Buku', 'Penerbit', 'Tanggal Pinjam', 'Jatuh Tempo', 'Status', 'Tanggal Kembali']);

    foreach ($riwayat as $row) {
        $status_text = $row['status'];
        if ($status_text === 'dikembalikan') {
            $status_text = 'Sudah Kembali';
        } else {
            if (time() > strtotime($row['tanggal_jatuh_tempo'])) {
                $status_text = 'Terlambat';
            } else {
                $status_text = 'Dipinjam';
            }
        }
        
        fputcsv($output, [
            $row['judul_buku'],
            $row['penerbit'],
            date('d M Y', strtotime($row['tanggal_pinjam'])),
            date('d M Y', strtotime($row['tanggal_jatuh_tempo'])),
            $status_text,
            $row['tanggal_kembali'] ? date('d M Y', strtotime($row['tanggal_kembali'])) : '-'
        ]);
    }
    fclose($output);
    exit;
}

if ($action === 'ajukan_pinjam' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = $_SESSION['siswa_id'];
    $id_buku = $_POST['id_buku'] ?? 0;
    $durasi = (int)($_POST['durasi'] ?? 7);
    
    // Check book availability
    $stmtBuku = $conn->prepare("SELECT jumlah_buku, status FROM buku WHERE id_buku = ?");
    $stmtBuku->execute([$id_buku]);
    $buku = $stmtBuku->fetch(PDO::FETCH_ASSOC);

    if ($buku && $buku['jumlah_buku'] > 0 && $buku['status'] === 'tersedia') {
        try {
            $conn->beginTransaction();
            
            // Create borrowing with status 'diajukan'
            $kode_peminjaman = "REQ-" . strtoupper(uniqid());
            $stmt = $conn->prepare("INSERT INTO peminjaman (kode_peminjaman, id_siswa, id_user, tanggal_pinjam, tanggal_jatuh_tempo, status) VALUES (?, ?, ?, ?, ?, ?)");
            $tgl_pinjam = date('Y-m-d');
            $tgl_tempo = date('Y-m-d', strtotime("+$durasi days"));
            $stmt->execute([$kode_peminjaman, $siswa_id, 1, $tgl_pinjam, $tgl_tempo, 'diajukan']);
            
            $id_peminjaman = $conn->lastInsertId();
            
            // Detail
            $stmtDetail = $conn->prepare("INSERT INTO detail_peminjaman (id_peminjaman, id_buku, jumlah) VALUES (?, ?, ?)");
            $stmtDetail->execute([$id_peminjaman, $id_buku, 1]);
            
            $conn->commit();
            $_SESSION['success_msg'] = "Permohonan pinjam berhasil dikirim! Menunggu konfirmasi pustakawan.";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_msg'] = "Gagal mengajukan: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Buku tidak tersedia untuk dipinjam.";
    }
    header("Location: ../view/siswa/SiswaDashboard.php?page=katalog");
    exit;
}

if ($action === 'update_profil' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = $_SESSION['siswa_id'];
    $nama = $_POST['nama_siswa'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_hp = $_POST['no_hp'] ?? '';
    $nama_orangtua = $_POST['nama_orangtua'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';

    try {
        $conn->beginTransaction();

        // Handle foto upload
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $target_dir = "../assets/img/profil/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
            $foto = "siswa_" . $siswa_id . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES["foto"]["tmp_name"], $target_dir . $foto);
        }

        // Build update query
        $sql = "UPDATE siswa SET nama_siswa=?, alamat=?, no_hp=?, nama_orangtua=?";
        $params = [$nama, $alamat, $no_hp, $nama_orangtua];

        if ($foto) { $sql .= ", foto=?"; $params[] = $foto; }
        if (!empty($password_baru)) { $sql .= ", password=?"; $params[] = $password_baru; }

        $sql .= " WHERE id_siswa=?";
        $params[] = $siswa_id;
        $conn->prepare($sql)->execute($params);

        // Update session name
        $_SESSION['nama'] = $nama;

        $conn->commit();
        $_SESSION['success_msg'] = "Profil berhasil diperbarui!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_msg'] = "Gagal memperbarui profil: " . $e->getMessage();
    }
    header("Location: ../view/siswa/SiswaDashboard.php?page=pengaturan");
    exit;
}

// default fallback
header("Location: ../view/siswa/SiswaDashboard.php");
exit;
