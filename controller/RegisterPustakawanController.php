<?php
session_start();
require_once "../config/database.php";
require_once "../model/User.php";

class RegisterPustakawanController {
    public function register() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $data = [
                'nama_user' => $_POST['nama_user'] ?? '',
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'level' => $_POST['level'] ?? ''
            ];

            if (empty($data['nama_user']) || empty($data['username']) || empty($data['password']) || empty($data['level'])) {
                $_SESSION['error'] = "Mohon lengkapi semua bidang pendaftaran!";
                header("Location: ../view/auth/RegisterPustakawan.php");
                exit;
            }

            // Validasi level hanya boleh admin atau pustakawan
            if (!in_array($data['level'], ['admin', 'pustakawan'])) {
                $_SESSION['error'] = "Jabatan tidak valid!";
                header("Location: ../view/auth/RegisterPustakawan.php");
                exit;
            }

            $database = new Database();
            $db = $database->connect();
            $userModel = new User($db);

            $result = $userModel->registerUser($data);

            if ($result === true) {
                $_SESSION['success'] = "Pendaftaran berhasil! Silakan masuk dengan Username dan Kata Sandi Anda.";
                header("Location: ../view/auth/Login.php?role=" . $data['level']);
                exit;
            } else {
                $_SESSION['error'] = $result ?: "Gagal mendaftar, silakan coba lagi.";
                header("Location: ../view/auth/RegisterPustakawan.php");
                exit;
            }
        }
    }
}

$controller = new RegisterPustakawanController();
$controller->register();
