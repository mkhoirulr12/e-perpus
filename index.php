<?php
session_start();

require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();

if (!$conn) {
    die("<div style='text-align:center; padding: 50px; font-family:sans-serif; background:#fef2f2; color:#991b1b; min-height: 100vh;'>
        <h2 style='font-size:24px; margin-bottom:10px;'>Koneksi Database Gagal (Target Machine Actively Refused)</h2>
        <p>Aplikasi tidak dapat terhubung ke database. Masalah ini biasanya terjadi karena <b>MySQL belum berjalan</b>.</p>
        <p style='margin-top:20px; padding:15px; background:#fff; display:inline-block; border:1px solid #fca5a5; border-radius:8px;'>
            <b>Solusi Cepat:</b><br/>
            1. Buka <b>XAMPP Control Panel</b> di komputer Anda.<br/>
            2. Klik tombol <b>Start</b> pada baris <b>MySQL</b> (pastikan berubah menjadi warna hijau).<br/>
            3. <i>Refresh</i> (Muat ulang) halaman ini.
        </p>
        </div>");
}

// Ambil statistik dari database
$stmt = $conn->query("SELECT SUM(jumlah_buku) as total_buku FROM buku");
$buku_row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_buku = $buku_row['total_buku'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total_siswa FROM siswa WHERE status = 'aktif'");
$total_siswa = $stmt->fetch(PDO::FETCH_ASSOC)['total_siswa'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total_pinjam FROM peminjaman");
$total_pinjam = $stmt->fetch(PDO::FETCH_ASSOC)['total_pinjam'] ?? 0;



// Ambil buku terbaru dengan tema atau berdasar pencarian
$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $conn->prepare("SELECT b.*, t.nama_tema FROM buku b LEFT JOIN tema t ON b.id_tema = t.id_tema WHERE b.judul_buku LIKE ? OR b.penerbit LIKE ? ORDER BY b.created_at DESC LIMIT 12");
    $stmt->execute(["%$search%", "%$search%"]);
    $latest_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $is_search_result = true;
} else {
    // Cek apakah ada buku unggulan
    $stmt = $conn->query("SELECT b.*, t.nama_tema FROM buku b LEFT JOIN tema t ON b.id_tema = t.id_tema WHERE b.is_featured = 1 ORDER BY b.created_at DESC");
    $latest_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika tidak ada buku unggulan, ambil 4 terbaru
    if (empty($latest_books)) {
        $stmt = $conn->query("SELECT b.*, t.nama_tema FROM buku b LEFT JOIN tema t ON b.id_tema = t.id_tema ORDER BY b.created_at DESC LIMIT 4");
        $latest_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $is_search_result = false;
}

// Ambil pengaturan sistem
$stmt = $conn->query("SELECT kunci, nilai FROM pengaturan");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$tentang_judul = $settings['tentang_judul'] ?? 'Pusat Literasi dan Inovasi Akademik';
$tentang_deskripsi = $settings['tentang_deskripsi'] ?? 'Perpustakaan kami bukan sekadar tempat penyimpanan buku, melainkan ekosistem pembelajaran yang dirancang untuk membangkitkan rasa ingin tahu.';

// Ambil buku untuk visual tentang
$tentang_buku_ids = $settings['tentang_buku_ids'] ?? '';
$tentang_visual_books = [];
if (!empty($tentang_buku_ids)) {
    $ids = explode(',', $tentang_buku_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT b.*, t.nama_tema FROM buku b LEFT JOIN tema t ON b.id_tema = t.id_tema WHERE b.id_buku IN ($placeholders) LIMIT 2");
    $stmt->execute($ids);
    $tentang_visual_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>E-Perpus Prayasqi - Gerbang Pengetahuan</title>
<link rel="icon" type="image/png" href="assets/img/logo.png">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "primary": "#2d5a27",
                "primary-dark": "#1e3d1a",
                "primary-container": "#2d5a27",
                "gold": "#C5A059",
            },
            "fontFamily": {
                "stat-number": ["Newsreader"],
                "body-main": ["Public Sans"],
                "label-caps": ["Public Sans"],
                "h3": ["Newsreader"],
                "h2": ["Newsreader"],
                "h1": ["Newsreader"],
                "nav-link": ["Public Sans"]
            }
          }
        }
      }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .text-gold { color: #C5A059; }
        .bg-gold { background-color: #C5A059; }
        html { scroll-behavior: smooth; }
        /* Smooth fade-in animation for sections */
        .fade-section {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.7s ease-out, transform 0.7s ease-out;
        }
        .fade-section.visible {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-scaleIn { animation: scaleIn 0.3s ease-out forwards; }
        /* Dropdown animation */
        .nav-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .group:hover .nav-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-body-main selection:bg-amber-100">
<!-- TopNavBar Shell -->
    <nav class="text-white font-['Newsreader'] serif tracking-tight fixed w-full top-0 z-50 border-b-2 border-[#C5A059] shadow-md" style="background-color: #2d5a27;">
<div class="flex justify-between items-center w-full px-8 py-4 max-w-[1200px] mx-auto">
<a href="index.php" class="flex items-center gap-3 text-2xl font-bold font-['Newsreader'] text-white hover:text-[#C5A059] transition-colors duration-300">
    <img src="assets/img/logo.png" alt="Logo" class="h-10 w-auto">
    <span>E-Perpus Prayasqi</span>
</a>
<div class="hidden md:flex items-center space-x-8">
<a class="nav-link text-[#C5A059] border-b-2 border-[#C5A059] pb-1 font-nav-link" href="#beranda">Beranda</a>
<div class="group relative py-1">
<button class="text-white/90 font-medium hover:text-[#C5A059] transition-colors duration-200 flex items-center gap-1 font-nav-link text-nav-link">
                        Buku <span class="material-symbols-outlined text-sm transition-transform duration-300 group-hover:rotate-180">expand_more</span>
</button>
<div class="nav-dropdown absolute top-full left-0 w-56 bg-white text-slate-900 border-t-2 border-[#C5A059] shadow-xl rounded-b-lg overflow-hidden z-50">
<a class="block px-4 py-3 hover:bg-slate-50 hover:text-amber-600 font-nav-link text-nav-link transition-colors duration-200 border-b border-slate-50" href="view/buku/pembelajaran.php">
    <span class="material-symbols-outlined text-sm mr-2 align-middle text-slate-400">school</span>
    Buku Pembelajaran
</a>
<a class="block px-4 py-3 hover:bg-slate-50 hover:text-amber-600 font-nav-link text-nav-link transition-colors duration-200 border-b border-slate-50" href="view/buku/umum.php">
    <span class="material-symbols-outlined text-sm mr-2 align-middle text-slate-400">auto_stories</span>
    Buku Umum
</a>
<a class="block px-4 py-3 hover:bg-slate-50 hover:text-amber-600 font-nav-link text-nav-link transition-colors duration-200" href="view/buku/islami.php">
    <span class="material-symbols-outlined text-sm mr-2 align-middle text-slate-400">mosque</span>
    Buku Islami
</a>
</div>
</div>
<a class="nav-link text-white/90 font-medium hover:text-[#C5A059] transition-colors duration-200 font-nav-link text-nav-link" href="#pustakawan">Pustakawan</a>
<a class="nav-link text-white/90 font-medium hover:text-[#C5A059] transition-colors duration-200 font-nav-link text-nav-link" href="#tentang">Tentang</a>
<a class="nav-link text-white/90 font-medium hover:text-[#C5A059] transition-colors duration-200 font-nav-link text-nav-link" href="#statistik">Statistik</a>

</div>
<div class="flex items-center gap-4">
<?php if(!isset($_SESSION['level'])): ?>
    <a href="view/auth/Login.php" class="ml-4 px-6 py-2 bg-[#C5A059] hover:bg-[#b08d4a] text-white rounded shadow-lg transition-all hover:scale-105 active:scale-95 font-nav-link uppercase tracking-wider inline-block">Masuk</a>
<?php else: ?>
    <div class="flex items-center gap-3">
        <?php
            $dash_url = "view/pustakawan/PustakawanDashboard.php";
            if($_SESSION['level'] == 'admin') $dash_url = "view/admin/dashboard.php";
            if($_SESSION['level'] == 'siswa') $dash_url = "view/siswa/SiswaDashboard.php";
        ?>
        <a href="<?= $dash_url ?>" class="px-6 py-2 bg-[#C5A059] hover:bg-[#b08d4a] text-white rounded shadow-lg transition-all hover:scale-105 active:scale-95 font-nav-link uppercase tracking-wider inline-block">Dashboard</a>
        <a href="controller/LogoutController.php" class="p-2 text-white/70 hover:text-white transition-colors" title="Keluar">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
<?php endif; ?>
</div>
</div>
</nav>

<!-- Hero Section -->
<header id="beranda" class="relative h-[500px] flex items-center overflow-hidden bg-gradient-to-br from-[#1e3d1a] to-[#2d5a27] pt-[72px]">
<div class="relative z-10 max-w-[1200px] mx-auto px-8 w-full text-white">
<div class="max-w-2xl">
<p class="font-label-caps text-gold uppercase tracking-[0.2em] mb-4">Gerbang Pengetahuan Institusi</p>
<h1 class="font-h1 text-4xl md:text-5xl mb-6 leading-tight font-bold text-white">Temukan Inspirasi di Setiap Halaman</h1>
<p class="font-body-main text-white/90 mb-10 max-w-lg">Akses berbagai koleksi literatur dan sumber pembelajaran terpercaya untuk mendukung akademik Anda di E-Perpus Prayasqi.</p>
<!-- Search Bar -->
<form action="index.php#koleksi" method="GET" class="relative flex items-center w-full max-w-xl group">
<div class="absolute left-4 z-10 text-slate-400">
<span class="material-symbols-outlined">search</span>
</div>
<input name="q" value="<?= htmlspecialchars($search) ?>" class="w-full pl-12 pr-32 py-4 rounded-lg bg-white text-slate-900 border-none focus:ring-2 focus:ring-primary font-body-main shadow-2xl" placeholder="Cari judul buku, penerbit..." type="text"/>
<button type="submit" class="absolute right-2 bg-primary px-6 py-2.5 text-white rounded-md font-label-caps hover:bg-primary-dark transition-all">CARI</button>
</form>
</div>
</div>
</header>

<!-- Library Stats Section -->
<section id="statistik" class="fade-section relative z-20 -mt-10 max-w-[1200px] mx-auto px-8">
<div class="grid grid-cols-1 md:grid-cols-3 gap-0 bg-white shadow-xl rounded overflow-hidden border border-slate-100">
<!-- Stat 1 -->
<div class="p-8 border-r border-slate-100 flex flex-col items-center text-center">
<span class="font-stat-number text-4xl font-bold text-gold mb-2"><?= htmlspecialchars($total_buku) ?></span>
<span class="font-label-caps text-sm text-primary tracking-widest uppercase font-bold">Total Buku</span>
</div>
<!-- Stat 2 -->
<div class="p-8 border-r border-slate-100 flex flex-col items-center text-center">
<span class="font-stat-number text-4xl font-bold text-gold mb-2"><?= htmlspecialchars($total_siswa) ?></span>
<span class="font-label-caps text-sm text-primary tracking-widest uppercase font-bold">Siswa Aktif</span>
</div>
<!-- Stat 3 -->
<div class="p-8 flex flex-col items-center text-center">
<span class="font-stat-number text-4xl font-bold text-gold mb-2"><?= htmlspecialchars($total_pinjam) ?></span>
<span class="font-label-caps text-sm text-primary tracking-widest uppercase font-bold">Peminjaman</span>
</div>
</div>
</section>


<!-- New Arrivals Section -->
<section id="koleksi" class="fade-section py-20 max-w-[1200px] mx-auto px-8">
<div class="flex justify-between items-end mb-12">
    <div>
        <p class="font-label-caps text-gold uppercase tracking-widest mb-2 font-bold"><?= $is_search_result ? 'Jelajahi' : (isset($latest_books[0]['is_featured']) && $latest_books[0]['is_featured'] ? 'Pilihan Redaksi' : 'Terbaru') ?></p>
        <h2 class="font-h2 text-3xl font-bold text-primary"><?= $is_search_result ? 'Hasil Pencarian: "' . htmlspecialchars($search) . '"' : (isset($latest_books[0]['is_featured']) && $latest_books[0]['is_featured'] ? 'Koleksi Unggulan' : 'Koleksi Buku Terbaru') ?></h2>
    </div>
</div>

<!-- Books Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-8">
<?php if(empty($latest_books)): ?>
    <p class="col-span-4 text-center text-slate-500 py-10"><?= $search ? 'Buku yang Anda cari tidak ditemukan.' : 'Belum ada data buku.' ?></p>
<?php else: ?>
    <?php foreach($latest_books as $buku): ?>
    <div class="group cursor-pointer" onclick="showBookPopup(<?= htmlspecialchars(json_encode($buku)) ?>)">
    <div class="aspect-[2/3] bg-slate-200 flex items-center justify-center overflow-hidden rounded-lg mb-4 shadow-sm group-hover:shadow-xl transition-all duration-300 relative border border-slate-200">
    <?php if(!empty($buku['gambar'])): ?>
        <img src="assets/img/buku/<?= $buku['gambar'] ?>" class="w-full h-full object-cover">
    <?php else: ?>
        <span class="material-symbols-outlined text-6xl text-slate-400">auto_stories</span>
    <?php endif; ?>
    <div class="absolute top-2 right-2">
        <?php if(isset($buku['is_featured']) && $buku['is_featured']): ?>
            <span class="bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase tracking-tighter shadow-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-[10px]">star</span> UNGGULAN
            </span>
        <?php else: ?>
            <span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-tighter">Baru</span>
        <?php endif; ?>
    </div>
    </div>
    <h3 class="font-h3 text-xl text-primary leading-tight mb-1 group-hover:text-gold transition-colors font-semibold"><?= htmlspecialchars($buku['judul_buku']) ?></h3>
    <p class="font-body-main text-xs text-gold uppercase font-bold mb-1"><?= htmlspecialchars($buku['nama_tema'] ?? 'Tanpa Tema') ?></p>
    <p class="font-body-main text-sm text-slate-500"><?= htmlspecialchars($buku['penerbit']) ?> (<?= htmlspecialchars($buku['tahun_terbit']) ?>)</p>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</section>

<!-- Pustakawan Section -->
<section id="pustakawan" class="fade-section py-20 bg-white border-y border-slate-200">
<div class="max-w-[1200px] mx-auto px-8">
<div class="text-center mb-12">
<p class="font-label-caps text-gold uppercase tracking-widest mb-2 font-bold">Tim Kami</p>
<h2 class="font-h2 text-3xl font-bold text-primary">Pustakawan</h2>
<p class="text-slate-500 mt-4 max-w-2xl mx-auto">Merekalah yang berdedikasi menjaga sumber ilmu dan membantu Anda menemukan informasi yang tepat.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
<?php
// Ambil data pustakawan lengkap dengan foto
$stmt = $conn->query("
    SELECT u.nama_user, u.level, p.poto_profil 
    FROM user u 
    LEFT JOIN pustakawan p ON u.id_user = p.id_user 
    WHERE u.level = 'pustakawan' AND u.status = 'aktif'
");
$pustakawans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if(empty($pustakawans)): ?>
    <p class="col-span-3 text-center text-slate-500 py-10">Belum ada data pustakawan.</p>
<?php else: ?>
    <?php foreach($pustakawans as $p): ?>
    <div class="bg-slate-50 rounded-xl p-8 text-center shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
        <div class="w-24 h-24 bg-primary text-white rounded-full mx-auto flex items-center justify-center mb-4 text-3xl shadow-lg overflow-hidden">
            <?php if(!empty($p['poto_profil']) && $p['poto_profil'] !== 'default.jpg' && file_exists('assets/img/profil/'.$p['poto_profil'])): ?>
                <img src="assets/img/profil/<?= $p['poto_profil'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <span class="material-symbols-outlined text-4xl">person</span>
            <?php endif; ?>
        </div>
        <h3 class="font-h3 text-xl font-bold text-primary mb-1"><?= htmlspecialchars($p['nama_user']) ?></h3>
        <p class="font-label-caps text-sm text-gold uppercase tracking-wider"><?= htmlspecialchars($p['level']) ?></p>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</section>

<!-- Tentang Section -->
<section id="tentang" class="fade-section py-20 bg-slate-50">
<div class="max-w-[1200px] mx-auto px-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
    <div>
        <p class="font-label-caps text-gold uppercase tracking-widest mb-2 font-bold">Tentang Perpustakaan</p>
        <h2 class="font-h2 text-3xl font-bold text-primary mb-6"><?= htmlspecialchars($tentang_judul) ?></h2>
        <p class="text-slate-600 mb-6 leading-relaxed">
            <?= nl2br(htmlspecialchars($tentang_deskripsi)) ?>
        </p>
        <div class="flex gap-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-gold mt-1">verified</span>
                <div>
                    <h4 class="font-label-caps text-sm text-primary uppercase font-bold mb-1">Koleksi Terkurasi</h4>
                    <p class="text-xs text-slate-500">Standar pendidikan terbaik.</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-gold mt-1">auto_stories</span>
                <div>
                    <h4 class="font-label-caps text-sm text-primary uppercase font-bold mb-1">E-Library 24/7</h4>
                    <p class="text-xs text-slate-500">Akses katalog digital darimana saja.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div class="aspect-square bg-slate-200 rounded-lg overflow-hidden flex items-center justify-center text-slate-400 border border-slate-200 shadow-lg <?= isset($tentang_visual_books[0]) ? 'cursor-pointer hover:scale-[1.02] transition-transform duration-300' : '' ?>" 
             onclick="<?= isset($tentang_visual_books[0]) ? 'showBookPopup('.htmlspecialchars(json_encode($tentang_visual_books[0])).')' : '' ?>">
            <?php if(isset($tentang_visual_books[0])): ?>
                <img src="assets/img/buku/<?= $tentang_visual_books[0]['gambar'] ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($tentang_visual_books[0]['judul_buku']) ?>">
            <?php else: ?>
                <span class="material-symbols-outlined text-5xl">menu_book</span>
            <?php endif; ?>
        </div>
        <div class="aspect-square bg-slate-200 rounded-lg overflow-hidden flex items-center justify-center text-slate-400 mt-8 border border-slate-200 shadow-lg <?= isset($tentang_visual_books[1]) ? 'cursor-pointer hover:scale-[1.02] transition-transform duration-300' : '' ?>"
             onclick="<?= isset($tentang_visual_books[1]) ? 'showBookPopup('.htmlspecialchars(json_encode($tentang_visual_books[1])).')' : '' ?>">
            <?php if(isset($tentang_visual_books[1])): ?>
                <img src="assets/img/buku/<?= $tentang_visual_books[1]['gambar'] ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($tentang_visual_books[1]['judul_buku']) ?>">
            <?php else: ?>
                <span class="material-symbols-outlined text-5xl">import_contacts</span>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</section>

<!-- Footer -->
<footer class="bg-white border-t border-slate-200 mt-10">
<div class="max-w-[1200px] mx-auto py-12 px-8 flex flex-col md:flex-row justify-between items-center gap-6">
<div class="flex flex-col items-center md:items-start gap-2">
<div class="font-['Newsreader'] text-lg font-bold text-primary flex items-center gap-2">
    <img src="assets/img/logo.png" alt="Logo" class="h-6 w-auto">
    E-Perpus Prayasqi
</div>
<div class="text-slate-500 text-sm">© 2026 Hak Cipta Dilindungi.</div>
</div>
</div>
</footer>

<script>
// Smooth scroll with offset for fixed navbar
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const target = document.getElementById(targetId);
        if (target) {
            const navHeight = document.querySelector('nav').offsetHeight;
            const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight - 10;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Intersection Observer for fade-in animation
const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.fade-section').forEach(section => {
    fadeObserver.observe(section);
});

// Active nav link highlight on scroll
const sections = document.querySelectorAll('section[id], header[id]');
const navLinks = document.querySelectorAll('.nav-link');

window.addEventListener('scroll', () => {
    let current = '';
    const navHeight = document.querySelector('nav').offsetHeight;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop - navHeight - 50;
        if (window.scrollY >= sectionTop) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('text-[#C5A059]', 'border-b-2', 'border-[#C5A059]');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('text-[#C5A059]', 'border-b-2', 'border-[#C5A059]');
        }
    });
});
</script>
</body><!-- Book Popup Modal -->
<div id="bookPopup" class="hidden fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm transition-all">
    <div class="bg-white rounded-2xl overflow-hidden w-full max-w-3xl flex flex-col md:flex-row shadow-2xl animate-scaleIn">
        <div class="w-full md:w-1/2 bg-slate-100 aspect-[2/3] md:aspect-auto">
            <img id="popupImg" src="" class="w-full h-full object-cover">
        </div>
        <div class="w-full md:w-1/2 p-8 flex flex-col justify-between">
            <div>
                <button onclick="closeBookPopup()" class="float-right text-slate-400 hover:text-primary"><span class="material-symbols-outlined">close</span></button>
                <p id="popupTema" class="text-xs font-bold text-gold uppercase tracking-widest mb-2"></p>
                <h3 id="popupJudul" class="text-3xl font-bold text-primary leading-tight mb-4"></h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">edit_note</span><span id="popupPenerbit" class="text-sm"></span></div>
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">event</span><span id="popupTahun" class="text-sm"></span></div>
                    <div class="flex items-center gap-3 text-slate-500"><span class="material-symbols-outlined text-sm">inventory_2</span><span id="popupStok" class="text-sm"></span></div>
                </div>
            </div>
            <div class="mt-8">
                <a href="view/auth/Login.php" class="block w-full bg-primary text-white text-center py-4 rounded-xl font-bold shadow-lg hover:bg-primary-dark transition-all">MASUK UNTUK PINJAM</a>
            </div>
        </div>
    </div>
</div>

<script>
function showBookPopup(b) {
    const popupImg = document.getElementById('popupImg');
    const placeholder = '<div class="w-full h-full bg-slate-200 flex items-center justify-center"><span class="material-symbols-outlined text-8xl text-slate-400">auto_stories</span></div>';
    
    if (b.gambar) {
        // Gunakan path yang lebih kuat
        const imgPath = 'assets/img/buku/' + b.gambar;
        popupImg.src = imgPath;
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
    document.getElementById('popupPenerbit').innerText = 'Penerbit: ' + b.penerbit;
    document.getElementById('popupTahun').innerText = 'Tahun Terbit: ' + b.tahun_terbit;
    document.getElementById('popupStok').innerText = 'Stok Tersedia: ' + b.jumlah_buku;
    document.getElementById('bookPopup').classList.remove('hidden');
}
function closeBookPopup() {
    document.getElementById('bookPopup').classList.add('hidden');
}
</script>
