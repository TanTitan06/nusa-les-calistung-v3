<?php

class AuthRepository
{
    public function __construct(private PDO $db) {}

    public function login(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_user, u.username, u.password_hash, u.role, ot.id_orang_tua, ot.nama_orang_tua
             FROM USERS u
             LEFT JOIN ORANG_TUA ot ON u.id_user = ot.id_user
             WHERE u.username = :username
             LIMIT 1"
        );
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }

        return null;
    }
}