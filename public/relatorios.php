<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Repositories/RelatorioRepository.php';

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (method_exists('Auth', 'requireAuth')) {
    Auth::requireAuth();
} else {
    Auth::requireLogin();
}
Auth::requireAdmin();
Auth::requireSenhaAtualizada();

function dataRelatorioValida(string $data): bool
{
    $dt = DateTime::createFromFormat('!Y-m-d', $data);
    return $dt !== false && $dt->format('Y-m-d') === $data;
}

function formatarDataRelatorio(?string $data): string
{
    if ($data === null || $data === '') {
        return '-';
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

function formatarMesRelatorio(?string $mes): string
{
    if ($mes === null || $mes === '') {
        return '-';
    }

    $partes = explode('-', $mes);
    if (count($partes) !== 2) {
        return $mes;
    }

    [$ano, $numeroMes] = $partes;
    $meses = [
        '01' => 'Jan',
        '02' => 'Fev',
        '03' => 'Mar',
        '04' => 'Abr',
        '05' => 'Mai',
        '06' => 'Jun',
        '07' => 'Jul',
        '08' => 'Ago',
        '09' => 'Set',
        '10' => 'Out',
        '11' => 'Nov',
        '12' => 'Dez',
    ];

    return ($meses[$numeroMes] ?? $numeroMes) . '/' . substr($ano, 2);
}

function truncarTextoPdf(string $texto, int $limite = 34): string
{
    $texto = trim($texto);
    if ($texto === '') {
        return '-';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($texto, 0, $limite, '...');
    }

    return strlen($texto) > $limite ? substr($texto, 0, $limite - 3) . '...' : $texto;
}

if (class_exists('TCPDF')) {
    class JtroRelatorioPdf extends TCPDF
    {
        public string $tituloRelatorio = '';
        public string $periodoRelatorio = '';
        public string $usuarioGerador = 'Sistema';
        public string $escopoRelatorio = 'Igreja';
        public string $urlIgreja = 'comunhaoabba.com.br';

        public function Header(): void
        {
            $this->SetFillColor(15, 61, 110);
            $this->Rect(0, 0, 210, 20, 'F');
            $this->SetFillColor(24, 95, 165);
            $this->Rect(60, 0, 150, 20, 'F');
            $this->SetFillColor(29, 158, 117);
            $this->Rect(145, 0, 65, 20, 'F');

            $this->SetFillColor(255, 255, 255);
            $this->RoundedRect(12, 5, 9, 9, 1.5, '1111', 'F');
            $this->SetFont('helvetica', 'B', 9);
            $this->SetTextColor(24, 95, 165);
            $this->SetXY(12, 7);
            $this->Cell(9, 5, 'JT', 0, 0, 'C');

            $this->SetTextColor(255, 255, 255);
            $this->SetFont('helvetica', 'B', 9);
            $this->SetXY(24, 5.8);
            $this->Cell(70, 4.8, 'Comunhao Crista Abba', 0, 0, 'L');
            $this->SetFont('helvetica', '', 7.4);
            $this->SetXY(24, 10.2);
            $this->Cell(70, 4.5, 'JTRO - The Relational Organizer', 0, 0, 'L');

            $this->SetFont('helvetica', '', 7.8);
            $this->SetTextColor(220, 232, 245);
            $this->SetXY(100, 4.8);
            $this->Cell(98, 4.2, 'Relatorio', 0, 0, 'R');

            $this->SetFont('helvetica', 'B', 10.2);
            $this->SetTextColor(255, 255, 255);
            $this->SetXY(100, 8.3);
            $this->Cell(98, 5.5, truncarTextoPdf($this->tituloRelatorio, 80), 0, 0, 'R');

            $this->SetFont('helvetica', '', 7.3);
            $this->SetTextColor(220, 232, 245);
            $this->SetXY(100, 13.3);
            $this->Cell(98, 4, 'Periodo: ' . $this->periodoRelatorio, 0, 0, 'R');

            $this->SetFillColor(244, 247, 251);
            $this->Rect(0, 20, 210, 10, 'F');
            $this->SetFont('helvetica', '', 7.2);
            $this->SetTextColor(93, 108, 126);
            $this->SetXY(12, 22.3);
            $this->Cell(70, 4.3, 'Gerado em: ' . date('d/m/Y H:i'), 0, 0, 'L');
            $this->SetXY(76, 22.3);
            $this->Cell(65, 4.3, 'Gerado por: ' . truncarTextoPdf($this->usuarioGerador, 32), 0, 0, 'L');
            $this->SetXY(140, 22.3);
            $this->Cell(58, 4.3, 'Escopo: ' . truncarTextoPdf($this->escopoRelatorio, 30), 0, 0, 'R');

            $this->SetDrawColor(221, 229, 239);
            $this->Line(12, 30, 198, 30);
        }

        public function Footer(): void
        {
            $this->SetY(-12);
            $this->SetFillColor(244, 247, 251);
            $this->Rect(0, 285, 210, 12, 'F');
            $this->SetDrawColor(221, 229, 239);
            $this->Line(12, 285, 198, 285);

            $this->SetFont('helvetica', '', 6.8);
            $this->SetTextColor(93, 108, 126);
            $this->SetX(12);
            $this->Cell(70, 4.5, 'Documento gerado automaticamente pelo JTRO', 0, 0, 'L');

            $this->SetFont('helvetica', 'B', 6.8);
            $this->SetTextColor(24, 95, 165);
            $this->SetX(80);
            $this->Cell(50, 4.5, $this->urlIgreja, 0, 0, 'C');

            $this->SetFont('helvetica', '', 6.8);
            $this->SetTextColor(93, 108, 126);
            $this->SetX(140);
            $this->Cell(58, 4.5, 'Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}

function garantirEspacoPdf(TCPDF $pdf, float $alturaNecessaria): void
{
    if ($pdf->GetY() + $alturaNecessaria > 274) {
        $pdf->AddPage();
    }
}

function tituloSecaoPdf(TCPDF $pdf, string $titulo): void
{
    garantirEspacoPdf($pdf, 12);
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetTextColor(24, 95, 165);
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(0, 5.5, strtoupper($titulo), 'B', 1, 'L');
    $pdf->SetY($pdf->GetY() + 1);
}

function renderizarLinhaKpiPdf(TCPDF $pdf, array $kpis): void
{
    garantirEspacoPdf($pdf, 27);
    $xInicial = 12.0;
    $y = $pdf->GetY() + 1;
    $larguraTotal = 186.0;
    $espaco = 3.0;
    $larguraCard = ($larguraTotal - ($espaco * 3)) / 4;

    foreach ($kpis as $indice => $kpi) {
        $x = $xInicial + ($indice * ($larguraCard + $espaco));
        $fundo = $kpi['fundo'];
        $texto = $kpi['texto'];
        $valor = (string) $kpi['valor'];
        $label = (string) $kpi['label'];

        $pdf->SetFillColor($fundo[0], $fundo[1], $fundo[2]);
        $pdf->RoundedRect($x, $y, $larguraCard, 20, 1.8, '1111', 'F');

        $pdf->SetTextColor($texto[0], $texto[1], $texto[2]);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY($x, $y + 2.2);
        $pdf->Cell($larguraCard, 7.2, truncarTextoPdf($valor, 18), 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 7.2);
        $pdf->SetTextColor(78, 96, 116);
        $pdf->SetXY($x, $y + 11.8);
        $pdf->Cell($larguraCard, 5, truncarTextoPdf($label, 30), 0, 0, 'C');
    }

    $pdf->SetY($y + 22.5);
}

function corSemanticaPercentualPdf(float $valor): array
{
    if ($valor >= 85) {
        return [46, 125, 50];
    }
    if ($valor >= 70) {
        return [24, 95, 165];
    }
    if ($valor >= 50) {
        return [133, 79, 11];
    }

    return [163, 45, 45];
}

function renderizarTabelaPdf(TCPDF $pdf, array $cabecalhos, array $linhas, ?array $larguras = null, bool $colorirUltimaColuna = false): void
{
    $quantidadeColunas = count($cabecalhos);
    if ($quantidadeColunas === 0) {
        return;
    }

    $largurasColunas = $larguras;
    if ($largurasColunas === null) {
        $larguraPadrao = 186 / $quantidadeColunas;
        $largurasColunas = array_fill(0, $quantidadeColunas, $larguraPadrao);
    }

    garantirEspacoPdf($pdf, 10);

    $pdf->SetFillColor(24, 95, 165);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 7.1);
    foreach ($cabecalhos as $indice => $titulo) {
        $pdf->Cell($largurasColunas[$indice], 6, truncarTextoPdf((string) $titulo, 34), 0, 0, 'L', true);
    }
    $pdf->Ln();

    if (count($linhas) === 0) {
        $pdf->SetTextColor(93, 108, 126);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(array_sum($largurasColunas), 6.4, 'Nenhum registro encontrado no periodo selecionado.', 0, 1, 'L');
        $pdf->SetY($pdf->GetY() + 1);
        return;
    }

    $pdf->SetFont('helvetica', '', 7.0);

    foreach ($linhas as $indiceLinha => $linha) {
        garantirEspacoPdf($pdf, 7);

        if ($indiceLinha % 2 === 0) {
            $pdf->SetFillColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(244, 247, 251);
        }

        foreach ($linha as $indiceColuna => $valorCelula) {
            $conteudo = truncarTextoPdf((string) $valorCelula, 42);
            $alinhamento = is_numeric($valorCelula) ? 'R' : 'L';

            if ($colorirUltimaColuna && $indiceColuna === count($linha) - 1) {
                $valorNumerico = (float) str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', (string) $valorCelula));
                [$r, $g, $b] = corSemanticaPercentualPdf($valorNumerico);
                $pdf->SetTextColor($r, $g, $b);
            } else {
                $pdf->SetTextColor(26, 38, 54);
            }

            $pdf->Cell($largurasColunas[$indiceColuna], 5.6, $conteudo, 0, 0, $alinhamento, true);
        }

        $pdf->Ln();
    }

    $pdf->SetY($pdf->GetY() + 1.5);
}

function renderizarInfoGfPdf(TCPDF $pdf, ?array $gf): void
{
    if (!$gf) {
        return;
    }

    garantirEspacoPdf($pdf, 9);
    $pdf->SetFont('helvetica', '', 7.6);
    $pdf->SetTextColor(93, 108, 126);
    $pdf->Cell(
        0,
        5,
        'Grupo Familiar: ' . ($gf['gf_nome'] ?? '-') . ' | Lider(es): ' . ($gf['lider_nome'] ?? '-'),
        0,
        1,
        'L'
    );
    $pdf->SetY($pdf->GetY() + 0.8);
}

function renderizarMapeamentoAssiduidadePdf(TCPDF $pdf, array $dados): void
{
    $kpis = $dados['kpis'] ?? [];

    tituloSecaoPdf($pdf, 'Indicadores do Periodo');
    renderizarLinhaKpiPdf($pdf, [
        [
            'label' => 'Membros ativos',
            'valor' => (string) ($kpis['total_membros'] ?? 0),
            'fundo' => [230, 241, 251],
            'texto' => [24, 95, 165],
        ],
        [
            'label' => 'Taxa de presenca',
            'valor' => (string) ($kpis['taxa_presenca'] ?? 0) . '%',
            'fundo' => [234, 243, 222],
            'texto' => [46, 125, 50],
        ],
        [
            'label' => 'Faltas justificadas',
            'valor' => (string) ($kpis['faltas_justificadas'] ?? 0),
            'fundo' => [250, 238, 218],
            'texto' => [133, 79, 11],
        ],
        [
            'label' => 'Faltas injustificadas',
            'valor' => (string) ($kpis['faltas_injustificadas'] ?? 0),
            'fundo' => [252, 235, 235],
            'texto' => [163, 45, 45],
        ],
    ]);

    tituloSecaoPdf($pdf, 'Assiduidade mensal');
    $linhasMensal = [];
    foreach (($dados['mensal'] ?? []) as $mes) {
        $linhasMensal[] = [
            formatarMesRelatorio((string) ($mes['mes'] ?? '')),
            (string) ((int) ($mes['presencas'] ?? 0)),
            (string) ((int) ($mes['just'] ?? 0)),
            (string) ((int) ($mes['injust'] ?? 0)),
            (string) ((float) ($mes['taxa_presenca'] ?? 0)) . '%',
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Mes', 'Presencas', 'Faltas Just.', 'Faltas Injust.', '% Presenca'],
        $linhasMensal,
        [34, 37, 37, 37, 41],
        true
    );

    tituloSecaoPdf($pdf, 'Assiduidade por grupo familiar');
    $linhasGf = [];
    foreach (($dados['por_gf'] ?? []) as $grupo) {
        $linhasGf[] = [
            (string) ($grupo['gf_nome'] ?? '-'),
            (string) ($grupo['lider_nome'] ?? '-'),
            (string) ((int) ($grupo['qtd_membros'] ?? 0)),
            (string) ((int) ($grupo['qtd_reunioes'] ?? 0)),
            (string) ((int) ($grupo['presencas'] ?? 0)),
            (string) ((int) ($grupo['just'] ?? 0)),
            (string) ((int) ($grupo['injust'] ?? 0)),
            (string) ((float) ($grupo['taxa_presenca'] ?? 0)) . '%',
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['GF', 'Lider', 'Membros', 'Reunioes', 'Presencas', 'F.Just', 'F.Injust', '%'],
        $linhasGf,
        [29, 34, 16, 18, 22, 19, 20, 28],
        true
    );
}

function renderizarTermometroPdf(TCPDF $pdf, array $dados): void
{
    $lideres = $dados['lideres'] ?? [];
    $total = count($lideres);
    $alto = count(array_filter($lideres, static fn(array $row): bool => ($row['nivel'] ?? '') === 'alto'));
    $medio = count(array_filter($lideres, static fn(array $row): bool => ($row['nivel'] ?? '') === 'medio'));
    $baixo = count(array_filter($lideres, static fn(array $row): bool => ($row['nivel'] ?? '') === 'baixo'));

    tituloSecaoPdf($pdf, 'Indicadores de sobrecarga');
    renderizarLinhaKpiPdf($pdf, [
        [
            'label' => 'Lideres avaliados',
            'valor' => (string) $total,
            'fundo' => [230, 241, 251],
            'texto' => [24, 95, 165],
        ],
        [
            'label' => 'Risco alto',
            'valor' => (string) $alto,
            'fundo' => [252, 235, 235],
            'texto' => [163, 45, 45],
        ],
        [
            'label' => 'Risco medio',
            'valor' => (string) $medio,
            'fundo' => [250, 238, 218],
            'texto' => [133, 79, 11],
        ],
        [
            'label' => 'Risco baixo',
            'valor' => (string) $baixo,
            'fundo' => [234, 243, 222],
            'texto' => [46, 125, 50],
        ],
    ]);

    tituloSecaoPdf($pdf, 'Lideres e score de sobrecarga');
    $linhas = [];
    foreach ($lideres as $lider) {
        $nivel = strtoupper((string) ($lider['nivel'] ?? 'baixo'));
        $linhas[] = [
            (string) ($lider['lider_nome'] ?? '-'),
            (string) ($lider['gf_nome'] ?? '-'),
            (string) ((int) ($lider['qtd_membros'] ?? 0)),
            (string) ((int) ($lider['faltas_injust'] ?? 0)),
            (string) ((float) ($lider['score_sobrecarga'] ?? 0)),
            $nivel,
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Lider', 'GF', 'Membros', 'F.Injust.', 'Score', 'Nivel'],
        $linhas,
        [45, 46, 24, 25, 20, 26]
    );
}

function renderizarEngajamentoVisitantesPdf(TCPDF $pdf, array $dados): void
{
    tituloSecaoPdf($pdf, 'Indicadores de visitantes');
    renderizarLinhaKpiPdf($pdf, [
        [
            'label' => 'Visitantes',
            'valor' => (string) ($dados['total'] ?? 0),
            'fundo' => [230, 241, 251],
            'texto' => [24, 95, 165],
        ],
        [
            'label' => 'Taxa de retorno',
            'valor' => (string) ($dados['taxa_retorno'] ?? 0) . '%',
            'fundo' => [234, 243, 222],
            'texto' => [46, 125, 50],
        ],
        [
            'label' => 'Convertidos',
            'valor' => (string) ($dados['convertidos'] ?? 0),
            'fundo' => [236, 243, 254],
            'texto' => [24, 95, 165],
        ],
        [
            'label' => 'Taxa de conversao',
            'valor' => (string) ($dados['taxa_conversao'] ?? 0) . '%',
            'fundo' => [250, 238, 218],
            'texto' => [133, 79, 11],
        ],
    ]);

    tituloSecaoPdf($pdf, 'Visitantes no periodo');
    $linhas = [];
    foreach (($dados['visitantes'] ?? []) as $visitante) {
        $linhas[] = [
            (string) ($visitante['membro_nome'] ?? '-'),
            (string) ($visitante['gf_nome'] ?? '-'),
            formatarDataRelatorio($visitante['primeira_visita'] ?? null),
            formatarDataRelatorio($visitante['ultima_visita'] ?? null),
            (string) ((int) ($visitante['qtd_visitas'] ?? 0)),
            (int) ($visitante['membro_ativo'] ?? 0) === 1 ? 'Sim' : 'Nao',
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Visitante', 'GF', 'Primeira', 'Ultima', 'Visitas', 'Ativo'],
        $linhas,
        [44, 48, 26, 26, 18, 24]
    );
}

function renderizarRaioXVulnerabilidadePdf(TCPDF $pdf, array $dados): void
{
    renderizarInfoGfPdf($pdf, $dados['gf'] ?? null);

    tituloSecaoPdf($pdf, 'Membros em vulnerabilidade');
    $linhas = [];
    foreach (($dados['membros'] ?? []) as $membro) {
        $nivel = strtoupper((string) ($membro['nivel_alerta'] ?? 'observacao'));
        $linhas[] = [
            (string) ($membro['membro_nome'] ?? '-'),
            (string) ($membro['contato'] ?? '-'),
            (string) ((int) ($membro['total_ausencias'] ?? 0)),
            (string) ((int) ($membro['total_just'] ?? 0)),
            (string) ((float) ($membro['taxa_ausencia'] ?? 0)) . '%',
            $nivel,
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Membro', 'Contato', 'Ausencias', 'Just.', '% Ausencia', 'Nivel'],
        $linhas,
        [52, 33, 24, 20, 26, 31],
        true
    );
}

function renderizarHistoricoOracaoPdf(TCPDF $pdf, array $dados): void
{
    renderizarInfoGfPdf($pdf, $dados['gf'] ?? null);

    tituloSecaoPdf($pdf, 'Pedidos de oracao');
    $linhas = [];
    foreach (($dados['pedidos'] ?? []) as $pedido) {
        $linhas[] = [
            formatarDataRelatorio($pedido['data_reuniao'] ?? null),
            (string) ($pedido['membro_nome'] ?? '-'),
            ucfirst(str_replace('_', ' ', (string) ($pedido['status'] ?? '-'))),
            (string) ($pedido['observacao'] ?? '-'),
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Data', 'Membro', 'Status', 'Pedido'],
        $linhas,
        [25, 45, 24, 92]
    );
}

function renderizarPontualidadePdf(TCPDF $pdf, array $dados): void
{
    renderizarInfoGfPdf($pdf, $dados['gf'] ?? null);

    tituloSecaoPdf($pdf, 'Pontualidade e frequencia');
    $linhas = [];
    foreach (($dados['membros'] ?? []) as $membro) {
        $linhas[] = [
            (string) ($membro['membro_nome'] ?? '-'),
            (string) ((int) ($membro['total_reunioes'] ?? 0)),
            (string) ((int) ($membro['presencas'] ?? 0)),
            (string) ((int) ($membro['no_horario'] ?? 0)),
            (string) ((int) ($membro['atrasado'] ?? 0)),
            (string) ((float) ($membro['taxa_presenca'] ?? 0)) . '%',
            (string) ((float) ($membro['taxa_pontualidade'] ?? 0)) . '%',
        ];
    }
    renderizarTabelaPdf(
        $pdf,
        ['Membro', 'Reun.', 'Pres.', 'No horario', 'Atraso', '% Presenca', '% Pontual.'],
        $linhas,
        [47, 17, 16, 22, 18, 32, 34],
        true
    );
}

function gerarPdfRelatorio(array $dados): void
{
    $pdf = new JtroRelatorioPdf('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('JTRO');
    $pdf->SetAuthor('Comunhao Crista Abba');
    $pdf->SetTitle((string) ($dados['titulo'] ?? 'Relatorio JTRO'));
    $pdf->SetSubject('Relatorio JTRO');
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    $pdf->SetMargins(12, 34, 12);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(5);
    $pdf->SetAutoPageBreak(true, 16);

    $pdf->tituloRelatorio = (string) ($dados['titulo'] ?? 'Relatorio');
    $pdf->periodoRelatorio = formatarDataRelatorio($dados['periodo'][0] ?? '') . ' ate ' . formatarDataRelatorio($dados['periodo'][1] ?? '');
    $pdf->usuarioGerador = (string) ($_SESSION['usuario']['nome'] ?? 'Sistema');
    $pdf->escopoRelatorio = (string) ($dados['escopo'] ?? 'Igreja');

    $pdf->AddPage();
    $pdf->SetY(34);

    switch ($dados['tipo'] ?? '') {
        case 'mapeamento_assiduidade':
            renderizarMapeamentoAssiduidadePdf($pdf, $dados);
            break;
        case 'termometro_sobrecarga':
            renderizarTermometroPdf($pdf, $dados);
            break;
        case 'engajamento_visitantes':
            renderizarEngajamentoVisitantesPdf($pdf, $dados);
            break;
        case 'raio_x_vulnerabilidade':
            renderizarRaioXVulnerabilidadePdf($pdf, $dados);
            break;
        case 'historico_oracao':
            renderizarHistoricoOracaoPdf($pdf, $dados);
            break;
        case 'pontualidade_frequencia':
            renderizarPontualidadePdf($pdf, $dados);
            break;
        default:
            tituloSecaoPdf($pdf, 'Sem dados');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(93, 108, 126);
            $pdf->Cell(0, 6, 'Nao foi possivel renderizar o tipo de relatorio solicitado.', 0, 1, 'L');
            break;
    }

    $nomeArquivo = 'jtro_' . ($dados['tipo'] ?? 'relatorio') . '_' . date('Ymd_His') . '.pdf';
    $pdf->Output($nomeArquivo, 'D');
}

$tipos = [
    'mapeamento_assiduidade' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::mapeamentoAssiduidadeGlobal($di, $df),
        'requer_gf' => false,
    ],
    'termometro_sobrecarga' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::termometroSobrecarga($di, $df),
        'requer_gf' => false,
    ],
    'engajamento_visitantes' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::engajamentoVisitantes($di, $df),
        'requer_gf' => false,
    ],
    'raio_x_vulnerabilidade' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::raioXVulnerabilidade($di, $df, (int) $gfId),
        'requer_gf' => true,
    ],
    'historico_oracao' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::historicoPedidosOracao($di, $df, (int) $gfId),
        'requer_gf' => true,
    ],
    'pontualidade_frequencia' => [
        'metodo' => static fn(string $di, string $df, ?int $gfId): array => RelatorioRepository::desempenhoPontualidade($di, $df, (int) $gfId),
        'requer_gf' => true,
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = trim((string) ($_POST['tipo'] ?? ''));
    $dataInicial = trim((string) ($_POST['data_inicial'] ?? ''));
    $dataFinal = trim((string) ($_POST['data_final'] ?? ''));
    $gfIdPost = trim((string) ($_POST['gf_id'] ?? ''));
    $gfId = $gfIdPost !== '' ? (int) $gfIdPost : null;

    if (!isset($tipos[$tipo])) {
        $_SESSION['erro'] = 'Tipo de relatorio invalido.';
        header('Location: /relatorios.php');
        exit;
    }

    if ($dataInicial === '' || $dataFinal === '') {
        $_SESSION['erro'] = 'Informe a data inicial e a data final.';
        header('Location: /relatorios.php');
        exit;
    }

    if (!dataRelatorioValida($dataInicial) || !dataRelatorioValida($dataFinal)) {
        $_SESSION['erro'] = 'Informe datas validas no formato correto.';
        header('Location: /relatorios.php');
        exit;
    }

    if ($dataFinal < $dataInicial) {
        $_SESSION['erro'] = 'A data final precisa ser maior ou igual a data inicial.';
        header('Location: /relatorios.php');
        exit;
    }

    if (($tipos[$tipo]['requer_gf'] ?? false) && (!$gfId || $gfId <= 0)) {
        $_SESSION['erro'] = 'Selecione um Grupo Familiar para este tipo de relatorio.';
        header('Location: /relatorios.php');
        exit;
    }

    if (!class_exists('TCPDF')) {
        $_SESSION['erro'] = 'TCPDF nao encontrado. Instale a dependencia com: composer require tecnickcom/tcpdf';
        header('Location: /relatorios.php');
        exit;
    }

    try {
        $dados = $tipos[$tipo]['metodo']($dataInicial, $dataFinal, $gfId);
        if (($tipos[$tipo]['requer_gf'] ?? false) && !empty($dados['gf']['gf_nome'])) {
            $dados['escopo'] = (string) $dados['gf']['gf_nome'];
        } else {
            $dados['escopo'] = 'Igreja';
        }
        gerarPdfRelatorio($dados);
        exit;
    } catch (Throwable $e) {
        $_SESSION['erro'] = 'Nao foi possivel gerar o PDF: ' . $e->getMessage();
        header('Location: /relatorios.php');
        exit;
    }
}

$gruposFamiliares = RelatorioRepository::listarGruposFamiliares();
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);

$pageTitle = 'Relatorios - JTRO';
require_once __DIR__ . '/../src/Views/relatorios/index.php';
