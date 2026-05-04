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
<title>Lupa Kata Sandi - E-Perpus Prayasqi</title>
<link rel="icon" type="image/png" href="../../assets/img/logo.png">
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
                  "primary-container": "#2d5a27",
                  "secondary": "#735c00",
                  "background": "#fbf9f8",
                  "error-container": "#ffdad6",
                  "on-error-container": "#93000a",
                  "outline": "#74777f",
                  "surface-variant": "#e4e2e1",
                  "on-surface-variant": "#44474e",
                  "surface-container-lowest": "#ffffff",
          },
          "fontFamily": {
                  "body-main": ["Public Sans"],
                  "label-caps": ["Public Sans"],
                  "h2": ["Newsreader"],
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
<main class="min-h-screen flex items-center justify-center p-8">
<div class="w-full max-w-md bg-white shadow-[0px_4px_40px_rgba(0,0,0,0.08)] rounded-lg p-10">

<a href="Login.php?role=<?= htmlspecialchars($role) ?>" class="inline-flex items-center gap-2 text-sm font-label-caps text-outline hover:text-primary-container transition-colors uppercase tracking-widest mb-8 bg-slate-50 px-4 py-2 rounded-full border border-slate-200">
    <span class="material-symbols-outlined text-lg">arrow_back</span>
    Kembali ke Login
</a>

<div class="text-center mb-8">
    <div class="w-16 h-16 bg-[#2d5a27] text-white rounded-full mx-auto flex items-center justify-center mb-4 overflow-hidden border-2 border-gold">
        <img src="../../assets/img/logo.png" alt="Logo" class="w-full h-full object-contain">
    </div>
    <h2 class="font-h2 text-2xl text-[#2d5a27] font-bold mb-2">Lupa Kata Sandi?</h2>
    <p class="text-sm text-slate-500">
        <?php if($role === 'siswa'): ?>
            Masukkan <strong>NIS</strong> Anda dan kata sandi baru.
        <?php else: ?>
            Masukkan <strong>Username</strong> Anda dan kata sandi baru.
        <?php endif; ?>
    </p>
</div>

<?php if (!empty($error)): ?>
<div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm border border-red-300">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
<?php if (!empty($success)): ?>
<div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm border border-green-300">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<form class="space-y-5" method="POST" action="../../controller/ForgotPasswordController.php">
    <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

    <!-- Identity -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-[#2d5a27] uppercase tracking-wider font-bold">
            <?= $role === 'siswa' ? 'NIS' : 'Username' ?>
        </label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline"><?= $role === 'siswa' ? 'badge' : 'person' ?></span>
            <input required class="w-full pl-10 pr-4 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" name="identity" placeholder="<?= $role === 'siswa' ? 'Masukkan NIS' : 'Masukkan Username' ?>" type="text"/>
        </div>
    </div>

    <!-- New Password -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-[#2d5a27] uppercase tracking-wider font-bold">Kata Sandi Baru</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
            <input required class="w-full pl-10 pr-12 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" id="newPassword" name="new_password" placeholder="Masukkan Kata Sandi Baru" type="password"/>
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-[#2d5a27] transition-colors" type="button" onclick="togglePw('newPassword','eyeNew')">
                <span class="material-symbols-outlined" id="eyeNew">visibility</span>
            </button>
        </div>
    </div>

    <!-- Confirm Password -->
    <div class="space-y-2">
        <label class="block font-label-caps text-xs text-[#2d5a27] uppercase tracking-wider font-bold">Konfirmasi Kata Sandi</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
            <input required class="w-full pl-10 pr-12 py-3 border border-outline-variant focus:border-[#C5A059] focus:ring-1 focus:ring-[#C5A059] rounded-lg bg-slate-50" id="confirmPassword" name="confirm_password" placeholder="Ulangi Kata Sandi Baru" type="password"/>
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-[#2d5a27] transition-colors" type="button" onclick="togglePw('confirmPassword','eyeConfirm')">
                <span class="material-symbols-outlined" id="eyeConfirm">visibility</span>
            </button>
        </div>
    </div>

    <button class="w-full mt-2 py-4 bg-[#2d5a27] text-white font-nav-link text-sm rounded-lg hover:bg-[#1e3d1a] transition-all duration-200 flex items-center justify-center gap-2 shadow-lg uppercase tracking-wider" type="submit">
        Ubah Kata Sandi
        <span class="material-symbols-outlined">save</span>
    </button>
</form>
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
