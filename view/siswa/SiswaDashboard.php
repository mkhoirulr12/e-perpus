<?php
session_start();
if (!isset($_SESSION['siswa_id']) || $_SESSION['level'] !== 'siswa') {
    header("Location: ../../view/auth/Login.php?role=siswa");
    exit;
}

require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

$siswa_id = $_SESSION['siswa_id'];
$nama_siswa = $_SESSION['nama'];

$page = $_GET['page'] ?? 'dashboard';

// Ambil profil siswa
$stmtProfil = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
$stmtProfil->execute([$siswa_id]);
$profil = $stmtProfil->fetch(PDO::FETCH_ASSOC);

// Ambil statistik literasi
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_siswa = ?");
$stmtTotal->execute([$siswa_id]);
$totalLiterasi = $stmtTotal->fetchColumn();

// Ambil pinjaman aktif (Status buku yang sedang dipinjam)
$stmtAktif = $conn->prepare("
    SELECT p.id_peminjaman, p.kode_peminjaman, p.tanggal_jatuh_tempo, b.judul_buku, b.penerbit, b.gambar, b.id_buku
    FROM peminjaman p 
    JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
    JOIN buku b ON dp.id_buku = b.id_buku 
    WHERE p.id_siswa = ? AND p.status = 'dipinjam' 
    ORDER BY p.tanggal_jatuh_tempo ASC
");
$stmtAktif->execute([$siswa_id]);
$pinjamanAktif = $stmtAktif->fetchAll(PDO::FETCH_ASSOC);
$totalAktif = count($pinjamanAktif);

// Dashboard Page Data
if ($page === 'dashboard') {
    // Buku Terpopuler (Paling banyak dipinjam)
    $stmtPopuler = $conn->query("
        SELECT b.judul_buku, b.penerbit, b.gambar, COUNT(dp.id_buku) as total_pinjam
        FROM buku b
        JOIN detail_peminjaman dp ON b.id_buku = dp.id_buku
        GROUP BY b.id_buku
        ORDER BY total_pinjam DESC
        LIMIT 3
    ");
    $bukuPopuler = $stmtPopuler->fetchAll(PDO::FETCH_ASSOC);

    // Riwayat Singkat
    $stmtSingkat = $conn->prepare("
        SELECT b.judul_buku, p.tanggal_pinjam, p.status
        FROM peminjaman p
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman
        JOIN buku b ON dp.id_buku = b.id_buku
        WHERE p.id_siswa = ?
        ORDER BY p.created_at DESC
        LIMIT 3
    ");
    $stmtSingkat->execute([$siswa_id]);
    $riwayatSingkat = $stmtSingkat->fetchAll(PDO::FETCH_ASSOC);
}

// Katalog Page Data
$katalog_buku = [];
$categories = $conn->query("SELECT * FROM kategori")->fetchAll(PDO::FETCH_ASSOC);
$search_query = $_GET['q'] ?? '';
$filter_kategori = $_GET['kategori'] ?? '';

if ($page === 'katalog') {
    $sql = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori WHERE 1=1";
    $params = [];

    if (!empty($search_query)) {
        $sql .= " AND (b.judul_buku LIKE ? OR b.penerbit LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    if (!empty($filter_kategori)) {
        $sql .= " AND b.id_kategori = ?";
        $params[] = $filter_kategori;
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT 24";
    $stmtBuku = $conn->prepare($sql);
    $stmtBuku->execute($params);
    $katalog_buku = $stmtBuku->fetchAll(PDO::FETCH_ASSOC);
}

// Transaksi Page Data
$riwayat = [];
if ($page === 'transaksi') {
    $stmtRiwayat = $conn->prepare("
        SELECT p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.status, b.judul_buku, b.penerbit, pg.tanggal_kembali 
        FROM peminjaman p 
        JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman 
        JOIN buku b ON dp.id_buku = b.id_buku 
        LEFT JOIN pengembalian pg ON p.id_peminjaman = pg.id_peminjaman 
        WHERE p.id_siswa = ? ORDER BY p.created_at DESC
    ");
    $stmtRiwayat->execute([$siswa_id]);
    $riwayat = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
}

// Check session messages
$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

function getNavClass($current_page, $target_page) {
    if ($current_page === $target_page) {
        return "flex items-center gap-3 px-4 py-3 bg-[#2d5a27] text-white rounded-lg font-semibold shadow-sm transition-all";
    }
    return "flex items-center gap-3 px-4 py-3 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all";
}
?>
<!DOCTYPE html>
<html lang="id">
    <title>Siswa Dashboard - E-Perpus Prayasqi</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            font-family: 'Public Sans', sans-serif;
            background-color: #fbf9f8;
        }
        html { scroll-behavior: smooth; }
        /* Page transition animation */
        .page-content { animation: fadeSlideIn 0.4s ease-out; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        /* Sidebar link hover animation */
        aside nav a { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        aside nav a:hover { transform: translateX(4px); }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                  "primary": "#2d5a27",
                  "secondary": "#C5A059",
                  "error": "#ba1a1a",
                  "surface": "#fbf9f8"
              },
              "fontFamily": {
                  "stat-number": ["Newsreader"],
                  "body-main": ["Public Sans"],
                  "label-caps": ["Public Sans"],
                  "h3": ["Newsreader"],
                  "h2": ["Newsreader"],
                  "nav-link": ["Public Sans"]
              }
            }
          }
        }
    </script>
</head>
<body class="bg-surface text-slate-900">
<div class="flex min-h-screen">
    <!-- SideNavBar Navigation Shell -->
    <aside class="hidden md:flex flex-col h-screen w-64 bg-slate-50 border-r border-slate-200 p-4 space-y-2 sticky top-0 shrink-0">
        <a href="../../index.php" class="flex items-center gap-2 px-3 py-2 mb-2 text-slate-400 hover:text-primary rounded-lg hover:bg-slate-200 transition-all group">
            <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_back</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">E-Perpus Prayasqi</span>
        </a>
        <div class="mb-6 px-4">
            <div class="flex items-center gap-2 mb-1">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-auto">
                <h1 class="font-bold text-primary font-h3 text-xl">Prayasqi</h1>
            </div>
            <p class="text-slate-500 font-body-main text-[10px] uppercase tracking-widest mt-1">Civitas Akademika</p>
        </div>
        <nav class="flex-1 space-y-1">
            <a class="<?= getNavClass($page, 'dashboard') ?>" href="?page=dashboard">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-nav-link text-sm">Dashboard</span>
            </a>
            <a class="<?= getNavClass($page, 'katalog') ?>" href="?page=katalog">
                <span class="material-symbols-outlined">menu_book</span>
                <span class="font-nav-link text-sm">Katalog Buku</span>
            </a>
            <a class="<?= getNavClass($page, 'anggota') ?>" href="?page=anggota">
                <span class="material-symbols-outlined">group</span>
                <span class="font-nav-link text-sm">Data Anggota</span>
            </a>
            <a class="<?= getNavClass($page, 'transaksi') ?>" href="?page=transaksi">
                <span class="material-symbols-outlined">history</span>
                <span class="font-nav-link text-sm">Transaksi</span>
            </a>
            <a class="<?= getNavClass($page, 'pengaturan') ?>" href="?page=pengaturan">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-nav-link text-sm">Pengaturan</span>
            </a>
        </nav>
        <div class="pt-4 border-t border-slate-200">
            <a href="../../controller/LogoutController.php" class="w-full text-left flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded transition-all">
                <span class="material-symbols-outlined">logout</span>
                <span class="font-nav-link text-sm font-semibold">Keluar</span>
            </a>
        </div>
    </aside>

    <!-- Main Canvas -->
    <main class="flex-1 max-w-[1200px] mx-auto p-8 overflow-y-auto page-content">
        <!-- Header section -->
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="font-h2 text-3xl text-primary font-bold">Selamat Datang, <?= htmlspecialchars($nama_siswa) ?></h2>
                <p class="font-body-main text-slate-500 mt-1">Portal perpustakaan personal Anda.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="font-label-caps text-xs text-primary font-bold">ID ANGGOTA: #<?= htmlspecialchars($profil['nis']) ?></p>
                    <p class="font-body-main text-slate-500 text-[11px] uppercase tracking-wider">Kelas <?= htmlspecialchars($profil['kelas']) ?></p>
                </div>
                <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center overflow-hidden border-2 border-[#C5A059]">
                    <?php if(!empty($profil['foto'])): ?>
                        <img src="../../assets/img/profil/<?= $profil['foto'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="material-symbols-outlined text-slate-400 text-3xl">person</span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT ROUTING -->
        <?php if($page === 'dashboard'): ?>
            <!-- Dashboard View -->
            <section class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Active Loan Card -->
                    <div class="md:col-span-2 bg-white border border-slate-200 p-6 rounded-xl flex flex-col justify-between shadow-sm">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="font-h3 text-2xl text-primary font-bold">Status Pinjaman Aktif</h3>
                                <p class="font-body-main text-slate-500 text-sm mt-1">Buku yang sedang Anda bawa saat ini.</p>
                            </div>
                            <span class="bg-primary text-white px-3 py-1 font-bold rounded-full text-[10px] tracking-widest uppercase"><?= $totalAktif ?> Buku</span>
                        </div>
                        <div class="mt-6 space-y-4">
                            <?php if(empty($pinjamanAktif)): ?>
                                <div class="text-center py-8">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-2">library_books</span>
                                    <p class="text-sm text-slate-500">Tidak ada buku yang sedang dipinjam.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($pinjamanAktif as $pa): 
                                    $tgl_jatuh_tempo = strtotime($pa['tanggal_jatuh_tempo']);
                                    $is_terlambat = time() > $tgl_jatuh_tempo;
                                    $border_color = $is_terlambat ? 'border-red-500' : 'border-[#C5A059]';
                                ?>
                                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-lg border-l-4 <?= $border_color ?>">
                                    <div class="w-12 h-16 bg-slate-200 flex-shrink-0 rounded overflow-hidden flex items-center justify-center">
                                        <?php if(!empty($pa['gambar'])): ?>
                                            <img src="../../assets/img/buku/<?= $pa['gambar'] ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="material-symbols-outlined text-slate-400">menu_book</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-primary text-lg leading-tight mb-1"><?= htmlspecialchars($pa['judul_buku']) ?></p>
                                        <p class="font-body-main text-[11px] text-slate-500 uppercase tracking-wide">Batas Kembali: <strong class="<?= $is_terlambat ? 'text-red-600' : 'text-slate-700' ?>"><?= date('d M Y', $tgl_jatuh_tempo) ?></strong></p>
                                    </div>
                                    <div onclick="showQR('<?= $pa['kode_peminjaman'] ?>-<?= $pa['id_buku'] ?>', '<?= htmlspecialchars($pa['judul_buku'], ENT_QUOTES) ?>')" class="w-16 h-16 bg-white p-1 rounded border border-slate-200 ml-4 hidden sm:block cursor-pointer hover:ring-2 hover:ring-gold transition-all">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=PINJAM-<?= $pa['kode_peminjaman'] ?>-BUKU-<?= $pa['id_buku'] ?>" alt="QR" class="w-full h-full object-contain">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats Side Panel -->
                    <div class="flex flex-col gap-6">
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm text-center">
                            <p class="font-bold text-slate-500 text-[10px] uppercase tracking-widest mb-4">Total Buku Dibaca</p>
                            <div class="flex items-center justify-center gap-2">
                                <span class="font-stat-number text-6xl text-[#C5A059] font-bold"><?= $totalLiterasi ?></span>
                            </div>
                            <p class="mt-3 font-body-main text-xs text-slate-400">Selama menjadi anggota</p>
                        </div>
                        
                        <!-- Riwayat Singkat -->
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <h4 class="font-bold text-primary text-sm mb-4 uppercase tracking-widest border-b border-slate-50 pb-2">Riwayat Terakhir</h4>
                            <div class="space-y-4">
                                <?php if(empty($riwayatSingkat)): ?>
                                    <p class="text-xs text-slate-400 text-center">Belum ada riwayat.</p>
                                <?php else: ?>
                                    <?php foreach($riwayatSingkat as $rs): ?>
                                    <div class="flex items-start gap-3">
                                        <div class="w-2 h-2 rounded-full bg-secondary mt-1.5 shrink-0"></div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800 line-clamp-1"><?= htmlspecialchars($rs['judul_buku']) ?></p>
                                            <p class="text-[10px] text-slate-500 uppercase"><?= date('d M Y', strtotime($rs['tanggal_pinjam'])) ?> • <?= $rs['status'] ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buku Terpopuler Section -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-h3 text-xl text-primary font-bold mb-6 border-b border-slate-50 pb-4">Buku Paling Banyak Dipinjam</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <?php if(empty($bukuPopuler)): ?>
                            <p class="col-span-3 text-center text-slate-400 text-sm py-4">Data belum tersedia.</p>
                        <?php else: ?>
                            <?php foreach($bukuPopuler as $bp): ?>
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-lg">
                                <div class="w-12 h-16 bg-slate-200 rounded overflow-hidden flex items-center justify-center shrink-0">
                                    <?php if(!empty($bp['gambar'])): ?>
                                        <img src="../../assets/img/buku/<?= $bp['gambar'] ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-slate-400">trending_up</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-primary text-sm line-clamp-1"><?= htmlspecialchars($bp['judul_buku']) ?></h4>
                                    <p class="text-[10px] text-slate-500 uppercase"><?= htmlspecialchars($bp['penerbit']) ?></p>
                                    <p class="text-[10px] font-bold text-secondary mt-1"><?= $bp['total_pinjam'] ?>x Dipinjam</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        <?php elseif($page === 'katalog'): ?>
            <!-- Katalog Buku View -->
            <section>
                <div class="mb-8 space-y-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <h3 class="font-h3 text-2xl text-primary font-bold">Eksplorasi Koleksi</h3>
                        
                        <!-- Filter Kategori -->
                        <div class="flex flex-wrap gap-2">
                            <a href="?page=katalog" class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-colors <?= empty($filter_kategori) ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Semua</a>
                            <?php foreach($categories as $cat): ?>
                                <a href="?page=katalog&kategori=<?= $cat['id_kategori'] ?>&q=<?= urlencode($search_query) ?>" 
                                   class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-colors <?= ($filter_kategori == $cat['id_kategori']) ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    <?= htmlspecialchars($cat['nama_kategori']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <form action="" method="GET" class="flex max-w-2xl gap-2">
                        <input type="hidden" name="page" value="katalog">
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($filter_kategori) ?>">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-4 top-3 text-slate-400">search</span>
                            <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" class="w-full border border-slate-300 rounded-lg py-3 pl-12 pr-4 focus:ring-2 focus:ring-[#C5A059] focus:border-[#C5A059] outline-none" placeholder="Cari judul atau penerbit...">
                        </div>
                        <button type="submit" class="bg-[#C5A059] text-white px-8 py-3 rounded-lg font-bold tracking-wider hover:bg-[#b08d4a]">CARI</button>
                    </form>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <?php if(empty($katalog_buku)): ?>
                        <div class="col-span-full py-12 text-center text-slate-500 border border-dashed border-slate-300 rounded-xl">
                            <span class="material-symbols-outlined text-4xl mb-2">menu_book</span>
                            <p>Buku yang Anda cari tidak ditemukan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($katalog_buku as $buku): ?>
                        <div class="cursor-pointer bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow group flex flex-col" onclick='showBookPopup(<?= json_encode($buku) ?>)'>
                            <div class="aspect-[2/3] bg-slate-100 flex items-center justify-center text-slate-300 border-b border-slate-100 overflow-hidden">
                                <?php if(!empty($buku['gambar'])): ?>
                                    <img src="../../assets/img/buku/<?= $buku['gambar'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-5xl">auto_stories</span>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 flex flex-col flex-1">
                                <h4 class="font-bold text-primary text-sm mb-1 line-clamp-2 leading-tight"><?= htmlspecialchars($buku['judul_buku']) ?></h4>
                                <p class="text-[10px] text-slate-400 uppercase tracking-wide mb-3 line-clamp-1"><?= htmlspecialchars($buku['penerbit'] ?? '') ?></p>
                                <div class="mt-auto">
                                    <?php if(($buku['status'] ?? 'tersedia') === 'tersedia'): ?>
                                        <button onclick="event.stopPropagation(); openModalPinjam(<?= $buku['id_buku'] ?>, '<?= htmlspecialchars($buku['judul_buku'], ENT_QUOTES) ?>')" class="w-full bg-primary text-white text-[10px] font-bold py-2 rounded flex items-center justify-center gap-1 uppercase tracking-wider hover:opacity-90 transition-opacity">
                                            <span class="material-symbols-outlined text-xs">add_circle</span> Ajukan Pinjam
                                        </button>
                                    <?php else: ?>
                                        <button disabled class="w-full bg-slate-100 text-slate-400 text-[10px] font-bold py-2 rounded flex items-center justify-center gap-1 cursor-not-allowed uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-xs">block</span> Tidak Tersedia
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        <?php elseif($page === 'anggota'): ?>
            <!-- Data Anggota View -->
            <section>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <!-- Banner Hijau -->
                    <div class="h-32 bg-primary relative">
                        <div class="absolute -bottom-16 left-8 w-32 h-32 bg-white rounded-full p-1.5 shadow-lg">
                            <div class="w-full h-full bg-slate-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-secondary">
                                <?php if(!empty($profil['foto'])): ?>
                                    <img src="../../assets/img/profil/<?= $profil['foto'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-slate-400 text-6xl">person</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-20 px-8 pb-8">
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-primary mb-1"><?= htmlspecialchars($profil['nama_siswa']) ?></h3>
                                <p class="text-slate-500 font-medium">Siswa Aktif - Kelas <?= htmlspecialchars($profil['kelas']) ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-12">
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Nomor Induk Siswa (NIS)</p>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($profil['nis']) ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Jenis Kelamin</p>
                                <p class="font-semibold text-slate-800"><?= isset($profil['jenis_kelamin']) ? ($profil['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') : '-' ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Tanggal Bergabung</p>
                                <p class="font-semibold text-slate-800"><?= date('d M Y', strtotime($profil['created_at'])) ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Nomor Handphone / WA</p>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($profil['no_hp'] ?: 'Belum diatur') ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Nama Orang Tua / Wali</p>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($profil['nama_orangtua'] ?: '-') ?></p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Kelas</p>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($profil['kelas']) ?></p>
                            </div>
                            <div class="md:col-span-3">
                                <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1">Alamat Domisili</p>
                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($profil['alamat'] ?: 'Belum diatur') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        <?php elseif($page === 'transaksi'): ?>
            <!-- Transaksi View -->
            <section>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="font-h3 text-2xl text-primary font-bold">Riwayat Peminjaman</h3>
                        <button onclick="document.getElementById('rekapModal').classList.remove('hidden')" class="bg-primary hover:bg-primary-dark text-white font-bold text-xs uppercase tracking-widest px-4 py-2 rounded flex items-center gap-2 shadow-sm transition-colors">
                            <span class="material-symbols-outlined text-sm">description</span> LIHAT REKAP PDF
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left font-body-main">
                            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase tracking-widest border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4">Buku</th>
                                    <th class="px-6 py-4">Tgl Pinjam</th>
                                    <th class="px-6 py-4">Tgl Kembali / Jatuh Tempo</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if(empty($riwayat)): ?>
                                    <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">Belum ada riwayat transaksi.</td></tr>
                                <?php else: ?>
                                    <?php foreach($riwayat as $r): 
                                        $status_text = '';
                                        $status_class = '';
                                        if ($r['status'] == 'dikembalikan') {
                                            $status_text = 'SUDAH KEMBALI';
                                            $status_class = 'bg-green-100 text-green-700';
                                            $tgl_display = date('d M Y', strtotime($r['tanggal_kembali']));
                                        } elseif ($r['status'] == 'diajukan') {
                                            $status_text = 'MENUNGGU ACC';
                                            $status_class = 'bg-yellow-100 text-yellow-700';
                                            $tgl_display = '-';
                                        } else {
                                            $tgl_jatuh_tempo = strtotime($r['tanggal_jatuh_tempo']);
                                            $tgl_display = date('d M Y', $tgl_jatuh_tempo);
                                            if (time() > $tgl_jatuh_tempo) {
                                                $status_text = 'TERLAMBAT';
                                                $status_class = 'bg-red-100 text-red-700';
                                            } else {
                                                $status_text = 'DIPINJAM';
                                                $status_class = 'bg-blue-100 text-blue-700';
                                            }
                                        }
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-primary text-sm"><?= htmlspecialchars($r['judul_buku']) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5 uppercase"><?= htmlspecialchars($r['penerbit']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-700"><?= date('d M Y', strtotime($r['tanggal_pinjam'])) ?></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-slate-800"><?= $tgl_display ?></td>
                                        <td class="px-6 py-4">
                                            <span class="<?= $status_class ?> px-2 py-1 rounded text-[10px] font-bold tracking-wider inline-block text-center min-w-[90px]"><?= $status_text ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        <?php elseif($page === 'pengaturan'): ?>
            <!-- Pengaturan View -->
            <section>
                <?php if(!empty($success_msg)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 text-sm font-medium">
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">check_circle</span> <?= $success_msg ?></div>
                    </div>
                <?php endif; ?>
                <?php if(!empty($error_msg)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 text-sm font-medium">
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">error</span> <?= $error_msg ?></div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-xl font-bold text-primary">Pengaturan Akun</h3>
                        <p class="text-sm text-slate-500 mt-1">Kelola informasi profil dan keamanan akun Anda.</p>
                    </div>

                    <form action="../../controller/SiswaController.php?action=update_profil" method="POST" enctype="multipart/form-data" class="p-6">
                        <!-- Foto Profil -->
                        <div class="flex items-center gap-6 mb-8 pb-6 border-b border-slate-100">
                            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-secondary flex-shrink-0">
                                <?php if(!empty($profil['foto'])): ?>
                                    <img src="../../assets/img/profil/<?= $profil['foto'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="material-symbols-outlined text-slate-400 text-4xl">person</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Foto Profil</label>
                                <input type="file" name="foto" accept="image/*" class="text-sm text-slate-500">
                                <p class="text-[10px] text-slate-400 mt-1">Format: JPG/PNG, Max 2MB.</p>
                            </div>
                        </div>

                        <!-- Data Pribadi -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 mb-8">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Nama Lengkap</label>
                                <input type="text" name="nama_siswa" value="<?= htmlspecialchars($profil['nama_siswa'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">NIS (ID Anggota)</label>
                                <input type="text" value="<?= htmlspecialchars($profil['nis'] ?? '') ?>" class="w-full border border-slate-200 rounded-lg p-3 bg-slate-50 text-slate-500 text-sm cursor-not-allowed" disabled>
                                <p class="text-[10px] text-slate-400 mt-1">NIS tidak dapat diubah.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Nomor WhatsApp</label>
                                <input type="text" name="no_hp" value="<?= htmlspecialchars($profil['no_hp'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm" placeholder="08123456789">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Nama Orang Tua / Wali</label>
                                <input type="text" name="nama_orangtua" value="<?= htmlspecialchars($profil['nama_orangtua'] ?? '') ?>" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm" placeholder="Nama orang tua / wali">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Alamat Domisili</label>
                                <textarea name="alamat" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm" rows="3" placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($profil['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Keamanan -->
                        <div class="pt-6 border-t border-slate-100 mb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-secondary">lock</span>
                                <h4 class="font-bold text-primary text-lg">Ubah Kata Sandi</h4>
                            </div>
                            <div class="max-w-md">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Kata Sandi Baru</label>
                                <input type="password" name="password_baru" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm" placeholder="Biarkan kosong jika tidak ingin mengubah">
                                <p class="text-[10px] text-slate-400 mt-1">Hanya isi jika ingin mengganti password login Anda.</p>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="bg-primary text-white font-bold tracking-wider px-8 py-3 rounded-lg shadow hover:opacity-90 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">save</span> SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>

    </main>
</div>

<!-- MODAL REKAP -->
<div id="rekapModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl w-full max-w-md p-8 shadow-2xl scale-in transform transition-all">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-primary">Rekap Peminjaman</h3>
            <button onclick="document.getElementById('rekapModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <p class="text-slate-500 mb-8">Pilih jenis rekap yang ingin Anda lihat dalam format PDF.</p>
        
        <div class="space-y-4">
            <!-- Rekap Semua -->
            <a href="rekap_pdf.php?filter=all" target="_blank" class="flex items-center gap-4 p-4 border-2 border-slate-100 rounded-xl hover:border-primary hover:bg-slate-50 transition-all group">
                <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                    <span class="material-symbols-outlined text-primary text-2xl">history</span>
                </div>
                <div class="text-left">
                    <p class="font-bold text-slate-800">Semua Riwayat</p>
                    <p class="text-xs text-slate-500">Seluruh data peminjaman selama ini</p>
                </div>
            </a>

            <!-- Rekap Per Bulan -->
            <div class="p-6 border-2 border-slate-100 rounded-xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">calendar_month</span>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800">Rekap Per Bulan</p>
                        <p class="text-xs text-slate-500">Pilih bulan dan tahun laporan</p>
                    </div>
                </div>
                
                <form action="rekap_pdf.php" method="GET" target="_blank" class="space-y-4">
                    <input type="hidden" name="filter" value="month">
                    <div class="grid grid-cols-2 gap-3">
                        <select name="month" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-primary outline-none">
                            <?php 
                            $months_list = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            foreach($months_list as $i => $m_name): 
                            ?>
                                <option value="<?= $i+1 ?>" <?= (date('n') == $i+1) ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-primary outline-none">
                            <?php for($y=date('Y'); $y>=2020; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 text-white font-bold py-2.5 rounded-lg text-sm hover:bg-slate-900 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">print</span> CETAK REKAP BULANAN
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- QR Code Popup Modal -->
<div id="modalQR" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" onclick="this.classList.add('hidden')">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl" onclick="event.stopPropagation()">
        <h4 class="font-bold text-primary text-lg mb-2">QR Code Peminjaman</h4>
        <p id="qrTitle" class="text-sm text-slate-500 mb-6"></p>
        <div class="flex justify-center mb-6">
            <img id="qrImage" src="" class="w-48 h-48 border-2 border-slate-100 rounded-xl p-2">
        </div>
        <p class="text-[10px] text-slate-400 uppercase tracking-widest">Scan QR ini untuk verifikasi pengembalian</p>
        <button onclick="document.getElementById('modalQR').classList.add('hidden')" class="mt-6 bg-primary text-white px-8 py-2 rounded-lg font-bold text-sm hover:opacity-90 transition-all">Tutup</button>
    </div>
</div>

<!-- Modal Ajukan Pinjam -->
<div id="modalPinjam" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h4 class="font-bold text-primary text-lg">Ajukan Pinjaman</h4>
            <button onclick="document.getElementById('modalPinjam').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form action="../../controller/SiswaController.php?action=ajukan_pinjam" method="POST">
            <input type="hidden" name="id_buku" id="pinjam_id_buku">
            <p id="pinjam_judul" class="text-sm font-bold text-slate-700 mb-4 bg-slate-50 p-3 rounded-lg border border-slate-100"></p>
            
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Lama Peminjaman (Hari)</label>
            <input type="number" name="durasi" value="7" min="1" max="14" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm mb-6" required>
            
            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold text-sm hover:opacity-90 transition-all flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">send</span> Ajukan Sekarang
            </button>
        </form>
    </div>
</div>

<!-- Book Popup Modal -->
<div id="bookPopup" class="hidden fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all" onclick="closeBookPopup()">
    <div class="bg-white rounded-2xl overflow-hidden w-full max-w-3xl flex flex-col md:flex-row shadow-2xl scale-in transform transition-all" onclick="event.stopPropagation()">
        <div class="w-full md:w-1/2 bg-slate-100 aspect-[2/3] md:aspect-auto">
            <img id="popupImg" src="" class="w-full h-full object-cover">
        </div>
        <div class="w-full md:w-1/2 p-8 flex flex-col justify-between">
            <div>
                <button onclick="closeBookPopup()" class="float-right text-slate-400 hover:text-primary"><span class="material-symbols-outlined">close</span></button>
                <p id="popupTema" class="text-xs font-bold text-[#C5A059] uppercase tracking-widest mb-2"></p>
                <h3 id="popupJudul" class="text-3xl font-bold text-primary leading-tight mb-4 font-h3"></h3>
                <div class="space-y-3 font-body-main">
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">edit_note</span><span id="popupPenerbit" class="text-sm"></span></div>
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">event</span><span id="popupTahun" class="text-sm"></span></div>
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">inventory_2</span><span id="popupStok" class="text-sm"></span></div>
                </div>
            </div>
            <div class="mt-8">
                <button id="btnPopupPinjam" onclick="" class="w-full bg-primary text-white py-3 rounded-lg font-bold text-sm uppercase tracking-wider shadow-lg hover:opacity-90 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_circle</span> AJUKAN PINJAM
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showQR(code, title) {
    document.getElementById('qrTitle').innerText = title;
    document.getElementById('qrImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PINJAM-' + code;
    document.getElementById('modalQR').classList.remove('hidden');
}

function openModalPinjam(id, judul) {
    document.getElementById('pinjam_id_buku').value = id;
    document.getElementById('pinjam_judul').innerText = judul;
    document.getElementById('modalPinjam').classList.remove('hidden');
    closeBookPopup();
}

function showBookPopup(b) {
    const popupImg = document.getElementById('popupImg');
    if (b.gambar) {
        popupImg.src = '../../assets/img/buku/' + b.gambar;
        popupImg.classList.remove('hidden');
        if(document.getElementById('popupPlaceholder')) document.getElementById('popupPlaceholder').remove();
    } else {
        popupImg.classList.add('hidden');
        if(!document.getElementById('popupPlaceholder')) {
            const div = document.createElement('div');
            div.id = 'popupPlaceholder';
            div.className = 'w-full h-full bg-slate-200 flex items-center justify-center';
            div.innerHTML = '<span class="material-symbols-outlined text-8xl text-slate-400">auto_stories</span>';
            popupImg.parentElement.appendChild(div);
        }
    }
    document.getElementById('popupJudul').innerText = b.judul_buku;
    document.getElementById('popupTema').innerText = b.nama_tema || 'UMUM';
    document.getElementById('popupPenerbit').innerText = 'Penerbit: ' + (b.penerbit || '-');
    document.getElementById('popupTahun').innerText = 'Tahun Terbit: ' + (b.tahun_terbit || '-');
    document.getElementById('popupStok').innerText = 'Stok Tersedia: ' + b.jumlah_buku;
    
    const btnPinjam = document.getElementById('btnPopupPinjam');
    if (b.status === 'tersedia' || !b.status) {
        btnPinjam.onclick = function() { openModalPinjam(b.id_buku, b.judul_buku); };
        btnPinjam.classList.remove('hidden');
    } else {
        btnPinjam.classList.add('hidden');
    }
    
    document.getElementById('bookPopup').classList.remove('hidden');
}

function closeBookPopup() {
    document.getElementById('bookPopup').classList.add('hidden');
}
</script>
</body>
</html>