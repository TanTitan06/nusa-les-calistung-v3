<?php

class AdminRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ================= 1. DASHBOARD BERANDA =================
    public function getStatistikRingkas(): array
    {
        $totalMurid = (int)$this->db->query("SELECT COUNT(*) FROM MURID")->fetchColumn();

        $stmtLastDate = $this->db->query("SELECT MAX(tanggal_pertemuan) FROM KEHADIRAN");
        $lastDate = $stmtLastDate->fetchColumn();

        $totalHadir = 0;
        $totalTercatat = 0;
        if ($lastDate) {
            $stmtHadir = $this->db->prepare(
                "SELECT 
                    SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                    COUNT(*) AS total
                 FROM KEHADIRAN WHERE tanggal_pertemuan = :tgl"
            );
            $stmtHadir->execute([':tgl' => $lastDate]);
            $rowHadir = $stmtHadir->fetch();
            $totalHadir = (int)($rowHadir['hadir'] ?? 0);
            $totalTercatat = (int)($rowHadir['total'] ?? 0);
        }

        $bulanIni = date('Y-m');
        $stmtBayar = $this->db->prepare(
            "SELECT COALESCE(SUM(jumlah), 0) FROM PEMBAYARAN 
             WHERE status_pembayaran = 'Lunas' AND DATE_FORMAT(tanggal, '%Y-%m') = :bln"
        );
        $stmtBayar->execute([':bln' => $bulanIni]);
        $pemasukanBulan = (float)$stmtBayar->fetchColumn();

        $pendaftarMenunggu = (int)$this->db->query(
            "SELECT COUNT(*) FROM PENDAFTARAN_ONLINE WHERE status_verifikasi = 'Menunggu'"
        )->fetchColumn();

        return [
            'total_murid'        => $totalMurid,
            'kehadiran_terakhir' => [
                'tanggal_pertemuan' => $lastDate ?: '-',
                'total_hadir'       => $totalHadir,
                'total_tercatat'    => $totalTercatat
            ],
            'pemasukan_bulan'    => $pemasukanBulan,
            'pendaftar_menunggu' => $pendaftarMenunggu
        ];
    }

    public function getMuridJilidEmpatKeAtas(): array
    {
        $sql = "SELECT m.nama_murid, ot.nama_orang_tua, j.jilid, j.halaman, j.tanggal
                FROM JURNAL_PEMBELAJARAN j
                JOIN MURID m ON j.id_murid = m.id_murid
                JOIN ORANG_TUA ot ON m.id_orang_tua = ot.id_orang_tua
                WHERE j.id_jurnal IN (
                    SELECT MAX(id_jurnal) FROM JURNAL_PEMBELAJARAN GROUP BY id_murid
                )
                AND j.jilid IN ('Jilid 4', 'Jilid 5', 'Jilid 6', 'Jilid 7', 'Mengaji')
                ORDER BY j.tanggal DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // ================= 2. MURID & ORANG TUA =================
    public function getDaftarMuridDanOrtu(string $cari = ''): array
    {
        $sql = "SELECT m.id_murid, m.nama_murid, m.tanggal_lahir, m.alamat_murid,
                       ot.id_orang_tua, ot.nama_orang_tua, ot.no_telepon,
                       u.username
                FROM MURID m
                JOIN ORANG_TUA ot ON m.id_orang_tua = ot.id_orang_tua
                LEFT JOIN USERS u ON ot.id_user = u.id_user";
        $params = [];

        if ($cari !== '') {
            $sql .= " WHERE m.nama_murid LIKE :q OR ot.nama_orang_tua LIKE :q";
            $params[':q'] = "%{$cari}%";
        }

        $sql .= " ORDER BY m.nama_murid ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function tambahMuridDanOrtu(array $data): void
    {
        $namaMurid   = trim($data['nama_murid'] ?? '');
        $tglLahir    = trim($data['tanggal_lahir'] ?? '');
        $alamat      = trim($data['alamat_murid'] ?? '');
        $namaOrtu    = trim($data['nama_orang_tua'] ?? '');
        $noTelp      = trim($data['no_telepon'] ?? '');
        $username    = trim($data['username_ortu'] ?? '');
        $password    = $data['password_ortu'] ?? '';

        if ($namaMurid === '' || $tglLahir === '' || $alamat === '' || $namaOrtu === '' || $noTelp === '') {
            throw new InvalidArgumentException("Semua kolom identitas murid dan orang tua wajib diisi.");
        }

        $this->db->beginTransaction();
        try {
            $idUser = null;
            if ($username !== '') {
                if (strlen($password) < 6) {
                    throw new InvalidArgumentException("Password akun orang tua minimal 6 karakter.");
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser = $this->db->prepare("INSERT INTO USERS (username, password_hash, role) VALUES (:u, :p, 'orang_tua')");
                $stmtUser->execute([':u' => $username, ':p' => $hash]);
                $idUser = (int)$this->db->lastInsertId();
            }

            $stmtOrtu = $this->db->prepare(
                "INSERT INTO ORANG_TUA (id_user, nama_orang_tua, no_telepon) VALUES (:uid, :nama, :telp)"
            );
            $stmtOrtu->execute([
                ':uid'  => $idUser,
                ':nama' => $namaOrtu,
                ':telp' => $noTelp
            ]);
            $idOrtu = (int)$this->db->lastInsertId();

            $stmtMurid = $this->db->prepare(
                "INSERT INTO MURID (id_orang_tua, nama_murid, tanggal_lahir, alamat_murid) VALUES (:oid, :nama, :tgl, :alamat)"
            );
            $stmtMurid->execute([
                ':oid'    => $idOrtu,
                ':nama'   => $namaMurid,
                ':tgl'    => $tglLahir,
                ':alamat' => $alamat
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function hapusMurid(int $idMurid): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM JURNAL_PEMBELAJARAN WHERE id_murid = :id")->execute([':id' => $idMurid]);
            $this->db->prepare("DELETE FROM KEHADIRAN WHERE id_murid = :id")->execute([':id' => $idMurid]);
            $this->db->prepare("DELETE FROM PEMBAYARAN WHERE id_murid = :id")->execute([':id' => $idMurid]);
            $this->db->prepare("DELETE FROM MURID WHERE id_murid = :id")->execute([':id' => $idMurid]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ================= 3. VERIFIKASI PENDAFTARAN =================
    public function getDaftarPendaftaranOnline(): array
    {
        $sql = "SELECT * FROM PENDAFTARAN_ONLINE 
                ORDER BY FIELD(status_verifikasi, 'Menunggu', 'Diterima', 'Ditolak'), tanggal_daftar DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function prosesVerifikasi(int $idDaftar, string $aksi): void
    {
        if (!in_array($aksi, ['Diterima', 'Ditolak'], true)) {
            throw new InvalidArgumentException("Status verifikasi tidak valid.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM PENDAFTARAN_ONLINE WHERE id_daftar = :id FOR UPDATE");
            $stmt->execute([':id' => $idDaftar]);
            $pendaftar = $stmt->fetch();

            if (!$pendaftar) {
                throw new InvalidArgumentException("Data pendaftaran tidak ditemukan.");
            }
            if ($pendaftar['status_verifikasi'] !== 'Menunggu') {
                throw new InvalidArgumentException("Pendaftaran ini sudah diproses sebelumnya.");
            }

            $upd = $this->db->prepare("UPDATE PENDAFTARAN_ONLINE SET status_verifikasi = :st WHERE id_daftar = :id");
            $upd->execute([':st' => $aksi, ':id' => $idDaftar]);

            if ($aksi === 'Diterima') {
                $stmtOrtu = $this->db->prepare(
                    "INSERT INTO ORANG_TUA (id_user, nama_orang_tua, no_telepon) VALUES (NULL, :nama, :telp)"
                );
                $stmtOrtu->execute([
                    ':nama' => $pendaftar['nama_orang_tua'],
                    ':telp' => $pendaftar['no_telepon']
                ]);
                $idOrtu = (int)$this->db->lastInsertId();

                $stmtMurid = $this->db->prepare(
                    "INSERT INTO MURID (id_orang_tua, nama_murid, tanggal_lahir, alamat_murid) VALUES (:oid, :nama, :tgl, :alamat)"
                );
                $stmtMurid->execute([
                    ':oid'    => $idOrtu,
                    ':nama'   => $pendaftar['nama_calon_murid'],
                    ':tgl'    => $pendaftar['tanggal_lahir'],
                    ':alamat' => $pendaftar['alamat_murid']
                ]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ================= 4. MANAJEMEN AKUN ADMIN & ORANG TUA =================
    public function getSemuaAkun(): array
    {
        $sql = "SELECT u.id_user, u.username, u.role,
                       ot.id_orang_tua, ot.nama_orang_tua, ot.no_telepon
                FROM USERS u
                LEFT JOIN ORANG_TUA ot ON u.id_user = ot.id_user
                ORDER BY u.role ASC, u.id_user ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getOrtuTanpaAkun(): array
    {
        $sql = "SELECT id_orang_tua, nama_orang_tua, no_telepon 
                FROM ORANG_TUA 
                WHERE id_user IS NULL 
                ORDER BY nama_orang_tua ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function buatAkunBaru(string $username, string $password, string $role, ?int $idOrangTua = null): void
    {
        $username = trim($username);
        if ($username === '' || strlen($password) < 6) {
            throw new InvalidArgumentException("Username wajib diisi dan password minimal 6 karakter.");
        }
        if (!in_array($role, ['admin', 'orang_tua'], true)) {
            throw new InvalidArgumentException("Peran akun tidak valid.");
        }
        if ($role === 'orang_tua' && empty($idOrangTua)) {
            throw new InvalidArgumentException("Pilih data Orang Tua yang akan dihubungkan dengan akun ini.");
        }

        $this->db->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO USERS (username, password_hash, role) VALUES (:u, :p, :r)");
            $stmt->execute([':u' => $username, ':p' => $hash, ':r' => $role]);
            $newUserId = (int)$this->db->lastInsertId();

            if ($role === 'orang_tua' && $idOrangTua) {
                $upd = $this->db->prepare("UPDATE ORANG_TUA SET id_user = :uid WHERE id_orang_tua = :oid AND id_user IS NULL");
                $upd->execute([':uid' => $newUserId, ':oid' => $idOrangTua]);
                if ($upd->rowCount() === 0) {
                    throw new InvalidArgumentException("Data orang tua tidak ditemukan atau sudah memiliki akun.");
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function ubahAkun(int $idUser, string $username, string $namaOrangTua = '', string $passwordBaru = ''): void
    {
        $username = trim($username);
        if ($idUser <= 0 || $username === '') {
            throw new InvalidArgumentException("ID akun dan username wajib diisi.");
        }

        $this->db->beginTransaction();
        try {
            if ($passwordBaru !== '') {
                if (strlen($passwordBaru) < 6) {
                    throw new InvalidArgumentException("Password baru minimal 6 karakter.");
                }
                $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE USERS SET username = :u, password_hash = :p WHERE id_user = :id");
                $stmt->execute([':u' => $username, ':p' => $hash, ':id' => $idUser]);
            } else {
                $stmt = $this->db->prepare("UPDATE USERS SET username = :u WHERE id_user = :id");
                $stmt->execute([':u' => $username, ':id' => $idUser]);
            }

            if (trim($namaOrangTua) !== '') {
                $stmtOrtu = $this->db->prepare("UPDATE ORANG_TUA SET nama_orang_tua = :nama WHERE id_user = :id");
                $stmtOrtu->execute([':nama' => trim($namaOrangTua), ':id' => $idUser]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}