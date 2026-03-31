<?php

function formatarDataBr(?string $data): string
{
    if (!$data) {
        return '—';
    }

    $date = DateTime::createFromFormat('Y-m-d', $data);

    if (!$date) {
        return $data;
    }

    return $date->format('d/m/Y');
}

function formatarDataHoraBr(?string $dataHora): string
{
    if (!$dataHora) {
        return '—';
    }

    $date = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);

    if (!$date) {
        return $dataHora;
    }

    return $date->format('d/m/Y H:i');
}

function calcularIdade(?string $data): ?int
{
    if (!$data) {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $data);

    if (!$date) {
        return null;
    }

    $hoje = new DateTime('today');
    return $date->diff($hoje)->y;
}

function formatarTelefone(?string $telefone): string
{
    $digitos = preg_replace('/\D+/', '', (string) $telefone);

    if ($digitos === '') {
        return '—';
    }

    if (strlen($digitos) === 10) {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 4), substr($digitos, 6, 4));
    }

    if (strlen($digitos) === 11) {
        return sprintf('(%s) %s %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 1), substr($digitos, 3, 4), substr($digitos, 7, 4));
    }

    return $digitos;
}

function formatarCep(?string $cep): string
{
    $digitos = preg_replace('/\D+/', '', (string) $cep);

    if ($digitos === '') {
        return '—';
    }

    if (strlen($digitos) === 8) {
        return substr($digitos, 0, 5) . '-' . substr($digitos, 5, 3);
    }

    return $digitos;
}

function formatarEnderecoPessoa(array $pessoa): string
{
    $logradouro = trim((string) ($pessoa['endereco_logradouro'] ?? ''));
    $numero = trim((string) ($pessoa['endereco_numero'] ?? ''));
    $complemento = trim((string) ($pessoa['endereco_complemento'] ?? ''));
    $bairro = trim((string) ($pessoa['endereco_bairro'] ?? ''));
    $cidade = trim((string) ($pessoa['endereco_cidade'] ?? ''));
    $uf = strtoupper(trim((string) ($pessoa['endereco_uf'] ?? '')));
    $cep = formatarCep($pessoa['endereco_cep'] ?? null);

    if (
        $logradouro === '' &&
        $numero === '' &&
        $complemento === '' &&
        $bairro === '' &&
        $cidade === '' &&
        $uf === '' &&
        $cep === '—'
    ) {
        return '—';
    }

    $partes = [];

    if ($logradouro !== '' || $numero !== '') {
        $linhaPrincipal = $logradouro !== '' ? $logradouro : 'Endereço';
        if ($numero !== '') {
            $linhaPrincipal .= ', ' . $numero;
        }
        if ($complemento !== '') {
            $linhaPrincipal .= ', ' . $complemento;
        }
        $partes[] = $linhaPrincipal;
    }

    if ($bairro !== '') {
        $partes[] = $bairro;
    }

    $cidadeUf = trim($cidade . ($cidade !== '' && $uf !== '' ? '/' : '') . $uf, '/');
    if ($cidadeUf !== '') {
        $partes[] = $cidadeUf;
    }

    if ($cep !== '—') {
        $partes[] = $cep;
    }

    return count($partes) > 0 ? implode(' - ', $partes) : '—';
}

function opcoesEstadoCivil(): array
{
    return [
        'solteiro' => 'Solteiro(a)',
        'casado' => 'Casado(a)',
        'uniao_estavel' => 'União estável',
        'divorciado' => 'Divorciado(a)',
        'viuvo' => 'Viúvo(a)',
    ];
}

function labelEstadoCivil(?string $estadoCivil): string
{
    $opcoes = opcoesEstadoCivil();
    return $opcoes[$estadoCivil ?? ''] ?? '—';
}

function opcoesPerfilGrupo(): array
{
    return [
        'casais' => 'Casais',
        'jovens' => 'Jovens',
        'teen' => 'Teen',
        'mulheres' => 'Mulheres',
        'integracao' => 'Integração',
    ];
}

function labelPerfilGrupo(?string $perfil): string
{
    $opcoes = opcoesPerfilGrupo();
    return $opcoes[$perfil ?? ''] ?? '—';
}

function labelSimNao(?int $valor): string
{
    return (int) $valor === 1 ? 'Sim' : 'Não';
}

function opcoesMotivoDesativacaoPessoa(): array
{
    return [
        'mudanca_igreja' => 'Mudança de Igreja',
        'transferencia_abba' => 'Transferência para outra C.C. Abba',
        'nao_sabe' => 'Não sabe para qual Igreja vai',
        'nao_frequenta' => 'Decidiu não frequentar mais igreja evangélica',
        'motivos_pessoais' => 'Motivos pessoais',
    ];
}

function labelMotivoDesativacaoPessoa(?string $motivo): string
{
    $opcoes = opcoesMotivoDesativacaoPessoa();
    return $opcoes[$motivo ?? ''] ?? '—';
}

function aulasIntegracao(): array
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

function labelAulaIntegracao(?string $codigo): string
{
    $aulas = aulasIntegracao();
    return $aulas[$codigo ?? ''] ?? '—';
}

function opcoesDestinoChamado(): array
{
    return [
        'secretaria' => 'Secretaria da Igreja',
        'suporte' => 'Suporte Técnico',
    ];
}

function labelDestinoChamado(?string $destino): string
{
    $opcoes = opcoesDestinoChamado();
    return $opcoes[$destino ?? ''] ?? '—';
}

function opcoesAssuntoSecretaria(): array
{
    return [
        'edicao_pessoa' => 'Edição de Cadastro de Pessoa',
        'desativacao_pessoa' => 'Desativação de Pessoa',
        'edicao_gf' => 'Edição de Cadastro de Grupo Familiar',
        'desativacao_gf' => 'Desativação de GF',
        'outros' => 'Outros',
    ];
}

function opcoesCamposPessoaChamado(): array
{
    return [
        'nome' => 'Nome',
        'cpf' => 'CPF',
        'email' => 'E-mail',
        'perfil' => 'Perfil',
        'status' => 'Status',
        'data_nascimento' => 'Data de nasc.',
        'estado_civil' => 'Estado civil',
        'eh_lider' => 'É líder',
        'grupo_familiar_id' => 'Grupo Familiar que pertence',
        'concluiu_integracao' => 'Concluiu integração',
    ];
}

function opcoesCamposGFChamado(): array
{
    return [
        'nome' => 'Nome',
        'dia_semana' => 'Dia',
        'horario' => 'Horário',
        'lideres' => 'Líderes',
        'membros' => 'Membros',
        'perfil_grupo' => 'Perfil do grupo',
        'local_padrao' => 'Local',
        'item_celeiro' => 'Celeiro',
        'domingo_oracao_culto' => 'Dom. de oração',
    ];
}

function opcoesAssuntoSuporte(): array
{
    return [
        'problema_tela' => 'Problemas em determinada tela',
        'outros' => 'Outros',
    ];
}

function opcoesTelasSuporte(): array
{
    return [
        'login' => 'Login',
        'dashboard' => 'Dashboard (tela inicial)',
        'diagnostico_gf' => 'Diagnóstico de GFs',
        'presencas' => 'Reuniões e Presenças',
        'agenda' => 'Agenda',
        'cartas' => 'Carta Semanal',
        'avisos' => 'Notificações',
        'meu_perfil' => 'Meu Perfil',
    ];
}

function opcoesStatusChamado(): array
{
    return [
        'em_analise' => 'Em análise',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];
}

function labelStatusChamado(?string $status): string
{
    $opcoes = opcoesStatusChamado();
    return $opcoes[$status ?? ''] ?? '—';
}

function opcoesUF(): array
{
    return [
        'AC' => 'AC',
        'AL' => 'AL',
        'AP' => 'AP',
        'AM' => 'AM',
        'BA' => 'BA',
        'CE' => 'CE',
        'DF' => 'DF',
        'ES' => 'ES',
        'GO' => 'GO',
        'MA' => 'MA',
        'MT' => 'MT',
        'MS' => 'MS',
        'MG' => 'MG',
        'PA' => 'PA',
        'PB' => 'PB',
        'PR' => 'PR',
        'PE' => 'PE',
        'PI' => 'PI',
        'RJ' => 'RJ',
        'RN' => 'RN',
        'RS' => 'RS',
        'RO' => 'RO',
        'RR' => 'RR',
        'SC' => 'SC',
        'SP' => 'SP',
        'SE' => 'SE',
        'TO' => 'TO',
    ];
}
