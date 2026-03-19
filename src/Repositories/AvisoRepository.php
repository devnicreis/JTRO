<?php

require_once __DIR__ . '/../Core/Database.php';

class AvisoRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function marcarComoLido(int $usuarioId, string $chaveAviso): void
    {
        $stmt = $this->connection->prepare("
            INSERT INTO avisos_lidos (usuario_id, chave_aviso, lido_em)
            VALUES (:usuario_id, :chave_aviso, :lido_em)
            ON CONFLICT(usuario_id, chave_aviso)
            DO UPDATE SET lido_em = excluded.lido_em
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':chave_aviso' => $chaveAviso,
            ':lido_em' => date('Y-m-d H:i:s')
        ]);
    }

    public function desmarcarComoLido(int $usuarioId, string $chaveAviso): void
    {
        $stmt = $this->connection->prepare("
            DELETE FROM avisos_lidos
            WHERE usuario_id = :usuario_id
            AND chave_aviso = :chave_aviso
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':chave_aviso' => $chaveAviso
        ]);
    }

    public function listarChavesLidas(int $usuarioId): array
    {
        $stmt = $this->connection->prepare("
            SELECT chave_aviso
            FROM avisos_lidos
            WHERE usuario_id = :usuario_id
        ");

        $stmt->execute([':usuario_id' => $usuarioId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
