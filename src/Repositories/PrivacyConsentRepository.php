<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/PrivacySettings.php';

class PrivacyConsentRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function registrarAceite(int $pessoaId, ?string $ipAddress, ?string $userAgent): void
    {
        $aceitoEm = date('Y-m-d H:i:s');
        $termosVersao = PrivacySettings::termosVersao();
        $politicaVersao = PrivacySettings::politicaVersao();

        $this->connection->beginTransaction();

        try {
            $update = $this->connection->prepare("
                UPDATE pessoas
                SET privacidade_aceita_em = :aceito_em,
                    termos_versao_aceita = :termos_versao,
                    politica_versao_aceita = :politica_versao
                WHERE id = :pessoa_id
            ");

            $update->execute([
                ':aceito_em' => $aceitoEm,
                ':termos_versao' => $termosVersao,
                ':politica_versao' => $politicaVersao,
                ':pessoa_id' => $pessoaId,
            ]);

            $insert = $this->connection->prepare("
                INSERT INTO privacidade_consentimentos (
                    pessoa_id,
                    termos_versao,
                    politica_versao,
                    aceito_em,
                    ip_address,
                    user_agent
                ) VALUES (
                    :pessoa_id,
                    :termos_versao,
                    :politica_versao,
                    :aceito_em,
                    :ip_address,
                    :user_agent
                )
            ");

            $insert->execute([
                ':pessoa_id' => $pessoaId,
                ':termos_versao' => $termosVersao,
                ':politica_versao' => $politicaVersao,
                ':aceito_em' => $aceitoEm,
                ':ip_address' => $this->normalizarTextoOpcional($ipAddress),
                ':user_agent' => $this->normalizarTextoOpcional($userAgent),
            ]);

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    private function normalizarTextoOpcional(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }
}
