<?php

class OrtuRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getProfilOrtuByUserId(int $idUser): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ORANG_TUA WHERE id_user = :id_user LIMIT 1");
        $stmt->execute([':id_user' => $idUser]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getDaftarAnak(int $idOrangTua): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM MURID WHERE id_orang_tua = :id_ortu ORDER BY id_murid ASC");
        $stmt->execute([':id_ortu' => $idOrangTua]);
        return $stmt->fetchAll();
    }

    public function getPerkembanganTerakhir(int $idMurid): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM JURNAL_PEMBELAJARAN 
             WHERE id_murid = :id_murid 
             ORDER BY tanggal DESC, id_jurnal DESC LIMIT 1"
        );
        $stmt->execute([':id_murid' => $idMurid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getRiwayatJurnal(int $idMurid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM JURNAL_PEMBELAJARAN 
             WHERE id_murid = :id_murid 
             ORDER BY tanggal DESC, id_jurnal DESC"
        );
        $stmt->execute([':id_murid' => $idMurid]);
        return $stmt->fetchAll();
    }

    public function getRiwayatKehadiran(int $idMurid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM KEHADIRAN 
             WHERE id_murid = :id_murid 
             ORDER BY tanggal_pertemuan DESC"
        );
        $stmt->execute([':id_murid' => $idMurid]);
        return $stmt->fetchAll();
    }

    public function getRiwayatPembayaran(int $idMurid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM PEMBAYARAN 
             WHERE id_murid = :id_murid 
             ORDER BY tanggal DESC, id_pembayaran DESC"
        );
        $stmt->execute([':id_murid' => $idMurid]);
        return $stmt->fetchAll();
    }
}