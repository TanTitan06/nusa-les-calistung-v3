<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Repositories/OrtuRepository.php';
require_once __DIR__ . '/../../src/Helpers/AuthGuard.php';
require_once __DIR__ . '/../../src/Helpers/JsonResponse.php';

$user = AuthGuard::requireApiRole('orang_tua');

try {
    $db = (new Database())->getConnection();
    $ortuRepo = new OrtuRepository($db);

    $profilOrtu = $ortuRepo->getProfilOrtuByUserId((int)$user['id_user']);
    if (!$profilOrtu) {
        JsonResponse::error("Profil orang tua tidak ditemukan.", 404);
    }

    $daftarAnak = $ortuRepo->getDaftarAnak((int)$profilOrtu['id_orang_tua']);
    $selectedId = (int)($_GET['id_murid'] ?? ($daftarAnak[0]['id_murid'] ?? 0));

    $detailAnak = null;
    $jurnalTerakhir = null;
    $riwayatJurnal = [];
    $riwayatHadir = [];
    $riwayatBayar = [];

    if ($selectedId > 0) {
        foreach ($daftarAnak as $anak) {
            if ((int)$anak['id_murid'] === $selectedId) {
                $detailAnak = $anak;
                break;
            }
        }
        if ($detailAnak) {
            $jurnalTerakhir = $ortuRepo->getPerkembanganTerakhir($selectedId);
            $riwayatJurnal  = $ortuRepo->getRiwayatJurnal($selectedId);
            $riwayatHadir   = $ortuRepo->getRiwayatKehadiran($selectedId);
            $riwayatBayar   = $ortuRepo->getRiwayatPembayaran($selectedId);
        }
    }

    JsonResponse::success([
        'profil_ortu'     => $profilOrtu,
        'daftar_anak'     => $daftarAnak,
        'detail_anak'     => $detailAnak,
        'jurnal_terakhir' => $jurnalTerakhir,
        'riwayat_jurnal'  => $riwayatJurnal,
        'riwayat_hadir'   => $riwayatHadir,
        'riwayat_bayar'   => $riwayatBayar
    ]);
} catch (Exception $e) {
    error_log("Ortu API Error: " . $e->getMessage());
    JsonResponse::error("Gagal memuat data perkembangan anak.", 500);
}