<?php

class PendaftaranModel
{
    private string $namaCalonMurid;
    private string $tanggalLahir;
    private string $alamatMurid;
    private string $namaOrangTua;
    private string $noTelepon;
    private string $tanggalDaftar;

    public function __construct(
        string $namaCalonMurid,
        string $tanggalLahir,
        string $alamatMurid,
        string $namaOrangTua,
        string $noTelepon
    ) {
        $this->setNamaCalonMurid($namaCalonMurid);$this->setTanggalLahir($tanggalLahir);$this->setAlamatMurid($alamatMurid);$this->setNamaOrangTua($namaOrangTua);$this->setNoTelepon($noTelepon);$this->tanggalDaftar = date('Y-m-d');
    }

    private function setNamaCalonMurid(string $nama): void
    {
        $nama = trim($nama);
        if (empty($nama)) {
            throw new InvalidArgumentException("Nama lengkap calon murid wajib diisi.");
        }
        $this->namaCalonMurid =$nama;
    }

    private function setTanggalLahir(string $tanggal): void
    {
        if (empty(trim($tanggal))) {
            throw new InvalidArgumentException("Tanggal lahir calon murid wajib diisi.");
        }
        $this->tanggalLahir =$tanggal;
    }

    private function setAlamatMurid(string $alamat): void
    {
        $alamat = trim($alamat);
        if (empty($alamat)) {
            throw new InvalidArgumentException("Alamat tempat tinggal wajib diisi.");
        }
        $this->alamatMurid =$alamat;
    }

    private function setNamaOrangTua(string $nama): void
    {
        $nama = trim($nama);
        if (empty($nama)) {
            throw new InvalidArgumentException("Nama orang tua/wali wajib diisi.");
        }
        $this->namaOrangTua =$nama;
    }

    private function setNoTelepon(string $noTelp): void
    {
        $noTelp = trim($noTelp);
        if (!preg_match('/^[0-9]{10,15}$/',$noTelp)) {
            throw new InvalidArgumentException("Nomor WhatsApp/Telepon harus terdiri dari 10 hingga 15 digit angka.");
        }
        $this->noTelepon =$noTelp;
    }

    public function getNamaCalonMurid(): string { return $this->namaCalonMurid; }
    public function getTanggalLahir(): string { return $this->tanggalLahir; }
    public function getAlamatMurid(): string { return $this->alamatMurid; }
    public function getNamaOrangTua(): string { return $this->namaOrangTua; }
    public function getNoTelepon(): string { return $this->noTelepon; }
    public function getTanggalDaftar(): string { return $this->tanggalDaftar; }
}