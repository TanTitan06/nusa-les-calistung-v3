<?php

class AuthGuard
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function requireApiRole(string $role): array
    {
        self::startSession();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
            JsonResponse::error("Akses ditolak. Silakan login terlebih dahulu.", 401);
        }
        return $_SESSION['user'];
    }
}