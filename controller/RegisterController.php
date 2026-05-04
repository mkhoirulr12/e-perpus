<?php
session_start();
require_once "../config/database.php";
require_once "../model/Siswa.php";

class RegisterController {
    public function register() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $data = [
                'nis' => $_POST['nis'] ?? '',
                'nama_siswa' => $_POST['nama_siswa'] ?? '',
                'password' => $_POST['password'] ?? '',
                'jenis_kelamin' => $_POST['jenis_kelamin'] ?? '',
                'kelas' => $_POST['kelas'] ?? '',
                'username' => $_POST['username'] ?? ''
            ];

            if (empty($data['nis']) || empty($data['nama_siswa']) || empty($data['password']) || 
                empty($data['jenis_kelamin']) || empty($data['kelas']) || empty($data['username'])) {
                $_SESSION['error'] = "Mohon lengkapi semua bidang pendaftaran!";
                header("Location: ../view/auth/Register.php");
                exit;
            }

            $database = new Database();
            $db = $database->connect();
            $siswaModel = new Siswa($db);

            $result = $siswaModel->registerSiswa($data);

            if ($result === true) {
                $_SESSION['success'] = "Pendaftaran berhasil! Silakan masuk dengan NIS dan Kata Sandi Anda.";
                header("Location: ../view/auth/Login.php");
                exit;
            } else {
                $_SESSION['error'] = $result ?: "Gagal mendaftar, silakan coba lagi.";
                header("Location: ../view/auth/Register.php");
                exit;
            }
        }
    }
}

$register = new RegisterController();
$register->register();
