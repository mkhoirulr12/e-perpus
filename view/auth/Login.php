<?php
session_start();
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$role = $_GET['role'] ?? 'siswa';
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&amp;family=Public+Sans:ital,wght@0,100..900;1,100..900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "outline-variant": "#c4c6cf",
                  "on-tertiary-fixed": "#191c1d",
                  "on-primary-container": "#708ab5",
                  "surface-bright": "#fbf9f8",
                  "outline": "#74777f",
                  "surface-container-lowest": "#ffffff",
                  "on-primary": "#ffffff",
                  "tertiary-container": "#1f2223",
                  "on-tertiary": "#ffffff",
                  "inverse-surface": "#303030",
                  "inverse-primary": "#aec7f6",
                  "tertiary-fixed-dim": "#c5c7c8",
                  "on-tertiary-fixed-variant": "#454748",
                  "primary-container": "#2d5a27",
                  "on-secondary-container": "#745c00",
                  "surface-container": "#f0eded",
                  "on-error-container": "#93000a",
                  "surface-tint": "#465f88",
                  "surface-dim": "#dcd9d9",
                  "on-secondary-fixed-variant": "#574500",
                  "surface-container-high": "#eae8e7",
                  "background": "#fbf9f8",
                  "inverse-on-surface": "#f3f0f0",
                  "secondary": "#735c00",
                  "surface": "#fbf9f8",
                  "tertiary": "#090b0c",
                  "on-primary-fixed-variant": "#2d476f",
                  "on-surface-variant": "#44474e",
                  "on-tertiary-container": "#87898a",
                  "secondary-container": "#fed65b",
                  "on-error": "#ffffff",
                  "error-container": "#ffdad6",
                  "on-surface": "#1b1c1c",
                  "surface-container-low": "#f6f3f2",
                  "secondary-fixed-dim": "#e9c349",
                  "on-background": "#1b1c1c",
                  "primary": "#2d5a27",
                  "primary-fixed": "#d6e3ff",
                  "on-secondary-fixed": "#241a00",
                  "surface-variant": "#e4e2e1",
                  "tertiary-fixed": "#e1e3e4",
                  "error": "#ba1a1a",
                  "on-secondary": "#ffffff",
                  "surface-container-highest": "#e4e2e1",
                  "primary-fixed-dim": "#aec7f6",
                  "secondary-fixed": "#ffe088",
                  "on-primary-fixed": "#001b3d"
          },
          "borderRadius": {
                  "DEFAULT": "0.125rem",
                  "lg": "0.25rem",
                  "xl": "0.5rem",
                  "full": "0.75rem"
          },
          "spacing": {
                  "gutter": "24px",
                  "card-gap": "32px",
                  "section-padding": "80px",
                  "container-max": "1200px",
                  "base": "8px"
          },
          "fontFamily": {
                  "stat-number": ["Newsreader"],
                  "body-main": ["Public Sans"],
                  "label-caps": ["Public Sans"],
                  "h3": ["Newsreader"],
                  "h2": ["Newsreader"],
                  "h1": ["Newsreader"],
                  "nav-link": ["Public Sans"]
          },
          "fontSize": {
                  "stat-number": ["40px", {"lineHeight": "1.1", "fontWeight": "700"}],
                  "body-main": ["16px", {"lineHeight": "1.6", "fontWeight": "400"}],
                  "label-caps": ["12px", {"lineHeight": "1", "fontWeight": "700"}],
                  "h3": ["28px", {"lineHeight": "1.4", "fontWeight": "500"}],
                  "h2": ["36px", {"lineHeight": "1.3", "fontWeight": "500"}],
                  "h1": ["48px", {"lineHeight": "1.2", "fontWeight": "600"}],
                  "nav-link": ["15px", {"lineHeight": "1.5", "letterSpacing": "0.02em", "fontWeight": "600"}]
          }
        },
      },
    }
  </script>
<style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    body {
      background-color: #fbf9f8;
    }
  </style>
</head>
<body class="font-body-main text-on-background selection:bg-secondary-container selection:text-on-secondary-container">
<!-- Main Content Canvas -->
<main class="min-h-screen flex items-center justify-center lg:p-12 overflow-hidden">
<!-- Login Split-Screen Container -->
<div class="flex w-full max-w-[1200px] h-[800px] bg-white shadow-[0px_4px_40px_rgba(0,0,0,0.08)] overflow-hidden rounded-lg">
<!-- Visual Side (Library Imagery) -->
<div class="hidden lg:flex lg:w-3/5 relative overflow-hidden bg-primary-container">
<img alt="Perpustakaan Digital" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB35VsK7oyK04qbQVHicV5JGgqwlqhWhruJqyrWPMd3_Iuc6RyDzIB-NvYsDeTKbaMBQvSIL6vTPW5RHCCp-UwENsWmLhz9fsusiZfnD6g5giX9qvc1UsEsfb9-GCPoGhWSmDX6OobR7H-4Dk2L_FaaAch3pzooMFp6Qf3WPyc6mGC3f14XL-2Dtl2ID5bpp9k6iRujBPFJdgmXkwpyv0giEYL28aEcysNxfnGXNW4U2gTG16hvTlgOYn5yh5miendyV9kswBdK7ko"/>
<div class="relative z-10 p-16 flex flex-col justify-between h-full text-white">
<div class="flex items-center gap-3">
<img src="../../assets/img/logo.png" alt="Logo" class="h-10 w-auto">
<span class="font-h2 text-h3 tracking-tight">E-Perpus Prayasqi</span>
</div>
<div class="space-y-6">
<h1 class="font-h1 text-h1 max-w-md">Gerbang Menuju Cakrawala Ilmu.</h1>
<p class="font-body-main text-lg text-white/80 max-w-sm">
              Temukan ribuan koleksi literatur, jurnal penelitian, dan karya klasik dalam genggaman Anda di E-Perpus Prayasqi.
            </p>
              <!-- Statistics removed as requested -->

</div>
<div class="text-white/60 text-sm">
            © 2026 E-Perpus Prayasqi. Hak Cipta Dilindungi.
          </div>
</div>
<!-- Golden Accent Line -->
<div class="absolute right-0 top-0 bottom-0 w-1 bg-[#C5A059]"></div>
</div>
<!-- Form Side -->
<div class="w-full lg:w-2/5 flex flex-col bg-white p-8 md:p-16 justify-center overflow-y-auto">
<!-- Mobile Header (Visible only on small screens) -->
<div class="lg:hidden flex items-center gap-3 mb-12">
<img src="../../assets/img/logo.png" alt="Logo" class="h-8 w-auto">
<span class="font-h2 text-h3 text-primary-container">E-Perpus Prayasqi</span>
</div>
<div class="mb-8">
<a href="../../index.php" class="inline-flex items-center gap-2 text-sm font-label-caps text-outline hover:text-primary-container transition-colors uppercase tracking-widest mb-6 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
    <span class="material-symbols-outlined text-lg">arrow_back</span>
    Kembali ke Homepage
</a>
<h2 class="font-h2 text-h2 text-primary-container mb-2">Selamat Datang</h2>
<p class="font-body-main text-on-surface-variant mb-6">Silakan masuk ke akun Anda untuk melanjutkan akses literasi.</p>

<!-- Toggle Tabs -->
<div class="flex p-1 bg-surface-variant rounded-lg mb-6 max-w-md overflow-x-auto">
    <a href="?role=siswa" class="flex-1 py-2 px-4 text-center rounded-md text-[10px] font-label-caps uppercase tracking-wider transition-all <?= $role === 'siswa' ? 'bg-white shadow text-primary-container font-bold' : 'text-on-surface-variant hover:text-primary-container' ?>">Siswa</a>
    <a href="?role=pustakawan" class="flex-1 py-2 px-4 text-center rounded-md text-[10px] font-label-caps uppercase tracking-wider transition-all <?= $role === 'pustakawan' ? 'bg-white shadow text-primary-container font-bold' : 'text-on-surface-variant hover:text-primary-container' ?>">Pustakawan</a>
    <a href="?role=admin" class="flex-1 py-2 px-4 text-center rounded-md text-[10px] font-label-caps uppercase tracking-wider transition-all <?= $role === 'admin' ? 'bg-white shadow text-primary-container font-bold' : 'text-on-surface-variant hover:text-primary-container' ?>">Admin</a>
</div>

<?php if (!empty($success)): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="mb-4 p-3 bg-error-container text-on-error-container rounded-lg text-sm border border-error">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>
</div>

<form class="space-y-6" method="POST" action="../../controller/LoginController.php">
<input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

<!-- Input Identity -->
<?php if($role === 'siswa'): ?>
<div class="space-y-2">
<label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider" for="identity">Nomor Induk Siswa (NIS)</label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">badge</span>
<input required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] transition-all outline-none rounded-lg bg-surface-container-lowest" id="identity" name="identity" placeholder="Masukkan NIS" type="text"/>
</div>
</div>
<?php else: ?>
<div class="space-y-2">
<label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider" for="identity">Username <?= ucfirst($role) ?></label>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">person</span>
<input required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] transition-all outline-none rounded-lg bg-surface-container-lowest" id="identity" name="identity" placeholder="Masukkan Username" type="text"/>
</div>
</div>
<?php endif; ?>

<!-- Input Password -->
<div class="space-y-2">
<div class="flex justify-between items-center">
<label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider" for="password">Kata Sandi</label>
<button type="button" class="font-label-caps text-label-caps text-secondary hover:text-primary-container transition-colors uppercase" onclick="document.getElementById('forgotPasswordModal').classList.remove('hidden')">Lupa Kata Sandi?</button>
</div>
<div class="relative group">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
<input required class="w-full pl-10 pr-12 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] transition-all outline-none rounded-lg bg-surface-container-lowest" id="password" name="password" placeholder="••••••••" type="password"/>
<button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary-container transition-colors" type="button" id="togglePassword">
<span class="material-symbols-outlined" id="eyeIcon">visibility</span>
</button>
</div>
</div>

<!-- Remember Me -->
<div class="flex items-center gap-2">
<input class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-primary-container" id="remember" type="checkbox"/>
<label class="font-body-main text-sm text-on-surface-variant" for="remember">Ingat saya di perangkat ini</label>
</div>

<!-- Submit Button -->
<button class="w-full py-4 bg-primary-container text-white font-nav-link text-nav-link rounded-lg hover:bg-on-primary-fixed-variant transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-primary-container/10 active:scale-[0.98]" type="submit">
            Masuk ke Akun
            <span class="material-symbols-outlined">arrow_forward</span>
</button>
</form>

<!-- Register Link -->
<div class="mt-10 pt-6 border-t border-surface-container-high text-center">
<p class="font-body-main text-sm text-on-surface-variant mb-4">Belum memiliki akun <?= $role ?>?</p>
<?php if($role === 'siswa'): ?>
<a class="inline-flex items-center gap-2 font-nav-link text-nav-link text-secondary hover:text-[#C5A059] transition-colors py-2 px-6 border-2 border-secondary rounded-full hover:bg-secondary/5" href="Register.php">
            Daftar Anggota Baru
            <span class="material-symbols-outlined">person_add</span>
</a>
<?php else: ?>
<a class="inline-flex items-center gap-2 font-nav-link text-nav-link text-secondary hover:text-[#C5A059] transition-colors py-2 px-6 border-2 border-secondary rounded-full hover:bg-secondary/5" href="RegisterPustakawan.php?role=<?= $role ?>">
            Daftar <?= ucfirst($role) ?> Baru
            <span class="material-symbols-outlined">admin_panel_settings</span>
</a>
<?php endif; ?>
</div>
</div>
</div>
<!-- Background Decorative Elements -->
<div class="fixed top-0 right-0 -z-10 w-1/3 h-1/3 bg-gradient-to-br from-secondary/5 to-transparent blur-3xl"></div>
<div class="fixed bottom-0 left-0 -z-10 w-1/3 h-1/3 bg-gradient-to-tr from-primary-container/5 to-transparent blur-3xl"></div>
</main>

<!-- Toggle Password Visibility Script -->
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.textContent = 'visibility_off';
    } else {
        passwordInput.type = 'password';
        eyeIcon.textContent = 'visibility';
    }
});
</script>

<!-- Supplemental Navigation (Minimal Footer) -->
<footer class="w-full py-12 px-8 bg-surface-container-low border-t border-outline-variant/20">
<div class="max-w-[1200px] mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
<div class="flex flex-col items-center md:items-start gap-2">
<span class="font-h3 text-lg font-semibold text-primary-container flex items-center gap-2">
    <img src="../../assets/img/logo.png" alt="Logo" class="h-6 w-auto">
    E-Perpus Prayasqi
</span>
<span class="font-label-caps text-xs text-on-surface-variant uppercase tracking-[0.2em]">© 2026 E-Perpus Prayasqi. Hak Cipta Dilindungi.</span>
</div>
<div class="flex flex-wrap justify-center gap-x-8 gap-y-4">
<a class="font-label-caps text-xs text-outline hover:text-primary-container transition-all uppercase tracking-widest hover:underline decoration-[#C5A059] underline-offset-4" href="#">Kontak</a>
<a class="font-label-caps text-xs text-outline hover:text-primary-container transition-all uppercase tracking-widest hover:underline decoration-[#C5A059] underline-offset-4" href="#">Kebijakan Privasi</a>
<a class="font-label-caps text-xs text-outline hover:text-primary-container transition-all uppercase tracking-widest hover:underline decoration-[#C5A059] underline-offset-4" href="#">Syarat &amp; Ketentuan</a>
</div>
</div>
</footer>
<!-- Forgot Password Modal -->
<div id="forgotPasswordModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden relative">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-h3 font-bold text-primary-container">Lupa Kata Sandi</h3>
        <button type="button" onclick="document.getElementById('forgotPasswordModal').classList.add('hidden')" class="text-outline hover:text-primary-container">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      
      <form action="../../controller/ForgotPasswordController.php" method="POST" class="space-y-4">
        <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
        
        <div class="space-y-2">
            <label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider">Identitas (NIS/Username)</label>
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">badge</span>
                <input required class="w-full pl-10 pr-4 py-2 border border-outline-variant focus:border-[#C5A059] outline-none rounded-lg bg-surface-container-lowest" name="identity" placeholder="Masukkan NIS atau Username" type="text"/>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider">Kata Sandi Baru</label>
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
                <input required class="w-full pl-10 pr-4 py-2 border border-outline-variant focus:border-[#C5A059] outline-none rounded-lg bg-surface-container-lowest" name="new_password" placeholder="••••••••" type="password"/>
            </div>
        </div>

        <div class="space-y-2">
            <label class="block font-label-caps text-label-caps text-primary-container uppercase tracking-wider">Konfirmasi Kata Sandi</label>
            <div class="relative group">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
                <input required class="w-full pl-10 pr-4 py-2 border border-outline-variant focus:border-[#C5A059] outline-none rounded-lg bg-surface-container-lowest" name="confirm_password" placeholder="••••••••" type="password"/>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('forgotPasswordModal').classList.add('hidden')" class="px-6 py-2 rounded-md font-label-caps text-sm bg-surface-container-high text-on-surface hover:bg-outline-variant transition-colors">Batal</button>
          <button type="submit" class="px-6 py-2 rounded-md font-label-caps text-sm bg-primary-container text-white hover:bg-on-primary-fixed-variant transition-colors">Reset Sandi</button>
        </div>
      </form>
    </div>
  </div>
</div>

</body></html>