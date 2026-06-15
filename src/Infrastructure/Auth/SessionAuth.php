<?php

/**
 * Autenticação Simples por Sessão PHP
 * Gerencia login/logout de administradores do sistema.
 */

namespace App\Infrastructure\Auth;

class SessionAuth
{
    private const SESSION_KEY = 'biblioteca_admin';

    /**
     * Inicia a sessão se ainda não iniciada.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se há um administrador autenticado na sessão.
     */
    public static function isAuthenticated(): bool
    {
        self::start();
        return isset($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY] === true;
    }

    /**
     * Redireciona para login se não autenticado.
     */
    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Registra o administrador como autenticado.
     */
    public static function login(): void
    {
        self::start();
        $_SESSION[self::SESSION_KEY] = true;
    }

    /**
     * Encerra a sessão e faz logout.
     */
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
