<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Models/PendaftaranModel.php';
require_once __DIR__ . '/../../src/Repositories/PublicRepository.php';
require_once __DIR__ . '/../../src/Helpers/JsonResponse.php';

$action = $_GET['action'] ?? 'home';

try {
    $db = (new Database())->getConnection();
    $publicRepo = new PublicRepository($db);

    if ($action === 'home' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $info = $publicRepo->getPengaturanWeb();
        $testimoni = $publicRepo->getTestimoniTampil();
        JsonResponse::success([
            'info'      => $info,
            'testimoni' => $testimoni
        ]);
    }

    if ($action === 'daftar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $model = new PendaftaranModel(
            $input['nama_calon_murid'] ?? '',
            $input['tanggal_lahir'] ?? '',
            $input['alamat_murid'] ?? '',
            $input['nama_orang_tua'] ?? '',
            $input['no_telepon'] ?? ''
        );

        $publicRepo->simpanPendaftaran($model);
        JsonResponse::success(null, "Pra-pendaftaran berhasil dikirim! Data Anda menunggu verifikasi pengajar.", 201);
    }

    JsonResponse::error("Permintaan tidak valid.", 400);
} catch (InvalidArgumentException $e) {
    JsonResponse::error($e->getMessage(), 422);
} catch (Exception $e) {
    error_log("Public API Error: " . $e->getMessage());
    JsonResponse::error("Terjadi gangguan sistem saat memproses permintaan.", 500);
}