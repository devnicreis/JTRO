<?php

require_once __DIR__ . '/RequestContext.php';
require_once __DIR__ . '/PrivacySettings.php';
require_once __DIR__ . '/SecurityHeaders.php';

class Auth
{
    public static function start(): void
    {
        SecurityHeaders::send();
        SecurityHeaders::sendNoStore();

        if (session_status() === PHP_SESSION_NONE) {
            $secureCookie = RequestContext::isHttps();

            if (!headers_sent()) {
                ini_set('session.use_only_cookies', '1');
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_trans_sid', '0');
                ini_set('session.cookie_httponly', '1');
                ini_set('session.cookie_secure', $secureCookie ? '1' : '0');

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'secure' => $secureCookie,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            session_start();
        }

        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function login(array $pessoa): void
    {
        self::start();

        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['usuario'] = self::usuarioParaSessao($pessoa);
    }

    public static function atualizarSessao(array $pessoa): void
    {
        self::start();

        $_SESSION['usuario'] = self::usuarioParaSessao($pessoa);
    }

    public static function logout(): void
    {
        self::start();

        unset($_SESSION['usuario'], $_SESSION['csrf_token']);
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
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

    public static function precisaAceitarPrivacidade(): bool
    {
        $usuario = self::usuario();
        return $usuario && !PrivacySettings::consentimentoAtual($usuario);
    }

    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function csrfMetaTag(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function requireCsrf(?string $providedToken = null): void
    {
        self::start();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $token = $providedToken ?? (string) ($_POST['_csrf'] ?? '');

        if (self::tokenCsrfValido($token) || self::origemMesmaAplicacao()) {
            return;
        }

        http_response_code(419);
        die('Sessao expirada ou token CSRF invalido.');
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::requireCsrf();
        }

        self::enforceAccountRequirements();
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
    }

    private static function tokenCsrfValido(string $providedToken): bool
    {
        $storedToken = $_SESSION['csrf_token'] ?? '';

        return $providedToken !== ''
            && is_string($storedToken)
            && hash_equals($storedToken, $providedToken);
    }

    private static function origemMesmaAplicacao(): bool
    {
        $expectedOrigins = [];

        $appOrigin = RequestContext::appOrigin();
        if ($appOrigin !== null) {
            $expectedOrigins[] = $appOrigin;
        }

        $currentOrigin = RequestContext::currentOrigin();
        if ($currentOrigin !== null) {
            $expectedOrigins[] = $currentOrigin;
        }

        if ($expectedOrigins === []) {
            return false;
        }

        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
            $value = trim((string) ($_SERVER[$header] ?? ''));
            if ($value === '') {
                continue;
            }

            $origin = RequestContext::parseUrlOrigin($value);
            if ($origin === null) {
                return false;
            }

            foreach ($expectedOrigins as $expectedOrigin) {
                if (RequestContext::sameOrigin($origin, $expectedOrigin)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private static function enforceAccountRequirements(): void
    {
        $paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
        $precisaTrocarSenha = self::precisaTrocarSenha();
        $precisaAceitarPrivacidade = self::precisaAceitarPrivacidade();

        if ($precisaAceitarPrivacidade && !in_array($paginaAtual, ['privacidade_consentimento.php', 'logout.php'], true)) {
            header('Location: /privacidade_consentimento.php');
            exit;
        }

        if ($precisaTrocarSenha && !in_array($paginaAtual, ['meu_perfil.php', 'privacidade_consentimento.php', 'logout.php'], true)) {
            header('Location: /meu_perfil.php?forcar_troca=1');
            exit;
        }
    }

    private static function usuarioParaSessao(array $pessoa): array
    {
        return [
            'id' => (int) $pessoa['id'],
            'nome' => $pessoa['nome'],
            'cpf' => $pessoa['cpf'],
            'cargo' => $pessoa['cargo'],
            'ativo' => (int) $pessoa['ativo'],
            'precisa_trocar_senha' => (int) ($pessoa['precisa_trocar_senha'] ?? 0),
            'privacidade_aceita_em' => $pessoa['privacidade_aceita_em'] ?? null,
            'termos_versao_aceita' => $pessoa['termos_versao_aceita'] ?? null,
            'politica_versao_aceita' => $pessoa['politica_versao_aceita'] ?? null,
        ];
    }
}
