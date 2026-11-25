<?php

class PembayaranModel
{
    private int $idMurid;
    private string $tanggal;
    private string $jenisPembayaran;
    private float $jumlah;
    private string $statusPembayaran;
    private string $noKuitansi;

    public function __construct(
        int $idMurid,
        string $tanggal,
        string $jenisPembayaran,
        float $jumlah,
        string $statusPembayaran = 'Lunas'
    ) {
        $this->setIdMurid($idMurid);
        $this->setTanggal($tanggal);
        $this->setJenisPembayaran($jenisPembayaran);
        $this->setJumlah($jumlah);
        $this->setStatusPembayaran($statusPembayaran);
        $this->noKuitansi = $this->generateNoKuitansi();
    }

    private function setIdMurid(int $idMurid): void
    {
        if ($idMurid <= 0) {
            throw new InvalidArgumentException("ID Murid tidak valid.");
        }
        $this->idMurid = $idMurid;
    }

    private function setTanggal(string $tanggal): void
    {
        if (empty(trim($tanggal))) {
            throw new InvalidArgumentException("Tanggal pembayaran wajib diisi.");
        }
        $this->tanggal = $tanggal;
    }

    private function setJenisPembayaran(string $jenis): void
    {
        if (empty(trim($jenis))) {
            throw new InvalidArgumentException("Jenis pembayaran wajib diisi.");
        }
        $this->jenisPembayaran = trim($jenis);
    }

    private function setJumlah(float $jumlah): void
    {
        if ($jumlah < 0) {
            throw new InvalidArgumentException("Jumlah pembayaran tidak boleh negatif.");
        }
        $this->jumlah = $jumlah;
    }

    private function setStatusPembayaran(string $status): void
    {
        if (!in_array($status, ['Lunas', 'Belum Lunas'], true)) {
            throw new InvalidArgumentException("Status pembayaran tidak valid.");
        }
        $this->statusPembayaran = $status;
    }

    private function generateNoKuitansi(): string
    {
        return 'KWT-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -4));
    }

    public function getIdMurid(): int { return $this->idMurid; }
    public function getTanggal(): string { return $this->tanggal; }
    public function getJenisPembayaran(): string { return $this->jenisPembayaran; }
    public function getJumlah(): float { return $this->jumlah; }
    public function getStatusPembayaran(): string { return $this->statusPembayaran; }
    public function getNoKuitansi(): string { return $this->noKuitansi; }
}