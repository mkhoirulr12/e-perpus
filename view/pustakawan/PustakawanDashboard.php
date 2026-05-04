<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'pustakawan') {
    header("Location: ../../view/auth/Login.php?role=pustakawan");
    exit;
}

require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

$page = $_GET['page'] ?? 'dashboard';
$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['nama'];

// Ambil Data Detail Pustakawan
$stmtP = $conn->prepare("
    SELECT u.*, p.guru, p.alamat, p.no_hp, p.poto_profil 
    FROM user u 
    LEFT JOIN pustakawan p ON u.id_user = p.id_user 
    WHERE u.id_user = ?
");
$stmtP->execute([$user_id]);
$pustakawan_data = $stmtP->fetch(PDO::FETCH_ASSOC);

// Ambil Statistik Global
$totalBuku = $conn->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalSiswa = $conn->query("SELECT COUNT(*) FROM siswa WHERE status='aktif'")->fetchColumn();
$totalPinjam = $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$totalPending = $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status='diajukan'")->fetchColumn();

// Logic per halaman
if ($page === 'dashboard') {
    $stmtHistory = $conn->query("
        SELECT p.*, s.nama_siswa, s.kelas, b.judul_buku 
        FROM peminjaman p 
        JOIN siswa s ON p.id_siswa = s.id_siswa 
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
        JOIN buku b ON dp.id_buku = b.id_buku 
        ORDER BY p.created_at DESC LIMIT 10
    ");
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Permohonan (Pending)
    $stmtPending = $conn->query("
        SELECT p.id_peminjaman, p.status, p.created_at, 
               s.nama_siswa, s.kelas, b.judul_buku
        FROM peminjaman p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
        JOIN buku b ON dp.id_buku = b.id_buku
        WHERE p.status = 'diajukan'
        ORDER BY p.created_at ASC
    ");
    $pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buku Paling Banyak Dipinjam
    $stmtTopBooks = $conn->query("
        SELECT b.judul_buku, COUNT(dp.id_buku) as total 
        FROM detail_peminjaman dp 
        JOIN buku b ON dp.id_buku = b.id_buku 
        GROUP BY dp.id_buku 
        ORDER BY total DESC LIMIT 5
    ");
    $topBooks = $stmtTopBooks->fetchAll(PDO::FETCH_ASSOC);

    // 3. Peminjam Paling Sering
    $stmtTopStudents = $conn->query("
        SELECT s.nama_siswa, s.kelas, COUNT(p.id_peminjaman) as total 
        FROM peminjaman p 
        JOIN siswa s ON p.id_siswa = s.id_siswa 
        GROUP BY p.id_siswa 
        ORDER BY total DESC LIMIT 5
    ");
    $topStudents = $stmtTopStudents->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'buku') {
    $search = $_GET['q'] ?? '';
    $stmtCat = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    $stmtTema = $conn->query("SELECT * FROM tema ORDER BY nama_tema ASC");
    $themes = $stmtTema->fetchAll(PDO::FETCH_ASSOC);
    
    $query = "SELECT b.*, k.nama_kategori, t.nama_tema FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori LEFT JOIN tema t ON b.id_tema = t.id_tema";
    if($search) $query .= " WHERE b.judul_buku LIKE ? OR b.kode_buku LIKE ?";
    $query .= " ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);
    $search ? $stmt->execute(["%$search%", "%$search%"]) : $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'siswa') {
    $search = $_GET['q'] ?? '';
    $query = "SELECT * FROM siswa";
    if($search) $query .= " WHERE nama_siswa LIKE ? OR nis LIKE ?";
    $query .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $search ? $stmt->execute(["%$search%", "%$search%"]) : $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'transaksi') {
    $stmt = $conn->query("
        SELECT p.*, s.nama_siswa, b.judul_buku 
        FROM peminjaman p 
        JOIN siswa s ON p.id_siswa = s.id_siswa 
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
        JOIN buku b ON dp.id_buku = b.id_buku 
        ORDER BY p.created_at DESC
    ");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Notifications
$stmtNotif = $conn->query("
    SELECT p.id_peminjaman, s.nama_siswa, b.judul_buku, p.created_at 
    FROM peminjaman p 
    JOIN siswa s ON p.id_siswa = s.id_siswa 
    JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
    JOIN buku b ON dp.id_buku = b.id_buku 
    WHERE p.status = 'dipinjam' 
    ORDER BY p.created_at DESC LIMIT 5
");
$notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

function getNavClass($current, $target) {
    if ($current === $target) {
        return "flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold shadow-lg transition-all";
    }
    return "flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-100 rounded-xl transition-all";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard Pustakawan - E-Perpus Prayasqi</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: { "primary-container": "#2d5a27", "gold": "#C5A059" },
                    fontFamily: { "body": ["Public Sans"], "heading": ["Newsreader"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { font-family: 'Public Sans', sans-serif; background-color: #f8fafc; }
        html { scroll-behavior: smooth; }
        /* Page transition animation */
        .page-content { animation: fadeSlideIn 0.4s ease-out; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        /* Sidebar link hover animation */
        aside nav a { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        aside nav a:hover { transform: translateX(4px); }
    </style>
</head>
<body class="text-slate-900">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 bg-white flex flex-col p-4 space-y-2 sticky top-0 h-screen shrink-0 shadow-sm z-20">
        <a href="../../index.php" class="flex items-center gap-2 px-3 py-2 mb-2 text-slate-400 hover:text-primary-container rounded-lg hover:bg-slate-50 transition-all group">
            <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_back</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">E-Perpus Prayasqi</span>
        </a>
        <div class="mb-8 px-2">
            <div class="flex items-center gap-2 mb-1">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-auto">
                <h1 class="font-bold text-primary-container font-heading text-xl tracking-tight">Prayasqi</h1>
            </div>
            <p class="text-[10px] text-slate-400 uppercase tracking-[0.2em] font-bold">Librarian Portal</p>
        </div>
        
        <nav class="flex-grow space-y-1">
            <a class="<?= getNavClass($page, 'dashboard') ?>" href="?page=dashboard">
                <div class="flex items-center gap-3 flex-grow">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span class="text-sm">Ringkasan</span>
                </div>
                <?php if($totalPending > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                        <?= $totalPending ?>
                    </span>
                <?php endif; ?>
            </a>
            <a class="<?= getNavClass($page, 'buku') ?>" href="?page=buku">
                <span class="material-symbols-outlined">auto_stories</span>
                <span class="text-sm">Koleksi Buku</span>
            </a>
            <a class="<?= getNavClass($page, 'siswa') ?>" href="?page=siswa">
                <span class="material-symbols-outlined">group</span>
                <span class="text-sm">Data Siswa</span>
            </a>
            <a class="<?= getNavClass($page, 'transaksi') ?>" href="?page=transaksi">
                <span class="material-symbols-outlined">swap_horiz</span>
                <span class="text-sm">Sirkulasi</span>
            </a>
            <a class="<?= getNavClass($page, 'pengaturan') ?>" href="?page=pengaturan">
                <span class="material-symbols-outlined">settings</span>
                <span class="text-sm">Pengaturan</span>
            </a>
        </nav>

        <div class="pt-6 border-t border-slate-100">
            <div class="flex items-center gap-3 px-3 py-3 mb-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="w-10 h-10 bg-primary-container text-white rounded-full flex items-center justify-center font-bold text-lg shadow-inner overflow-hidden">
                    <?php if(!empty($pustakawan_data['poto_profil']) && $pustakawan_data['poto_profil'] !== 'default.jpg'): ?>
                        <img src="../../assets/img/profil/<?= $pustakawan_data['poto_profil'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= strtoupper(substr($user_nama, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold truncate"><?= htmlspecialchars($user_nama) ?></p>
                    <p class="text-[9px] text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($pustakawan_data['guru'] ?? 'Pustakawan') ?></p>
                </div>
            </div>
            <a href="../../controller/LogoutController.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl transition-all font-bold text-sm">
                <span class="material-symbols-outlined">logout</span>
                Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col min-w-0">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-5 flex justify-between items-center sticky top-0 z-50 shadow-sm">
            <div>
                <h2 class="font-heading text-xl font-bold text-primary-container">
                    <?php 
                        if($page == 'dashboard') echo "Ringkasan Aktivitas";
                        elseif($page == 'buku') echo "Manajemen Koleksi";
                        elseif($page == 'siswa') echo "Manajemen Anggota";
                        elseif($page == 'transaksi') echo "Layanan Sirkulasi";
                        else echo "Pengaturan Profil";
                    ?>
                </h2>
            </div>
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <div class="relative group">
                    <button class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <?php if($totalPending > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
                                <?= $totalPending ?>
                            </span>
                        <?php elseif(!empty($notifications)): ?>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[70] overflow-hidden">
                        <div class="p-4 border-b border-slate-50 bg-slate-50/50">
                            <h4 class="text-xs font-bold text-primary-container uppercase tracking-widest">Pinjaman Berlangsung</h4>
                        </div>
                        <div class="max-h-[300px] overflow-y-auto divide-y divide-slate-50">
                            <?php if(empty($notifications)): ?>
                                <p class="p-6 text-center text-xs text-slate-400">Tidak ada pinjaman aktif.</p>
                            <?php else: ?>
                                <?php foreach($notifications as $nt): ?>
                                <div class="p-4 hover:bg-slate-50 transition-colors">
                                    <p class="text-sm font-bold text-slate-800 leading-tight"><?= htmlspecialchars($nt['nama_siswa']) ?></p>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($nt['judul_buku']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8 page-content">
            <!-- Alerts -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl flex items-center gap-3">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span class="text-sm font-bold"><?= $_SESSION['success'] ?></span>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if($page === 'dashboard'): ?>
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><span class="material-symbols-outlined text-3xl">auto_stories</span></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Buku</p>
                            <h3 class="text-3xl font-heading font-bold text-primary-container"><?= number_format($totalBuku) ?></h3>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center"><span class="material-symbols-outlined text-3xl">group</span></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Anggota Aktif</p>
                            <h3 class="text-3xl font-heading font-bold text-primary-container"><?= number_format($totalSiswa) ?></h3>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center"><span class="material-symbols-outlined text-3xl">swap_horiz</span></div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Peminjaman</p>
                            <h3 class="text-3xl font-heading font-bold text-primary-container"><?= number_format($totalPinjam) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Peminjaman Cepat Form -->
                        <div class="bg-primary-container text-white p-8 rounded-2xl shadow-xl relative overflow-hidden">
                            <div class="absolute -right-10 -bottom-10 opacity-10">
                                <span class="material-symbols-outlined text-[150px]">assignment_add</span>
                            </div>
                            <div class="relative z-10">
                                <h4 class="font-heading text-2xl mb-6 italic text-gold">Input Peminjaman Baru</h4>
                                <form action="../../controller/PustakawanController.php?action=pinjam_cepat" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    <div class="md:col-span-1">
                                        <label class="block text-[10px] font-bold text-white/50 uppercase tracking-widest mb-2">NIS Siswa</label>
                                        <input required name="nis" type="text" class="w-full bg-white/10 border-white/20 rounded-xl py-3 px-4 focus:ring-gold text-white placeholder-white/30" placeholder="Contoh: 2024001">
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-[10px] font-bold text-white/50 uppercase tracking-widest mb-2">Kode Buku</label>
                                        <input required name="kode_buku" type="text" class="w-full bg-white/10 border-white/20 rounded-xl py-3 px-4 focus:ring-gold text-white placeholder-white/30" placeholder="Contoh: NOV-001">
                                    </div>
                                    <div class="md:col-span-1">
                                        <button type="submit" class="w-full bg-gold text-primary-container font-bold py-3 px-6 rounded-xl shadow-lg hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                                            <span class="material-symbols-outlined">add_circle</span> PROSES
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Permohonan Pinjam (Menunggu ACC) -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-yellow-50/50">
                                <h4 class="font-bold text-yellow-800 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-yellow-600">pending_actions</span> Permohonan Pinjam (<?= count($pendingRequests) ?>)
                                </h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="px-6 py-4">Siswa</th>
                                            <th class="px-6 py-4">Buku</th>
                                            <th class="px-6 py-4">Waktu</th>
                                            <th class="px-6 py-4 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if(empty($pendingRequests)): ?>
                                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">Tidak ada permohonan baru.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($pendingRequests as $pr): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-primary-container leading-tight"><?= htmlspecialchars($pr['nama_siswa']) ?></p>
                                                    <p class="text-xs text-slate-500 mt-1">Kelas <?= htmlspecialchars($pr['kelas']) ?></p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <p class="font-medium text-slate-700"><?= htmlspecialchars($pr['judul_buku']) ?></p>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-400"><?= date('d M, H:i', strtotime($pr['created_at'])) ?></td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <a href="../../controller/PustakawanController.php?action=acc_pinjam&id=<?= $pr['id_peminjaman'] ?>" class="bg-green-600 hover:bg-green-700 text-white p-2 rounded-lg transition-all shadow-sm flex items-center gap-1 text-xs font-bold px-4">
                                                            <span class="material-symbols-outlined text-sm">check_circle</span> ACC
                                                        </a>
                                                        <a href="../../controller/PustakawanController.php?action=tolak_pinjam&id=<?= $pr['id_peminjaman'] ?>" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg transition-all flex items-center gap-1 text-xs font-bold px-4" onclick="return confirm('Tolak permohonan ini?')">
                                                            <span class="material-symbols-outlined text-sm">cancel</span> TOLAK
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Aktivitas Terkini -->
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h4 class="font-bold text-primary-container">Aktivitas Terkini</h4>
                                <a href="?page=transaksi" class="text-xs font-bold text-gold hover:underline uppercase tracking-widest">Lihat Semua</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if(empty($history)): ?>
                                            <tr><td class="px-6 py-10 text-center text-slate-400">Belum ada aktivitas.</td></tr>
                                        <?php else: ?>
                                            <?php foreach($history as $h): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <p class="font-bold text-primary-container leading-tight"><?= htmlspecialchars($h['judul_buku']) ?></p>
                                                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($h['nama_siswa']) ?> • <?= $h['kelas'] ?></p>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider <?= $h['status'] === 'dipinjam' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>">
                                                        <?= $h['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-slate-400 text-right"><?= date('d M, H:i', strtotime($h['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Sidebar -->
                    <div class="space-y-6">
                        <!-- Buku Terpopuler -->
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-slate-50">
                                <h4 class="text-xs font-bold text-primary-container uppercase tracking-widest flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gold text-lg">workspace_premium</span> Buku Terpopuler
                                </h4>
                            </div>
                            <div class="p-4 space-y-4">
                                <?php foreach($topBooks as $index => $tb): ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs"><?= $index + 1 ?></div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-primary-container truncate leading-tight"><?= htmlspecialchars($tb['judul_buku']) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5"><?= $tb['total'] ?>x Dipinjam</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Peminjam Aktif -->
                        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-slate-50">
                                <h4 class="text-xs font-bold text-primary-container uppercase tracking-widest flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gold text-lg">stars</span> Peminjam Aktif
                                </h4>
                            </div>
                            <div class="p-4 space-y-4">
                                <?php foreach($topStudents as $index => $ts): ?>
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-full bg-primary-container text-white flex items-center justify-center font-bold text-[10px]"><?= strtoupper(substr($ts['nama_siswa'], 0, 1)) ?></div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-primary-container truncate leading-tight"><?= htmlspecialchars($ts['nama_siswa']) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Kelas <?= $ts['kelas'] ?> • <?= $ts['total'] ?> Buku</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif($page === 'buku'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <form action="" method="GET" class="flex gap-2">
                                <input type="hidden" name="page" value="buku">
                                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="rounded-lg border-slate-200 text-sm focus:ring-gold" placeholder="Cari judul/kode...">
                                <button type="submit" class="bg-primary-container text-white px-4 py-2 rounded-lg text-sm font-bold">Cari</button>
                            </form>
                            <a href="../../controller/PustakawanController.php?action=export_template" class="p-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 transition-all text-[10px] font-bold uppercase flex items-center gap-1" title="Unduh CSV">
                                <span class="material-symbols-outlined text-sm">download</span> CSV
                            </a>
                        </div>
                        <button onclick="openAddBuku()" class="bg-gold text-primary-container px-6 py-2 rounded-lg text-sm font-bold shadow-sm hover:shadow-md transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">add</span> Tambah Koleksi
                        </button>
                    </div>
                    <!-- Table Buku... (Keep existing table code from previous version) -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Info Buku</th>
                                    <th class="px-6 py-4">Kategori</th>
                                    <th class="px-6 py-4">Stok</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tabelBuku" class="divide-y divide-slate-100">
                                <?php foreach($books as $b): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 flex items-center gap-3">
                                        <div onclick="previewCover('<?= $b['gambar'] ?>')" class="w-10 h-14 bg-slate-100 rounded overflow-hidden flex-shrink-0 flex items-center justify-center border border-slate-100 cursor-pointer hover:ring-2 hover:ring-gold transition-all">
                                            <?php if(!empty($b['gambar'])): ?>
                                                <img src="../../assets/img/buku/<?= $b['gambar'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-slate-300">auto_stories</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-primary-container leading-tight"><?= htmlspecialchars($b['judul_buku']) ?></p>
                                            <p class="text-[10px] text-slate-400 mt-1"><?= $b['kode_buku'] ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-bold text-primary-container"><?= htmlspecialchars($b['nama_kategori']) ?></p>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-0.5"><?= htmlspecialchars($b['nama_tema'] ?? '-') ?></p>
                                    </td>
                                    <td class="px-6 py-4 font-bold"><?= $b['jumlah_buku'] ?></td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button onclick="showBukuQR('<?= $b['kode_buku'] ?>', '<?= htmlspecialchars($b['judul_buku'], ENT_QUOTES) ?>', <?= $b['id_buku'] ?>)" class="text-slate-400 hover:text-primary-container" title="QR Code Buku"><span class="material-symbols-outlined text-lg">qr_code_2</span></button>
                                        <button onclick='openEditBuku(<?= json_encode($b) ?>)' class="text-blue-400 hover:text-blue-600"><span class="material-symbols-outlined text-lg">edit</span></button>
                                        <a href="../../controller/PustakawanController.php?action=delete_buku&id=<?= $b['id_buku'] ?>" onclick="return confirm('Hapus buku ini?')" class="text-red-400 hover:text-red-600"><span class="material-symbols-outlined text-lg">delete</span></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Tambah Buku -->
                <div id="modalTambahBuku" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="font-bold text-primary-container" id="titleBuku">Tambah Koleksi Baru</h3>
                            <button onclick="document.getElementById('modalTambahBuku').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><span class="material-symbols-outlined">close</span></button>
                        </div>
                        <form id="formBuku" action="../../controller/PustakawanController.php?action=tambah_buku" method="POST" enctype="multipart/form-data" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <input type="hidden" name="id_buku" id="id_buku">
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kode Buku</label><input required name="kode_buku" id="kode_buku" class="w-full rounded-xl border-slate-200" placeholder="Misal: B-001"></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Judul Buku</label><input required name="judul_buku" id="judul_buku" class="w-full rounded-xl border-slate-200"></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kategori</label>
                                <select required name="id_kategori" id="id_kategori_buku" class="w-full rounded-xl border-slate-200">
                                    <option value="" disabled selected>Pilih Kategori</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id_kategori'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Penerbit</label><input required name="penerbit" id="penerbit_buku" class="w-full rounded-xl border-slate-200"></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tahun Terbit</label>
                                <select required name="tahun_terbit" id="tahun_terbit_buku" class="w-full rounded-xl border-slate-200">
                                    <?php for($y = date('Y'); $y >= date('Y')-30; $y--): ?>
                                        <option value="<?=$y?>"><?=$y?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Stok</label><input required type="number" name="jumlah_buku" id="stok_buku" class="w-full rounded-xl border-slate-200"></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Tema Buku</label>
                                <select required name="id_tema" id="id_tema_buku" class="w-full rounded-xl border-slate-200">
                                    <option value="" disabled selected>Pilih Tema</option>
                                    <?php foreach($themes as $tema): ?>
                                        <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nama_tema']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Lokasi Rak</label><input required name="lokasi_rak" id="rak_buku" class="w-full rounded-xl border-slate-200"></div>
                            <div class="md:col-span-2"><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Cover Buku (JPG/PNG)</label><input type="file" name="gambar" class="w-full text-xs"></div>
                            <div class="md:col-span-2 pt-4"><button type="submit" class="w-full bg-primary-container text-white py-4 rounded-xl font-bold shadow-lg">Simpan Koleksi</button></div>
                        </form>
                    </div>
                </div>

            <?php elseif($page === 'siswa'): ?>
                <!-- Siswa List View... (Same as before) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <form action="" method="GET" class="flex gap-2">
                            <input type="hidden" name="page" value="siswa">
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="rounded-lg border-slate-200 text-sm focus:ring-gold" placeholder="Cari nama/NIS...">
                            <button type="submit" class="bg-primary-container text-white px-4 py-2 rounded-lg text-sm font-bold">Cari</button>
                        </form>
                        <button onclick="tambahSiswa()" class="bg-primary-container text-white px-6 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">person_add</span> Tambah Siswa
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Siswa</th>
                                    <th class="px-6 py-4">Kelas</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($students as $s): ?>
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-primary-container"><?= htmlspecialchars($s['nama_siswa']) ?></p>
                                        <p class="text-[10px] text-slate-500">NIS: <?= $s['nis'] ?></p>
                                    </td>
                                    <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($s['kelas']) ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="editSiswa(<?= htmlspecialchars(json_encode($s)) ?>)" class="text-blue-400"><span class="material-symbols-outlined text-lg">edit</span></button>
                                        <a href="../../controller/PustakawanController.php?action=siswa_delete&id=<?= $s['id_siswa'] ?>" class="text-red-400"><span class="material-symbols-outlined text-lg">delete</span></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'transaksi'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50">
                        <h4 class="font-bold text-primary-container">Riwayat Peminjaman</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Peminjam</th>
                                    <th class="px-6 py-4">Buku</th>
                                    <th class="px-6 py-4">Tgl Pinjam</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($transactions as $t): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4"><?= htmlspecialchars($t['nama_siswa']) ?></td>
                                    <td class="px-6 py-4 font-bold text-primary-container"><?= htmlspecialchars($t['judul_buku']) ?></td>
                                    <td class="px-6 py-4 text-xs"><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></td>
                                    <td class="px-6 py-4"><span class="px-2 py-1 rounded text-[9px] font-bold uppercase <?= $t['status'] === 'dipinjam' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>"><?= $t['status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'pengaturan'): ?>
                <!-- Pengaturan Profil (Same as before) -->
                <div class="max-w-4xl grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-center">
                        <div class="w-32 h-32 mx-auto bg-slate-100 rounded-full mb-4 border-4 border-white shadow-md overflow-hidden flex items-center justify-center">
                            <?php if(!empty($pustakawan_data['poto_profil']) && $pustakawan_data['poto_profil'] !== 'default.jpg'): ?>
                                <img src="../../assets/img/profil/<?= $pustakawan_data['poto_profil'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-5xl text-slate-300">person</span>
                            <?php endif; ?>
                        </div>
                        <h4 class="font-bold text-primary-container"><?= htmlspecialchars($user_nama) ?></h4>
                    </div>
                    <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                        <h4 class="font-heading text-xl font-bold mb-6 border-b pb-4">Profil Pustakawan</h4>
                        <form action="../../controller/PustakawanController.php?action=update_profile" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nama</label><input name="nama_user" value="<?= htmlspecialchars($user_nama) ?>" class="w-full rounded-lg border-slate-200 text-sm"></div>
                                <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Guru</label><input name="guru" value="<?= htmlspecialchars($pustakawan_data['guru'] ?? '') ?>" class="w-full rounded-lg border-slate-200 text-sm"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">No HP</label><input name="no_hp" value="<?= htmlspecialchars($pustakawan_data['no_hp'] ?? '') ?>" class="w-full rounded-lg border-slate-200 text-sm"></div>
                                <div><label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Password Baru</label><input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="w-full rounded-lg border-slate-200 text-sm"></div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Alamat</label>
                                <textarea name="alamat" rows="3" class="w-full rounded-lg border-slate-200 text-sm"><?= htmlspecialchars($pustakawan_data['alamat'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Foto Profil</label>
                                <input type="file" name="poto_profil" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-50 file:text-primary-container hover:file:bg-slate-100">
                            </div>
                            <div class="pt-4"><button type="submit" class="bg-gold text-primary-container px-10 py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition-all">Simpan Perubahan</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Siswa -->
<div id="modalSiswa" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-6 border-b flex justify-between bg-slate-50"><h3 class="font-bold" id="titleSiswa">Tambah Data Siswa</h3><button onclick="document.getElementById('modalSiswa').classList.add('hidden')" class="text-slate-400"><span class="material-symbols-outlined">close</span></button></div>
        <form id="formSiswa" action="../../controller/PustakawanController.php?action=siswa_add" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_siswa" id="id_siswa">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">NIS</label><input required name="nis" id="nis_siswa" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Kelas</label><input required name="kelas" id="kelas_siswa" class="w-full rounded-xl border-slate-200 text-sm"></div>
            </div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Nama Siswa</label><input required name="nama_siswa" id="nama_siswa" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Jenis Kelamin</label><select name="jenis_kelamin" id="jk_siswa" class="w-full rounded-xl border-slate-200 text-sm"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">No HP</label><input name="no_hp" id="hp_siswa" class="w-full rounded-xl border-slate-200 text-sm"></div>
            </div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Nama Orangtua</label><input name="nama_orangtua" id="ortu_siswa" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Alamat</label><textarea name="alamat" id="alamat_siswa" class="w-full rounded-xl border-slate-200 text-sm"></textarea></div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Password</label><input name="password" type="password" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Isi untuk baru/ubah"></div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold">Simpan Siswa</button>
        </form>
    </div>
</div>

<!-- Preview Cover Modal -->
<div id="modalPreview" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" onclick="this.classList.add('hidden')">
    <div class="max-w-md w-full">
        <img id="previewImg" src="" class="w-full rounded-2xl shadow-2xl">
    </div>
</div>

<script>
function previewCover(img) {
    if(!img || img === 'null' || img === '') return;
    document.getElementById('previewImg').src = '../../assets/img/buku/' + img;
    document.getElementById('modalPreview').classList.remove('hidden');
}

function openAddBuku() {
    document.getElementById('titleBuku').innerText = 'Tambah Koleksi Baru';
    document.getElementById('formBuku').reset();
    document.getElementById('id_buku').value = '';
    document.getElementById('modalTambahBuku').classList.remove('hidden');
}

function openEditBuku(b) {
    document.getElementById('titleBuku').innerText = 'Edit Koleksi Buku';
    document.getElementById('formBuku').reset();
    document.getElementById('id_buku').value = b.id_buku;
    document.getElementById('kode_buku').value = b.kode_buku;
    document.getElementById('judul_buku').value = b.judul_buku;
    document.getElementById('id_kategori_buku').value = b.id_kategori;
    document.getElementById('penerbit_buku').value = b.penerbit;
    document.getElementById('tahun_terbit_buku').value = b.tahun_terbit;
    document.getElementById('stok_buku').value = b.jumlah_buku;
    document.getElementById('id_tema_buku').value = b.id_tema;
    document.getElementById('rak_buku').value = b.lokasi_rak;
    document.getElementById('modalTambahBuku').classList.remove('hidden');
}

function tambahSiswa() {
    document.getElementById('titleSiswa').innerText = 'Tambah Data Siswa';
    document.getElementById('formSiswa').reset();
    document.getElementById('id_siswa').value = '';
    document.getElementById('formSiswa').action = '../../controller/PustakawanController.php?action=siswa_add';
    document.getElementById('modalSiswa').classList.remove('hidden');
}

function editSiswa(s) {
    document.getElementById('titleSiswa').innerText = 'Edit Data Siswa';
    document.getElementById('formSiswa').reset();
    document.getElementById('id_siswa').value = s.id_siswa;
    document.getElementById('nis_siswa').value = s.nis;
    document.getElementById('nama_siswa').value = s.nama_siswa;
    document.getElementById('kelas_siswa').value = s.kelas;
    document.getElementById('jk_siswa').value = s.jenis_kelamin;
    document.getElementById('hp_siswa').value = s.no_hp;
    document.getElementById('ortu_siswa').value = s.nama_orangtua;
    document.getElementById('alamat_siswa').value = s.alamat;
    document.getElementById('formSiswa').action = '../../controller/PustakawanController.php?action=siswa_edit';
    document.getElementById('modalSiswa').classList.remove('hidden');
}

function showBukuQR(kode, judul, id_buku) {
    document.getElementById('qrBukuTitle').innerText = judul;
    document.getElementById('qrBukuImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=BUKU-' + kode;
    document.getElementById('modalBukuQR').classList.remove('hidden');
    
    // Fetch book status
    document.getElementById('qrBookStatus').innerHTML = '<p class="text-xs text-slate-400">Memuat status...</p>';
    fetch('../../controller/AdminController.php?action=get_book_status&id=' + id_buku)
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                document.getElementById('qrBookStatus').innerHTML = '<p class="text-xs text-red-500">' + data.error + '</p>';
                return;
            }
            
            let html = '<div class="bg-slate-50 p-4 rounded-xl text-left">';
            html += '<p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 border-b pb-2">Status Stok: <span class="text-primary-container">' + data.stok + ' Tersedia</span></p>';
            
            if(data.borrowers && data.borrowers.length > 0) {
                html += '<p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Peminjam Aktif:</p>';
                html += '<div class="space-y-2 max-h-32 overflow-y-auto">';
                data.borrowers.forEach(b => {
                    html += '<div class="bg-white p-2 rounded border border-slate-100 flex justify-between items-center">';
                    html += '<div><p class="text-xs font-bold text-primary-container leading-tight">' + b.nama_siswa + '</p><p class="text-[9px] text-slate-400">Kelas ' + b.kelas + '</p></div>';
                    
                    let tempo = new Date(b.tanggal_jatuh_tempo).getTime();
                    let now = new Date().getTime();
                    let statusClass = now > tempo ? 'text-red-500' : 'text-slate-500';
                    
                    html += '<p class="text-[10px] ' + statusClass + ' text-right">Tempo:<br>' + new Date(b.tanggal_jatuh_tempo).toLocaleDateString('id-ID') + '</p>';
                    html += '</div>';
                });
                html += '</div>';
            } else {
                html += '<p class="text-xs text-slate-400 italic text-center py-2">Tidak ada peminjam aktif saat ini.</p>';
            }
            html += '</div>';
            
            document.getElementById('qrBookStatus').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('qrBookStatus').innerHTML = '<p class="text-xs text-red-500">Gagal memuat status.</p>';
        });
}

function filterBuku() {
    let input = document.getElementById("searchBuku");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("tabelBuku");
    let tr = table.getElementsByTagName("tr");

    for (let i = 0; i < tr.length; i++) {
        let tdTitle = tr[i].getElementsByTagName("td")[0];
        if (tdTitle) {
            let txtValue = tdTitle.textContent || tdTitle.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}
</script>

<!-- QR Code Buku Modal -->
<div id="modalBukuQR" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" onclick="this.classList.add('hidden')">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full text-center shadow-2xl animate-scaleIn" onclick="event.stopPropagation()">
        <h4 class="font-bold text-primary-container text-lg mb-1">QR Code & Status</h4>
        <p id="qrBukuTitle" class="text-xs font-bold text-slate-500 mb-4 line-clamp-1"></p>
        <div class="flex justify-center mb-4">
            <img id="qrBukuImage" src="" class="w-40 h-40 border-2 border-slate-100 rounded-xl p-2">
        </div>
        
        <!-- Book Status Area -->
        <div id="qrBookStatus" class="mb-4 text-left"></div>
        
        <p class="text-[9px] text-slate-400 uppercase tracking-widest mb-4">Tempelkan QR ini pada fisik buku</p>
        <button onclick="document.getElementById('modalBukuQR').classList.add('hidden')" class="w-full bg-primary-container text-white py-2 rounded-lg font-bold text-sm hover:opacity-90 transition-all">Tutup</button>
    </div>
</div>
</body>
</html>