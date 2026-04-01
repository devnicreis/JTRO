<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<div class="auth-wrapper">
    <article class="auth-card auth-card-wide legal-card">
        <div class="auth-logo">
            <span class="auth-logo-nome">JTRO</span>
            <span class="auth-logo-sub">The Relational Organizer</span>
        </div>

        <h1 class="auth-titulo">Termos de Uso do JTRO</h1>
        <p class="auth-subtitulo">Versão: <?php echo htmlspecialchars($documentoVersao); ?></p>

        <div class="legal-section">
            <h2>1. Objeto</h2>
            <p>O JTRO é uma plataforma de organização e gestão (Software as a Service - SaaS) voltada para igrejas e instituições religiosas. Suas funcionalidades incluem o gerenciamento de grupos familiares, acompanhamento de membros, registros de reuniões, controle de presenças e controles administrativos gerais.</p>
        </div>

        <div class="legal-section">
            <h2>2. Papéis e Responsabilidades</h2>
            <p>Para os fins legais aplicáveis, a Instituição Religiosa (Igreja) que contrata o JTRO atua como Controladora dos dados de seus membros, sendo responsável por coletar o consentimento destes. O JTRO atua exclusivamente como Operador, processando os dados de acordo com as diretrizes da Instituição e os limites destes Termos.</p>
        </div>

        <div class="legal-section">
            <h2>3. Acesso ao Sistema e Credenciais</h2>
            <p>O acesso ao JTRO depende da criação de credenciais individuais (login e senha). Cada usuário é o único responsável:</p>
            <p>Pela guarda, sigilo e uso seguro de sua senha.</p>
            <p>Pela veracidade, exatidão e atualização dos dados inseridos no sistema.</p>
            <p>Por não compartilhar seu acesso com terceiros.</p>
        </div>

        <div class="legal-section">
            <h2>4. Uso Adequado e Proibições</h2>
            <p>O usuário se compromete a utilizar a plataforma de boa-fé. É estritamente proibido:</p>
            <p>Utilizar o JTRO para finalidades ilícitas, discriminatórias ou não autorizadas pela Igreja.</p>
            <p>Tentar acessar dados de outros usuários sem o nível de permissão (privilégio) adequado.</p>
            <p>Praticar qualquer ato que comprometa a segurança, a estabilidade, a disponibilidade ou a integridade da plataforma e de seus bancos de dados (ex: engenharia reversa, injeção de código malicioso). O descumprimento destas regras pode resultar na suspensão ou exclusão imediata da conta do usuário infrator.</p>
        </div>

        <div class="legal-section">
            <h2>5. Propriedade Intelectual</h2>
            <p>Todo o código-fonte, design, logotipos, interfaces e estrutura de banco de dados do JTRO são de propriedade exclusiva de seus desenvolvedores. O uso do sistema não confere ao usuário ou à Instituição contratante nenhum direito de propriedade intelectual sobre a plataforma.</p>
        </div>

        <div class="legal-section">
            <h2>6. Limitação de Responsabilidade</h2>
            <p>O JTRO não se responsabiliza por indisponibilidades temporárias decorrentes de manutenção, falhas na infraestrutura de servidores terceirizados ou eventos de força maior. Da mesma forma, o JTRO não é responsável pelo conteúdo das informações inseridas pelos usuários ou pelas decisões tomadas pela liderança da Instituição com base nos dados do sistema.</p>
        </div>

        <div class="legal-section">
            <h2>7. Atualizações dos Termos</h2>
            <p>Estes Termos de Uso podem ser atualizados periodicamente para refletir novas funcionalidades, ajustes operacionais, obrigações legais ou melhorias de segurança. Mudanças relevantes serão notificadas aos usuários e poderão exigir um novo aceite no acesso seguinte.</p>
        </div>

        <div class="privacy-links">
            <a href="/politica_privacidade.php" class="botao-link botao-secundario">Ver Política de Privacidade</a>
            <a href="/login.php" class="botao-link botao-secundario">Voltar</a>
        </div>
    </article>
</div>

</body>
</html>
