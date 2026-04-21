<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Presenca.php';
require_once __DIR__ . '/AvisoRepository.php';

class PresencaRepository
{
    private PDO $connection;
    private AvisoRepository $avisoRepository;

    public function __construct()
    {
        $this->connection = Database::getConnection();
        $this->avisoRepository = new AvisoRepository();
    }

    private function comprimentoTexto(string $texto): int
    {
        return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
    }

    private function aulasIntegracaoMap(): array
    {
        return [
            'A01' => 'Grupos Familiares',
            'A02' => 'As Quatro Ênfases da Abba',
            'A03' => 'Batismo nas Águas',
            'A04' => 'Cinco Ministérios',
            'A05' => 'Dízimos',
            'A06' => 'Departamentos da Abba',
            'A07' => 'Relacionando-me com o Pai',
            'A08' => 'Perdão',
            'A09' => 'Panorama Geral da Obra Divina',
            'A10' => 'Autoridade Espiritual',
            'A11' => 'Mais que Vencedores',
            'A12' => 'Aliança de Sangue',
            'A13' => 'Mente Renovada',
            'A14' => 'Espírito Santo',
        ];
    }

    private function validarAulaIntegracao(?string $codigo): ?string
    {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return null;
        }

        if (!array_key_exists($codigo, $this->aulasIntegracaoMap())) {
            throw new InvalidArgumentException('Selecione uma aula de integração válida.');
        }

        return $codigo;
    }

    private function buscarGrupoPorId(int $grupoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT id, nome, dia_semana, horario, perfil_grupo, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function obterDiaSemanaEmPortugues(string $data): string
    {
        $dias = [
            'Sunday' => 'domingo',
            'Monday' => 'segunda-feira',
            'Tuesday' => 'terça-feira',
            'Wednesday' => 'quarta-feira',
            'Thursday' => 'quinta-feira',
            'Friday' => 'sexta-feira',
            'Saturday' => 'sábado',
        ];

        $dateTime = new DateTime($data);
        return $dias[$dateTime->format('l')] ?? '';
    }

    private function validarPayloadPresenca(array $presenca): array
    {
        $status = $presenca['status'] ?? '';
        if (!in_array($status, [Presenca::STATUS_PRESENTE, Presenca::STATUS_AUSENTE], true)) {
            throw new InvalidArgumentException('Marque a presença ou ausência de todos os membros antes de salvar.');
        }

        $resultado = [
            'status' => $status,
            'presente_tempo' => null,
            'justificativa_atraso' => null,
            'ausencia_tipo' => null,
            'justificativa_ausencia' => null,
        ];

        if ($status === Presenca::STATUS_PRESENTE) {
            $presenteTempo = (string) ($presenca['presente_tempo'] ?? '');
            if ($presenteTempo === '') {
                $presenteTempo = 'no_horario';
            }
            if (!in_array($presenteTempo, ['no_horario', 'atrasado'], true)) {
                throw new InvalidArgumentException('Informe se a presença foi no horário ou atrasada.');
            }

            $justificativaAtraso = trim((string) ($presenca['justificativa_atraso'] ?? ''));
            if ($presenteTempo === 'atrasado' && $this->comprimentoTexto($justificativaAtraso) > 50) {
                throw new InvalidArgumentException('A justificativa do atraso deve ter no máximo 50 caracteres.');
            }

            $resultado['presente_tempo'] = $presenteTempo;
            $resultado['justificativa_atraso'] = $presenteTempo === 'atrasado' && $justificativaAtraso !== '' ? $justificativaAtraso : null;
        }

        if ($status === Presenca::STATUS_AUSENTE) {
            $ausenciaTipo = $presenca['ausencia_tipo'] ?? '';
            if (!in_array($ausenciaTipo, ['justificada', 'injustificada'], true)) {
                throw new InvalidArgumentException('Informe se a ausência foi justificada ou injustificada.');
            }

            $justificativaAusencia = trim((string) ($presenca['justificativa_ausencia'] ?? ''));
            if ($ausenciaTipo === 'justificada' && $justificativaAusencia === '') {
                throw new InvalidArgumentException('Informe a justificativa da ausência justificada.');
            }
            if ($this->comprimentoTexto($justificativaAusencia) > 50) {
                throw new InvalidArgumentException('A justificativa da ausência deve ter no máximo 50 caracteres.');
            }

            $resultado['ausencia_tipo'] = $ausenciaTipo;
            $resultado['justificativa_ausencia'] = $ausenciaTipo === 'justificada' ? $justificativaAusencia : null;
        }

        return $resultado;
    }

    private function normalizarModoPedidosOracao(?string $modo): string
    {
        return $modo === 'casal_compartilhado' ? 'casal_compartilhado' : 'individual';
    }

    private function pessoaEstaEmUniaoConjugal(array $pessoa): bool
    {
        $estadoCivil = (string) ($pessoa['estado_civil'] ?? '');
        return in_array($estadoCivil, ['casado', 'uniao_estavel'], true);
    }

    private function resolverConjugePresenteId(array $pessoa, array $presentesMap): ?int
    {
        $conjugePessoaId = (int) ($pessoa['conjuge_pessoa_id'] ?? 0);
        if ($conjugePessoaId > 0 && isset($presentesMap[$conjugePessoaId])) {
            return $conjugePessoaId;
        }

        $conjugeCpf = trim((string) ($pessoa['conjuge_cpf'] ?? ''));
        if ($conjugeCpf === '') {
            return null;
        }

        foreach ($presentesMap as $id => $presente) {
            if ($id === (int) ($pessoa['pessoa_id'] ?? 0)) {
                continue;
            }

            if (trim((string) ($presente['cpf'] ?? '')) === $conjugeCpf) {
                return (int) $id;
            }
        }

        return null;
    }

    private function podemCompartilharPedidoOracao(array $pessoaA, array $pessoaB): bool
    {
        if (!$this->pessoaEstaEmUniaoConjugal($pessoaA) || !$this->pessoaEstaEmUniaoConjugal($pessoaB)) {
            return false;
        }

        $idA = (int) ($pessoaA['pessoa_id'] ?? 0);
        $idB = (int) ($pessoaB['pessoa_id'] ?? 0);
        if ($idA <= 0 || $idB <= 0 || $idA === $idB) {
            return false;
        }

        $cpfA = trim((string) ($pessoaA['cpf'] ?? ''));
        $cpfB = trim((string) ($pessoaB['cpf'] ?? ''));
        $conjugeIdA = (int) ($pessoaA['conjuge_pessoa_id'] ?? 0);
        $conjugeIdB = (int) ($pessoaB['conjuge_pessoa_id'] ?? 0);
        $conjugeCpfA = trim((string) ($pessoaA['conjuge_cpf'] ?? ''));
        $conjugeCpfB = trim((string) ($pessoaB['conjuge_cpf'] ?? ''));

        return $conjugeIdA === $idB
            || $conjugeIdB === $idA
            || ($conjugeCpfA !== '' && $cpfB !== '' && $conjugeCpfA === $cpfB)
            || ($conjugeCpfB !== '' && $cpfA !== '' && $conjugeCpfB === $cpfA);
    }

    private function montarCamposPedidosOracao(array $presentes, array $pedidosMap, bool $modoCompartilhado): array
    {
        $campos = [];
        $presentesMap = [];

        foreach ($presentes as $presente) {
            $presentesMap[(int) $presente['pessoa_id']] = $presente;
        }

        $processados = [];
        foreach ($presentes as $presente) {
            $pessoaId = (int) $presente['pessoa_id'];
            if ($pessoaId <= 0 || isset($processados[$pessoaId])) {
                continue;
            }

            $idsCampo = [$pessoaId];
            $rotulo = (string) $presente['nome'];

            if ($modoCompartilhado && $this->pessoaEstaEmUniaoConjugal($presente)) {
                $conjugeId = $this->resolverConjugePresenteId($presente, $presentesMap);
                if ($conjugeId !== null && isset($presentesMap[$conjugeId])) {
                    $conjuge = $presentesMap[$conjugeId];
                    if (!isset($processados[$conjugeId]) && $this->podemCompartilharPedidoOracao($presente, $conjuge)) {
                        $idsCampo = [$pessoaId, $conjugeId];
                        sort($idsCampo, SORT_NUMERIC);
                        $rotulo = $presentesMap[$idsCampo[0]]['nome'] . ' e ' . $presentesMap[$idsCampo[1]]['nome'];
                    }
                }
            }

            foreach ($idsCampo as $idCampo) {
                $processados[$idCampo] = true;
            }

            $campoId = count($idsCampo) === 2
                ? 'casal_' . $idsCampo[0] . '_' . $idsCampo[1]
                : 'pessoa_' . $idsCampo[0];

            $pedidoAtual = '';
            foreach ($idsCampo as $idCampo) {
                $valorAtual = trim((string) ($pedidosMap[$idCampo] ?? ''));
                if ($valorAtual !== '') {
                    $pedidoAtual = $valorAtual;
                    break;
                }
            }

            $campos[] = [
                'campo_id' => $campoId,
                'pessoa_ids' => $idsCampo,
                'rotulo' => $rotulo,
                'pedido' => $pedidoAtual,
                'compartilhado_casal' => count($idsCampo) === 2,
            ];
        }

        return $campos;
    }

    private function calcularMotivoAlteracao(array $grupo, string $data, string $horario, string $local): ?string
    {
        $motivos = [];
        $diaSemanaInformado = $this->obterDiaSemanaEmPortugues($data);

        if ($diaSemanaInformado !== '' && $diaSemanaInformado !== $grupo['dia_semana']) {
            $motivos[] = 'Reunião realizada fora do dia padrão do GF.';
        }
        if ($horario !== $grupo['horario']) {
            $motivos[] = 'Reunião realizada fora do horário padrão do GF.';
        }
        if (!empty($grupo['local_padrao']) && trim($local) !== trim($grupo['local_padrao'])) {
            $motivos[] = 'Reunião realizada em local fora do padrão do GF.';
        }

        return count($motivos) > 0 ? implode(' ', $motivos) : null;
    }

    private function buscarPessoasVinculadasNaIntegracaoDaReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT pessoa_id
            FROM pessoa_integracao_aulas
            WHERE reuniao_id = :reuniao_id
              AND origem = 'reuniao'
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function listarPresentesDaReuniaoPorId(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT pessoa_id
            FROM presencas
            WHERE reuniao_id = :reuniao_id
              AND status = :status
        ");
        $stmt->execute([
            ':reuniao_id' => $reuniaoId,
            ':status' => Presenca::STATUS_PRESENTE,
        ]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function notificarConclusaoIntegracao(int $pessoaId, string $nomePessoa, int $grupoId): void
    {
        $destinatarios = [];

        $stmtAdmins = $this->connection->query("
            SELECT id
            FROM pessoas
            WHERE ativo = 1
              AND cargo = 'admin'
        ");
        $destinatarios = array_merge($destinatarios, array_map('intval', $stmtAdmins->fetchAll(PDO::FETCH_COLUMN)));

        if ($grupoId > 0) {
            $stmtLideres = $this->connection->prepare("
                SELECT DISTINCT gl.pessoa_id
                FROM grupo_lideres gl
                INNER JOIN pessoas p ON p.id = gl.pessoa_id
                WHERE gl.grupo_familiar_id = :grupo_id
                  AND p.ativo = 1
            ");
            $stmtLideres->execute([':grupo_id' => $grupoId]);
            $destinatarios = array_merge($destinatarios, array_map('intval', $stmtLideres->fetchAll(PDO::FETCH_COLUMN)));
        }

        foreach (array_values(array_unique($destinatarios)) as $usuarioId) {
            $this->avisoRepository->criarAvisoSistema(
                $usuarioId,
                'integracao_concluida_pessoa_' . $pessoaId . '_usuario_' . $usuarioId,
                'integracao_concluida',
                'Integração concluída',
                $nomePessoa . ' concluiu a integração.',
                '/pessoas_integracao.php?id=' . $pessoaId
            );
        }
    }

    private function atualizarStatusConclusaoIntegracaoPessoa(int $pessoaId): void
    {
        $stmtPessoa = $this->connection->prepare("
            SELECT id, nome, grupo_familiar_id, concluiu_integracao, integracao_conclusao_manual
            FROM pessoas
            WHERE id = :id
            LIMIT 1
        ");
        $stmtPessoa->execute([':id' => $pessoaId]);
        $pessoa = $stmtPessoa->fetch(PDO::FETCH_ASSOC);

        if (!$pessoa) {
            return;
        }

        $stmtTotal = $this->connection->prepare("
            SELECT COUNT(DISTINCT aula_codigo)
            FROM pessoa_integracao_aulas
            WHERE pessoa_id = :pessoa_id
              AND concluida = 1
        ");
        $stmtTotal->execute([':pessoa_id' => $pessoaId]);

        $totalConcluido = (int) $stmtTotal->fetchColumn();
        $totalAulas = count($this->aulasIntegracaoMap());
        $manual = (int) ($pessoa['integracao_conclusao_manual'] ?? 0) === 1;
        $concluiuAtual = (int) ($pessoa['concluiu_integracao'] ?? 0) === 1;

        if ($manual) {
            return;
        }

        if ($totalConcluido >= $totalAulas && !$concluiuAtual) {
            $stmtUpdate = $this->connection->prepare("
                UPDATE pessoas
                SET concluiu_integracao = 1,
                    integracao_conclusao_manual = 0
                WHERE id = :id
            ");
            $stmtUpdate->execute([':id' => $pessoaId]);
            $this->notificarConclusaoIntegracao($pessoaId, $pessoa['nome'], (int) ($pessoa['grupo_familiar_id'] ?? 0));
            return;
        }

        if ($totalConcluido < $totalAulas && $concluiuAtual) {
            $stmtUpdate = $this->connection->prepare("
                UPDATE pessoas
                SET concluiu_integracao = 0,
                    integracao_conclusao_manual = 0
                WHERE id = :id
            ");
            $stmtUpdate->execute([':id' => $pessoaId]);
        }
    }

    private function atualizarParticipacaoRetiroPessoa(int $pessoaId): void
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM retiros_integracao
            WHERE pessoa_id = :pessoa_id
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);

        $participou = (int) $stmt->fetchColumn() > 0 ? 1 : 0;

        $stmtUpdate = $this->connection->prepare("
            UPDATE pessoas
            SET participou_retiro_integracao = :participou
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':participou' => $participou,
            ':id' => $pessoaId,
        ]);
    }

    private function sincronizarIntegracaoDaReuniao(int $reuniaoId, int $grupoId, ?string $aulaCodigo, string $data): void
    {
        $grupo = $this->buscarGrupoPorId($grupoId);
        if (!$grupo || ($grupo['perfil_grupo'] ?? '') !== 'integracao') {
            return;
        }

        $afetados = $this->buscarPessoasVinculadasNaIntegracaoDaReuniao($reuniaoId);

        $stmtDelete = $this->connection->prepare("
            DELETE FROM pessoa_integracao_aulas
            WHERE reuniao_id = :reuniao_id
              AND origem = 'reuniao'
        ");
        $stmtDelete->execute([':reuniao_id' => $reuniaoId]);

        if ($aulaCodigo !== null) {
            $presentes = $this->listarPresentesDaReuniaoPorId($reuniaoId);
            $afetados = array_values(array_unique(array_merge($afetados, $presentes)));
            $aulaTitulo = $this->aulasIntegracaoMap()[$aulaCodigo];

            $stmtInsert = $this->connection->prepare("
                INSERT INTO pessoa_integracao_aulas (
                    pessoa_id, aula_codigo, aula_titulo, data_aula, origem, reuniao_id, retiro_id, concluida
                ) VALUES (
                    :pessoa_id, :aula_codigo, :aula_titulo, :data_aula, 'reuniao', :reuniao_id, NULL, 1
                )
                ON CONFLICT(pessoa_id, aula_codigo)
                DO UPDATE SET
                    aula_titulo = excluded.aula_titulo,
                    data_aula = excluded.data_aula,
                    origem = excluded.origem,
                    reuniao_id = excluded.reuniao_id,
                    retiro_id = excluded.retiro_id,
                    concluida = 1
            ");

            foreach ($presentes as $pessoaId) {
                $stmtInsert->execute([
                    ':pessoa_id' => $pessoaId,
                    ':aula_codigo' => $aulaCodigo,
                    ':aula_titulo' => $aulaTitulo,
                    ':data_aula' => $data,
                    ':reuniao_id' => $reuniaoId,
                ]);
            }
        }

        foreach (array_values(array_unique($afetados)) as $pessoaId) {
            $this->atualizarStatusConclusaoIntegracaoPessoa((int) $pessoaId);
        }
    }

    public function listarGruposFamiliares(): array
    {
        return $this->connection->query("
            SELECT id, nome, dia_semana, horario, perfil_grupo, local_padrao, local_fixo
            FROM grupos_familiares
            WHERE ativo = 1
            ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarGruposFamiliaresPorLider(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
            SELECT gf.id, gf.nome, gf.dia_semana, gf.horario, gf.perfil_grupo, gf.local_padrao, gf.local_fixo
            FROM grupos_familiares gf
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1
              AND gl.pessoa_id = :pessoa_id
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarReuniaoPorGrupoEData(int $grupoId, string $data): ?int
    {
        $dateTime = DateTime::createFromFormat('!Y-m-d', $data);
        if ($dateTime === false || $dateTime->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data da reunião inválida.');
        }

        $stmt = $this->connection->prepare("
            SELECT id
            FROM reunioes
            WHERE grupo_familiar_id = :grupo_id
              AND data = :data
            LIMIT 1
        ");
        $stmt->execute([':grupo_id' => $grupoId, ':data' => $data]);
        $reuniaoId = $stmt->fetchColumn();

        return $reuniaoId ? (int) $reuniaoId : null;
    }

    public function buscarReuniao(int $reuniaoId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT
                r.id,
                r.grupo_familiar_id,
                r.data,
                r.horario,
                r.local,
                r.aula_integracao_codigo,
                r.motivo_alteracao,
                r.observacoes,
                r.pedidos_oracao_modo,
                r.finalizada,
                gf.nome AS grupo_nome,
                gf.perfil_grupo,
                gf.local_fixo,
                gf.local_padrao
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $reuniaoId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarPresencasPorReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                p.id,
                p.reuniao_id,
                p.pessoa_id,
                p.status,
                p.presente_tempo,
                p.justificativa_atraso,
                p.ausencia_tipo,
                p.justificativa_ausencia,
                pe.nome,
                pe.cpf,
                pe.cargo
            FROM presencas p
            INNER JOIN pessoas pe ON pe.id = p.pessoa_id
            WHERE p.reuniao_id = :reuniao_id
            ORDER BY pe.nome ASC
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarReuniao(int $reuniaoId, string $local, string $observacoes, ?string $aulaIntegracaoCodigo = null): void
    {
        if ($this->comprimentoTexto($observacoes) > 500) {
            throw new InvalidArgumentException('O campo observações deve ter no máximo 500 caracteres.');
        }

        $reuniao = $this->buscarReuniao($reuniaoId);
        if (!$reuniao) {
            throw new InvalidArgumentException('Reunião não encontrada.');
        }

        if (trim($local) === '') {
            throw new InvalidArgumentException('Informe o local da reunião.');
        }

        if (($reuniao['perfil_grupo'] ?? '') === 'integracao') {
            $aulaIntegracaoCodigo = $this->validarAulaIntegracao($aulaIntegracaoCodigo);
            if ($aulaIntegracaoCodigo === null) {
                throw new InvalidArgumentException('Selecione a aula da integração para este GF.');
            }
        } else {
            $aulaIntegracaoCodigo = null;
        }

        $motivos = [];
        $motivoAtual = trim((string) ($reuniao['motivo_alteracao'] ?? ''));
        $localPadrao = trim((string) ($reuniao['local_padrao'] ?? ''));

        if ($motivoAtual !== '' && stripos($motivoAtual, 'local fora do padrão') === false) {
            $motivos[] = $motivoAtual;
        }
        if ($localPadrao !== '' && trim($local) !== $localPadrao) {
            $motivos[] = 'Reunião realizada em local fora do padrão do GF.';
        }
        $motivoAlteracao = count($motivos) > 0 ? implode(' ', $motivos) : null;

        $stmt = $this->connection->prepare("
            UPDATE reunioes
            SET local = :local,
                observacoes = :observacoes,
                aula_integracao_codigo = :aula_integracao_codigo,
                motivo_alteracao = :motivo_alteracao
            WHERE id = :id
        ");
        $stmt->bindValue(':local', $local);
        $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null, $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':aula_integracao_codigo', $aulaIntegracaoCodigo, $aulaIntegracaoCodigo !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':motivo_alteracao', $motivoAlteracao, $motivoAlteracao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $reuniaoId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function atualizarStatusDetalhado(int $presencaId, array $presenca): void
    {
        $dados = $this->validarPayloadPresenca($presenca);

        $stmt = $this->connection->prepare("
            UPDATE presencas
            SET status = :status,
                presente_tempo = :presente_tempo,
                justificativa_atraso = :justificativa_atraso,
                ausencia_tipo = :ausencia_tipo,
                justificativa_ausencia = :justificativa_ausencia
            WHERE id = :id
        ");
        $stmt->bindValue(':status', $dados['status']);
        $stmt->bindValue(':presente_tempo', $dados['presente_tempo'], $dados['presente_tempo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':justificativa_atraso', $dados['justificativa_atraso'], $dados['justificativa_atraso'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':ausencia_tipo', $dados['ausencia_tipo'], $dados['ausencia_tipo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':justificativa_ausencia', $dados['justificativa_ausencia'], $dados['justificativa_ausencia'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $presencaId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function atualizarPresencasEReuniao(int $reuniaoId, string $local, string $observacoes, array $presencas, ?string $aulaIntegracaoCodigo = null): void
    {
        $this->connection->beginTransaction();

        try {
            $reuniao = $this->buscarReuniao($reuniaoId);
            if (!$reuniao) {
                throw new InvalidArgumentException('Reunião não encontrada.');
            }

            $this->atualizarReuniao($reuniaoId, $local, $observacoes, $aulaIntegracaoCodigo);

            foreach ($presencas as $presencaId => $dadosPresenca) {
                $this->atualizarStatusDetalhado((int) $presencaId, $dadosPresenca);
            }

            $this->connection->prepare("
                UPDATE reunioes
                SET finalizada = 1
                WHERE id = :id
            ")->execute([':id' => $reuniaoId]);

            $this->sincronizarIntegracaoDaReuniao(
                $reuniaoId,
                (int) $reuniao['grupo_familiar_id'],
                $this->validarAulaIntegracao($aulaIntegracaoCodigo),
                $reuniao['data']
            );

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function liderPodeAcessarGrupo(int $pessoaId, int $grupoId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM grupo_lideres gl
            INNER JOIN grupos_familiares gf ON gf.id = gl.grupo_familiar_id
            WHERE gl.pessoa_id = :pessoa_id
              AND gl.grupo_familiar_id = :grupo_id
              AND gf.ativo = 1
        ");
        $stmt->execute([':pessoa_id' => $pessoaId, ':grupo_id' => $grupoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function buscarResumoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id,
                gf.nome,
                gf.dia_semana,
                gf.horario,
                gf.perfil_grupo,
                gf.local_padrao,
                gf.local_fixo,
                (
                    SELECT COUNT(*)
                    FROM grupo_membros gm
                    INNER JOIN pessoas p ON p.id = gm.pessoa_id
                    WHERE gm.grupo_familiar_id = gf.id
                      AND p.ativo = 1
                ) AS total_membros_ativos,
                (
                    SELECT COUNT(*)
                    FROM reunioes r
                    WHERE r.grupo_familiar_id = gf.id
                      AND r.finalizada = 1
                ) AS total_reunioes,
                (
                    SELECT MAX(r.data)
                    FROM reunioes r
                    WHERE r.grupo_familiar_id = gf.id
                      AND r.finalizada = 1
                ) AS ultima_data_reuniao
            FROM grupos_familiares gf
            WHERE gf.id = :grupo_id
            LIMIT 1
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarUltimasReunioesDoGrupo(int $grupoId, int $limite = 5): array
    {
        $limite = max(1, $limite);
        $stmt = $this->connection->prepare("
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.observacoes,
                r.aula_integracao_codigo,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            WHERE r.grupo_familiar_id = :grupo_id
              AND r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarFilhosDoGrupo(int $grupoId, int $idadeMaxima = 9): array
    {
        $idadeMaxima = max(0, $idadeMaxima);

        $stmt = $this->connection->prepare("
            WITH integrantes AS (
                SELECT gm.pessoa_id
                FROM grupo_membros gm
                WHERE gm.grupo_familiar_id = :grupo_id
                UNION
                SELECT gl.pessoa_id
                FROM grupo_lideres gl
                WHERE gl.grupo_familiar_id = :grupo_id
            )
            SELECT DISTINCT
                filho.id,
                filho.nome,
                filho.data_nascimento
            FROM pessoas filho
            WHERE filho.ativo = 1
              AND filho.data_nascimento IS NOT NULL
              AND CAST((julianday('now', 'localtime') - julianday(filho.data_nascimento)) / 365.2425 AS INTEGER)
                    BETWEEN 0 AND :idade_maxima
              AND (
                    filho.responsavel_1_pessoa_id IN (SELECT pessoa_id FROM integrantes)
                 OR filho.responsavel_2_pessoa_id IN (SELECT pessoa_id FROM integrantes)
                 OR EXISTS (
                        SELECT 1
                        FROM integrantes i
                        INNER JOIN pessoas responsavel ON responsavel.id = i.pessoa_id
                        WHERE responsavel.cpf = filho.responsavel_1_cpf
                    )
                 OR EXISTS (
                        SELECT 1
                        FROM integrantes i
                        INNER JOIN pessoas responsavel ON responsavel.id = i.pessoa_id
                        WHERE responsavel.cpf = filho.responsavel_2_cpf
                    )
              )
            ORDER BY filho.nome ASC
        ");
        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':idade_maxima' => $idadeMaxima,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarReunioes(): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM reunioes")->fetchColumn();
    }

    public function listarUltimasReunioesGerais(int $limite = 5): array
    {
        $limite = max(1, (int) $limite);
        return $this->connection->query("
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.aula_integracao_codigo,
                r.motivo_alteracao,
                gf.nome AS grupo_nome,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            WHERE r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarUltimasReunioesDoLider(int $pessoaId, int $limite = 5): array
    {
        $limite = max(1, (int) $limite);
        $stmt = $this->connection->prepare("
            SELECT
                r.id,
                r.data,
                r.horario,
                r.local,
                r.aula_integracao_codigo,
                r.motivo_alteracao,
                gf.nome AS grupo_nome,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'presente'
                ) AS total_presentes,
                (
                    SELECT COUNT(*)
                    FROM presencas p
                    WHERE p.reuniao_id = r.id
                      AND p.status = 'ausente'
                ) AS total_ausentes
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gl.pessoa_id = :pessoa_id
              AND r.finalizada = 1
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarResumoPresencaPorGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
                SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
                COUNT(*) AS total_registros
            FROM presencas p
            INNER JOIN reunioes r ON r.id = p.reuniao_id
            WHERE r.grupo_familiar_id = :grupo_id
              AND r.finalizada = 1
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $presencas = (int) ($resultado['total_presencas'] ?? 0);
        $ausencias = (int) ($resultado['total_ausencias'] ?? 0);
        $total = (int) ($resultado['total_registros'] ?? 0);

        return [
            'total_presencas' => $presencas,
            'total_ausencias' => $ausencias,
            'percentual_presencas' => $total > 0 ? round(($presencas / $total) * 100, 1) : 0,
            'percentual_ausencias' => $total > 0 ? round(($ausencias / $total) * 100, 1) : 0,
        ];
    }

    public function buscarResumoPorMembroDoGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                pe.id AS pessoa_id,
                pe.nome,
                pe.lider_grupo_familiar,
                pe.lider_departamento,
                SUM(CASE WHEN p.status = 'presente' THEN 1 ELSE 0 END) AS total_presencas,
                SUM(CASE WHEN p.status = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
                MAX(CASE WHEN p.status = 'presente' THEN r.data ELSE NULL END) AS ultima_presenca
            FROM grupo_membros gm
            INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
            LEFT JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
            LEFT JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
            WHERE gm.grupo_familiar_id = :grupo_id
              AND pe.ativo = 1
            GROUP BY pe.id, pe.nome, pe.lider_grupo_familiar, pe.lider_departamento
            ORDER BY pe.nome ASC
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhas as &$linha) {
            $presencas = (int) ($linha['total_presencas'] ?? 0);
            $ausencias = (int) ($linha['total_ausencias'] ?? 0);
            $total = $presencas + $ausencias;
            $linha['percentual_presenca'] = $total > 0 ? round(($presencas / $total) * 100, 1) : 0;
            $liderancas = [];
            if ((int) ($linha['lider_departamento'] ?? 0) === 1) {
                $liderancas[] = 'Dpto.';
            }
            if ((int) ($linha['lider_grupo_familiar'] ?? 0) === 1) {
                $liderancas[] = 'GF';
            }
            $linha['lideranca_label'] = count($liderancas) > 0 ? implode(' / ', $liderancas) : '-';
        }
        unset($linha);

        return $linhas;
    }

    public function buscarMembrosComFaltasConsecutivasGerais(int $minimo = 2): array
    {
        $linhas = $this->connection->query("
            SELECT
                gf.id AS grupo_id,
                gf.nome AS grupo_nome,
                (
                    SELECT GROUP_CONCAT(p2.nome, ', ')
                    FROM grupo_lideres gl2
                    INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                    WHERE gl2.grupo_familiar_id = gf.id
                      AND p2.ativo = 1
                ) AS lideres,
                pe.id AS pessoa_id,
                pe.nome,
                r.data,
                p.status
            FROM grupo_membros gm
            INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
            INNER JOIN grupos_familiares gf ON gf.id = gm.grupo_familiar_id
            INNER JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
            INNER JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
            WHERE pe.ativo = 1
              AND gf.ativo = 1
            ORDER BY gf.id ASC, pe.id ASC, r.data DESC, r.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $controle = [];
        foreach ($linhas as $linha) {
            $chave = $linha['grupo_id'] . '-' . $linha['pessoa_id'];
            if (!isset($controle[$chave])) {
                $controle[$chave] = [
                    'grupo_id' => (int) $linha['grupo_id'],
                    'grupo_nome' => $linha['grupo_nome'],
                    'lideres' => $linha['lideres'],
                    'pessoa_id' => (int) $linha['pessoa_id'],
                    'nome' => $linha['nome'],
                    'faltas' => 0,
                    'encerrado' => false,
                ];
            }
            if ($controle[$chave]['encerrado']) {
                continue;
            }
            if ($linha['status'] === 'ausente') {
                $controle[$chave]['faltas']++;
            } else {
                $controle[$chave]['encerrado'] = true;
            }
        }

        foreach ($controle as $item) {
            if ($item['faltas'] >= $minimo) {
                $resultado[] = [
                    'grupo_id' => $item['grupo_id'],
                    'grupo_nome' => $item['grupo_nome'],
                    'lideres' => $item['lideres'],
                    'pessoa_id' => $item['pessoa_id'],
                    'nome' => $item['nome'],
                    'faltas_consecutivas' => $item['faltas'],
                ];
            }
        }

        usort($resultado, fn($a, $b) => $b['faltas_consecutivas'] <=> $a['faltas_consecutivas']);
        return $resultado;
    }

    public function buscarMembrosComFaltasConsecutivasDoLider(int $pessoaId, int $minimo = 2): array
    {
        $grupos = $this->listarGruposFamiliaresPorLider($pessoaId);
        $resultado = [];

        foreach ($grupos as $grupo) {
            $faltosos = $this->buscarMembrosComFaltasConsecutivas((int) $grupo['id'], $minimo);
            foreach ($faltosos as $faltoso) {
                $resultado[] = [
                    'grupo_id' => (int) $grupo['id'],
                    'grupo_nome' => $grupo['nome'],
                    'lideres' => null,
                    'pessoa_id' => $faltoso['pessoa_id'],
                    'nome' => $faltoso['nome'],
                    'faltas_consecutivas' => $faltoso['faltas_consecutivas'],
                ];
            }
        }

        usort($resultado, fn($a, $b) => $b['faltas_consecutivas'] <=> $a['faltas_consecutivas']);
        return $resultado;
    }

    public function buscarMembrosComFaltasConsecutivas(int $grupoId, int $minimo = 2): array
    {
        $stmt = $this->connection->prepare("
            SELECT pe.id AS pessoa_id, pe.nome, r.data, p.status
            FROM grupo_membros gm
            INNER JOIN pessoas pe ON pe.id = gm.pessoa_id
            INNER JOIN reunioes r ON r.grupo_familiar_id = gm.grupo_familiar_id
            INNER JOIN presencas p ON p.reuniao_id = r.id AND p.pessoa_id = pe.id
            WHERE gm.grupo_familiar_id = :grupo_id
              AND pe.ativo = 1
            ORDER BY pe.id ASC, r.data DESC, r.id DESC
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        $controle = [];
        foreach ($linhas as $linha) {
            $pessoaId = (int) $linha['pessoa_id'];
            if (!isset($controle[$pessoaId])) {
                $controle[$pessoaId] = ['nome' => $linha['nome'], 'faltas' => 0, 'encerrado' => false];
            }
            if ($controle[$pessoaId]['encerrado']) {
                continue;
            }
            if ($linha['status'] === 'ausente') {
                $controle[$pessoaId]['faltas']++;
            } else {
                $controle[$pessoaId]['encerrado'] = true;
            }
        }

        foreach ($controle as $pessoaId => $info) {
            if ($info['faltas'] >= $minimo) {
                $resultado[] = [
                    'pessoa_id' => $pessoaId,
                    'nome' => $info['nome'],
                    'faltas_consecutivas' => $info['faltas'],
                ];
            }
        }

        usort($resultado, fn($a, $b) => $b['faltas_consecutivas'] <=> $a['faltas_consecutivas']);
        return $resultado;
    }

    public function buscarGruposAlarmantes(): array
    {
        $grupos = $this->connection->query("
            SELECT
                gf.id,
                gf.nome,
                gf.dia_semana,
                gf.horario,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id
                      AND p.ativo = 1
                ) AS lideres
            FROM grupos_familiares gf
            WHERE gf.ativo = 1
            ORDER BY gf.nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($grupos as $grupo) {
            $resumo = $this->buscarResumoPresencaPorGrupo((int) $grupo['id']);
            $totalRegistros = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];
            if ($totalRegistros > 0 && (float) $resumo['percentual_presencas'] < 50) {
                $grupo['resumo_presenca'] = $resumo;
                $resultado[] = $grupo;
            }
        }
        return $resultado;
    }

    public function buscarGruposAlarmantesDoLider(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                gf.id,
                gf.nome,
                gf.dia_semana,
                gf.horario,
                (
                    SELECT GROUP_CONCAT(p2.nome, ', ')
                    FROM grupo_lideres gl2
                    INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                    WHERE gl2.grupo_familiar_id = gf.id
                      AND p2.ativo = 1
                ) AS lideres
            FROM grupos_familiares gf
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1
              AND gl.pessoa_id = :pessoa_id
            ORDER BY gf.nome ASC
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($grupos as $grupo) {
            $resumo = $this->buscarResumoPresencaPorGrupo((int) $grupo['id']);
            $totalRegistros = (int) $resumo['total_presencas'] + (int) $resumo['total_ausencias'];
            if ($totalRegistros > 0 && (float) $resumo['percentual_presencas'] < 50) {
                $grupo['resumo_presenca'] = $resumo;
                $resultado[] = $grupo;
            }
        }
        return $resultado;
    }

    public function buscarReunioesForaDoPadrao(int $limite = 20): array
    {
        $limite = max(1, (int) $limite);
        return $this->connection->query("
            SELECT
                r.id,
                r.data,
                r.horario,
                r.motivo_alteracao,
                gf.id AS grupo_id,
                gf.nome AS grupo_nome,
                (
                    SELECT GROUP_CONCAT(p.nome, ', ')
                    FROM grupo_lideres gl
                    INNER JOIN pessoas p ON p.id = gl.pessoa_id
                    WHERE gl.grupo_familiar_id = gf.id
                      AND p.ativo = 1
                ) AS lideres
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            WHERE gf.ativo = 1
              AND r.finalizada = 1
              AND r.motivo_alteracao IS NOT NULL
              AND TRIM(r.motivo_alteracao) <> ''
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarReunioesForaDoPadraoDoLider(int $pessoaId, int $limite = 20): array
    {
        $limite = max(1, (int) $limite);
        $stmt = $this->connection->prepare("
            SELECT
                r.id,
                r.data,
                r.horario,
                r.motivo_alteracao,
                gf.id AS grupo_id,
                gf.nome AS grupo_nome,
                (
                    SELECT GROUP_CONCAT(p2.nome, ', ')
                    FROM grupo_lideres gl2
                    INNER JOIN pessoas p2 ON p2.id = gl2.pessoa_id
                    WHERE gl2.grupo_familiar_id = gf.id
                      AND p2.ativo = 1
                ) AS lideres
            FROM reunioes r
            INNER JOIN grupos_familiares gf ON gf.id = r.grupo_familiar_id
            INNER JOIN grupo_lideres gl ON gl.grupo_familiar_id = gf.id
            WHERE gf.ativo = 1
              AND r.finalizada = 1
              AND gl.pessoa_id = :pessoa_id
              AND r.motivo_alteracao IS NOT NULL
              AND TRIM(r.motivo_alteracao) <> ''
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$limite}
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarResumoDaReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                COUNT(*) AS total_registros,
                SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) AS total_presentes,
                SUM(CASE WHEN status = 'ausente' THEN 1 ELSE 0 END) AS total_ausentes
            FROM presencas
            WHERE reuniao_id = :reuniao_id
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalRegistros = (int) ($resultado['total_registros'] ?? 0);
        $totalPresentes = (int) ($resultado['total_presentes'] ?? 0);
        $totalAusentes = (int) ($resultado['total_ausentes'] ?? 0);

        return [
            'total_registros' => $totalRegistros,
            'total_presentes' => $totalPresentes,
            'total_ausentes' => $totalAusentes,
            'percentual_presencas' => $totalRegistros > 0 ? round(($totalPresentes / $totalRegistros) * 100, 1) : 0,
            'percentual_ausencias' => $totalRegistros > 0 ? round(($totalAusentes / $totalRegistros) * 100, 1) : 0,
        ];
    }

    public function buscarLideresDoGrupo(int $grupoId): string
    {
        $stmt = $this->connection->prepare("
            SELECT GROUP_CONCAT(p.nome, ', ')
            FROM grupo_lideres gl
            INNER JOIN pessoas p ON p.id = gl.pessoa_id
            WHERE gl.grupo_familiar_id = :grupo_id
              AND p.ativo = 1
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        return $stmt->fetchColumn() ?: '—';
    }

    public function listarPedidosOracaoPorReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT po.id, po.reuniao_id, po.pessoa_id, po.pedido, p.nome
            FROM pedidos_oracao po
            INNER JOIN pessoas p ON p.id = po.pessoa_id
            WHERE po.reuniao_id = :reuniao_id
            ORDER BY p.nome ASC
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarCamposPedidosOracaoDaReuniao(int $reuniaoId): array
    {
        $reuniao = $this->buscarReuniao($reuniaoId);
        if (!$reuniao) {
            return [];
        }

        $pedidosMap = [];
        foreach ($this->listarPedidosOracaoPorReuniao($reuniaoId) as $item) {
            $pedidosMap[(int) $item['pessoa_id']] = $item['pedido'];
        }

        $modoCompartilhado = $this->normalizarModoPedidosOracao($reuniao['pedidos_oracao_modo'] ?? null) === 'casal_compartilhado';
        $presentes = $this->listarPresentesDaReuniao($reuniaoId);

        return $this->montarCamposPedidosOracao($presentes, $pedidosMap, $modoCompartilhado);
    }

    public function salvarPedidosOracao(int $reuniaoId, array $pedidos): void
    {
        $campos = $this->listarCamposPedidosOracaoDaReuniao($reuniaoId);

        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("
                INSERT INTO pedidos_oracao (reuniao_id, pessoa_id, pedido, created_at, updated_at)
                VALUES (:reuniao_id, :pessoa_id, :pedido, :created_at, :updated_at)
                ON CONFLICT(reuniao_id, pessoa_id)
                DO UPDATE SET
                    pedido = excluded.pedido,
                    updated_at = excluded.updated_at
            ");

            $agora = date('Y-m-d H:i:s');

            foreach ($campos as $campo) {
                $campoId = (string) ($campo['campo_id'] ?? '');
                $pessoaIds = array_map('intval', (array) ($campo['pessoa_ids'] ?? []));
                if ($campoId === '' || count($pessoaIds) === 0) {
                    continue;
                }

                $pedidoCampo = null;
                if (array_key_exists($campoId, $pedidos)) {
                    $pedidoCampo = $pedidos[$campoId];
                } else {
                    foreach ($pessoaIds as $pessoaId) {
                        $chavePessoa = (string) $pessoaId;
                        if (array_key_exists($chavePessoa, $pedidos)) {
                            $pedidoCampo = $pedidos[$chavePessoa];
                            break;
                        }
                        if (array_key_exists($pessoaId, $pedidos)) {
                            $pedidoCampo = $pedidos[$pessoaId];
                            break;
                        }
                    }
                }

                $pedidoTexto = trim((string) ($pedidoCampo ?? ''));

                foreach ($pessoaIds as $pessoaId) {
                    if ($pessoaId <= 0) {
                        continue;
                    }

                    $stmt->execute([
                        ':reuniao_id' => $reuniaoId,
                        ':pessoa_id' => $pessoaId,
                        ':pedido' => $pedidoTexto !== '' ? $pedidoTexto : null,
                        ':created_at' => $agora,
                        ':updated_at' => $agora,
                    ]);
                }
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarPresentesDaReuniao(int $reuniaoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT
                p.pessoa_id,
                pe.nome,
                pe.cpf,
                pe.estado_civil,
                pe.conjuge_pessoa_id,
                pe.conjuge_cpf
            FROM presencas p
            INNER JOIN pessoas pe ON pe.id = p.pessoa_id
            WHERE p.reuniao_id = :reuniao_id
              AND p.status = 'presente'
            ORDER BY pe.nome ASC
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reuniaoTemPresencasPendentes(int $reuniaoId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM presencas
            WHERE reuniao_id = :reuniao_id
              AND status = :status
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId, ':status' => Presenca::STATUS_PENDENTE]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function contarPresencasDaReuniao(int $reuniaoId): int
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*)
            FROM presencas
            WHERE reuniao_id = :reuniao_id
        ");
        $stmt->execute([':reuniao_id' => $reuniaoId]);
        return (int) $stmt->fetchColumn();
    }

    public function criarReuniaoComPresencas(int $grupoId, string $data, string $horario, string $local, ?string $observacoes, array $presencas, ?string $aulaIntegracaoCodigo = null): int
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $data);
        if ($dateTime === false || $dateTime->format('Y-m-d') !== $data) {
            throw new InvalidArgumentException('Data da reunião inválida.');
        }
        if ($this->buscarReuniaoPorGrupoEData($grupoId, $data) !== null) {
            throw new InvalidArgumentException('Já existe uma reunião registrada para este GF nessa data.');
        }

        $grupo = $this->buscarGrupoPorId($grupoId);
        if (!$grupo) {
            throw new InvalidArgumentException('Grupo Familiar não encontrado.');
        }

        if (($grupo['perfil_grupo'] ?? '') === 'integracao') {
            $aulaIntegracaoCodigo = $this->validarAulaIntegracao($aulaIntegracaoCodigo);
            if ($aulaIntegracaoCodigo === null) {
                throw new InvalidArgumentException('Selecione a aula da integração para este GF.');
            }
        } else {
            $aulaIntegracaoCodigo = null;
        }

        $motivoAlteracao = $this->calcularMotivoAlteracao($grupo, $data, $horario, $local);

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO reunioes (
                    grupo_familiar_id, data, horario, local, aula_integracao_codigo, motivo_alteracao, observacoes, pedidos_oracao_modo, finalizada
                )
                VALUES (
                    :grupo_familiar_id, :data, :horario, :local, :aula_integracao_codigo, :motivo_alteracao, :observacoes, :pedidos_oracao_modo, 1
                )
            ");
            $stmt->bindValue(':grupo_familiar_id', $grupoId, PDO::PARAM_INT);
            $stmt->bindValue(':data', $data);
            $stmt->bindValue(':horario', $horario);
            $stmt->bindValue(':local', $local);
            $stmt->bindValue(':aula_integracao_codigo', $aulaIntegracaoCodigo, $aulaIntegracaoCodigo !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':motivo_alteracao', $motivoAlteracao, $motivoAlteracao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $observacoes !== '' ? $observacoes : null, $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':pedidos_oracao_modo', 'casal_compartilhado');
            $stmt->execute();

            $reuniaoId = (int) $this->connection->lastInsertId();
            $stmtP = $this->connection->prepare("
                INSERT INTO presencas (
                    reuniao_id, pessoa_id, status, presente_tempo, justificativa_atraso, ausencia_tipo, justificativa_ausencia
                )
                VALUES (
                    :reuniao_id, :pessoa_id, :status, :presente_tempo, :justificativa_atraso, :ausencia_tipo, :justificativa_ausencia
                )
            ");

            foreach ($presencas as $pessoaId => $dadosPresenca) {
                $dadosValidados = $this->validarPayloadPresenca($dadosPresenca);
                $stmtP->bindValue(':reuniao_id', $reuniaoId, PDO::PARAM_INT);
                $stmtP->bindValue(':pessoa_id', (int) $pessoaId, PDO::PARAM_INT);
                $stmtP->bindValue(':status', $dadosValidados['status']);
                $stmtP->bindValue(':presente_tempo', $dadosValidados['presente_tempo'], $dadosValidados['presente_tempo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmtP->bindValue(':justificativa_atraso', $dadosValidados['justificativa_atraso'], $dadosValidados['justificativa_atraso'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmtP->bindValue(':ausencia_tipo', $dadosValidados['ausencia_tipo'], $dadosValidados['ausencia_tipo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmtP->bindValue(':justificativa_ausencia', $dadosValidados['justificativa_ausencia'], $dadosValidados['justificativa_ausencia'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmtP->execute();
            }

            $this->sincronizarIntegracaoDaReuniao($reuniaoId, $grupoId, $aulaIntegracaoCodigo, $data);

            $this->connection->commit();
            return $reuniaoId;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function listarMembrosPorGrupo(int $grupoId): array
    {
        $stmt = $this->connection->prepare("
            SELECT gm.pessoa_id AS id, p.nome, p.cargo
            FROM grupo_membros gm
            INNER JOIN pessoas p ON p.id = gm.pessoa_id
            WHERE gm.grupo_familiar_id = :grupo_id
              AND p.ativo = 1
            ORDER BY p.nome ASC
        ");
        $stmt->execute([':grupo_id' => $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarProgressoIntegracaoPessoa(int $pessoaId): array
    {
        $stmt = $this->connection->prepare("
            SELECT aula_codigo, aula_titulo, data_aula, origem, concluida
            FROM pessoa_integracao_aulas
            WHERE pessoa_id = :pessoa_id
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapa = [];
        foreach ($registros as $registro) {
            $mapa[$registro['aula_codigo']] = $registro;
        }

        $resultado = [];
        foreach ($this->aulasIntegracaoMap() as $codigo => $titulo) {
            $registro = $mapa[$codigo] ?? null;
            $resultado[] = [
                'codigo' => $codigo,
                'titulo' => $titulo,
                'concluida' => $registro ? (int) ($registro['concluida'] ?? 0) === 1 : false,
                'data_aula' => $registro['data_aula'] ?? null,
                'origem' => $registro['origem'] ?? null,
            ];
        }

        return $resultado;
    }

    public function listarGruposIntegracao(): array
    {
        return array_values(array_filter(
            $this->listarGruposFamiliares(),
            fn($grupo) => ($grupo['perfil_grupo'] ?? '') === 'integracao'
        ));
    }

    public function listarGruposIntegracaoPorLider(int $pessoaId): array
    {
        return array_values(array_filter(
            $this->listarGruposFamiliaresPorLider($pessoaId),
            fn($grupo) => ($grupo['perfil_grupo'] ?? '') === 'integracao'
        ));
    }

    public function listarMembrosIntegracaoPorGrupo(int $grupoId): array
    {
        $grupo = $this->buscarGrupoPorId($grupoId);
        if (!$grupo || ($grupo['perfil_grupo'] ?? '') !== 'integracao') {
            return [];
        }

        return $this->listarMembrosPorGrupo($grupoId);
    }

    public function listarAulasDisponiveisParaRetiro(int $pessoaId, ?int $retiroId = null): array
    {
        $aulas = $this->aulasIntegracaoMap();

        $stmt = $this->connection->prepare("
            SELECT aula_codigo, origem, retiro_id
            FROM pessoa_integracao_aulas
            WHERE pessoa_id = :pessoa_id
              AND concluida = 1
        ");
        $stmt->execute([':pessoa_id' => $pessoaId]);

        $bloqueadas = [];
        $preSelecionadas = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $registro) {
            $codigo = $registro['aula_codigo'] ?? '';
            $origem = $registro['origem'] ?? '';
            $retiroRelacionando = (int) ($registro['retiro_id'] ?? 0);

            if ($codigo === '' || !isset($aulas[$codigo])) {
                continue;
            }

            if ($retiroId !== null && $origem === 'retiro' && $retiroRelacionando === $retiroId) {
                $preSelecionadas[$codigo] = true;
                continue;
            }

            $bloqueadas[$codigo] = true;
        }

        $resultado = [];
        foreach ($aulas as $codigo => $titulo) {
            if (isset($bloqueadas[$codigo]) && !isset($preSelecionadas[$codigo])) {
                continue;
            }

            $resultado[] = [
                'codigo' => $codigo,
                'titulo' => $titulo,
                'selecionada' => isset($preSelecionadas[$codigo]),
            ];
        }

        return $resultado;
    }

    public function buscarRetiroIntegracao(int $retiroId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT
                ri.id,
                ri.grupo_familiar_id,
                ri.pessoa_id,
                ri.data_retiro,
                ri.created_at,
                ri.updated_at,
                gf.nome AS grupo_nome,
                gf.perfil_grupo,
                p.nome AS pessoa_nome
            FROM retiros_integracao ri
            INNER JOIN grupos_familiares gf ON gf.id = ri.grupo_familiar_id
            INNER JOIN pessoas p ON p.id = ri.pessoa_id
            WHERE ri.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $retiroId]);
        $retiro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$retiro) {
            return null;
        }

        $stmtAulas = $this->connection->prepare("
            SELECT aula_codigo
            FROM pessoa_integracao_aulas
            WHERE retiro_id = :retiro_id
              AND origem = 'retiro'
            ORDER BY aula_codigo ASC
        ");
        $stmtAulas->execute([':retiro_id' => $retiroId]);
        $retiro['aulas_codigos'] = array_values($stmtAulas->fetchAll(PDO::FETCH_COLUMN));

        return $retiro;
    }

    public function listarRetirosIntegracao(?int $liderId = null): array
    {
        $sql = "
            SELECT
                ri.id,
                ri.grupo_familiar_id,
                ri.pessoa_id,
                ri.data_retiro,
                ri.created_at,
                gf.nome AS grupo_nome,
                p.nome AS pessoa_nome
            FROM retiros_integracao ri
            INNER JOIN grupos_familiares gf ON gf.id = ri.grupo_familiar_id
            INNER JOIN pessoas p ON p.id = ri.pessoa_id
            WHERE gf.ativo = 1
              AND gf.perfil_grupo = 'integracao'
        ";

        $params = [];
        if ($liderId !== null) {
            $sql .= "
              AND EXISTS (
                  SELECT 1
                  FROM grupo_lideres gl
                  WHERE gl.grupo_familiar_id = gf.id
                    AND gl.pessoa_id = :lider_id
              )
            ";
            $params[':lider_id'] = $liderId;
        }

        $sql .= " ORDER BY ri.data_retiro DESC, ri.id DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtAulas = $this->connection->prepare("
            SELECT aula_codigo
            FROM pessoa_integracao_aulas
            WHERE retiro_id = :retiro_id
              AND origem = 'retiro'
            ORDER BY aula_codigo ASC
        ");

        foreach ($registros as &$registro) {
            $stmtAulas->execute([':retiro_id' => (int) $registro['id']]);
            $registro['aulas_codigos'] = array_values($stmtAulas->fetchAll(PDO::FETCH_COLUMN));
        }

        return $registros;
    }

    public function salvarRetiroIntegracao(int $grupoId, int $pessoaId, string $dataRetiro, array $aulasCodigos): int
    {
        $grupo = $this->buscarGrupoPorId($grupoId);
        if (!$grupo || ($grupo['perfil_grupo'] ?? '') !== 'integracao') {
            throw new InvalidArgumentException('Selecione um GF de integração válido.');
        }

        $membros = array_column($this->listarMembrosIntegracaoPorGrupo($grupoId), 'id');
        if (!in_array($pessoaId, array_map('intval', $membros), true)) {
            throw new InvalidArgumentException('Selecione um membro válido do GF informado.');
        }

        $dt = DateTime::createFromFormat('!Y-m-d', $dataRetiro);
        if ($dt === false || $dt->format('Y-m-d') !== $dataRetiro || $dt > new DateTime('today')) {
            throw new InvalidArgumentException('Informe uma data de retiro válida.');
        }

        $aulasCodigos = array_values(array_unique(array_filter(array_map('trim', $aulasCodigos))));
        if (count($aulasCodigos) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos uma aula ministrada no retiro.');
        }

        foreach ($aulasCodigos as $codigo) {
            $this->validarAulaIntegracao($codigo);
        }

        $placeholders = implode(',', array_fill(0, count($aulasCodigos), '?'));
        $params = array_merge([$pessoaId], $aulasCodigos);

        $stmtConflito = $this->connection->prepare("
            SELECT COUNT(*)
            FROM pessoa_integracao_aulas
            WHERE pessoa_id = ?
              AND aula_codigo IN ({$placeholders})
              AND concluida = 1
        ");
        $stmtConflito->execute($params);
        if ((int) $stmtConflito->fetchColumn() > 0) {
            throw new InvalidArgumentException('Uma ou mais aulas selecionadas já foram concluídas anteriormente por este membro.');
        }

        $this->connection->beginTransaction();

        try {
            $agora = date('Y-m-d H:i:s');

            $stmtRetiro = $this->connection->prepare("
                INSERT INTO retiros_integracao (
                    grupo_familiar_id, pessoa_id, data_retiro, created_at, updated_at
                ) VALUES (
                    :grupo_familiar_id, :pessoa_id, :data_retiro, :created_at, :updated_at
                )
            ");
            $stmtRetiro->execute([
                ':grupo_familiar_id' => $grupoId,
                ':pessoa_id' => $pessoaId,
                ':data_retiro' => $dataRetiro,
                ':created_at' => $agora,
                ':updated_at' => $agora,
            ]);

            $retiroId = (int) $this->connection->lastInsertId();
            $stmtAula = $this->connection->prepare("
                INSERT INTO pessoa_integracao_aulas (
                    pessoa_id, aula_codigo, aula_titulo, data_aula, origem, reuniao_id, retiro_id, concluida
                ) VALUES (
                    :pessoa_id, :aula_codigo, :aula_titulo, :data_aula, 'retiro', NULL, :retiro_id, 1
                )
            ");

            foreach ($aulasCodigos as $codigo) {
                $stmtAula->execute([
                    ':pessoa_id' => $pessoaId,
                    ':aula_codigo' => $codigo,
                    ':aula_titulo' => $this->aulasIntegracaoMap()[$codigo],
                    ':data_aula' => $dataRetiro,
                    ':retiro_id' => $retiroId,
                ]);
            }

            $this->atualizarParticipacaoRetiroPessoa($pessoaId);
            $this->atualizarStatusConclusaoIntegracaoPessoa($pessoaId);

            $this->connection->commit();
            return $retiroId;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function atualizarRetiroIntegracao(int $retiroId, int $grupoId, int $pessoaId, string $dataRetiro, array $aulasCodigos): void
    {
        $retiroAtual = $this->buscarRetiroIntegracao($retiroId);
        if (!$retiroAtual) {
            throw new InvalidArgumentException('Retiro de integração não encontrado.');
        }

        $grupo = $this->buscarGrupoPorId($grupoId);
        if (!$grupo || ($grupo['perfil_grupo'] ?? '') !== 'integracao') {
            throw new InvalidArgumentException('Selecione um GF de integração válido.');
        }

        $membros = array_column($this->listarMembrosIntegracaoPorGrupo($grupoId), 'id');
        if (!in_array($pessoaId, array_map('intval', $membros), true)) {
            throw new InvalidArgumentException('Selecione um membro válido do GF informado.');
        }

        $dt = DateTime::createFromFormat('!Y-m-d', $dataRetiro);
        if ($dt === false || $dt->format('Y-m-d') !== $dataRetiro || $dt > new DateTime('today')) {
            throw new InvalidArgumentException('Informe uma data de retiro válida.');
        }

        $aulasCodigos = array_values(array_unique(array_filter(array_map('trim', $aulasCodigos))));
        if (count($aulasCodigos) === 0) {
            throw new InvalidArgumentException('Selecione pelo menos uma aula ministrada no retiro.');
        }

        foreach ($aulasCodigos as $codigo) {
            $this->validarAulaIntegracao($codigo);
        }

        $placeholders = implode(',', array_fill(0, count($aulasCodigos), '?'));
        $params = array_merge([$pessoaId], $aulasCodigos, [$retiroId]);

        $stmtConflito = $this->connection->prepare("
            SELECT COUNT(*)
            FROM pessoa_integracao_aulas
            WHERE pessoa_id = ?
              AND aula_codigo IN ({$placeholders})
              AND concluida = 1
              AND (origem <> 'retiro' OR COALESCE(retiro_id, 0) <> ?)
        ");
        $stmtConflito->execute($params);
        if ((int) $stmtConflito->fetchColumn() > 0) {
            throw new InvalidArgumentException('Uma ou mais aulas selecionadas já foram concluídas anteriormente por este membro.');
        }

        $this->connection->beginTransaction();

        try {
            $stmtRetiro = $this->connection->prepare("
                UPDATE retiros_integracao
                SET grupo_familiar_id = :grupo_familiar_id,
                    pessoa_id = :pessoa_id,
                    data_retiro = :data_retiro,
                    updated_at = :updated_at
                WHERE id = :id
            ");
            $stmtRetiro->execute([
                ':grupo_familiar_id' => $grupoId,
                ':pessoa_id' => $pessoaId,
                ':data_retiro' => $dataRetiro,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':id' => $retiroId,
            ]);

            $stmtDelete = $this->connection->prepare("
                DELETE FROM pessoa_integracao_aulas
                WHERE retiro_id = :retiro_id
                  AND origem = 'retiro'
            ");
            $stmtDelete->execute([':retiro_id' => $retiroId]);

            $stmtAula = $this->connection->prepare("
                INSERT INTO pessoa_integracao_aulas (
                    pessoa_id, aula_codigo, aula_titulo, data_aula, origem, reuniao_id, retiro_id, concluida
                ) VALUES (
                    :pessoa_id, :aula_codigo, :aula_titulo, :data_aula, 'retiro', NULL, :retiro_id, 1
                )
            ");

            foreach ($aulasCodigos as $codigo) {
                $stmtAula->execute([
                    ':pessoa_id' => $pessoaId,
                    ':aula_codigo' => $codigo,
                    ':aula_titulo' => $this->aulasIntegracaoMap()[$codigo],
                    ':data_aula' => $dataRetiro,
                    ':retiro_id' => $retiroId,
                ]);
            }

            $pessoasAfetadas = array_values(array_unique([
                (int) ($retiroAtual['pessoa_id'] ?? 0),
                $pessoaId,
            ]));

            foreach ($pessoasAfetadas as $pessoaAfetadaId) {
                if ($pessoaAfetadaId > 0) {
                    $this->atualizarParticipacaoRetiroPessoa($pessoaAfetadaId);
                    $this->atualizarStatusConclusaoIntegracaoPessoa($pessoaAfetadaId);
                }
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
