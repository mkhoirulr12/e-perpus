<?php
session_start();

// Check login (Student or Librarian/Admin)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['siswa_id'])) {
    header("Location: ../../view/auth/Login.php");
    exit;
}

require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

// Determine Student ID to display
if (isset($_SESSION['level']) && $_SESSION['level'] === 'siswa') {
    // Student role: force their own ID
    $id_siswa = $_SESSION['siswa_id'];
} else {
    // Librarian/Admin role: can view specified student via GET
    $id_siswa = $_GET['id_siswa'] ?? null;
}

if (!$id_siswa) {
    exit('ID Siswa tidak ditemukan atau Anda tidak memiliki akses.');
}

// Time filter
$filter = $_GET['filter'] ?? 'all';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Fetch Student Data
$stmtSiswa = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmtSiswa->execute([$id_siswa]);
$siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

if (!$siswa) exit('Data siswa tidak ditemukan.');

// Fetch Librarian Data for Signature
$stmtAdmin = $conn->query("SELECT nama_user FROM user WHERE level = 'pustakawan' LIMIT 1");
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

// Fetch Borrowing History with LEFT JOIN to pengembalian for return dates
$sql = "SELECT p.*, b.judul_buku, b.kode_buku, pg.tanggal_kembali 
        FROM peminjaman p 
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
        JOIN buku b ON dp.id_buku = b.id_buku 
        LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman
        WHERE p.id_siswa = ? AND p.status != 'diajukan'";

if ($filter === 'month') {
    $sql .= " AND MONTH(p.tanggal_pinjam) = ? AND YEAR(p.tanggal_pinjam) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_siswa, $month, $year]);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_siswa]);
}
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = $filter === 'month' ? "REKAP PEMINJAMAN BULAN " . strtoupper(date('F', mktime(0, 0, 0, $month, 10))) . " " . $year : "REKAP RIWAYAT PEMINJAMAN";

// Generate Tracking URL for QR Code
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$track_url = "$protocol://$host$uri";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Peminjaman - <?= htmlspecialchars($siswa['nama_siswa']) ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 40px; }
        .header { display: flex; align-items: center; border-bottom: 3px solid #2d5a27; padding-bottom: 20px; margin-bottom: 30px; position: relative; }
        .logo { width: 80px; height: auto; }
        .header-text { flex: 1; text-align: left; padding-left: 20px; }
        .header-text h1 { margin: 0; color: #2d5a27; font-size: 24px; }
        .header-text p { margin: 5px 0 0; color: #666; font-size: 12px; }
        
        .title { text-align: center; margin: 30px 0; font-weight: bold; font-size: 18px; color: #2d5a27; text-decoration: underline; }
        
        .info-siswa { margin-bottom: 20px; font-size: 14px; }
        .info-siswa table { width: 100%; border-collapse: collapse; }
        .info-siswa td { padding: 5px 0; }
        
        .main-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .main-table th { background-color: #2d5a27; color: white; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; }
        .main-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        .main-table tr:nth-child(even) { background-color: #fcfcfc; }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            z-index: -1;
            width: 400px;
        }
        
        .footer-sig { margin-top: 50px; display: flex; justify-content: flex-end; }
        .sig-box { text-align: center; width: 250px; }
        .sig-qr { margin: 15px 0; }
        .sig-qr img { width: 100px; height: 100px; }
        
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2d5a27;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            font-weight: bold;
            z-index: 100;
        }
        
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">CETAK / SIMPAN PDF</button>

    <img src="../../assets/img/logo.png" class="watermark" alt="Watermark">

    <div class="header">
        <img src="../../assets/img/logo.png" class="logo" alt="Logo">
        <div class="header-text">
            <h1>E-PERPUS PRAYASQI</h1>
            <p>Sistem Informasi Perpustakaan Digital SD IT Prayasqi</p>
            <p>Rekap Peminjaman Buku Siswa</p>
        </div>
    </div>

    <div class="title"><?= $title ?></div>

    <div class="info-siswa">
        <table>
            <tr>
                <td width="150">Nama Siswa</td>
                <td width="20">:</td>
                <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
            </tr>
            <tr>
                <td>NIS</td>
                <td>:</td>
                <td><?= $siswa['nis'] ?></td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>:</td>
                <td> Kelas <?= $siswa['kelas'] ?></td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="18%" style="white-space: nowrap;">Kode Buku</th>
                <th width="37%">Judul Buku</th>
                <th width="15%">Tgl Pinjam</th>
                <th width="15%">Tgl Kembali</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data)): ?>
                <tr><td colspan="6" style="text-align:center;">Tidak ada riwayat peminjaman.</td></tr>
            <?php else: ?>
                <?php foreach($data as $i => $d): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td style="white-space: nowrap;"><?= $d['kode_buku'] ?></td>
                    <td><?= htmlspecialchars($d['judul_buku']) ?></td>
                    <td><?= date('d/m/Y', strtotime($d['tanggal_pinjam'])) ?></td>
                    <td><?= $d['tanggal_kembali'] ? date('d/m/Y', strtotime($d['tanggal_kembali'])) : '-' ?></td>
                    <td><span style="text-transform:uppercase; font-weight:bold; color: <?= $d['status'] === 'dipinjam' ? '#e67e22' : '#27ae60' ?>"><?= $d['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-box">
            <p>Mengetahui,</p>
            <p>Pustakawan</p>
            
            <!-- QR Code moved here and linked to the tracking URL -->
            <div class="sig-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($track_url) ?>" alt="Tracking QR Code">
                <p style="font-size: 9px; color: #888; margin-top: 5px;">Scan untuk verifikasi data</p>
            </div>

            <p><strong>( <?= htmlspecialchars($admin['nama_user'] ?? 'Pustakawan') ?> )</strong></p>
        </div>
    </div>

    <div style="position: fixed; bottom: 0; left: 0; width: 100%; height: 5px; background: #2d5a27;"></div>
</body>
</html>
