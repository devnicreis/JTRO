<?php

require_once __DIR__ . '/../Core/Database.php';

class CartaRepository
{
    private PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function criar(
        string $dataCarta,
        ?string $conteudo,
        ?string $pregacaoTitulo,
        ?string $pregacaoLink,
        ?string $avisos,
        ?string $imagemUrl,
        int $criadoPor
    ): int {
        $stmt = $this->connection->prepare("
            INSERT INTO cartas_semanais
                (data_carta, conteudo, pregacao_titulo, pregacao_link, avisos, imagem_url, publicada, criado_por, created_at)
            VALUES
                (:data_carta, :conteudo, :pregacao_titulo, :pregacao_link, :avisos, :imagem_url, 0, :criado_por, :created_at)
        ");
        $stmt->execute([
            ':data_carta'      => $dataCarta,
            ':conteudo'        => $conteudo ?: null,
            ':pregacao_titulo' => $pregacaoTitulo ?: null,
            ':pregacao_link'   => $pregacaoLink ?: null,
            ':avisos'          => $avisos ?: null,
            ':imagem_url'      => $imagemUrl ?: null,
            ':criado_por'      => $criadoPor,
            ':created_at'      => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function atualizar(
        int $id,
        string $dataCarta,
        ?string $conteudo,
        ?string $pregacaoTitulo,
        ?string $pregacaoLink,
        ?string $avisos,
        ?string $imagemUrl
    ): void {
        $stmt = $this->connection->prepare("
            UPDATE cartas_semanais
            SET data_carta      = :data_carta,
                conteudo        = :conteudo,
                pregacao_titulo = :pregacao_titulo,
                pregacao_link   = :pregacao_link,
                avisos          = :avisos,
                imagem_url      = :imagem_url,
                updated_at      = :updated_at
            WHERE id = :id
        ");
        $stmt->execute([
            ':data_carta'      => $dataCarta,
            ':conteudo'        => $conteudo ?: null,
            ':pregacao_titulo' => $pregacaoTitulo ?: null,
            ':pregacao_link'   => $pregacaoLink ?: null,
            ':avisos'          => $avisos ?: null,
            ':imagem_url'      => $imagemUrl ?: null,
            ':updated_at'      => date('Y-m-d H:i:s'),
            ':id'              => $id,
        ]);
    }

    public function publicar(int $id): void
    {
        $stmt = $this->connection->prepare("
            UPDATE cartas_semanais SET publicada = 1, updated_at = :updated_at WHERE id = :id
        ");
        $stmt->execute([':updated_at' => date('Y-m-d H:i:s'), ':id' => $id]);
    }

    public function despublicar(int $id): void
    {
        $stmt = $this->connection->prepare("
            UPDATE cartas_semanais SET publicada = 0, updated_at = :updated_at WHERE id = :id
        ");
        $stmt->execute([':updated_at' => date('Y-m-d H:i:s'), ':id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->connection->prepare("DELETE FROM cartas_semanais WHERE id = :id")
            ->execute([':id' => $id]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT c.*, p.nome AS autor_nome
            FROM cartas_semanais c
            LEFT JOIN pessoas p ON p.id = c.criado_por
            WHERE c.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarTodas(): array
    {
        return $this->connection->query("
            SELECT c.id, c.data_carta, c.pregacao_titulo, c.publicada, c.created_at, c.updated_at,
                   p.nome AS autor_nome
            FROM cartas_semanais c
            LEFT JOIN pessoas p ON p.id = c.criado_por
            ORDER BY c.data_carta DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPublicadas(): array
    {
        return $this->connection->query("
            SELECT c.id, c.data_carta, c.pregacao_titulo, c.publicada, c.created_at,
                   p.nome AS autor_nome
            FROM cartas_semanais c
            LEFT JOIN pessoas p ON p.id = c.criado_por
            WHERE c.publicada = 1
            ORDER BY c.data_carta DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarMaisRecente(): ?array
    {
        $stmt = $this->connection->query("
            SELECT * FROM cartas_semanais
            WHERE publicada = 1
            ORDER BY data_carta DESC LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function contarNaoLidasPorUsuario(int $usuarioId, array $chavesLidas): int
    {
        $publicadas = $this->listarPublicadas();
        $count = 0;
        foreach ($publicadas as $carta) {
            $chave = 'carta_nova_' . $carta['id'];
            if (!isset($chavesLidas[$chave])) $count++;
        }
        return $count;
    }
}