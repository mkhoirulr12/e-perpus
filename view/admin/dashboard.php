<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../../view/auth/Login.php?role=admin");
    exit;
}

require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

$page = $_GET['page'] ?? 'dashboard';
$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['nama'];

// Data Detail Admin
$stmtA = $conn->prepare("SELECT u.*, a.nama_lengkap, a.no_telp, a.alamat, a.jabatan_guru, a.poto_profil FROM user u LEFT JOIN admin a ON u.id_user = a.id_user WHERE u.id_user = ?");
$stmtA->execute([$user_id]);
$admin_data = $stmtA->fetch(PDO::FETCH_ASSOC);

// Statistik Global
$totalBuku = $conn->query("SELECT SUM(jumlah_buku) FROM buku")->fetchColumn();
$totalSiswa = $conn->query("SELECT COUNT(*) FROM siswa WHERE status='aktif'")->fetchColumn();
$totalPeminjaman = $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();

// Data per Halaman
if ($page === 'dashboard') {
    // Siswa Peminjam Aktif
    $peminjam_aktif = $conn->query("SELECT s.*, p.tanggal_pinjam, b.judul_buku FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman JOIN buku b ON dp.id_buku = b.id_buku WHERE p.status = 'dipinjam' ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'buku') {
    $books = $conn->query("SELECT b.*, k.nama_kategori, t.nama_tema FROM buku b LEFT JOIN kategori k ON b.id_kategori = k.id_kategori LEFT JOIN tema t ON b.id_tema = t.id_tema ORDER BY b.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
    $themes = $conn->query("SELECT * FROM tema ORDER BY nama_tema ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'siswa') {
    $students = $conn->query("SELECT * FROM siswa ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'pustakawan') {
    $librarians = $conn->query("SELECT u.*, p.guru, p.no_hp, p.alamat, p.poto_profil FROM user u LEFT JOIN pustakawan p ON u.id_user = p.id_user WHERE u.level='pustakawan' ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'riwayat') {
    $history = $conn->query("SELECT p.*, s.nama_siswa, s.nis, b.judul_buku, b.kode_buku FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa JOIN detail_peminjaman dp ON p.id_peminjaman = dp.id_peminjaman JOIN buku b ON dp.id_buku = b.id_buku ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($page === 'pengaturan_beranda') {
    $stmt = $conn->query("SELECT kunci, nilai FROM pengaturan");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    // Fetch all books for selection
    $all_books = $conn->query("SELECT id_buku, judul_buku, kode_buku, is_featured FROM buku ORDER BY judul_buku ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getNavClass($current, $target) {
    return $current === $target ? "flex items-center gap-3 px-4 py-3 bg-primary-container text-white rounded-xl font-bold shadow-lg" : "flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-100 rounded-xl transition-all";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - E-Perpus Prayasqi</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@600&family=Public+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>tailwind.config={theme:{extend:{colors:{"primary-container":"#2d5a27","gold":"#C5A059"},fontFamily:{body:["Public Sans"],heading:["Newsreader"]}}}}</script>
    <style>
        .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;}
        body{font-family:'Public Sans',sans-serif;background-color:#f8fafc;}
        html{scroll-behavior:smooth;}
        .hide-scrollbar::-webkit-scrollbar{display:none;}
        /* Page transition animation */
        .page-content{animation:fadeSlideIn 0.4s ease-out;}
        @keyframes fadeSlideIn{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
        /* Sidebar link hover animation */
        aside nav a{transition:all 0.25s cubic-bezier(0.4, 0, 0.2, 1);}
        aside nav a:hover{transform:translateX(4px);}
    </style>
</head>
<body class="text-slate-900">
<div class="flex min-h-screen">
    <aside class="w-64 border-r border-slate-200 bg-white flex flex-col p-4 space-y-2 sticky top-0 h-screen shrink-0 shadow-sm z-20">
        <a href="../../index.php" class="flex items-center gap-2 px-3 py-2 mb-2 text-slate-400 hover:text-primary-container rounded-lg hover:bg-slate-50 transition-all group">
            <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_back</span>
            <span class="text-[10px] font-bold uppercase tracking-widest">E-Perpus Prayasqi</span>
        </a>
        <div class="mb-8 px-2"><div class="flex items-center gap-2 mb-1"><img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-auto"><h1 class="font-bold text-primary-container font-heading text-xl tracking-tight">Prayasqi</h1></div><p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Authority Panel</p></div>
        <nav class="flex-grow space-y-1">
            <a class="<?=getNavClass($page,'dashboard')?>" href="?page=dashboard"><span class="material-symbols-outlined">dashboard</span><span class="text-sm">Dashboard</span></a>
            <a class="<?=getNavClass($page,'buku')?>" href="?page=buku"><span class="material-symbols-outlined">auto_stories</span><span class="text-sm">Koleksi Buku</span></a>
            <a class="<?=getNavClass($page,'siswa')?>" href="?page=siswa"><span class="material-symbols-outlined">group</span><span class="text-sm">Data Siswa</span></a>
            <a class="<?=getNavClass($page,'pustakawan')?>" href="?page=pustakawan"><span class="material-symbols-outlined">supervisor_account</span><span class="text-sm">Pustakawan</span></a>
            <a class="<?=getNavClass($page,'riwayat')?>" href="?page=riwayat"><span class="material-symbols-outlined">history</span><span class="text-sm">Riwayat</span></a>
            <a class="<?=getNavClass($page,'pengaturan_beranda')?>" href="?page=pengaturan_beranda"><span class="material-symbols-outlined">settings_suggest</span><span class="text-sm">Pengaturan Beranda</span></a>
            <a class="<?=getNavClass($page,'pengaturan')?>" href="?page=pengaturan"><span class="material-symbols-outlined">account_circle</span><span class="text-sm">Profil</span></a>
        </nav>
        <div class="pt-6 border-t border-slate-100">
            <div class="flex items-center gap-3 px-3 py-3 mb-4 bg-slate-50 rounded-xl border border-slate-100">
                <div class="w-10 h-10 bg-primary-container text-white rounded-full flex items-center justify-center font-bold text-lg shadow-inner overflow-hidden">
                    <?php if(!empty($admin_data['poto_profil']) && $admin_data['poto_profil'] !== 'default.jpg' && file_exists('../../assets/img/profil/'.$admin_data['poto_profil'])): ?>
                        <img src="../../assets/img/profil/<?=$admin_data['poto_profil']?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <img src="../../assets/img/profil/admin_default.png" class="w-full h-full object-cover">
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold truncate"><?=$user_nama?></p>
                    <p class="text-[9px] text-slate-400 uppercase tracking-widest"><?=$admin_data['jabatan_guru']??'Admin'?></p>
                </div>
            </div>
            <a href="../../controller/LogoutController.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl font-bold text-sm"><span class="material-symbols-outlined">logout</span>Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-8 py-5 flex justify-between items-center sticky top-0 z-50 shadow-sm"><h2 class="font-heading text-xl font-bold text-primary-container capitalize"><?=$page?></h2></header>
        
        <div class="p-8 page-content">
            <!-- Alerts -->
            <?php if(isset($_SESSION['success'])): ?><div class="mb-6 p-4 bg-green-100 text-green-700 rounded-xl flex items-center gap-3"><span class="material-symbols-outlined">check_circle</span><span class="text-sm font-bold"><?=$_SESSION['success']?></span><?php unset($_SESSION['success']); ?></div><?php endif; ?>

            <?php if($page === 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center"><span class="material-symbols-outlined">book</span></div><div><p class="text-[10px] font-bold text-slate-400 uppercase">Total Buku</p><h3 class="text-2xl font-bold"><?=$totalBuku?></h3></div></div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center"><span class="material-symbols-outlined">group</span></div><div><p class="text-[10px] font-bold text-slate-400 uppercase">Siswa Aktif</p><h3 class="text-2xl font-bold"><?=$totalSiswa?></h3></div></div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4"><div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center"><span class="material-symbols-outlined">swap_horiz</span></div><div><p class="text-[10px] font-bold text-slate-400 uppercase">Sedang Pinjam</p><h3 class="text-2xl font-bold"><?=$totalPeminjaman?></h3></div></div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50"><h3 class="font-bold text-primary-container">Daftar Peminjaman Aktif</h3></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest"><tr><th class="px-6 py-4">Siswa</th><th class="px-6 py-4">Buku Dipinjam</th><th class="px-6 py-4">Tanggal Pinjam</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($peminjam_aktif as $pa): ?>
                                <tr class="hover:bg-slate-50"><td class="px-6 py-4"><b><?=$pa['nama_siswa']?></b><br><small class="text-slate-500"><?=$pa['nis']?></small></td><td class="px-6 py-4"><b><?=$pa['judul_buku']?></b></td><td class="px-6 py-4 text-slate-500"><?=date('d M Y', strtotime($pa['tanggal_pinjam']))?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'buku'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between bg-slate-50/50"><h4 class="font-bold text-primary-container">Katalog Buku Lengkap</h4><button onclick="openAddBuku()" class="bg-gold text-primary-container px-4 py-2 rounded-lg text-xs font-bold">+ Tambah Koleksi</button></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest"><tr><th class="px-6 py-4">Buku</th><th class="px-6 py-4">Kategori / Tema</th><th class="px-6 py-4">Stok</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($books as $b): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 flex items-center gap-4">
                                        <div onclick="previewCover('<?=$b['gambar']?>')" class="w-10 h-14 bg-slate-100 rounded overflow-hidden shadow-sm flex-shrink-0 flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-gold transition-all">
                                            <?php if(!empty($b['gambar'])): ?>
                                                <img src="../../assets/img/buku/<?=$b['gambar']?>" alt="Cover" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="material-symbols-outlined text-slate-300">auto_stories</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <b class="truncate block"><?=$b['judul_buku']?></b>
                                            <small class="text-slate-500"><?=$b['kode_buku']?></small>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 uppercase text-[10px]"><span class="text-primary font-bold"><?=$b['nama_kategori']?></span> / <span class="text-slate-500"><?=$b['nama_tema']??'-'?></span></td>
                                    <td class="px-6 py-4"><b><?=$b['jumlah_buku']?></b></td>
                                    <td class="px-6 py-4 text-right space-x-2"><button onclick="showBukuQR('<?=$b['kode_buku']?>', '<?=htmlspecialchars($b['judul_buku'], ENT_QUOTES)?>', <?=$b['id_buku']?>)" class="text-slate-400 hover:text-primary-container" title="QR Code Buku"><span class="material-symbols-outlined text-sm">qr_code_2</span></button><button onclick='openEditBuku(<?=json_encode($b)?>)' class="text-blue-500"><span class="material-symbols-outlined text-sm">edit</span></button><a href="../../controller/AdminController.php?action=delete_buku&id=<?=$b['id_buku']?>" onclick="return confirm('Hapus?')" class="text-red-500"><span class="material-symbols-outlined text-sm">delete</span></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'siswa'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between bg-slate-50/50"><h4 class="font-bold text-primary-container">Master Data Siswa</h4><button onclick="openAddSiswa()" class="bg-primary-container text-white px-4 py-2 rounded-lg text-xs font-bold">+ Tambah Siswa</button></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest"><tr><th class="px-6 py-4">Identitas</th><th class="px-6 py-4">Kelas</th><th class="px-6 py-4">Kontak</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($students as $s): ?>
                                <tr class="hover:bg-slate-50"><td class="px-6 py-4"><b><?=$s['nama_siswa']?></b><br><small class="text-slate-500"><?=$s['nis']?></small></td><td class="px-6 py-4"><?=$s['kelas']?></td><td class="px-6 py-4 text-[11px]"><?=$s['no_hp']?><br><?=$s['alamat']?></td><td class="px-6 py-4 text-right space-x-2"><button onclick='openEditSiswa(<?=json_encode($s)?>)' class="text-blue-500"><span class="material-symbols-outlined text-sm">edit</span></button><a href="../../controller/AdminController.php?action=delete_siswa&id=<?=$s['id_siswa']?>" onclick="return confirm('Hapus?')" class="text-red-500"><span class="material-symbols-outlined text-sm">delete</span></a></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'pustakawan'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between bg-slate-50/50"><h4 class="font-bold text-primary-container">Master Data Pustakawan</h4><button onclick="openAddPustakawan()" class="bg-gold text-primary-container px-4 py-2 rounded-lg text-xs font-bold">+ Tambah Pustakawan</button></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest"><tr><th class="px-6 py-4">User</th><th class="px-6 py-4">Guru / Jabatan</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($librarians as $l): ?>
                                <tr class="hover:bg-slate-50"><td class="px-6 py-4"><b><?=$l['nama_user']?></b><br><small class="text-slate-500"><?=$l['username']?></small></td><td class="px-6 py-4"><?=$l['guru']??'-'?></td><td class="px-6 py-4 text-right space-x-2"><button onclick='openEditPustakawan(<?=json_encode($l)?>)' class="text-blue-500"><span class="material-symbols-outlined text-sm">edit</span></button><a href="../../controller/AdminController.php?action=delete_user&id=<?=$l['id_user']?>" onclick="return confirm('Hapus?')" class="text-red-500"><span class="material-symbols-outlined text-sm">delete</span></a></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'riwayat'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50"><h4 class="font-bold text-primary-container">Riwayat Sirkulasi Perpustakaan</h4></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest"><tr><th class="px-6 py-4">Siswa</th><th class="px-6 py-4">Buku</th><th class="px-6 py-4">Tgl Pinjam</th><th class="px-6 py-4">Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach($history as $h): ?>
                                <tr class="hover:bg-slate-50"><td class="px-6 py-4"><b><?=$h['nama_siswa']?></b><br><small class="text-slate-500"><?=$h['nis']?></small></td><td class="px-6 py-4"><b><?=$h['judul_buku']?></b><br><small class="text-slate-500"><?=$h['kode_buku']?></small></td><td class="px-6 py-4"><?=date('d/m/Y', strtotime($h['tanggal_pinjam']))?></td><td class="px-6 py-4"><span class="px-2 py-1 rounded text-[9px] font-bold uppercase <?=$h['status']==='dipinjam'?'bg-blue-100 text-blue-700':'bg-green-100 text-green-700'?>"><?=$h['status']?></span></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif($page === 'pengaturan_beranda'): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 max-w-2xl">
                    <h3 class="font-heading text-2xl text-primary-container font-bold mb-2">Pengaturan Beranda</h3>
                    <p class="text-sm text-slate-500 mb-8">Kelola konten yang ditampilkan di halaman depan perpustakaan.</p>
                    
                    <form action="../../controller/AdminController.php?action=update_tentang" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Judul 'Tentang Perpustakaan'</label>
                            <input type="text" name="tentang_judul" value="<?= htmlspecialchars($settings['tentang_judul'] ?? '') ?>" class="w-full rounded-xl border-slate-200 focus:ring-primary-container focus:border-primary-container p-3 text-sm" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Deskripsi 'Tentang Perpustakaan'</label>
                            <textarea name="tentang_deskripsi" rows="6" class="w-full rounded-xl border-slate-200 focus:ring-primary-container focus:border-primary-container p-3 text-sm leading-relaxed" required><?= htmlspecialchars($settings['tentang_deskripsi'] ?? '') ?></textarea>
                            <p class="text-[10px] text-slate-400">Gunakan bahasa yang menarik untuk menyambut pengunjung di homepage.</p>
                        </div>
                        <div class="pt-4 border-t border-slate-50">
                            <button type="submit" class="bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">save</span> SIMPAN INFORMASI TENTANG
                            </button>
                        </div>
                    </form>

                    <hr class="my-10 border-slate-100">

                    <form action="../../controller/AdminController.php?action=update_featured_books" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <h4 class="font-bold text-primary-container">Buku Unggulan di Homepage</h4>
                            <p class="text-xs text-slate-500 mb-4">Pilih buku yang ingin Anda tampilkan secara khusus di halaman utama. Jika tidak ada yang dipilih, sistem akan menampilkan 4 buku terbaru secara otomatis.</p>
                            
                            <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar border border-slate-100 rounded-xl p-4 bg-slate-50/50">
                                <?php foreach($all_books as $bk): ?>
                                <label class="flex items-center gap-3 p-3 bg-white rounded-lg border border-slate-200 cursor-pointer hover:border-primary-container transition-all group">
                                    <input type="checkbox" name="featured_ids[]" value="<?= $bk['id_buku'] ?>" <?= $bk['is_featured'] ? 'checked' : '' ?> class="rounded text-primary-container focus:ring-primary-container">
                                    <div class="overflow-hidden">
                                        <p class="text-sm font-bold text-slate-700 group-hover:text-primary-container transition-colors truncate"><?= htmlspecialchars($bk['judul_buku']) ?></p>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest"><?= $bk['kode_buku'] ?></p>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="bg-gold text-primary-container px-8 py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">star</span> SIMPAN BUKU UNGGULAN
                            </button>
                        </div>
                    </form>

                    <hr class="my-10 border-slate-100">

                    <form action="../../controller/AdminController.php?action=update_tentang_visual" method="POST" class="space-y-6">
                        <div class="space-y-4">
                            <h4 class="font-bold text-primary-container">Buku Visual 'Tentang Kami'</h4>
                            <p class="text-xs text-slate-500">Pilih 2 buku yang akan ditampilkan sebagai ilustrasi besar di bagian 'Tentang Perpustakaan' pada halaman depan.</p>
                            
                            <?php 
                            $visual_ids = explode(',', ($settings['tentang_buku_ids'] ?? '')); 
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Buku Visual 1</label>
                                    <select name="visual_ids[]" class="w-full rounded-xl border-slate-200 text-sm">
                                        <option value="">-- Pilih Buku --</option>
                                        <?php foreach($all_books as $bk): ?>
                                            <option value="<?= $bk['id_buku'] ?>" <?= in_array($bk['id_buku'], $visual_ids) && ($visual_ids[0] ?? '') == $bk['id_buku'] ? 'selected' : '' ?>><?= htmlspecialchars($bk['judul_buku']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Buku Visual 2</label>
                                    <select name="visual_ids[]" class="w-full rounded-xl border-slate-200 text-sm">
                                        <option value="">-- Pilih Buku --</option>
                                        <?php foreach($all_books as $bk): ?>
                                            <option value="<?= $bk['id_buku'] ?>" <?= in_array($bk['id_buku'], $visual_ids) && ($visual_ids[1] ?? '') == $bk['id_buku'] ? 'selected' : '' ?>><?= htmlspecialchars($bk['judul_buku']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:opacity-90 transition-all flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">image</span> SIMPAN VISUAL TENTANG
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif($page === 'pengaturan'): ?>
                <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                        <div class="w-32 h-32 mx-auto bg-slate-100 rounded-full mb-4 overflow-hidden border-4 border-white shadow-md flex items-center justify-center">
                            <?php if(!empty($admin_data['poto_profil']) && $admin_data['poto_profil'] !== 'default.jpg' && file_exists('../../assets/img/profil/'.$admin_data['poto_profil'])): ?>
                                <img src="../../assets/img/profil/<?=$admin_data['poto_profil']?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <img src="../../assets/img/profil/admin_default.png" class="w-full h-full object-cover opacity-80">
                            <?php endif; ?>
                        </div>
                        <h4 class="font-bold text-primary-container"><?=htmlspecialchars($user_nama)?></h4>
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Root Administrator</p>
                    </div>
                    <div class="md:col-span-2 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                        <h4 class="font-heading text-xl font-bold mb-6 border-b pb-4">Data Lengkap Admin</h4>
                        <form action="../../controller/AdminController.php?action=update_profile_full" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Nama User (Login)</label><input name="nama_user" value="<?=htmlspecialchars($user_nama)?>" class="w-full rounded-xl border-slate-200"></div>
                                <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Nama Lengkap</label><input name="nama_lengkap" value="<?=htmlspecialchars($admin_data['nama_lengkap']??'')?>" class="w-full rounded-xl border-slate-200"></div>
                                <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Jabatan Guru</label><input name="jabatan_guru" value="<?=htmlspecialchars($admin_data['jabatan_guru']??'')?>" class="w-full rounded-xl border-slate-200"></div>
                                <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">No Telp</label><input name="no_telp" value="<?=htmlspecialchars($admin_data['no_telp']??'')?>" class="w-full rounded-xl border-slate-200"></div>
                            </div>
                            <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Alamat Lengkap</label><textarea name="alamat" class="w-full rounded-xl border-slate-200"><?=htmlspecialchars($admin_data['alamat']??'')?></textarea></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Ganti Password (Kosongkan jika tidak)</label><input type="password" name="password" class="w-full rounded-xl border-slate-200"></div>
                            <div><label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Poto Profil Baru</label><input type="file" name="poto_profil" class="text-xs"></div>
                            <div class="pt-4"><button type="submit" class="w-full bg-primary-container text-white py-4 rounded-xl font-bold shadow-lg">Perbarui Semua Data</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal-Modal CRUD Lengkap -->
<!-- Modal Buku -->
<div id="modalBuku" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-6 border-b flex justify-between bg-slate-50"><h3 class="font-bold" id="titleBuku">Tambah Koleksi Buku</h3><button onclick="document.getElementById('modalBuku').classList.add('hidden')" class="text-slate-400"><span class="material-symbols-outlined">close</span></button></div>
        <form action="../../controller/AdminController.php?action=save_buku_full" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="id_buku" id="id_buku">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Kode Buku</label><input required name="kode_buku" id="kode_buku" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Contoh: B-001"></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Judul Buku</label><input required name="judul_buku" id="judul_buku" class="w-full rounded-xl border-slate-200 text-sm"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Penerbit</label><input name="penerbit" id="penerbit_buku" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Tahun Terbit</label>
                    <select name="tahun_terbit" id="tahun_buku" class="w-full rounded-xl border-slate-200 text-sm">
                        <?php for($y = date('Y'); $y >= date('Y')-30; $y--): ?>
                            <option value="<?=$y?>"><?=$y?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Kategori</label><select name="id_kategori" id="id_kat_buku" class="w-full rounded-xl border-slate-200 text-sm"><option value="" disabled selected>Pilih Kategori</option><?php foreach($categories as $c): ?><option value="<?=$c['id_kategori']?>"><?=$c['nama_kategori']?></option><?php endforeach; ?></select></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Tema Buku</label><select name="id_tema" id="id_tema_buku" class="w-full rounded-xl border-slate-200 text-sm"><option value="" disabled selected>Pilih Tema</option><?php foreach($themes as $t): ?><option value="<?=$t['id_tema']?>"><?=$t['nama_tema']?></option><?php endforeach; ?></select></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Jumlah Stok</label><input required type="number" name="jumlah_buku" id="stok_buku" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Lokasi Rak</label><input required name="lokasi_rak" id="rak_buku" class="w-full rounded-xl border-slate-200 text-sm"></div>
            </div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Cover Buku (JPG/PNG)</label><input type="file" name="gambar" class="w-full text-xs"></div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold">Simpan Buku</button>
        </form>
    </div>
</div>

<!-- Modal Siswa -->
<div id="modalSiswa" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-6 border-b flex justify-between bg-slate-50"><h3 class="font-bold" id="titleSiswa">Tambah Data Siswa</h3><button onclick="document.getElementById('modalSiswa').classList.add('hidden')" class="text-slate-400"><span class="material-symbols-outlined">close</span></button></div>
        <form action="../../controller/AdminController.php?action=save_siswa_full" method="POST" class="p-6 space-y-4">
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

<!-- Modal Pustakawan -->
<div id="modalPustakawan" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-6 border-b flex justify-between bg-slate-50"><h3 class="font-bold" id="titlePust">Tambah Pustakawan</h3><button onclick="document.getElementById('modalPustakawan').classList.add('hidden')" class="text-slate-400"><span class="material-symbols-outlined">close</span></button></div>
        <form action="../../controller/AdminController.php?action=save_pustakawan_full" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_user" id="id_pust">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Username</label><input required name="username" id="username_pust" class="w-full rounded-xl border-slate-200 text-sm"></div>
                <div><label class="text-[10px] font-bold text-slate-400 uppercase">Nama Panggilan</label><input required name="nama_user" id="nama_pust" class="w-full rounded-xl border-slate-200 text-sm"></div>
            </div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Jabatan Guru</label><input name="guru" id="guru_pust" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">No HP</label><input name="no_hp" id="hp_pust" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Alamat</label><textarea name="alamat" id="alamat_pust" class="w-full rounded-xl border-slate-200 text-sm"></textarea></div>
            <div><label class="text-[10px] font-bold text-slate-400 uppercase">Password</label><input name="password" type="password" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <button type="submit" class="w-full bg-primary-container text-white py-3 rounded-xl font-bold">Simpan Pustakawan</button>
        </form>
    </div>
</div>

<script>
function openAddBuku() {
    document.getElementById('titleBuku').innerText='Tambah Koleksi Buku';
    document.getElementById('modalBuku').querySelector('form').reset();
    document.getElementById('id_buku').value='';
    document.getElementById('modalBuku').classList.remove('hidden');
}

function openAddSiswa() {
    document.getElementById('titleSiswa').innerText='Tambah Data Siswa';
    document.getElementById('modalSiswa').querySelector('form').reset();
    document.getElementById('id_siswa').value='';
    document.getElementById('modalSiswa').classList.remove('hidden');
}

function openAddPustakawan() {
    document.getElementById('titlePust').innerText='Tambah Pustakawan';
    document.getElementById('modalPustakawan').querySelector('form').reset();
    document.getElementById('id_pust').value='';
    document.getElementById('modalPustakawan').classList.remove('hidden');
}

function openEditBuku(b){
    document.getElementById('titleBuku').innerText='Edit Koleksi Buku';
    document.getElementById('id_buku').value=b.id_buku;
    document.getElementById('kode_buku').value=b.kode_buku;
    document.getElementById('judul_buku').value=b.judul_buku;
    document.getElementById('penerbit_buku').value=b.penerbit;
    document.getElementById('tahun_buku').value=b.tahun_terbit;
    document.getElementById('id_kat_buku').value=b.id_kategori;
    document.getElementById('id_tema_buku').value=b.id_tema;
    document.getElementById('stok_buku').value=b.jumlah_buku;
    document.getElementById('rak_buku').value=b.lokasi_rak;
    document.getElementById('modalBuku').classList.remove('hidden');
}

function previewCover(img) {
    if(!img || img == 'null') return;
    document.getElementById('previewImg').src = '../../assets/img/buku/' + img;
    document.getElementById('modalPreview').classList.remove('hidden');
}
function openEditSiswa(s){
    document.getElementById('titleSiswa').innerText='Edit Data Siswa';
    document.getElementById('id_siswa').value=s.id_siswa;
    document.getElementById('nis_siswa').value=s.nis;
    document.getElementById('nama_siswa').value=s.nama_siswa;
    document.getElementById('kelas_siswa').value=s.kelas;
    document.getElementById('jk_siswa').value=s.jenis_kelamin;
    document.getElementById('hp_siswa').value=s.no_hp;
    document.getElementById('ortu_siswa').value=s.nama_orangtua;
    document.getElementById('alamat_siswa').value=s.alamat;
    document.getElementById('modalSiswa').classList.remove('hidden');
}
function openEditPustakawan(l){
    document.getElementById('titlePust').innerText='Edit Data Pustakawan';
    document.getElementById('id_pust').value=l.id_user;
    document.getElementById('username_pust').value=l.username;
    document.getElementById('nama_pust').value=l.nama_user;
    document.getElementById('guru_pust').value=l.guru;
    document.getElementById('hp_pust').value=l.no_hp;
    document.getElementById('alamat_pust').value=l.alamat;
    document.getElementById('modalPustakawan').classList.remove('hidden');
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
</script>
<!-- Preview Cover Modal -->
<div id="modalPreview" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" onclick="this.classList.add('hidden')">
    <div class="max-w-md w-full animate-scaleIn">
        <img id="previewImg" src="" class="w-full rounded-2xl shadow-2xl">
    </div>
</div>
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
</body></html>
