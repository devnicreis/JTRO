<?php
// Partial compartilhado entre criar.php e editar.php
// Variáveis esperadas: $carta (array ou null), $erro, $mensagem
$isEdicao = !empty($carta);
$dataCarta = $isEdicao ? $carta['data_carta'] : date('Y-m-d');
$pregacaoTitulo = $isEdicao ? ($carta['pregacao_titulo'] ?? '') : '';
$pregacaoLink = $isEdicao ? ($carta['pregacao_link'] ?? '') : '';
$imagemUrl = $isEdicao ? ($carta['imagem_url'] ?? '') : '';
$conteudo = $isEdicao ? ($carta['conteudo'] ?? '') : '';
$conteudoFallback = trim(html_entity_decode(strip_tags((string) $conteudo), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$jaPublicada = $isEdicao && $carta['publicada'];

// Decodifica avisos
$avisosList = [];
if ($isEdicao && !empty($carta['avisos'])) {
    $avisosList = json_decode($carta['avisos'], true) ?? [];
}
?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="page-header">
    <h1><?php echo $isEdicao ? 'Editar Carta Semanal' : 'Nova Carta Semanal'; ?></h1>
    <a href="/cartas.php" style="font-size:13px; color:var(--color-text-muted);">&larr; Voltar para Cartas</a>
</div>

<?php if ($erro !== ''): ?>
    <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo $isEdicao ? '/carta_editar.php' : '/carta_criar.php'; ?>" id="formCarta">
    <?php if ($isEdicao): ?>
        <input type="hidden" name="id" value="<?php echo (int) $carta['id']; ?>">
    <?php endif; ?>

    <div class="carta-secao">
        <div class="campo">
            <label for="data_carta">Data da Carta</label>
            <input type="date" id="data_carta" name="data_carta" required value="<?php echo htmlspecialchars($dataCarta); ?>" style="max-width:200px;">
        </div>
    </div>

    <div class="carta-secao" style="padding:0; overflow:hidden;">
        <img src="/assets/icons/capa-carta.jpg" alt="Capa da Carta Semanal" style="width:100%; max-height:260px; object-fit:cover; display:block; border-radius:var(--radius-lg);" onerror="this.parentElement.style.display='none'">
    </div>

    <div class="carta-secao">
        <div class="carta-secao-titulo">Conte&uacute;do da Carta <span class="escala-hint">(devocional, reflex&atilde;o b&iacute;blica...)</span></div>
        <div id="toolbar-carta-semanal">
            <span class="ql-formats">
                <select class="ql-font">
                    <option selected></option>
                    <option value="serif"></option>
                    <option value="monospace"></option>
                </select>
                <select class="ql-size">
                    <option value="small"></option>
                    <option selected></option>
                    <option value="large"></option>
                    <option value="huge"></option>
                </select>
            </span>
            <span class="ql-formats">
                <button type="button" class="ql-bold"></button>
                <button type="button" class="ql-italic"></button>
                <button type="button" class="ql-underline"></button>
                <button type="button" class="ql-strike"></button>
            </span>
            <span class="ql-formats">
                <button type="button" class="ql-list" value="ordered"></button>
                <button type="button" class="ql-list" value="bullet"></button>
            </span>
            <span class="ql-formats">
                <button type="button" class="ql-link"></button>
                <button type="button" class="ql-clean"></button>
            </span>
        </div>
        <div id="editor-quill" style="min-height:220px; font-size:14px; font-family:'Plus Jakarta Sans',sans-serif;"><?php echo $conteudo; ?></div>
        <textarea id="editor-fallback" rows="10" style="display:none; min-height:220px;" placeholder="Escreva o devocional, reflexão bíblica ou mensagem da semana..."><?php echo htmlspecialchars($conteudoFallback); ?></textarea>
        <input type="hidden" name="conteudo" id="conteudo-hidden">
        <div style="text-align:right; font-size:11px; color:var(--color-text-muted); margin-top:4px;">
            <span id="quill-char-count">0</span> / 8000 caracteres
        </div>
    </div>

    <div class="carta-secao">
        <div class="carta-secao-titulo">Mensagem de Domingo</div>
        <div class="grid">
            <div class="campo">
                <label for="pregacao_titulo">T&iacute;tulo da mensagem</label>
                <input type="text" id="pregacao_titulo" name="pregacao_titulo" placeholder="Ex: A Palavra revelada na escrita" maxlength="120" value="<?php echo htmlspecialchars($pregacaoTitulo); ?>">
                <small>M&aacute;ximo 120 caracteres.</small>
            </div>
            <div class="campo">
                <label for="pregacao_link">Link da prega&ccedil;&atilde;o <span class="escala-hint">(YouTube, Spotify...)</span></label>
                <input type="url" id="pregacao_link" name="pregacao_link" placeholder="https://..." value="<?php echo htmlspecialchars($pregacaoLink); ?>">
            </div>
        </div>
        <div style="background:var(--color-bg); border:1px solid var(--color-border); border-radius:var(--radius-md); padding:12px 14px; font-size:13px; color:var(--color-text-muted); margin-top:4px;">
            <strong style="color:var(--color-text-secondary);">Pergunta fixa que aparecer&aacute; na carta:</strong><br>
            O que aprendeu na mensagem que ouviu? Alguma d&uacute;vida? Algum testemunho neste tema? Se sentiu desafiado em algo? Compartilhe com o grupo suas experi&ecirc;ncias com esta mensagem.
        </div>
    </div>

    <div class="carta-secao">
        <div class="carta-secao-titulo" style="display:flex; justify-content:space-between; align-items:center;">
            Avisos da Semana
            <button type="button" onclick="adicionarAviso()" style="display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:var(--color-green-light); color:var(--color-teal); border:1px solid #9fe1cb; border-radius:var(--radius-md); font-size:12px; font-weight:500; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; min-height:unset;">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 2v12M2 8h12"/></svg>
                Adicionar aviso
            </button>
        </div>
        <div id="lista-avisos">
            <?php /* Avisos existentes são preenchidos via JS no bloco de script abaixo */ ?>
        </div>
        <p id="avisos-vazio" style="color:var(--color-text-muted); font-size:13px; <?php echo !empty($avisosList) ? 'display:none;' : ''; ?>">
            Nenhum aviso adicionado ainda.
        </p>
    </div>

    <div style="display:flex; gap:12px; margin-top:8px; flex-wrap:wrap;">
        <?php if (!$jaPublicada): ?>
            <button type="submit" name="publicar" value="1" class="btn-presenca-oracao" style="max-width:240px;">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 8l4 4 8-8"/></svg>
                Publicar Carta
            </button>
        <?php else: ?>
            <button type="submit" name="publicar" value="1" class="btn-presenca-salvar" style="max-width:240px;">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 5l-7 7-3-3"/></svg>
                Salvar altera&ccedil;&otilde;es
            </button>
        <?php endif; ?>
    </div>
</form>

<template id="tmpl-aviso">
    <div class="carta-aviso-row" data-index="__i__">
        <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap;">
            <div class="campo" style="flex:0 0 120px; margin-bottom:0;">
                <label>Tipo</label>
                <select name="aviso_tipo[]" class="aviso-tipo-select" onchange="toggleDataAviso(this)">
                    <option value="texto">Texto</option>
                    <option value="evento">Evento</option>
                </select>
            </div>
            <div class="campo" style="flex:1; min-width:160px; margin-bottom:0;">
                <label>Nome / T&iacute;tulo</label>
                <input type="text" name="aviso_nome[]" placeholder="Ex: Caminhada de Oração" maxlength="80" required>
            </div>
            <div class="campo aviso-data-campo" style="flex:0 0 150px; margin-bottom:0; display:none;">
                <label>Data do evento</label>
                <input type="date" name="aviso_data[]">
            </div>
            <button type="button" onclick="removerAviso(this)" style="margin-top:22px; background:none; border:none; cursor:pointer; color:var(--color-text-muted); min-height:unset; padding:4px;">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="campo" style="margin-top:8px; margin-bottom:0;">
            <label>Conte&uacute;do</label>
            <textarea name="aviso_conteudo[]" rows="2" maxlength="400" placeholder="Descrição do aviso... (máx. 400 caracteres)"></textarea>
        </div>
    </div>
</template>

<link href="/assets/vendor/quill/quill.snow.css" rel="stylesheet">
<script src="/assets/vendor/quill/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const QUILL_MAX = 8000;
    const QUILL_FORMATS = ['font', 'size', 'bold', 'italic', 'underline', 'strike', 'list', 'bullet', 'link'];

    const formCarta = document.getElementById('formCarta');
    const toolbarContainer = document.getElementById('toolbar-carta-semanal');
    const editorQuill = document.getElementById('editor-quill');
    const editorFallback = document.getElementById('editor-fallback');
    const conteudoHidden = document.getElementById('conteudo-hidden');
    let quillInstance = null;
    let fallbackAtivo = false;

    function atualizarContadorComValor(count) {
        const el = document.getElementById('quill-char-count');
        if (!el) return;
        el.textContent = count;
        el.style.color = count > QUILL_MAX * 0.9
            ? (count > QUILL_MAX ? 'var(--color-red)' : 'var(--color-amber)')
            : '';
    }

    function escaparHtml(texto) {
        return (texto || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function converterTextoFallbackParaHtml(texto) {
        const conteudoTexto = (texto || '').replace(/\r\n?/g, '\n').trim();
        if (conteudoTexto === '') {
            return '';
        }

        return conteudoTexto
            .split(/\n{2,}/)
            .map(function(bloco) {
                return '<p>' + escaparHtml(bloco).replace(/\n/g, '<br>') + '</p>';
            })
            .join('');
    }

    function normalizarHtmlQuill(html) {
        const bruto = (html || '').trim();
        if (bruto === '' || bruto === '<p><br></p>') {
            return '';
        }

        const container = document.createElement('div');
        container.innerHTML = bruto;

        container.querySelectorAll('div').forEach(function(div) {
            const p = document.createElement('p');
            p.innerHTML = div.innerHTML || '<br>';
            div.replaceWith(p);
        });

        return container.innerHTML.trim();
    }

    function normalizarConteudoFallback(texto) {
        const bruto = (texto || '').trim();
        if (bruto === '') {
            return '';
        }

        if (/<[a-z][\s\S]*>/i.test(bruto)) {
            return normalizarHtmlQuill(bruto);
        }

        return converterTextoFallbackParaHtml(bruto);
    }

    function ativarFallbackTextarea() {
        if (editorQuill) {
            editorQuill.style.display = 'none';
        }
        if (!editorFallback) {
            return;
        }

        editorFallback.style.display = '';
        if (!fallbackAtivo) {
            editorFallback.addEventListener('input', function() {
                if (editorFallback.value.length > QUILL_MAX) {
                    editorFallback.value = editorFallback.value.slice(0, QUILL_MAX);
                }
                atualizarContadorComValor(editorFallback.value.trim().length);
            });
            fallbackAtivo = true;
        }
        atualizarContadorComValor(editorFallback.value.trim().length);
    }

    function inicializarEditorQuill() {
        if (quillInstance || typeof window.Quill !== 'function' || !editorQuill || !toolbarContainer) {
            return !!quillInstance;
        }

        try {
            quillInstance = new Quill('#editor-quill', {
                theme: 'snow',
                placeholder: 'Escreva o devocional, reflexão bíblica ou mensagem da semana...',
                modules: {
                    toolbar: '#toolbar-carta-semanal'
                },
                formats: QUILL_FORMATS
            });

            quillInstance.on('text-change', function() {
                if (quillInstance.getLength() - 1 > QUILL_MAX) {
                    quillInstance.deleteText(QUILL_MAX, quillInstance.getLength());
                }
                atualizarContadorComValor(quillInstance.getText().trim().length);
            });

            if (editorFallback) {
                editorFallback.style.display = 'none';
            }
            editorQuill.style.display = '';
            atualizarContadorComValor(quillInstance.getText().trim().length);
            return true;
        } catch (error) {
            quillInstance = null;
            return false;
        }
    }

    function tentarInicializarEditor(tentativa) {
        if (inicializarEditorQuill()) {
            return;
        }

        if (tentativa < 30) {
            window.setTimeout(function() {
                tentarInicializarEditor(tentativa + 1);
            }, 150);
            return;
        }

        ativarFallbackTextarea();
    }

    window.avisoCount = <?php echo count($avisosList); ?>;

    window.adicionarAviso = function() {
        const tmpl = document.getElementById('tmpl-aviso').innerHTML.replace(/__i__/g, window.avisoCount++);
        const div = document.createElement('div');
        div.innerHTML = tmpl;
        document.getElementById('lista-avisos').appendChild(div.firstElementChild);
        const vazio = document.getElementById('avisos-vazio');
        if (vazio) vazio.style.display = 'none';
    };

    window.removerAviso = function(btn) {
        btn.closest('.carta-aviso-row').remove();
        if (!document.querySelector('.carta-aviso-row')) {
            const vazio = document.getElementById('avisos-vazio');
            if (vazio) vazio.style.display = '';
        }
    };

    window.toggleDataAviso = function(sel) {
        const campo = sel.closest('.carta-aviso-row').querySelector('.aviso-data-campo');
        if (campo) campo.style.display = sel.value === 'evento' ? '' : 'none';
    };

    <?php foreach ($avisosList as $av): ?>
    (function() {
        adicionarAviso();
        const rows = document.querySelectorAll('.carta-aviso-row');
        const row = rows[rows.length - 1];
        row.querySelector('[name="aviso_nome[]"]').value = <?php echo json_encode($av['nome'] ?? ''); ?>;
        row.querySelector('[name="aviso_conteudo[]"]').value = <?php echo json_encode($av['conteudo'] ?? ''); ?>;
        const sel = row.querySelector('[name="aviso_tipo[]"]');
        sel.value = <?php echo json_encode($av['tipo'] ?? 'texto'); ?>;
        if (sel.value === 'evento') {
            row.querySelector('.aviso-data-campo').style.display = '';
            row.querySelector('[name="aviso_data[]"]').value = <?php echo json_encode($av['data'] ?? ''); ?>;
        }
    })();
    <?php endforeach; ?>

    tentarInicializarEditor(0);

    if (formCarta && conteudoHidden) {
        formCarta.addEventListener('submit', function() {
            if (quillInstance) {
                conteudoHidden.value = normalizarHtmlQuill(quillInstance.root.innerHTML);
                return;
            }
            conteudoHidden.value = normalizarConteudoFallback(editorFallback ? editorFallback.value : '');
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
