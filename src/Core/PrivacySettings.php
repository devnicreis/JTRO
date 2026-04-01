<?php

require_once __DIR__ . '/AppConfig.php';

class PrivacySettings
{
    private const DEFAULT_TERMOS_VERSAO = '2026-03-31';
    private const DEFAULT_POLITICA_VERSAO = '2026-03-31';

    public static function termosVersao(): string
    {
        return self::valorConfiguracao('privacy_terms_version', self::DEFAULT_TERMOS_VERSAO);
    }

    public static function politicaVersao(): string
    {
        return self::valorConfiguracao('privacy_policy_version', self::DEFAULT_POLITICA_VERSAO);
    }

    public static function supportContact(): string
    {
        return self::valorConfiguracao(
            'privacy_support_contact',
            'a administracao responsavel pelo seu cadastro ou o suporte do JTRO'
        );
    }

    public static function consentimentoAtual(array $pessoa): bool
    {
        return !empty($pessoa['privacidade_aceita_em'])
            && ($pessoa['termos_versao_aceita'] ?? '') === self::termosVersao()
            && ($pessoa['politica_versao_aceita'] ?? '') === self::politicaVersao();
    }

    private static function valorConfiguracao(string $key, string $default): string
    {
        return AppConfig::getString($key, $default);
    }
}
