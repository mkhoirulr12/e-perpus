<?php
session_start();
require_once "../config/database.php";
require_once "../model/User.php";
require_once "../model/Siswa.php";

class LoginController {
    public function login() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $usernameInput = $_POST['identity'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'siswa';

            if (empty($usernameInput) || empty($password)) {
                $_SESSION['error'] = "Mohon lengkapi Username/NIS dan Kata Sandi!";
                header("Location: ../view/auth/Login.php");
                exit;
            }

            $database = new Database();
            $db = $database->connect();
            $userModel = new User($db);

            // 1. Try unified 'user' table first
            $user = $userModel->getByUsername($usernameInput);

            if ($user && $password === $user['password'] && $user['level'] === $role) {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama_user'];
                $_SESSION['level'] = $user['level'];

                if ($user['level'] === 'admin') {
                    header("Location: ../view/admin/dashboard.php");
                } else if ($user['level'] === 'pustakawan') {
                    header("Location: ../view/pustakawan/PustakawanDashboard.php");
                } else if ($user['level'] === 'siswa') {
                    $_SESSION['siswa_id'] = $user['id_siswa'];
                    header("Location: ../view/siswa/SiswaDashboard.php");
                }
                exit;
            }

            // 2. Fallback for Students if not found in 'user' table (Existing Structure Support)
            if ($role === 'siswa') {
                $siswaModel = new Siswa($db);
                $siswa = $siswaModel->login($usernameInput, $password);
                
                if ($siswa) {
                    $_SESSION['user_id'] = null; // No linked user record yet
                    $_SESSION['siswa_id'] = $siswa['id_siswa'];
                    $_SESSION['nama'] = $siswa['nama_siswa'];
                    $_SESSION['level'] = 'siswa';
                    header("Location: ../view/siswa/SiswaDashboard.php");
                    exit;
                }
            }

            // If all fails
            $_SESSION['error'] = "Username/NIS atau Password salah!";
            header("Location: ../view/auth/Login.php?role=" . $role);
            exit;
        }
    }
}

$login = new LoginController();
$login->login();