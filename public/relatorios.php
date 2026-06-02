<?php
// public/relatorios.php — v2
// Acessível apenas por administradores.

require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Repositories/RelatorioRepository.php';
require_once __DIR__ . '/../vendor/autoload.php'; // TCPDF via Composer

class JTRORelatorioPdf extends TCPDF
{
    private array $dadosRelatorio = [];

    public function setDadosRelatorio(array $dados): void
    {
        $this->dadosRelatorio = $dados;
    }

    public function Header(): void
    {
        if (!empty($this->dadosRelatorio)) {
            _renderCabecalho($this, $this->dadosRelatorio);
        }
    }

    public function Footer(): void
    {
        $footerHeight = 13;
        $pageWidth = $this->getPageWidth();
        $pageHeight = $this->getPageHeight();
        $topoRodape = $pageHeight - $footerHeight;

        $this->SetFillColor(244, 247, 251);
        $this->Rect(0, $topoRodape, $pageWidth, $footerHeight, 'F');
        $this->SetDrawColor(220, 229, 240);
        $this->Line(14, $topoRodape, $pageWidth - 14, $topoRodape);

        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(122, 143, 166);
        $this->SetXY(14, $topoRodape + 3);
        $this->Cell(98, 5, 'Documento gerado automaticamente pelo JTRO', 0, 0, 'L');
        $this->SetX($pageWidth - 60);
        $this->Cell(46, 5, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

if (method_exists('Auth', 'requireAuth')) {
    Auth::requireAuth();
} else {
    Auth::requireLogin();
}
Auth::requireAdmin();
Auth::requireSenhaAtualizada();

// â”€â”€ GFs para o select â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$gruposFamiliares = RelatorioRepository::listarGruposFamiliares();

// â”€â”€ Processamento do formulÃ¡rio â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo        = $_POST['tipo']         ?? '';
    $dataInicial = $_POST['data_inicial'] ?? '';
    $dataFinal   = $_POST['data_final']   ?? '';
    $gfId        = isset($_POST['gf_id']) ? (int)$_POST['gf_id'] : null;

    // ValidaÃ§Ãµes
    if (empty($tipo) || empty($dataInicial) || empty($dataFinal)) {
        $_SESSION['erro_rel'] = 'Preencha todos os campos obrigatÃ³rios.';
        header('Location: relatorios.php');
        exit;
    }
    if ($dataFinal < $dataInicial) {
        $_SESSION['erro_rel'] = 'A data final deve ser maior ou igual Ã  data inicial.';
        header('Location: relatorios.php');
        exit;
    }

    // Buscar dados
    $dados = match ($tipo) {
        'mapeamento_assiduidade' => RelatorioRepository::mapeamentoAssiduidadeGlobal($dataInicial, $dataFinal),
        'termometro_sobrecarga'  => RelatorioRepository::termometroSobrecarga($dataInicial, $dataFinal),
        'diagnostico_gf'         => $gfId
            ? RelatorioRepository::diagnosticoGF($dataInicial, $dataFinal, $gfId)
            : null,
        default => null,
    };

    if ($dados === null) {
        $_SESSION['erro_rel'] = 'Tipo invÃ¡lido ou GF nÃ£o selecionado.';
        header('Location: relatorios.php');
        exit;
    }

    gerarPDF($dados);
    exit;
}

// â”€â”€ Renderizar a view â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$erro = $_SESSION['erro_rel'] ?? null;
unset($_SESSION['erro_rel']);
$paginaAtual = 'relatorios';

require_once __DIR__ . '/../src/Views/relatorios/index.php';


// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  FUNÃ‡ÃƒO DE GERAÃ‡ÃƒO DE PDF
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

function gerarPDF(array $dados): void
{
    $pdf = new JTRORelatorioPdf('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setDadosRelatorio($dados);

    // â”€â”€ Metadados â”€â”€
    $pdf->SetCreator('JTRO');
    $pdf->SetAuthor('ComunhÃ£o CristÃ£ Abba');
    $pdf->SetTitle($dados['titulo']);
    $pdf->SetSubject('RelatÃ³rio JTRO');

    $pdf->SetMargins(14, 48, 14);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);

    $pdf->AddPage();
    $pdf->SetY(48);

    // â”€â”€ ConteÃºdo por tipo â”€â”€
    switch ($dados['tipo']) {
        case 'mapeamento_assiduidade': _renderMapeamento($pdf, $dados); break;
        case 'termometro_sobrecarga':  _renderTermometro($pdf, $dados);  break;
        case 'diagnostico_gf':         _renderDiagnosticoGF($pdf, $dados); break;
    }
    $filename = 'jtro_' . $dados['tipo'] . '_' . date('Ymd_Hi') . '.pdf';
    $pdf->Output($filename, 'D');
}

// ── Cabeçalho do PDF ──────────────────────────────────────────────────────

function _renderCabecalho(TCPDF $pdf, array $dados): void
{
    // Fundo azul único (#185FA5)
    $pdf->SetFillColor(24, 95, 165);
    $pdf->Rect(0, 0, 210, 36, 'F');

    // Logo como imagem (canto superior esquerdo)
    $logoPath = realpath(__DIR__ . '/assets/icons/logo-com-nome-lado.png');
    if ($logoPath && file_exists($logoPath)) {
        // Imagem: x=12, y=6, w=50 (altura automática)
        $pdf->Image($logoPath, 12, 6, 50, 0, 'PNG');
    } else {
        // Fallback textual se a imagem não existir
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(12, 9);
        $pdf->Cell(60, 8, 'JTRO', 0, 0, 'L');
    }

    // Nome da igreja abaixo da logo
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(200, 225, 255);
    $pdf->SetXY(12, 28);
    $pdf->Cell(70, 4, 'Comunhão Cristã Abba Fazenda Rio Grande', 0, 0, 'L');

    // Título do relatório + período (lado direito)
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(90, 10);
    $pdf->Cell(106, 7, $dados['titulo'], 0, 0, 'R');

    $periodo = _fmtData($dados['periodo'][0]) . ' – ' . _fmtData($dados['periodo'][1]);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(200, 225, 255);
    $pdf->SetXY(90, 19);
    $pdf->Cell(106, 5, 'Período: ' . $periodo, 0, 0, 'R');

    // ── Barra de metadados ──
    $pdf->SetFillColor(244, 247, 251);
    $pdf->Rect(0, 36, 210, 10, 'F');
    $pdf->SetDrawColor(220, 229, 240);
    $pdf->Line(0, 36, 210, 36);

    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(90, 106, 126);
    $pdf->SetXY(14, 38);
    $pdf->Cell(55, 5, 'Emitido em: ' . date('d/m/Y') . ' às ' . date('H:i'), 0, 0, 'L');
    $pdf->SetX(75);
    $usuarioSessao = $_SESSION['usuario']['nome'] ?? null;
    $pdf->Cell(65, 5, 'Por: ' . ($usuarioSessao ?: 'Administrador'), 0, 0, 'L');
    $pdf->SetX(148);
    $pdf->Cell(48, 5, 'Uso restrito à liderança', 0, 0, 'R');
}

// ── Render: Mapeamento de Assiduidade ─────────────────────────────────────

function _renderMapeamento(TCPDF $pdf, array $d): void
{
    $kpi = $d['kpis'];

    // KPI cards (4 colunas)
    _secao($pdf, 'Indicadores do Período');
    _kpiRow($pdf, [
        ['Membros Ativos',        $kpi['total_membros'] ?? 0,   [235,242,251], [24,95,165]],
        ['Taxa de Presença',      ($kpi['taxa_presenca'] ?? 0).'%', [232,245,233], [46,125,50]],
        ['Faltas Justificadas',   $kpi['faltas_just'] ?? 0,     [255,243,224], [133,79,11]],
        ['Faltas Injustificadas', $kpi['faltas_injust'] ?? 0,   [253,236,234], [163,45,45]],
    ]);

    // Destaques: top 3 melhores / piores
    _secao($pdf, 'Destaques do Período');
    $melhores = array_map(fn($g) => [$g['gf_nome'], $g['taxa_presenca'] . '%'], $d['top_melhores']);
    $piores   = array_map(fn($g) => [$g['gf_nome'], $g['taxa_presenca'] . '%'], $d['top_piores']);

    $yBase = $pdf->GetY();
    $pdf->SetY($yBase);

    // Melhores (esquerda)
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->SetXY(14, $yBase);
    $pdf->Cell(90, 5, 'Top 3 Melhores Assiduidades', 0, 1, 'L');
    _tabelaSimples($pdf, ['Grupo Familiar', '% Presença'], $melhores, 90, 14, false, true, false, [72, 18]);

    // Piores (direita)
    $yBase2 = $yBase;
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(163, 45, 45);
    $pdf->SetXY(110, $yBase2);
    $pdf->Cell(96, 5, 'Top 3 Menores Assiduidades', 0, 1, 'L');
    _tabelaSimples($pdf, ['Grupo Familiar', '% Presença'], $piores, 96, 110, false, false, true, [76, 20]);

    $pdf->SetY(max($pdf->GetY(), $yBase + 30) + 4);

    // Assiduidade mensal
    _secao($pdf, 'Evolução Mensal de Assiduidade');
    $headers = ['Mês', 'Presenças', 'F. Just.', 'F. Injust.', 'Total', '% Presença', 'Tendência'];
    $rows = [];
    foreach ($d['mensal'] as $m) {
        $tend = $m['tendencia'] === null ? '–'
            : ($m['tendencia'] > 0 ? '↑ +' . $m['tendencia'] . '%' : ($m['tendencia'] < 0 ? '↓ ' . $m['tendencia'] . '%' : '→ 0%'));
        $rows[] = [
            _fmtMes($m['mes']),
            $m['presencas'],
            $m['just'],
            $m['injust'],
            $m['total'],
            $m['taxa_presenca'] . '%',
            $tend,
        ];
    }
    _tabela($pdf, $headers, $rows, colWidths: [20, 22, 22, 22, 18, 24, 24]);

    // Ranking por GF
    _secao($pdf, 'Assiduidade por Grupo Familiar');
    $h2 = ['Grupo Familiar', 'Membros', 'Reuniões', 'Presenças', 'F.Just.', 'F.Injust.', '% Pres.'];
    $r2 = [];
    foreach ($d['por_gf'] as $gf) {
        $r2[] = [
            $gf['gf_nome'],
            $gf['qtd_membros'],
            $gf['qtd_reunioes'],
            $gf['presencas'],
            $gf['just'],
            $gf['injust'],
            $gf['taxa_presenca'] . '%',
        ];
    }
    _tabela($pdf, $h2, $r2, semanatica: true, colWidths: [48, 18, 18, 18, 18, 18, 22]);
}

// ── Render: Termômetro de Sobrecarga ──────────────────────────────────────

function _renderTermometro(TCPDF $pdf, array $d): void
{
    // Caixa de legenda da fórmula
    $pdf->SetFillColor(255, 248, 235);
    $pdf->SetDrawColor(245, 192, 120);
    $yL = $pdf->GetY();
    $pdf->Rect(14, $yL, 182, 14, 'DF');
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(133, 79, 11);
    $pdf->SetXY(17, $yL + 2);
    $pdf->Cell(0, 5, 'COMO É CALCULADO O SCORE DE SOBRECARGA', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY(17, $yL + 7);
    $pdf->Cell(0, 5, 'Score = (Nº de Membros × 0,4) + (Faltas Injustificadas × 0,6)', 0, 1, 'L');
    $pdf->SetY($pdf->GetY() + 4);

    // Escala de referência dos níveis
    $yN = $pdf->GetY();
    $niveis = [
        ['BAIXO',  [232,245,233], [46,125,50],  'Score <= 6'],
        ['MÉDIO',  [255,243,224], [133,79,11],  'Score 7–12'],
        ['ALTO',   [253,236,234], [163,45,45],  'Score > 12'],
    ];
    $xN = 14;
    foreach ($niveis as [$label, $bg, $cor, $desc]) {
        $pdf->SetFillColor(...$bg);
        $pdf->Rect($xN, $yN, 58, 10, 'F');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...$cor);
        $pdf->SetXY($xN + 2, $yN + 1);
        $pdf->Cell(30, 4, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(90, 106, 126);
        $pdf->SetXY($xN + 2, $yN + 5.5);
        $pdf->Cell(54, 4, $desc, 0, 0, 'L');
        $xN += 62;
    }
    $pdf->SetY($yN + 16);

    // Tabela
    _secao($pdf, 'Análise de Carga Pastoral por Líder');
    $headers = ['GF', 'Membros', 'Reuniões', 'Presenças', 'Méd/Reun.', 'F.Injust.', 'Score', 'Nível'];
    $rows = [];
    foreach ($d['lideres'] as $l) {
        $nivel = match($l['nivel']) {
            'alto'  => 'ALTO',
            'medio' => 'MÉDIO',
            default => 'BAIXO',
        };
        $rows[] = [
            $l['gf_nome'],
            $l['qtd_membros'],
            $l['qtd_reunioes'],
            $l['presencas'],
            $l['media_presentes_reuniao'],
            $l['faltas_injust'],
            $l['score_sobrecarga'],
            $nivel,
        ];
    }
    _tabela($pdf, $headers, $rows, colWidths: [58, 16, 16, 16, 18, 18, 18, 22]);
}
// ── Render: Diagnóstico Completo do GF ────────────────────────────────────

function _renderDiagnosticoGF(TCPDF $pdf, array $d): void
{
    $gf  = $d['gf'];
    $kpi = $d['kpis'];
    $indicadores = $d['indicadores'] ?? [];

    // Banner do GF
    $pdf->SetFillColor(232, 248, 245);
    $pdf->SetDrawColor(168, 218, 207);
    $y0 = $pdf->GetY();
    $pdf->Rect(14, $y0, 182, 12, 'DF');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(15, 110, 86);
    $pdf->SetXY(17, $y0 + 2);
    $pdf->Cell(100, 5, 'Grupo Familiar: ' . ($gf['gf_nome'] ?? ''), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 8.5);
    $pdf->SetTextColor(90, 106, 126);
    $pdf->Cell(79, 5, 'Líder: ' . ($gf['lider_nome'] ?? ''), 0, 0, 'R');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY(17, $y0 + 7);
    $local   = $gf['local_padrao']   ? 'Local padrão: ' . $gf['local_padrao']   : '';
    $horario = $gf['horario_padrao'] ? '  |  Horário padrão: ' . $gf['horario_padrao'] : '';
    $pdf->Cell(182, 4, $local . $horario, 0, 1, 'L');
    $pdf->SetY($pdf->GetY() + 4);

    // Seção 1: Saúde Geral
    _secao($pdf, '1. Saúde Geral do Grupo');
    _kpiRow($pdf, [
        ['Membros Ativos', $kpi['qtd_membros'] ?? 0, [232,248,245], [15,110,86]],
        ['Taxa de Presença', ($kpi['taxa_presenca'] ?? 0) . '%', ($kpi['taxa_presenca'] ?? 0) >= 75 ? [232,245,233] : [253,236,234], ($kpi['taxa_presenca'] ?? 0) >= 75 ? [46,125,50] : [163,45,45]],
        ['Total de Reuniões no período', $indicadores['total_reunioes'] ?? ($kpi['qtd_reunioes'] ?? 0), [235,242,251], [24,95,165]],
        ['Líderes no GF', $indicadores['lideres_no_gf'] ?? 0, [247,243,255], [83,74,183]],
        ['Filhos (de 0 a 9 anos)', $indicadores['filhos_no_gf'] ?? 0, [245,243,255], [99,102,241]],
    ]);

    // Item celeiro e domingo
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(90, 106, 126);
    $celeiro = $gf['item_celeiro'] ? 'Item Celeiro: ' . $gf['item_celeiro'] : '';
    $domingo = $gf['domingo_oracao_culto'] ? '  |  Domingo de Oração/Culto: ' . $gf['domingo_oracao_culto'] : '';
    if ($celeiro || $domingo) {
        $pdf->SetXY(14, $pdf->GetY() - 1);
        $pdf->Cell(182, 5, $celeiro . $domingo, 0, 1, 'L');
    }
    $pdf->SetY($pdf->GetY() + 2);

    // Seção 2: Reuniões do Período
    _secao($pdf, '2. Reuniões Realizadas no Período');
    $hReu = ['Data', 'Local', 'Horário', 'Presentes', 'F. Just.', 'F. Injust.', '% Pres.', 'Alertas'];
    $rReu = [];
    foreach ($d['reunioes'] as $r) {
        $alertas = [];
        if ($r['local_anomalo'])   $alertas[] = 'Local';
        if ($r['horario_anomalo']) $alertas[] = 'Horário';
        $rReu[] = [
            _fmtData($r['data_reuniao']),
            $r['local_reuniao'] ?: $r['local_padrao'] ?: '–',
            $r['hora_inicio_reuniao'] ?: $r['horario_padrao'] ?: '–',
            $r['qtd_presentes'],
            $r['qtd_just'],
            $r['qtd_ausentes'],
            $r['taxa_presenca'] . '%',
            $alertas ? implode(' ', $alertas) : 'N/C',
        ];
    }
    _tabela($pdf, $hReu, $rReu, colWidths: [20, 36, 18, 18, 14, 18, 18, 24]);

    // Nota de alerta se houver reuniões anômalas
    $anomalas = array_filter($d['reunioes'], fn($r) => $r['local_anomalo'] || $r['horario_anomalo']);
    if (!empty($anomalas)) {
        $pdf->SetFillColor(255, 248, 235);
        $pdf->SetDrawColor(245, 192, 120);
        $yA = $pdf->GetY();
        $pdf->Rect(14, $yA, 182, 9, 'DF');
        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(133, 79, 11);
        $pdf->SetXY(17, $yA + 2.5);
        $pdf->Cell(0, 5, 'ATENÇÃO: ' . count($anomalas) . ' reunião(ões) ocorreu(eram) fora do padrão registrado (local ou horário diferente). Verifique com o líder.', 0, 1, 'L');
        $pdf->SetY($pdf->GetY() + 2);
    }

    // Seção 3: Vulnerabilidade Pastoral
    _secao($pdf, '3. Membros em Situação de Vulnerabilidade Pastoral');
    if (empty($d['vulneraveis'])) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(90, 106, 126);
        $pdf->Cell(0, 6, 'Nenhum membro com padrão de ausência elevado neste período.', 0, 1, 'L');
    } else {
    $hVul = ['Membro', 'Contato', 'Ausências', '% Pres.', 'Nível', 'Observações'];
        $rVul = [];
        foreach ($d['vulneraveis'] as $m) {
            $nivel = match($m['nivel_alerta']) {
                'urgente' => 'URGENTE',
                'atencao' => 'ATENÇÃO',
                default   => 'OBS.',
            };
            $rVul[] = [
                $m['membro_nome'],
                $m['contato'] ?? '–',
                $m['ausencias'],
                $m['taxa_presenca'] . '%',
                $nivel,
                mb_strimwidth($m['observacoes'] ?? '–', 0, 55, '...'),
            ];
        }
        _tabela($pdf, $hVul, $rVul, colWidths: [34, 26, 16, 18, 20, 68], alignments: ['L', 'L', 'C', 'C', 'C', 'L']);
    }
    $pdf->SetY($pdf->GetY() + 2);

    // Seção 4: Mapa de Frequência Individual
    _secao($pdf, '4. Mapa de Frequência Individual por Reunião');

    $reunioes  = $d['reunioes'];
    $membros   = $d['membros'];
    $nReu      = count($reunioes);

    if ($nReu === 0 || empty($membros)) {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(90, 106, 126);
        $pdf->Cell(0, 6, 'Sem dados de presença para o período selecionado.', 0, 1, 'L');
    } else {
        $totalW   = 182;
        $nomeW    = 42;
        $pctW     = 22;
        $dispW    = $totalW - $nomeW - $pctW;
        $celW     = min(14, max(7, round($dispW / $nReu, 1)));
        $maxCols  = max(1, (int)floor($dispW / $celW));
        $chunks   = array_chunk($reunioes, $maxCols);

        foreach ($chunks as $chunk) {
            $pdf->SetFillColor(24, 95, 165);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 6.5);
            $pdf->SetX(14);
            $pdf->Cell($nomeW, 6, 'Membro', 0, 0, 'C', true);
            foreach ($chunk as $r) {
                $pdf->Cell($celW, 6, _fmtDataCurto($r['data_reuniao']), 0, 0, 'C', true);
            }
            $pdf->Cell($pctW, 6, '% Pres.', 0, 0, 'C', true);
            $pdf->Ln();

            foreach ($membros as $i => $mb) {
                if ($pdf->GetY() > 268) {
                    $pdf->AddPage();
                }
                $bg = $i % 2 === 0 ? [255, 255, 255] : [244, 247, 251];
                $pdf->SetFillColor(...$bg);
                $pdf->SetFont('helvetica', '', 6.5);
                $pdf->SetTextColor(26, 38, 54);
                $pdf->SetX(14);
                $pdf->Cell($nomeW, 5.5, mb_strimwidth($mb['membro_nome'], 0, 28, '..'), 0, 0, 'L', true);

                foreach ($chunk as $r) {
                    $rid = $r['id'];
                    $status = $mb['mapa'][$rid] ?? null;
                    [$cor, $simbolo] = match($status) {
                        'presente'          => [[46,125,50], 'P'],
                        'falta_justificada' => [[133,79,11], 'J'],
                        'ausente'           => [[163,45,45], 'A'],
                        default             => [[180,180,180], '–'],
                    };
                    $pdf->SetTextColor(...$cor);
                    $pdf->Cell($celW, 5.5, $simbolo, 0, 0, 'C', true);
                }

                $taxaPres = $mb['taxa_presenca'];
                [$corP] = $taxaPres >= 85 ? [[46,125,50]] : ($taxaPres >= 70 ? [[24,95,165]] : ($taxaPres >= 50 ? [[133,79,11]] : [[163,45,45]]));
                $pdf->SetTextColor(...$corP);
                $pdf->Cell($pctW, 5.5, $taxaPres . '%', 0, 0, 'C', true);
                $pdf->Ln();
            }

            $pdf->SetY($pdf->GetY() + 2);
            $pdf->SetFont('helvetica', '', 6.5);
            $legendas = [
                [[46,125,50],  'P  Presente'],
                [[133,79,11],  'J  Falta Justificada'],
                [[163,45,45],  'A  Ausente'],
                [[180,180,180],'–  Sem Registro'],
            ];
            $xLeg = 14;
            foreach ($legendas as [$cor, $txt]) {
                $pdf->SetTextColor(...$cor);
                $pdf->SetXY($xLeg, $pdf->GetY());
                $pdf->Cell(38, 4, $txt, 0, 0, 'L');
                $xLeg += 38;
            }
            $pdf->Ln(6);
        }
    }

    // Seção 5: Ranking de Pontualidade
    _secao($pdf, '5. Ranking de Pontualidade e Frequência');
    $hPont = ['Membro', 'Reuniões', 'Presenças', 'No Horário', 'Atrasado', 'Ausências', '% Presença', '% Pontual'];
    $rPont = [];
    $membrosOrdenados = $d['membros'];
    usort($membrosOrdenados, fn($a, $b) => $b['taxa_presenca'] <=> $a['taxa_presenca']);
    foreach ($membrosOrdenados as $m) {
        $rPont[] = [
            $m['membro_nome'],
            $m['total_reunioes'],
            $m['presencas'],
            $m['no_horario'],
            $m['atrasado'],
            $m['ausencias'],
            $m['taxa_presenca'] . '%',
            $m['taxa_pontualidade'] . '%',
        ];
    }
    _tabela($pdf, $hPont, $rPont, semanatica: true, colWidths: [38, 16, 16, 18, 16, 16, 22, 20]);
}

// ══════════════════════════════════════════════════════════════════════════
//  HELPERS VISUAIS DO PDF
// ══════════════════════════════════════════════════════════════════════════

function _secao(TCPDF $pdf, string $titulo): void
{
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(24, 95, 165);
    $y = $pdf->GetY() + 3;
    $pdf->SetY($y);
    $pdf->Cell(0, 5, mb_strtoupper($titulo), 'B', 1, 'L');
    $pdf->SetDrawColor(24, 95, 165);
    $pdf->SetY($pdf->GetY() + 2);
}

function _kpiRow(TCPDF $pdf, array $kpis): void
{
    $y   = $pdf->GetY() + 1;
    $n   = count($kpis);
    $gap = 2;
    $w   = (182 - ($n - 1) * $gap) / $n;

    foreach ($kpis as $i => [$label, $valor, $bg, $cor]) {
        $cx = 14 + $i * ($w + $gap);
        $pdf->SetFillColor(...$bg);
        $pdf->Rect($cx, $y, $w, 17, 'F');
        $pdf->SetFont('helvetica', 'B', 17);
        $pdf->SetTextColor(...$cor);
        $pdf->SetXY($cx, $y + 1.5);
        $pdf->Cell($w, 8, (string)$valor, 0, 0, 'C');
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->SetTextColor(90, 106, 126);
        $pdf->SetXY($cx, $y + 10.5);
        $pdf->Cell($w, 4.5, $label, 0, 0, 'C');
    }
    $pdf->SetY($y + 21);
}

/**
 * Tabela com larguras de coluna explícitas e alinhamento centrado.
 * Linha de cabeçalho centralizada; dados centralizados exceto 1ª coluna (nomes → esquerda).
 *
 * @param array      $colWidths  Larguras em mm para cada coluna (deve somar <= 182)
 * @param bool       $semanatica Colorir última coluna por faixa de percentual
 */
function _tabela(
    TCPDF  $pdf,
    array  $headers,
    array  $rows,
    bool   $semanatica  = false,
    array  $colWidths   = [],
    array  $alignments  = []
): void {
    $n = count($headers);

    // Se não forem passadas larguras, distribui igualmente
    if (empty($colWidths)) {
        $w = round(182 / $n, 1);
        $colWidths = array_fill(0, $n, $w);
    }

    $renderHeader = function () use ($pdf, $headers, $colWidths, $n, $alignments): void {
        $pdf->SetFillColor(24, 95, 165);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetX(14);
        foreach ($headers as $i => $h) {
            $align = $alignments[$i] ?? 'C';
            $pdf->Cell($colWidths[$i], 6, $h, 0, 0, $align, true);
        }
        $pdf->Ln();
    };

    $renderHeader();
    $pdf->SetFont('helvetica', '', 6.5);

    // Dados
    foreach ($rows as $ri => $row) {
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
            $renderHeader();
            $pdf->SetFont('helvetica', '', 6.5);
        }

        $bg = $ri % 2 === 0 ? [255, 255, 255] : [244, 247, 251];
        $pdf->SetFillColor(...$bg);
        $pdf->SetTextColor(26, 38, 54);
        $pdf->SetX(14);

        foreach ($row as $ci => $cell) {
            // Semântica na última coluna
            if ($semanatica && $ci === count($row) - 1) {
                $val = (float)$cell;
                $corSem = $val >= 85 ? [46,125,50] : ($val >= 70 ? [24,95,165] : ($val >= 50 ? [133,79,11] : [163,45,45]));
                $pdf->SetTextColor(...$corSem);
            }
            // Primeira coluna: esquerda; demais: centro
            $align = $alignments[$ci] ?? ($ci === 0 ? 'L' : 'C');
            $pdf->Cell($colWidths[$ci], 5.5, (string)$cell, 0, 0, $align, true);
            $pdf->SetTextColor(26, 38, 54);
        }
        $pdf->Ln();
    }
    $pdf->SetY($pdf->GetY() + 3);
}

/** Tabela simples para blocos laterais (top 3) */
function _tabelaSimples(
    TCPDF  $pdf,
    array  $headers,
    array  $rows,
    float  $totalW,
    float  $startX,
    bool   $newLine   = false,
    bool   $corVerde  = false,
    bool   $corVermelha = false,
    array  $colWidths = [],
    array  $alignments = []
): void {
    $n  = count($headers);
    if (empty($colWidths)) {
        $cw = round($totalW / $n, 1);
        $colWidths = array_fill(0, $n, $cw);
    }

    $renderHeader = function () use ($pdf, $headers, $startX, $colWidths, $alignments): void {
        $pdf->SetFillColor(24, 95, 165);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetX($startX);
        foreach ($headers as $i => $h) {
            $align = $alignments[$i] ?? 'C';
            $pdf->Cell($colWidths[$i], 5, $h, 0, 0, $align, true);
        }
        $pdf->Ln();
    };

    $renderHeader();
    $pdf->SetFont('helvetica', '', 6.5);

    foreach ($rows as $ri => $row) {
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
            $renderHeader();
            $pdf->SetFont('helvetica', '', 6.5);
        }
        $bg = $ri % 2 === 0 ? [255,255,255] : [244,247,251];
        $pdf->SetFillColor(...$bg);
        $pdf->SetX($startX);
        foreach ($row as $ci => $cell) {
            if ($ci === count($row) - 1) {
                $cor = $corVerde ? [46,125,50] : ($corVermelha ? [163,45,45] : [26,38,54]);
                $pdf->SetTextColor(...$cor);
            } else {
                $pdf->SetTextColor(26, 38, 54);
            }
            $align = $alignments[$ci] ?? ($ci === 0 ? 'L' : 'C');
            $pdf->Cell($colWidths[$ci], 5, (string)$cell, 0, 0, $align, true);
        }
        $pdf->Ln();
    }
}

// ── Utilitários de formatação ────────────────────────────────────────────

function _fmtData(string $data): string
{
    if (!$data) return '–';
    $d = \DateTime::createFromFormat('Y-m-d', $data);
    return $d ? $d->format('d/m/Y') : $data;
}

function _fmtDataCurto(string $data): string
{
    if (!$data) return '–';
    $d = \DateTime::createFromFormat('Y-m-d', $data);
    return $d ? $d->format('d/m') : $data;
}

function _fmtMes(string $mes): string
{
    $m = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
          '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
    [$ano, $mm] = explode('-', $mes);
    return ($m[$mm] ?? $mm) . '/' . substr($ano, 2);
}
