# Prompt de Implementação: Tela de Relatórios JTRO — Revisão v2

## Contexto do projeto

O JTRO é um sistema de gestão eclesiástica para a Comunhão Cristã Abba, desenvolvido em **PHP puro com SQLite, sem framework**. A estrutura do projeto segue o padrão:

- `public/` — entry points / controllers (ex: `dashboard.php`, `pessoas.php`)
- `src/Views/<feature>/` — views PHP por funcionalidade
- `src/Repositories/` — acesso a dados com PDO/SQLite
- `src/Core/` — `Auth.php` e `Database.php`
- `storage/database.sqlite` — banco de dados
- `public/assets/icons/` — assets estáticos (incluindo `logo-com-nome-lado.png`)
- `vendor/autoload.php` — autoload do Composer (TCPDF instalado)

Design system:
- Fontes: `Outfit` (títulos, `var(--font-title)`) + `Plus Jakarta Sans` (corpo, `var(--font-body)`)
- Variáveis CSS: `--primary-blue: #185FA5`, `--amber: #854F0B`, `--green: #2e7d32`, `--red: #A32D2D`, `--teal: #0F6E56`, `--purple: #534AB7`, com variantes `*-light`
- Também disponíveis: `--white`, `--border-color`, `--text-primary`, `--text-muted`, `--bg-page`
- As views herdam o layout via `include __DIR__ . '/../layouts/header.php'` e `footer.php`

---

## Arquivos a substituir / criar

Você receberá três arquivos prontos para substituir diretamente:

| Arquivo recebido | Destino no projeto |
|---|---|
| `relatorios_view_index_v2.php` | `src/Views/relatorios/index.php` |
| `RelatorioRepository_v2.php` | `src/Repositories/RelatorioRepository.php` |
| `relatorios_controller_v2.php` | `public/relatorios.php` |

**Apenas copie e substitua.** O conteúdo já está completo e revisado.

---

## Dependência obrigatória: TCPDF

Se ainda não instalado, execute na raiz do projeto (PowerShell / CMD):

```
composer require tecnickcom/tcpdf
```

---

## Migrações necessárias (verificar antes de rodar)

### 1. Coluna `pontualidade` em `presencas`
Se ainda não existir, criar arquivo `migrate_pontualidade.php` na raiz e executar:

```php
<?php
require_once __DIR__ . '/src/Core/Database.php';
$pdo = App\Core\Database::getInstance();
$cols = $pdo->query("PRAGMA table_info(presencas)")->fetchAll(PDO::FETCH_ASSOC);
$nomes = array_column($cols, 'name');
if (!in_array('pontualidade', $nomes)) {
    $pdo->exec("ALTER TABLE presencas ADD COLUMN pontualidade TEXT DEFAULT 'no_horario'");
    echo "Coluna pontualidade adicionada.\n";
} else {
    echo "Coluna pontualidade já existe.\n";
}
```

Executar com: `php migrate_pontualidade.php`

### 2. Colunas em `membros_gf`
Verificar se existem `data_entrada` e `data_saida`:

```php
<?php
require_once __DIR__ . '/src/Core/Database.php';
$pdo = App\Core\Database::getInstance();
$cols = $pdo->query("PRAGMA table_info(membros_gf)")->fetchAll(PDO::FETCH_ASSOC);
$nomes = array_column($cols, 'name');
if (!in_array('data_entrada', $nomes)) {
    $pdo->exec("ALTER TABLE membros_gf ADD COLUMN data_entrada DATE");
    echo "data_entrada adicionada.\n";
}
if (!in_array('data_saida', $nomes)) {
    $pdo->exec("ALTER TABLE membros_gf ADD COLUMN data_saida DATE");
    echo "data_saida adicionada.\n";
}
```

### 3. Colunas de padrão em `grupos_familiares`
Verificar se existem `local_padrao`, `horario_padrao` e `data_inicio`:

```php
<?php
require_once __DIR__ . '/src/Core/Database.php';
$pdo = App\Core\Database::getInstance();
$cols = $pdo->query("PRAGMA table_info(grupos_familiares)")->fetchAll(PDO::FETCH_ASSOC);
$nomes = array_column($cols, 'name');
$adicionar = [
    'local_padrao'   => 'TEXT',
    'horario_padrao' => 'TEXT',
    'data_inicio'    => 'DATE',
];
foreach ($adicionar as $col => $tipo) {
    if (!in_array($col, $nomes)) {
        $pdo->exec("ALTER TABLE grupos_familiares ADD COLUMN {$col} {$tipo}");
        echo "{$col} adicionada em grupos_familiares.\n";
    }
}
```

### 4. Coluna `local` em `reunioes`
Verificar se existe e, se não, criar:

```php
<?php
require_once __DIR__ . '/src/Core/Database.php';
$pdo = App\Core\Database::getInstance();
$cols = $pdo->query("PRAGMA table_info(reunioes)")->fetchAll(PDO::FETCH_ASSOC);
$nomes = array_column($cols, 'name');
if (!in_array('local', $nomes)) {
    $pdo->exec("ALTER TABLE reunioes ADD COLUMN local TEXT");
    echo "Coluna local adicionada em reunioes.\n";
}
```

> **Dica:** todos esses scripts podem ser combinados em um único `migrate_relatorios.php` e executados de uma vez com `php migrate_relatorios.php`.

---

## Ajustes no menu lateral

No arquivo de layout (`src/Views/layouts/header.php` ou equivalente), adicionar o link de Relatórios **apenas para admins**, logo após o item de Carta Semanal ou Agenda:

```php
<?php if (Auth::isAdmin()): ?>
<a class="nav-item <?= ($paginaAtual ?? '') === 'relatorios' ? 'active' : '' ?>" href="relatorios.php">
  <span class="nav-icon">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M9 17H5a2 2 0 00-2 2v0a2 2 0 002 2h14a2 2 0 002-2v0a2 2 0 00-2-2h-4"/>
      <path d="M12 3v14M9 7l3-4 3 4"/>
    </svg>
  </span>
  Relatórios
</a>
<?php endif; ?>
```

No controller `public/relatorios.php`, a variável `$paginaAtual` deve ser definida antes do `include` da view:

```php
$paginaAtual = 'relatorios';
```

---

## O que mudou em relação à versão anterior (resumo para conferência)

### View (`relatorios/index.php`)
- ✅ Tabs com cor cinza grafite (`#334155`) em vez de azul escuro — mais legível sobre fundo claro
- ✅ Formulários dos cards Gerais: campos de data agora ficam em `.rel-form__row` (flex horizontal) e o botão "Gerar PDF" em `.rel-form__actions` em linha separada — corrige bug de posicionamento
- ✅ Removidos os cards de "Engajamento e Retenção de Visitantes" e "Histórico de Orações"
- ✅ Os três relatórios específicos por GF foram fundidos em um único card "Diagnóstico Completo do Grupo Familiar"
- ✅ Validação de datas adicionada inline via JS (sem recarregar a página)

### Repository (`RelatorioRepository.php`)
- ✅ Removidos os métodos `engajamentoVisitantes()`, `historicoPedidosOracao()`, `raioXVulnerabilidade()` e `desempenhoPontualidade()`
- ✅ Adicionado método `diagnosticoGF()` unificado, que retorna: info do GF, KPIs de saúde, crescimento do período, reuniões com flags de anomalia (local/horário fora do padrão), mapa de frequência por membro × reunião, membros vulneráveis e ranking de pontualidade
- ✅ `mapeamentoAssiduidadeGlobal()` agora retorna tendência mês a mês e os top 3 melhores/piores GFs
- ✅ `termometroSobrecarga()` agora retorna também `media_presentes_reuniao` e `tempo_lideranca`

### Controller / PDF (`public/relatorios.php`)
- ✅ Cabeçalho do PDF com cor única azul `#185FA5` (sem gradiente de 3 cores)
- ✅ Logo carregada via `$pdf->Image()` de `public/assets/icons/logo-com-nome-lado.png`; fallback textual se o arquivo não existir
- ✅ Nome da igreja exibido abaixo da logo no cabeçalho
- ✅ Todas as tabelas centralizadas (`'C'`), exceto a primeira coluna de nomes (`'L'`)
- ✅ Larguras de coluna explícitas via `$colWidths[]` em todas as chamadas a `_tabela()` — resolve o corte de conteúdo; o sistema usa colunas proporcionais definidas por relatório
- ✅ Legenda da fórmula do Score no Termômetro de Sobrecarga (caixa âmbar antes da tabela)
- ✅ PDF do Diagnóstico de GF inclui 5 seções: Saúde Geral, Reuniões do Período (com alertas de anomalia), Vulnerabilidade Pastoral, Mapa de Frequência Individual e Ranking de Pontualidade
- ✅ Mapa de frequência usa quebra em múltiplas passagens se o número de reuniões não couber em uma linha

---

## Observações técnicas

1. **Logo PNG**: o controller usa `realpath(__DIR__ . '/assets/icons/logo-com-nome-lado.png')`. Como o controller fica em `public/`, esse caminho resolve para `public/assets/icons/logo-com-nome-lado.png`. Se o arquivo tiver outro nome ou caminho, ajuste essa linha no controller.

2. **Coluna `horario` em `reunioes`**: o sistema original já usa o alias `horario` para `hora_inicio`. A query do `diagnosticoGF()` usa `r.horario` para consistência com o repositório existente. Se o nome da coluna real for diferente, ajuste a query.

3. **Coluna `local` em `reunioes`**: pode não existir ainda — ver migração acima.

4. **`gf.horario_padrao` vs. `gf.horario_inicio`**: o nome exato da coluna de horário padrão no GF deve ser confirmado no schema. O repository usa `gf.horario_padrao`; ajustar se necessário.

5. **Ambiente Windows/PowerShell**: não usar comandos PHP multi-linha com `-r`. Sempre criar arquivos `.php` separados e executar com `php arquivo.php`.

6. **Sessão**: o controller lê `$_SESSION['nome']` para identificar o emissor no rodapé do PDF. Garantir que a sessão está iniciada antes do require do controller (normalmente feito pelo `Auth::requireAuth()`).
