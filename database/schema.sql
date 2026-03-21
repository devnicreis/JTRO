CREATE TABLE IF NOT EXISTS pessoas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    cpf TEXT NOT NULL UNIQUE,
    email TEXT UNIQUE,
    cargo TEXT NOT NULL,
    ativo INTEGER NOT NULL DEFAULT 1,
    senha_hash TEXT,
    precisa_trocar_senha INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS grupos_familiares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL UNIQUE,
    dia_semana TEXT NOT NULL,
    horario TEXT NOT NULL,
    local_padrao TEXT,
    local_fixo INTEGER NOT NULL DEFAULT 0,
    item_celeiro TEXT,
    domingo_oracao_culto INTEGER,
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
 