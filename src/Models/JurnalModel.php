<?php

class JurnalModel
{
    private int $idMurid;
    private string $tanggal;
    private string $jilid;
    private int $halaman;
    private ?string $catatanPengajar;

    public function __construct(int $idMurid, string $tanggal, string $jilid, int $halaman, ?string $catatanPengajar = null)
    {
        $this->setIdMurid($idMurid);
        $this->setTanggal($tanggal);
        $this->setJilid($jilid);
        $this->setHalaman($halaman);
        $this->catatanPengajar = $catatanPengajar !== null ? trim($catatanPengajar) : null;
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
            throw new InvalidArgumentException("Tanggal pembelajaran wajib diisi.");
        }
        $this->tanggal = $tanggal;
    }

    private function setJilid(string $jilid): void
    {
        if (empty(trim($jilid))) {
            throw new InvalidArgumentException("Jilid buku wajib diisi.");
        }
        $this->jilid = trim($jilid);
    }

    private function setHalaman(int $halaman): void
    {
        if ($halaman <= 0) {
            throw new InvalidArgumentException("Nomor halaman harus lebih dari 0.");
        }
        $this->halaman = $halaman;
    }

    public function getIdMurid(): int { return $this->idMurid; }
    public function getTanggal(): string { return $this->tanggal; }
    public function getJilid(): string { return $this->jilid; }
    public function getHalaman(): int { return $this->halaman; }
    public function getCatatanPengajar(): ?string { return $this->catatanPengajar; }
}