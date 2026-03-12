CREATE TABLE IF NOT EXISTS pessoas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    cpf TEXT NOT NULL UNIQUE,
    cargo TEXT NOT NULL,
    ativo INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS grupos_familiares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    dia_semana TEXT NOT NULL,
    horario TEXT NOT NULL
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