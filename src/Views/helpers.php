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

function opcoesEstadoCivil(): array
{
    return [
        'solteiro' => 'Solteiro(a)',
        'casado' => 'Casado(a)',
        'uniao_estavel' => 'União Estável',
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
        'data_nascimento' => 'Data de Nasc.',
        'estado_civil' => 'Estado Civil',
        'eh_lider' => 'É Líder',
        'grupo_familiar_id' => 'Grupo Familiar que Pertence',
        'concluiu_integracao' => 'Concluiu Integração',
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
        'perfil_grupo' => 'Perfil do Grupo',
        'local_padrao' => 'Local',
        'item_celeiro' => 'Celeiro',
        'domingo_oracao_culto' => 'Dom. Oração',
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
        'em_analise' => 'Em Análise',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];
}

function labelStatusChamado(?string $status): string
{
    $opcoes = opcoesStatusChamado();
    return $opcoes[$status ?? ''] ?? '—';
}
