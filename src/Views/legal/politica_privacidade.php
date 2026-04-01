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

        <h1 class="auth-titulo">Política de Privacidade do JTRO</h1>
        <p class="auth-subtitulo">Versão: <?php echo htmlspecialchars($documentoVersao); ?></p>

        <div class="legal-section">
            <h2>1. Introdução e Papéis</h2>
            <p>O JTRO valoriza a privacidade dos seus usuários. Esta Política descreve como tratamos os dados pessoais inseridos na plataforma. O JTRO atua como Operador dos dados, realizando o tratamento em nome da Instituição Religiosa (Controladora), à qual o usuário (Titular) está vinculado.</p>
        </div>

        <div class="legal-section">
            <h2>2. Dados Tratados e Bases Legais (Art. 7º e 11º da LGPD)</h2>
            <p>O JTRO coleta e processa as seguintes categorias de dados, com base nas seguintes justificativas legais:</p>
            <p>Dados Cadastrais e de Contato: Nome, e-mail, telefone, CPF, endereço e data de nascimento. Base Legal: Execução de contrato (fornecimento do serviço) e Consentimento.</p>
            <p>Dados Operacionais e de Interação: Perfil de acesso, grupos familiares aos quais pertence, registros de reuniões, histórico de presenças e comunicações internas. Base Legal: Legítimo interesse da Controladora no acompanhamento de seus membros e Consentimento.</p>
            <p>Dados Sensíveis (Convicção Religiosa): Devido à natureza do sistema (gestão de igrejas), as informações cadastradas no JTRO podem revelar a filiação ou convicção religiosa do Titular. Base Legal: Consentimento específico e destacado do Titular.</p>
            <p>Logs de Acesso: Endereço IP, data, hora, ações no sistema e informações do dispositivo. Base Legal: Cumprimento de obrigação legal (Art. 15 do Marco Civil da Internet).</p>
        </div>

        <div class="legal-section">
            <h2>3. Finalidades do Tratamento</h2>
            <p>Os dados coletados têm propósitos estritamente operacionais e administrativos:</p>
            <p>Autenticar o acesso e garantir a segurança da conta do usuário.</p>
            <p>Permitir à liderança da Igreja a gestão de membros, grupos familiares e eventos.</p>
            <p>Facilitar a comunicação (emissão de avisos e notificações).</p>
            <p>Realizar auditorias internas de segurança e prestar suporte técnico.</p>
        </div>

        <div class="legal-section">
            <h2>4. Compartilhamento de Dados</h2>
            <p>O JTRO não vende, aluga ou monetiza os dados de seus usuários. O compartilhamento ocorre apenas de forma restrita e essencial para:</p>
            <p>Fornecedores de Infraestrutura: Provedores de hospedagem em nuvem e servidores de banco de dados onde o sistema está alocado, os quais também estão sujeitos a rigorosos padrões de segurança e privacidade.</p>
            <p>Obrigações Legais: Para cumprimento de ordens judiciais ou solicitações de autoridades competentes.</p>
        </div>

        <div class="legal-section">
            <h2>5. Segurança da Informação</h2>
            <p>Empregamos medidas técnicas e administrativas compatíveis com os padrões de mercado para proteger os dados pessoais contra acessos não autorizados, vazamentos, perda ou alteração. Isso inclui criptografia de senhas, controle rigoroso de acesso baseado em funções (RBAC) e monitoramento de atividades suspeitas.</p>
        </div>

        <div class="legal-section">
            <h2>6. Retenção dos Dados</h2>
            <p>Os dados pessoais serão mantidos no JTRO pelo tempo necessário para cumprir as finalidades para as quais foram coletados, ou enquanto o vínculo do usuário com a Instituição e da Instituição com o JTRO estiver ativo. Em caso de encerramento da conta, os logs de acesso serão guardados por no mínimo 6 (seis) meses, conforme exigência do Marco Civil da Internet, sendo os demais dados excluídos ou anonimizados, exceto se houver outra obrigação legal de retenção.</p>
        </div>

        <div class="legal-section">
            <h2>7. Direitos do Titular (Art. 18 da LGPD)</h2>
            <p>Você, como Titular dos dados, possui o direito de solicitar:</p>
            <p>A confirmação da existência de tratamento e o acesso aos seus dados.</p>
            <p>A correção de dados incompletos, inexatos ou desatualizados.</p>
            <p>A anonimização, bloqueio ou eliminação de dados desnecessários.</p>
            <p>A revogação do seu consentimento a qualquer momento.</p>
            <p>Para exercer seus direitos, o usuário deve acionar primeiramente a administração da sua Igreja (Controladora). Caso necessário, solicitações técnicas de exclusão podem ser encaminhadas ao suporte do JTRO.</p>
        </div>

        <div class="legal-section">
            <h2>8. Contato</h2>
            <p>Em caso de dúvidas sobre esta Política ou sobre o tratamento de seus dados, entre em contato através do e-mail: suporte@jtro.com.br</p>
        </div>

        <div class="privacy-links">
            <a href="/termos_uso.php" class="botao-link botao-secundario">Ver Termos de Uso</a>
            <a href="/login.php" class="botao-link botao-secundario">Voltar</a>
        </div>
    </article>
</div>

</body>
</html>
