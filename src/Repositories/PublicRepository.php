<?php

class PublicRepository
{
    public function __construct(private PDO $db) {}

    public function getPengaturanWeb(): array
    {
        $stmt = $this->db->query("SELECT * FROM PENGATURAN_WEB WHERE id_pengaturan = 1 LIMIT 1");
        $data = $stmt->fetch();

        return $data ?: [
            'nama_bimbel'     => 'Nusa Les Calistung',
            'profil_singkat'  => 'Bimbingan belajar membaca, menulis, berhitung, dan mengaji.',
            'lokasi_alamat'   => 'Sangatta Selatan, Kutai Timur',
            'link_gmaps'      => '#',
            'no_whatsapp'     => '6281234567890',
            'jadwal_belajar'  => 'Selasa, Rabu, Kamis | 15.00 - 16.00 WITA',
            'rincian_program' => 'Calistung & Mengaji',
            'rincian_biaya'   => 'Pendaftaran Rp 150.000 | Bulanan Rp 100.000'
        ];
    }

    public function getTestimoniTampil(): array
    {
        $stmt = $this->db->query(
            "SELECT nama_orang_tua, isi_testimoni, tanggal_dibuat 
             FROM TESTIMONI 
             WHERE status_tampil = 'Tampil' 
             ORDER BY tanggal_dibuat DESC"
        );
        return $stmt->fetchAll();
    }

    public function simpanPendaftaran(PendaftaranModel $data): bool
    {
        $sql = "INSERT INTO PENDAFTARAN_ONLINE 
                (nama_calon_murid, tanggal_lahir, alamat_murid, nama_orang_tua, no_telepon, tanggal_daftar, status_verifikasi) 
                VALUES (:nama_murid, :tgl_lahir, :alamat, :nama_ortu, :no_telp, :tgl_daftar, 'Menunggu')";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nama_murid' => $data->getNamaCalonMurid(),
            'tgl_lahir'  => $data->getTanggalLahir(),
            'alamat'     => $data->getAlamatMurid(),
            'nama_ortu'  => $data->getNamaOrangTua(),
            'no_telp'    => $data->getNoTelepon(),
            'tgl_daftar' => $data->getTanggalDaftar()
        ]);
    }
}