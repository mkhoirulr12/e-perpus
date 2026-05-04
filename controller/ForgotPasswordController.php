<?php
session_start();
require_once "../config/database.php";
require_once "../model/User.php";
require_once "../model/Siswa.php";

class ForgotPasswordController {
    public function resetPassword() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $role = $_POST['role'] ?? 'siswa';
            $identity = $_POST['identity'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($identity) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['error'] = "Mohon lengkapi semua bidang!";
                header("Location: ../view/auth/ForgotPassword.php?role=" . $role);
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = "Konfirmasi kata sandi tidak cocok!";
                header("Location: ../view/auth/ForgotPassword.php?role=" . $role);
                exit;
            }

            $database = new Database();
            $db = $database->connect();

            if ($role === 'siswa') {
                $siswaModel = new Siswa($db);
                $siswa = $siswaModel->getByNis($identity);

                if (!$siswa) {
                    $_SESSION['error'] = "NIS tidak ditemukan atau akun tidak aktif!";
                    header("Location: ../view/auth/ForgotPassword.php?role=siswa");
                    exit;
                }

                $result = $siswaModel->updatePassword($siswa['id_siswa'], $newPassword);
            } else {
                $userModel = new User($db);
                $user = $userModel->getByUsername($identity);

                if (!$user) {
                    $_SESSION['error'] = "Username tidak ditemukan atau akun tidak aktif!";
                    header("Location: ../view/auth/ForgotPassword.php?role=pustakawan");
                    exit;
                }

                $result = $userModel->updatePassword($user['id_user'], $newPassword);
            }

            if ($result) {
                $_SESSION['success'] = "Kata sandi berhasil diubah! Silakan login dengan kata sandi baru.";
                header("Location: ../view/auth/Login.php?role=" . $role);
                exit;
            } else {
                $_SESSION['error'] = "Gagal mengubah kata sandi, coba lagi.";
                header("Location: ../view/auth/ForgotPassword.php?role=" . $role);
                exit;
            }
        }
    }
}

$controller = new ForgotPasswordController();
$controller->resetPassword();
