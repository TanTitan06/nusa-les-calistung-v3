<?php

class OperasionalRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ================= 1. KEHADIRAN MASSAL =================
    public function getDaftarHadirByTanggal(string $tanggal): array
    {
        $sql = "SELECT m.id_murid, m.nama_murid, k.status_kehadiran
                FROM MURID m
                LEFT JOIN KEHADIRAN k 
                    ON m.id_murid = k.id_murid AND k.tanggal_pertemuan = :tgl
                ORDER BY m.nama_murid ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tgl' => $tanggal]);
        return $stmt->fetchAll();
    }

    public function simpanKehadiranMassal(string $tanggal, array $statusMap): void
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM KEHADIRAN WHERE tanggal_pertemuan = :tgl");
            $del->execute([':tgl' => $tanggal]);

            $ins = $this->db->prepare(
                "INSERT INTO KEHADIRAN (id_murid, tanggal_pertemuan, status_kehadiran) 
                 VALUES (:id_murid, :tgl, :status)"
            );

            foreach ($statusMap as $idMurid => $status) {
                if (in_array($status, ['Hadir', 'Tidak Hadir'], true)) {
                    $ins->execute([
                        ':id_murid' => (int)$idMurid,
                        ':tgl'      => $tanggal,
                        ':status'   => $status
                    ]);
                }
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ================= 2. JURNAL PEMBELAJARAN =================
    public function getDaftarJurnal(int $filterMurid = 0): array
    {
        $sql = "SELECT j.*, m.nama_murid
                FROM JURNAL_PEMBELAJARAN j
                JOIN MURID m ON j.id_murid = m.id_murid";
        $params = [];

        if ($filterMurid > 0) {
            $sql .= " WHERE j.id_murid = :id_murid";
            $params[':id_murid'] = $filterMurid;
        }

        $sql .= " ORDER BY j.tanggal DESC, j.id_jurnal DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function tambahJurnal(JurnalModel $model): void
    {
        $sql = "INSERT INTO JURNAL_PEMBELAJARAN (id_murid, tanggal, jilid, halaman, catatan_pengajar)
                VALUES (:id_murid, :tanggal, :jilid, :halaman, :catatan)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_murid' => $model->idMurid,
            ':tanggal'  => $model->tanggal,
            ':jilid'    => $model->jilid,
            ':halaman'  => $model->halaman,
            ':catatan'  => $model->catatanPengajar
        ]);
    }

    public function hapusJurnal(int $idJurnal): void
    {
        $stmt = $this->db->prepare("DELETE FROM JURNAL_PEMBELAJARAN WHERE id_jurnal = :id");
        $stmt->execute([':id' => $idJurnal]);
    }

    // ================= 3. PEMBAYARAN =================
    public function getDaftarPembayaran(): array
    {
        $sql = "SELECT p.*, m.nama_murid
                FROM PEMBAYARAN p
                JOIN MURID m ON p.id_murid = m.id_murid
                ORDER BY p.tanggal DESC, p.id_pembayaran DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function tambahPembayaran(PembayaranModel $model): string
    {
        $noKuitansi = 'INV-' . date('Ymd') . '-' . str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO PEMBAYARAN (id_murid, tanggal, jenis_pembayaran, jumlah, status_pembayaran, no_kuitansi)
                VALUES (:id_murid, :tanggal, :jenis, :jumlah, :status, :no_kuitansi)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_murid'    => $model->idMurid,
            ':tanggal'     => $model->tanggal,
            ':jenis'       => $model->jenisPembayaran,
            ':jumlah'      => $model->jumlah,
            ':status'      => $model->statusPembayaran,
            ':no_kuitansi' => $noKuitansi
        ]);
        return $noKuitansi;
    }

    // ================= 4. PENGATURAN WEB & TESTIMONI =================
    public function getPengaturanWeb(): array
    {
        $row = $this->db->query("SELECT * FROM PENGATURAN_WEB LIMIT 1")->fetch();
        return $row ?: [];
    }

    public function updatePengaturanWeb(array $data): void
    {
        $sql = "UPDATE PENGATURAN_WEB SET
                    nama_bimbel     = :nama,
                    profil_singkat  = :profil,
                    no_whatsapp     = :wa,
                    jadwal_belajar  = :jadwal,
                    lokasi_alamat   = :alamat,
                    link_gmaps      = :gmaps,
                    rincian_program = :program,
                    rincian_biaya   = :biaya
                WHERE id_pengaturan = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nama'    => trim($data['nama_bimbel'] ?? ''),
            ':profil'  => trim($data['profil_singkat'] ?? ''),
            ':wa'      => trim($data['no_whatsapp'] ?? ''),
            ':jadwal'  => trim($data['jadwal_belajar'] ?? ''),
            ':alamat'  => trim($data['lokasi_alamat'] ?? ''),
            ':gmaps'   => trim($data['link_gmaps'] ?? ''),
            ':program' => trim($data['rincian_program'] ?? ''),
            ':biaya'   => trim($data['rincian_biaya'] ?? '')
        ]);
    }

    public function getSemuaTestimoni(): array
    {
        return $this->db->query("SELECT * FROM TESTIMONI ORDER BY id_testimoni DESC")->fetchAll();
    }

    public function tambahTestimoni(string $namaOrtu, string $isi): void
    {
        if (trim($namaOrtu) === '' || trim($isi) === '') {
            throw new InvalidArgumentException("Nama orang tua dan isi testimoni wajib diisi.");
        }
        $stmt = $this->db->prepare(
            "INSERT INTO TESTIMONI (nama_orang_tua, isi_testimoni, status_tampil) VALUES (:nama, :isi, 'Tampil')"
        );
        $stmt->execute([
            ':nama' => trim($namaOrtu),
            ':isi'  => trim($isi)
        ]);
    }

    public function toggleStatusTestimoni(int $idTestimoni, string $statusBaru): void
    {
        if (!in_array($statusBaru, ['Tampil', 'Sembunyikan'], true)) {
            throw new InvalidArgumentException("Status testimoni tidak valid.");
        }
        $stmt = $this->db->prepare("UPDATE TESTIMONI SET status_tampil = :st WHERE id_testimoni = :id");
        $stmt->execute([':st' => $statusBaru, ':id' => $idTestimoni]);
    }
}