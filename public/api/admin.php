<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../src/Repositories/AdminRepository.php';
require_once __DIR__ . '/../../src/Repositories/OperasionalRepository.php';
require_once __DIR__ . '/../../src/Models/JurnalModel.php';
require_once __DIR__ . '/../../src/Models/PembayaranModel.php';
require_once __DIR__ . '/../../src/Helpers/AuthGuard.php';
require_once __DIR__ . '/../../src/Helpers/JsonResponse.php';

AuthGuard::requireApiRole('admin');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    $db = (new Database())->getConnection();
    $adminRepo = new AdminRepository($db);
    $opsRepo   = new OperasionalRepository($db);

    // 1. DASHBOARD
    if ($action === 'dashboard' && $method === 'GET') {
        JsonResponse::success([
            'statistik'    => $adminRepo->getStatistikRingkas(),
            'murid_lanjut' => $adminRepo->getMuridJilidEmpatKeAtas()
        ]);
    }

    // 2. MURID & ORTU
    if ($action === 'murid_list' && $method === 'GET') {
        $cari = trim($_GET['cari'] ?? '');
        JsonResponse::success($adminRepo->getDaftarMuridDanOrtu($cari));
    }
    if ($action === 'murid_tambah' && $method === 'POST') {
        $adminRepo->tambahMuridDanOrtu($input);
        JsonResponse::success(null, "Data murid dan orang tua baru berhasil ditambahkan.", 201);
    }
    if ($action === 'murid_hapus' && $method === 'POST') {
        $idMurid = (int)($input['id_murid'] ?? 0);
        if ($idMurid <= 0) JsonResponse::error("ID murid tidak valid.", 400);
        $adminRepo->hapusMurid($idMurid);
        JsonResponse::success(null, "Data murid berhasil dihapus.");
    }

    // 3. VERIFIKASI PENDAFTARAN
    if ($action === 'verifikasi_list' && $method === 'GET') {
        JsonResponse::success($adminRepo->getDaftarPendaftaranOnline());
    }
    if ($action === 'verifikasi_proses' && $method === 'POST') {
        $idDaftar = (int)($input['id_daftar'] ?? 0);
        $aksi     = $input['aksi'] ?? '';
        $adminRepo->prosesVerifikasi($idDaftar, $aksi);
        JsonResponse::success(null, "Status pendaftaran berhasil diperbarui menjadi {$aksi}.");
    }

    // 4. KEHADIRAN
    if ($action === 'kehadiran_list' && $method === 'GET') {
        $tgl = $_GET['tanggal'] ?? date('Y-m-d');
        JsonResponse::success($opsRepo->getDaftarHadirByTanggal($tgl));
    }
    if ($action === 'kehadiran_simpan' && $method === 'POST') {
        $tgl = $input['tanggal_pertemuan'] ?? date('Y-m-d');
        $statusMap = $input['status'] ?? [];
        if (!is_array($statusMap) || empty($statusMap)) {
            JsonResponse::error("Tidak ada data kehadiran yang dikirim.", 400);
        }
        $opsRepo->simpanKehadiranMassal($tgl, $statusMap);
        JsonResponse::success(null, "Data kehadiran tanggal {$tgl} berhasil disimpan.");
    }

    // 5. JURNAL PEMBELAJARAN
    if ($action === 'jurnal_list' && $method === 'GET') {
        $filterMurid = (int)($_GET['id_murid'] ?? 0);
        JsonResponse::success([
            'murid'  => $adminRepo->getDaftarMuridDanOrtu(),
            'jurnal' => $opsRepo->getDaftarJurnal($filterMurid)
        ]);
    }
    if ($action === 'jurnal_tambah' && $method === 'POST') {
        $model = JurnalModel::fromArray($input);
        $opsRepo->tambahJurnal($model);
        JsonResponse::success(null, "Catatan jurnal pembelajaran berhasil disimpan.", 201);
    }
    if ($action === 'jurnal_hapus' && $method === 'POST') {
        $idJurnal = (int)($input['id_jurnal'] ?? 0);
        if ($idJurnal <= 0) JsonResponse::error("ID jurnal tidak valid.", 400);
        $opsRepo->hapusJurnal($idJurnal);
        JsonResponse::success(null, "Catatan jurnal berhasil dihapus.");
    }

    // 6. PEMBAYARAN
    if ($action === 'pembayaran_list' && $method === 'GET') {
        JsonResponse::success([
            'murid'      => $adminRepo->getDaftarMuridDanOrtu(),
            'pembayaran' => $opsRepo->getDaftarPembayaran()
        ]);
    }
    if ($action === 'pembayaran_tambah' && $method === 'POST') {
        $model = PembayaranModel::fromArray($input);
        $noKuitansi = $opsRepo->tambahPembayaran($model);
        JsonResponse::success(['no_kuitansi' => $noKuitansi], "Transaksi pembayaran berhasil dicatat.", 201);
    }

    // 7. PENGATURAN WEB & TESTIMONI
    if ($action === 'pengaturan_get' && $method === 'GET') {
        JsonResponse::success([
            'info'      => $opsRepo->getPengaturanWeb(),
            'testimoni' => $opsRepo->getSemuaTestimoni()
        ]);
    }
    if ($action === 'pengaturan_update' && $method === 'POST') {
        $opsRepo->updatePengaturanWeb($input);
        JsonResponse::success(null, "Informasi profil website berhasil diperbarui.");
    }
    if ($action === 'testimoni_tambah' && $method === 'POST') {
        $opsRepo->tambahTestimoni($input['nama_orang_tua'] ?? '', $input['isi_testimoni'] ?? '');
        JsonResponse::success(null, "Testimoni baru berhasil ditambahkan.", 201);
    }
    if ($action === 'testimoni_toggle' && $method === 'POST') {
        $opsRepo->toggleStatusTestimoni((int)($input['id_testimoni'] ?? 0), $input['status_baru'] ?? '');
        JsonResponse::success(null, "Status tampil testimoni berhasil diubah.");
    }

    // 8. MANAJEMEN AKUN ADMIN & ORANG TUA
    if ($action === 'akun_list' && $method === 'GET') {
        JsonResponse::success([
            'akun'            => $adminRepo->getSemuaAkun(),
            'ortu_tanpa_akun' => $adminRepo->getOrtuTanpaAkun()
        ]);
    }
    if ($action === 'akun_tambah' && $method === 'POST') {
        $adminRepo->buatAkunBaru(
            $input['username'] ?? '',
            $input['password'] ?? '',
            $input['role'] ?? '',
            !empty($input['id_orang_tua']) ? (int)$input['id_orang_tua'] : null
        );
        JsonResponse::success(null, "Akun pengguna baru berhasil dibuat.", 201);
    }
    if ($action === 'akun_ubah' && $method === 'POST') {
        $adminRepo->ubahAkun(
            (int)($input['id_user'] ?? 0),
            $input['username'] ?? '',
            $input['nama_orang_tua'] ?? '',
            $input['password_baru'] ?? ''
        );
        JsonResponse::success(null, "Perubahan nama/username dan password akun berhasil disimpan.");
    }

    JsonResponse::error("Aksi API tidak dikenali.", 400);

} catch (InvalidArgumentException $e) {
    JsonResponse::error($e->getMessage(), 422);
} catch (PDOException $e) {
    error_log("Admin API DB Error: " . $e->getMessage());
    if ((int)$e->getCode() === 23000) {
        JsonResponse::error("Data duplikat atau melanggar relasi database (contoh: username sudah digunakan).", 409);
    }
    JsonResponse::error("Terjadi kesalahan pada database.", 500);
} catch (Exception $e) {
    error_log("Admin API Error: " . $e->getMessage());
    JsonResponse::error("Terjadi gangguan sistem.", 500);
}