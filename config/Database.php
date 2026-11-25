<?php

class Database
{
    private string $host = '';
    private string $dbName = '';
    private string $username = '';
    private string $password = '';
    private string $charset = 'utf8mb4';
    private ?PDO $pdo = null;

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database Error: " . $e->getMessage());
                throw new Exception("Terjadi gangguan koneksi pada sistem database.");
            }
        }

        return $this->pdo;
    }
}
