session_start();
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$role_req = $_GET['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "outline-variant": "#c4c6cf",
                  "on-primary-container": "#708ab5",
                  "surface-container-lowest": "#ffffff",
                  "primary-container": "#2d5a27",
                  "secondary": "#735c00",
                  "background": "#fbf9f8",
                  "error-container": "#ffdad6",
                  "on-error-container": "#93000a",
                  "outline": "#74777f",
          },
          "fontFamily": {
                  "body-main": ["Public Sans"],
                  "label-caps": ["Public Sans"],
                  "h2": ["Newsreader"],
                  "h1": ["Newsreader"],
                  "nav-link": ["Public Sans"]
          }
        },
      },
    }
  </script>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body { background-color: #fbf9f8; }
</style>
</head>
<body class="font-body-main text-slate-900 selection:bg-amber-100">
<main class="min-h-screen flex items-center justify-center lg:p-12 overflow-hidden py-10">
<div class="flex w-full max-w-[1200px] bg-white shadow-[0px_4px_40px_rgba(0,0,0,0.08)] overflow-hidden rounded-lg">
<!-- Visual Side -->
<div class="hidden lg:flex lg:w-2/5 relative overflow-hidden bg-primary-container">
<img alt="E-Perpus Prayasqi" class="absolute inset-0 w-full h-full object-cover opacity-50 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB35VsK7oyK04qbQVHicV5JGgqwlqhWhruJqyrWPMd3_Iuc6RyDzIB-NvYsDeTKbaMBQvSIL6vTPW5RHCCp-UwENsWmLhz9fsusiZfnD6g5giX9qvc1UsEsfb9-GCPoGhWSmDX6OobR7H-4Dk2L_FaaAch3pzooMFp6Qf3WPyc6mGC3f14XL-2Dtl2ID5bpp9k6iRujBPFJdgmXkwpyv0giEYL28aEcysNxfnGXNW4U2gTG16hvTlgOYn5yh5miendyV9kswBdK7ko"/>
<div class="relative z-10 p-16 flex flex-col justify-center h-full text-white">
<div class="flex items-center gap-3 mb-8">
<img src="../../assets/img/logo.png" alt="Logo" class="h-10 w-auto">
<span class="font-h2 text-2xl tracking-tight">E-Perpus Prayasqi</span>
</div>
<h1 class="font-h1 text-4xl mb-6">Daftar Sebagai Pustakawan.</h1>
<p class="font-body-main text-lg text-white/80">
    Bergabunglah sebagai Pustakawan atau Admin untuk mengelola perpustakaan secara menyeluruh.
</p>
</div>
<div class="absolute right-0 top-0 bottom-0 w-1 bg-[#C5A059]"></div>
</div>

<!-- Form Side -->
<div class="w-full lg:w-3/5 flex flex-col bg-white p-8 md:p-12 justify-center">
<div class="mb-8">
<h2 class="font-h2 text-3xl text-primary-container mb-2 font-bold">Pendaftaran Pustakawan</h2>
<p class="font-body-main text-slate-500">Lengkapi formulir berikut sesuai data tabel <code class="bg-slate-100 px-1 rounded text-xs">user</code> di database.</p>
<?php if (!empty($error)): ?>
<div class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm border border-red-300">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
<?php if (!empty($success)): ?>
<div class="mt-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
</div>

<form class="space-y-5" method="POST" action="../../controller/RegisterPustakawanController.php">
    <!-- Nama Lengkap (nama_user) -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-primary-container uppercase tracking-wider font-bold">Nama Lengkap</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">person</span>
            <input required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" name="nama_user" placeholder="Nama Lengkap Pustakawan" type="text"/>
        </div>
    </div>

    <!-- Username -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-primary-container uppercase tracking-wider font-bold">Username</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">account_circle</span>
            <input required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" name="username" placeholder="Pilih Username Unik" type="text"/>
        </div>
    </div>

    <!-- Password -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-primary-container uppercase tracking-wider font-bold">Kata Sandi</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
            <input required class="w-full pl-10 pr-12 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" id="regPassword" name="password" placeholder="Buat Kata Sandi" type="password"/>
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-[#2d5a27] transition-colors" type="button" onclick="togglePw('regPassword','eyeReg')">
                <span class="material-symbols-outlined" id="eyeReg">visibility</span>
            </button>
        </div>
    </div>

    <!-- Level/Role -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-primary-container uppercase tracking-wider font-bold">Jabatan / Role</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">shield_person</span>
            <select required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50 appearance-none" name="level">
                <option value="" disabled <?= empty($role_req) ? 'selected' : '' ?>>Pilih Jabatan</option>
                <option value="admin" <?= $role_req === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="pustakawan" <?= $role_req === 'pustakawan' ? 'selected' : '' ?>>Pustakawan</option>
            </select>
        </div>
    </div>

    <button class="w-full mt-4 py-4 bg-primary-container text-white font-nav-link text-sm rounded-lg hover:bg-[#1e3d1a] transition-all duration-200 flex items-center justify-center gap-2 shadow-lg uppercase tracking-wider" type="submit">
        Daftar Sekarang
        <span class="material-symbols-outlined">how_to_reg</span>
    </button>
</form>

<div class="mt-8 pt-6 border-t border-slate-200 text-center">
<p class="font-body-main text-sm text-slate-500 mb-4">Sudah memiliki akun?</p>
<a class="inline-flex items-center gap-2 font-nav-link text-sm text-primary-container hover:text-[#C5A059] transition-colors py-2 px-6 border-2 border-primary-container rounded-full hover:bg-slate-50 uppercase tracking-widest font-bold" href="Login.php?role=<?= $role_req ?: 'pustakawan' ?>">
    Kembali ke Login
</a>
</div>
</div>
</div>
</main>

<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') { input.type = 'text'; icon.textContent = 'visibility_off'; }
    else { input.type = 'password'; icon.textContent = 'visibility'; }
}
</script>
</body></html>
