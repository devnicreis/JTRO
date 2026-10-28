# JTRO: The Relational Organizer

Sistema web para gestão organizacional de igrejas, com foco em grupos familiares, acompanhamento de membros e registro de presenças.

---

## Funcionalidades

- Gestão de pessoas (cadastro, edição, ativação/desativação)
- Gestão de grupos familiares
- Criação automática de reuniões recorrentes
- Registro de presença e ausência
- Pedidos de oração por reunião
- Tela Inicial responsiva (celular, tablet e desktop), com versão para o administrador
  (presença registrada na última reunião prevista de cada GF, contadores, alertas não lidos,
  próximas reuniões e saúde dos GFs) e para o líder (pendências de presença, próxima reunião,
  frequência, membros com faltas seguidas, escalas e últimas reuniões)
- Menu lateral no desktop e barra inferior no celular em todas as telas autenticadas
- Diagnóstico de grupos (presença, faltas, etc.)
- Sistema de avisos (alertas e notificações)
- Auditoria de ações do sistema
- Controle de acesso por perfil (admin/líder)

---

## Arquitetura

O projeto segue uma organização inspirada em MVC:

src/ Core/   -> infraestrutura (Auth, DB, Router) 
Models/      -> entidades 
Repositories -> acesso ao banco 
Services/    -> regras de negócio 
Views/       -> renderização
public/      -> endpoints acessíveis 
database/    -> schema e setup

---

## Tecnologias

- PHP 8+
- SQLite (padrão atual)
- HTML/CSS/JS puro (sem framework)
- Arquitetura modular

---

## Como rodar o projeto

## 1. Clonar
bash
git clone https://github.com/seu-usuario/jtro.git
cd jtro

## 2. Criar configuração local
Copie: config/local.example.php
para: config/local.php

## 3. Inicializar banco
Bash
php database/init.php

## 4. Rodar servidor
Bash
php -S localhost:8000 -t public

Acesse: http://localhost:8000

---

## Segurança
* Arquivos sensíveis não são versionados
* Uso de autenticação por sessão
* Controle de acesso por perfil
* Auditoria de ações críticas

---

## Status do projeto
Em desenvolvimento contínuo.

Próximos passos:
* Levar o novo visual às demais telas (hoje só o menu e a Tela Inicial foram redesenhados;
  as outras páginas ganharam o menu novo, mas mantêm o conteúdo antigo)
* Revisar a responsividade das telas antigas, principalmente tabelas largas no celular
* Separação mais clara entre frontend/backend
* API futura

---

## Motivação
O JTRO: The Relational Organizer é inspirado no conselho de Jetro a Moisés (Êxodo 18), propondo uma organização relacional que distribui responsabilidades e reduz sobrecarga.

---

## Licença
Este projeto está sob a licença MIT.
