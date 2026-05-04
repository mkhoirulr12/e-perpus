<?php
require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

$nis = $_GET['nis'] ?? '';

if (empty($nis)) {
    die("Akses ditolak: NIS tidak valid.");
}

// Fetch Student and their Borrowing History
$stmtSiswa = $conn->prepare("SELECT * FROM siswa WHERE nis = ?");
$stmtSiswa->execute([$nis]);
$siswa = $stmtSiswa->fetch(PDO::FETCH_ASSOC);

if (!$siswa) {
    die("Data tidak ditemukan.");
}

$stmtPeminjaman = $conn->prepare("
    SELECT p.*, b.judul_buku, b.kode_buku 
    FROM peminjaman p 
    JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
    JOIN buku b ON dp.id_buku = b.id_buku 
    WHERE p.id_siswa = ? 
    ORDER BY p.tanggal_pinjam DESC 
    LIMIT 5
");
$stmtPeminjaman->execute([$siswa['id_siswa']]);
$data = $stmtPeminjaman->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Peminjaman - E-Perpus Prayasqi</title>
    <style>
        :root { --primary: #2d5a27; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: #444; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; border-top: 8px solid var(--primary); }
        .logo { width: 80px; margin-bottom: 20px; }
        h2 { color: var(--primary); margin-bottom: 10px; }
        .info { text-align: left; background: #f9f9f9; padding: 15px; border-radius: 10px; margin: 20px 0; border-left: 4px solid var(--primary); }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .dipinjam { background: #fff3e0; color: #ef6c00; }
        .kembali { background: #e8f5e9; color: #2e7d32; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .verified { color: var(--primary); font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="../../assets/img/logo.png" class="logo" alt="Logo">
        <h2>DATA TERVERIFIKASI</h2>
        <p>Sistem E-Perpus Prayasqi</p>

        <div class="info">
            <strong>Nama:</strong> <?= htmlspecialchars($siswa['nama_siswa']) ?><br>
            <strong>NIS:</strong> <?= $siswa['nis'] ?><br>
            <strong>Kelas:</strong> <?= $siswa['kelas'] ?>
        </div>

        <h3>Riwayat Terakhir</h3>
        <table>
            <thead>
                <tr>
                    <th>Buku</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['judul_buku']) ?></td>
                    <td>
                        <span class="status-badge <?= $d['status'] === 'dipinjam' ? 'dipinjam' : 'kembali' ?>">
                            <?= $d['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="verified">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Data Dokumen Sah & Valid
        </div>
        
        <p style="font-size: 11px; color: #888; margin-top: 30px;">Waktu Verifikasi: <?= date('d/m/Y H:i:s') ?></p>
    </div>
</body>
</html>
