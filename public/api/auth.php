<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Repositories/AuthRepository.php';
require_once __DIR__ . '/../../src/Helpers/AuthGuard.php';
require_once __DIR__ . '/../../src/Helpers/JsonResponse.php';

AuthGuard::startSession();
$action = $_GET['action'] ?? '';

try {
    if ($action === 'check') {
        if (!isset($_SESSION['user'])) {
            JsonResponse::error("Belum login.", 401);
        }
        JsonResponse::success($_SESSION['user'], "Sesi aktif.");
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        JsonResponse::success(null, "Logout berhasil.");
    }

    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        $db = (new Database())->getConnection();
        $authRepo = new AuthRepository($db);
        $user = $authRepo->login($username, $password);

        if (!$user) {
            JsonResponse::error("Username atau password salah.", 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        JsonResponse::success($user, "Login berhasil.");
    }

    JsonResponse::error("Endpoint atau metode tidak valid.", 405);
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    JsonResponse::error("Terjadi gangguan pada server autentikasi.", 500);
}