<?php

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $pessoa): void
    {
        self::start();

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int) $pessoa['id'],
            'nome' => $pessoa['nome'],
            'cpf' => $pessoa['cpf'],
            'cargo' => $pessoa['cargo'],
            'ativo' => (int) $pessoa['ativo'],
            'precisa_trocar_senha' => (int) ($pessoa['precisa_trocar_senha'] ?? 0)
        ];
    }

    public static function atualizarSessao(array $pessoa): void
    {
        self::start();

        $_SESSION['usuario'] = [
            'id' => (int) $pessoa['id'],
            'nome' => $pessoa['nome'],
            'cpf' => $pessoa['cpf'],
            'cargo' => $pessoa['cargo'],
            'ativo' => (int) $pessoa['ativo'],
            'precisa_trocar_senha' => (int) ($pessoa['precisa_trocar_senha'] ?? 0)
        ];
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION['usuario']);
        session_destroy();
    }

    public static function usuario(): ?array
    {
        self::start();
        return $_SESSION['usuario'] ?? null;
    }

    public static function check(): bool
    {
        return self::usuario() !== null;
    }

    public static function id(): ?int
    {
        $usuario = self::usuario();
        return $usuario ? (int) $usuario['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $usuario = self::usuario();
        return $usuario && $usuario['cargo'] === 'admin';
    }

    public static function precisaTrocarSenha(): bool
    {
        $usuario = self::usuario();
        return $usuario && (int) ($usuario['precisa_trocar_senha'] ?? 0) === 1;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }

    public static function requireSenhaAtualizada(): void
    {
        self::requireLogin();

        $paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');

        if (self::precisaTrocarSenha() && !in_array($paginaAtual, ['meu_perfil.php', 'logout.php'], true)) {
            header('Location: /meu_perfil.php?forcar_troca=1');
            exit;
        }
    }
}