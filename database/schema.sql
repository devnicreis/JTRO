CREATE TABLE IF NOT EXISTS pessoas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    cpf TEXT NOT NULL UNIQUE,
    email TEXT UNIQUE,
    cargo TEXT NOT NULL,
    data_nascimento TEXT,
    estado_civil TEXT NOT NULL DEFAULT 'solteiro',
    nome_conjuge TEXT,
    eh_lider INTEGER NOT NULL DEFAULT 0,
    lider_grupo_familiar INTEGER NOT NULL DEFAULT 0,
    lider_departamento INTEGER NOT NULL DEFAULT 0,
    grupo_familiar_id INTEGER,
    telefone_fixo TEXT,
    telefone_movel TEXT,
    endereco_cep TEXT,
    endereco_logradouro TEXT,
    endereco_numero TEXT,
    endereco_complemento TEXT,
    endereco_bairro TEXT,
    endereco_cidade TEXT,
    endereco_uf TEXT,
    concluiu_integracao INTEGER NOT NULL DEFAULT 0,
    integracao_conclusao_manual INTEGER NOT NULL DEFAULT 0,
    participou_retiro_integracao INTEGER NOT NULL DEFAULT 0,
    motivo_desativacao_tipo TEXT,
    motivo_desativacao_detalhe TEXT,
    motivo_desativacao_texto TEXT,
    ativo INTEGER NOT NULL DEFAULT 1,
    senha_hash TEXT,
    precisa_trocar_senha INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS grupos_familiares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL UNIQUE,
    dia_semana TEXT NOT NULL,
    horario TEXT NOT NULL,
    perfil_grupo TEXT NOT NULL DEFAULT 'casais',
    local_padrao TEXT,
    local_fixo INTEGER NOT NULL DEFAULT 0,
    item_celeiro TEXT,
    domingo_oracao_culto INTEGER,
    motivo_desativacao TEXT,
    ativo INTEGER NOT NULL DEFAULT 1,
    observacoes TEXT CHECK (length(observacoes) <= 255)
);

CREATE TABLE IF NOT EXISTS grupo_lideres (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_familiar_id INTEGER NOT NULL,
    pessoa_id INTEGER NOT NULL,
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS grupo_membros (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_familiar_id INTEGER NOT NULL,
    pessoa_id INTEGER NOT NULL,
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS reunioes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_familiar_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    horario TEXT NOT NULL,
    local TEXT NOT NULL,
    aula_integracao_codigo TEXT,
    motivo_alteracao TEXT,
    observacoes TEXT,
    finalizada INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id)
);

CREATE TABLE IF NOT EXISTS presencas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reuniao_id INTEGER NOT NULL,
    pessoa_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    presente_tempo TEXT,
    justificativa_atraso TEXT,
    ausencia_tipo TEXT,
    justificativa_ausencia TEXT,
    FOREIGN KEY (reuniao_id) REFERENCES reunioes(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pessoa_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expira_em TEXT NOT NULL,
    usado_em TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pessoa_id INTEGER,
    email TEXT NOT NULL,
    ip_address TEXT,
    status TEXT NOT NULL,
    requested_at TEXT NOT NULL,
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE INDEX IF NOT EXISTS idx_password_reset_requests_pessoa_status_data
    ON password_reset_requests(pessoa_id, status, requested_at);

CREATE INDEX IF NOT EXISTS idx_password_reset_requests_email_data
    ON password_reset_requests(email, requested_at);

CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    acao TEXT NOT NULL,
    entidade TEXT NOT NULL,
    entidade_id INTEGER,
    grupo_familiar_id INTEGER,
    reuniao_data TEXT,
    detalhes TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES pessoas(id),
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id)
);

CREATE TABLE IF NOT EXISTS avisos_lidos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    chave_aviso TEXT NOT NULL,
    lido_em TEXT NOT NULL,
    UNIQUE(usuario_id, chave_aviso),
    FOREIGN KEY (usuario_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS pedidos_oracao (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reuniao_id INTEGER NOT NULL,
    pessoa_id INTEGER NOT NULL,
    pedido TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    UNIQUE(reuniao_id, pessoa_id),
    FOREIGN KEY (reuniao_id) REFERENCES reunioes(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS pessoa_integracao_aulas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pessoa_id INTEGER NOT NULL,
    aula_codigo TEXT NOT NULL,
    aula_titulo TEXT NOT NULL,
    data_aula TEXT NOT NULL,
    origem TEXT NOT NULL DEFAULT 'reuniao',
    reuniao_id INTEGER,
    retiro_id INTEGER,
    concluida INTEGER NOT NULL DEFAULT 1,
    UNIQUE(pessoa_id, aula_codigo),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id),
    FOREIGN KEY (reuniao_id) REFERENCES reunioes(id)
);

CREATE TABLE IF NOT EXISTS avisos_sistema (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    chave_aviso TEXT NOT NULL UNIQUE,
    tipo TEXT NOT NULL,
    titulo TEXT NOT NULL,
    mensagem TEXT NOT NULL,
    link TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS retiros_integracao (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    grupo_familiar_id INTEGER NOT NULL,
    pessoa_id INTEGER NOT NULL,
    data_retiro TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS chamados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_sequencial INTEGER NOT NULL,
    numero_ano INTEGER NOT NULL,
    numero_formatado TEXT NOT NULL UNIQUE,
    solicitante_id INTEGER NOT NULL,
    destino TEXT NOT NULL,
    assunto_tipo TEXT NOT NULL,
    assunto_label TEXT NOT NULL,
    tela_problema TEXT,
    pessoa_id INTEGER,
    grupo_familiar_id INTEGER,
    campo_alteracao TEXT,
    motivo_desativacao_tipo TEXT,
    motivo_desativacao_detalhe TEXT,
    motivo_desativacao_texto TEXT,
    resumo_solicitacao TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'em_analise',
    observacao_admin TEXT,
    admin_responsavel_id INTEGER,
    solicitado_em TEXT NOT NULL,
    resolvido_em TEXT,
    FOREIGN KEY (solicitante_id) REFERENCES pessoas(id),
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id),
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id),
    FOREIGN KEY (admin_responsavel_id) REFERENCES pessoas(id)
);

CREATE TABLE IF NOT EXISTS cantina_escalas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data_escala TEXT NOT NULL UNIQUE,
    grupo_familiar_id INTEGER NOT NULL,
    observacoes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (grupo_familiar_id) REFERENCES grupos_familiares(id)
);

CREATE INDEX IF NOT EXISTS idx_cantina_escalas_data
    ON cantina_escalas(data_escala);

CREATE INDEX IF NOT EXISTS idx_cantina_escalas_grupo_data
    ON cantina_escalas(grupo_familiar_id, data_escala);

CREATE TABLE IF NOT EXISTS eventos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    data TEXT NOT NULL,
    hora_inicio TEXT NOT NULL,
    hora_fim TEXT,
    departamento TEXT NOT NULL,
    descricao TEXT,
    recorrente INTEGER NOT NULL DEFAULT 0,
    regra_recorrencia TEXT,
    criado_por INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    FOREIGN KEY (criado_por) REFERENCES pessoas(id)
);

CREATE INDEX IF NOT EXISTS idx_eventos_data
    ON eventos(data);

CREATE INDEX IF NOT EXISTS idx_eventos_departamento_data
    ON eventos(departamento, data);

CREATE TABLE IF NOT EXISTS cartas_semanais (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data_carta TEXT NOT NULL,
    conteudo TEXT,
    pregacao_titulo TEXT,
    pregacao_link TEXT,
    avisos TEXT,
    imagem_url TEXT,
    publicada INTEGER NOT NULL DEFAULT 0,
    criado_por INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    FOREIGN KEY (criado_por) REFERENCES pessoas(id)
);

CREATE INDEX IF NOT EXISTS idx_cartas_semanais_data
    ON cartas_semanais(data_carta DESC);

CREATE INDEX IF NOT EXISTS idx_cartas_semanais_publicada_data
    ON cartas_semanais(publicada, data_carta DESC);
