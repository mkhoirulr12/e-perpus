<?php
session_start();
require_once "../../config/database.php";
$database = new Database();
$conn = $database->connect();

// Cari ID kategori "Umum" atau yang mengandung kata "umum"
$stmtKat = $conn->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE nama_kategori LIKE ?");
$stmtKat->execute(['%umum%']);
$kategori = $stmtKat->fetch(PDO::FETCH_ASSOC);

$kategori_nama = $kategori['nama_kategori'] ?? 'Umum';
$books = [];

if ($kategori) {
    $stmtBuku = $conn->prepare("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori = k.id_kategori WHERE b.id_kategori = ? ORDER BY b.created_at DESC");
    $stmtBuku->execute([$kategori['id_kategori']]);
    $books = $stmtBuku->fetchAll(PDO::FETCH_ASSOC);
}

// Search
$search = $_GET['q'] ?? '';
if (!empty($search) && $kategori) {
    $stmtSearch = $conn->prepare("SELECT b.*, k.nama_kategori FROM buku b JOIN kategori k ON b.id_kategori = k.id_kategori WHERE b.id_kategori = ? AND (b.judul_buku LIKE ? OR b.penerbit LIKE ?) ORDER BY b.created_at DESC");
    $stmtSearch->execute([$kategori['id_kategori'], "%$search%", "%$search%"]);
    $books = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>E-Perpus Prayasqi - Koleksi Tema</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,wght@0,400;0,600;0,700;1,400&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { "primary": "#2d5a27", "primary-dark": "#1e3d1a", "gold": "#C5A059" },
                    fontFamily: { "body": ["Public Sans"], "heading": ["Newsreader"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        body { font-family: 'Public Sans', sans-serif; }
        html { scroll-behavior: smooth; }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .book-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .book-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0, 33, 71, 0.12); }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .animate-scaleIn { animation: scaleIn 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <!-- Navigation -->
    <nav class="bg-primary text-white fixed w-full top-0 z-50 border-b-2 border-gold shadow-md">
        <div class="flex justify-between items-center w-full px-8 py-4 max-w-[1200px] mx-auto">
            <a href="../../index.php" class="flex items-center gap-3 text-2xl font-bold font-heading text-white hover:text-gold transition-colors duration-300">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-10 w-auto">
                <span>E-Perpus Prayasqi</span>
            </a>
            <div class="hidden md:flex items-center space-x-8">
                <a class="text-white/90 font-medium hover:text-gold transition-colors duration-200 text-sm" href="../../index.php">Beranda</a>
                <a class="text-white/90 font-medium hover:text-gold transition-colors duration-200 text-sm" href="pembelajaran.php">Buku Pembelajaran</a>
                <a class="text-gold border-b-2 border-gold pb-1 font-semibold text-sm" href="umum.php">Buku Umum</a>
                <a class="text-white/90 font-medium hover:text-gold transition-colors duration-200 text-sm" href="islami.php">Buku Islami</a>
            </div>
            <div class="flex items-center gap-4">
                <?php if(isset($_SESSION['level'])): ?>
                    <?php
                        $dash_url = "../pustakawan/PustakawanDashboard.php";
                        if($_SESSION['level'] == 'admin') $dash_url = "../admin/dashboard.php";
                        if($_SESSION['level'] == 'siswa') $dash_url = "../siswa/SiswaDashboard.php";
                    ?>
                    <a href="<?= $dash_url ?>" class="px-5 py-2 bg-gold hover:bg-[#b08d4a] text-primary font-bold rounded-lg transition-all text-xs uppercase tracking-wider">Dashboard</a>
                <?php else: ?>
                    <a href="../../index.php" class="flex items-center gap-2 text-white/70 hover:text-white transition-colors text-sm">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        Kembali
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <header class="pt-[72px]">
        <div class="bg-gradient-to-br from-primary via-emerald-800 to-primary-dark text-white py-20 relative overflow-hidden">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute top-10 left-10"><span class="material-symbols-outlined text-[200px]">auto_stories</span></div>
                <div class="absolute bottom-10 right-10"><span class="material-symbols-outlined text-[150px]">library_books</span></div>
            </div>
            <div class="max-w-[1200px] mx-auto px-8 relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-gold text-4xl">auto_stories</span>
                    <span class="text-gold text-[10px] font-bold uppercase tracking-[0.3em] bg-gold/10 px-3 py-1 rounded-full">Koleksi Tema</span>
                </div>
                <h1 class="font-heading text-4xl md:text-5xl font-bold mb-4">Buku Umum</h1>
                <p class="text-white/70 max-w-xl text-lg">Koleksi buku pengetahuan umum yang memperluas wawasan dan cakrawala berpikir. Dari sains hingga seni, temukan buku favoritmu.</p>
                <div class="mt-8 flex items-center gap-4">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-6 py-3 text-center">
                        <p class="text-3xl font-heading font-bold text-gold"><?= count($books) ?></p>
                        <p class="text-[10px] text-white/50 uppercase tracking-widest font-bold">Total Buku</p>
                    </div>
                </div>

            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="max-w-[1200px] mx-auto px-8 py-12 fade-in">
        <!-- Search Bar -->
        <div class="mb-10">
            <form action="" method="GET" class="flex max-w-xl gap-3">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-4 top-3.5 text-slate-400">search</span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                           class="w-full border border-slate-200 rounded-xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none shadow-sm" 
                           placeholder="Cari judul buku umum...">
                </div>
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-primary-dark transition-colors shadow-sm">
                    Cari
                </button>
            </form>
        </div>

        <!-- Book Grid -->
        <?php if(empty($books)): ?>
            <div class="text-center py-20 border-2 border-dashed border-slate-200 rounded-2xl">
                <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">menu_book</span>
                <h3 class="text-xl font-bold text-slate-400 mb-2">Belum Ada Buku</h3>
                <p class="text-slate-400 text-sm">Koleksi buku umum belum tersedia atau kategori belum dibuat.</p>
                <a href="../../index.php" class="inline-flex items-center gap-2 mt-6 text-primary font-bold text-sm hover:text-gold transition-colors">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Kembali ke Beranda
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                <?php foreach($books as $buku): ?>
                <div class="book-card bg-white border border-slate-200 rounded-2xl overflow-hidden cursor-pointer flex flex-col h-full group" onclick="showBookPopup(<?= htmlspecialchars(json_encode($buku)) ?>)">
                    <div class="aspect-[2/3] bg-gradient-to-br from-emerald-50 to-slate-100 flex items-center justify-center text-slate-300 border-b border-slate-100 group-hover:from-emerald-100 group-hover:to-slate-200 transition-colors overflow-hidden">
                        <?php if(!empty($buku['gambar']) && file_exists('../../assets/img/buku/'.$buku['gambar'])): ?>
                            <img src="../../assets/img/buku/<?= htmlspecialchars($buku['gambar']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-5xl group-hover:scale-110 transition-transform">auto_stories</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-primary text-sm mb-1 line-clamp-2 leading-tight group-hover:text-emerald-700 transition-colors"><?= htmlspecialchars($buku['judul_buku']) ?></h4>
                            <p class="text-[11px] text-slate-500 uppercase tracking-wide mb-2"><?= htmlspecialchars($buku['penerbit'] ?? '-') ?></p>
                        </div>
                        <div class="mt-auto pt-3 border-t border-slate-100 flex justify-between items-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= $buku['tahun_terbit'] ?? '-' ?></span>
                            <span class="text-[10px] font-bold <?= ($buku['status'] === 'tersedia') ? 'text-green-600' : 'text-red-500' ?> uppercase">
                                <?= htmlspecialchars($buku['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-10">
        <div class="max-w-[1200px] mx-auto py-8 px-8 flex justify-between items-center">
            <div class="font-heading text-lg font-bold text-primary flex items-center gap-2">
                <img src="../../assets/img/logo.png" alt="Logo" class="h-6 w-auto">
                E-Perpus Prayasqi
            </div>
            <div class="text-slate-400 text-sm">© 2026 Hak Cipta Dilindungi.</div>
        </div>
    </footer>

    <!-- Book Popup Modal -->
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
                    <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'siswa'): ?>
                        <a href="../siswa/SiswaDashboard.php" class="block w-full bg-primary text-white text-center py-4 rounded-xl font-bold shadow-lg hover:bg-primary-dark transition-all">PINJAM SEKARANG</a>
                    <?php elseif(isset($_SESSION['level'])): ?>
                        <button disabled class="block w-full bg-slate-300 text-slate-500 text-center py-4 rounded-xl font-bold cursor-not-allowed">HANYA SISWA YANG BISA MEMINJAM</button>
                    <?php else: ?>
                        <a href="../auth/Login.php" class="block w-full bg-primary text-white text-center py-4 rounded-xl font-bold shadow-lg hover:bg-primary-dark transition-all">MASUK UNTUK PINJAM</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showBookPopup(b) {
        const popupImg = document.getElementById('popupImg');
        const placeholder = '<div class="w-full h-full bg-slate-200 flex items-center justify-center"><span class="material-symbols-outlined text-8xl text-slate-400">auto_stories</span></div>';
        
        if (b.gambar) {
            const imgPath = '../../assets/img/buku/' + b.gambar;
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
        document.getElementById('popupTema').innerText = b.nama_kategori || 'UMUM';
        document.getElementById('popupPenerbit').innerText = 'Penerbit: ' + b.penerbit;
        document.getElementById('popupTahun').innerText = 'Tahun Terbit: ' + b.tahun_terbit;
        document.getElementById('popupStok').innerText = 'Stok Tersedia: ' + b.jumlah_buku;
        document.getElementById('bookPopup').classList.remove('hidden');
    }
    function closeBookPopup() {
        document.getElementById('bookPopup').classList.add('hidden');
    }
    </script>
</body>
</html>
