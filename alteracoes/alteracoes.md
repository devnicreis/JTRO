No "Mapeamento de Assiduidade Global" e "Termômetro de Sobrecarga":
1. Precisamos remover as colunas de nome do "Líder" de todas as tabelas dos relatórios gerais, já que o próprio GF já carrega o nome do líder consigo. 
2. Com isso, também, vamos aumentar as células de nome do GF, e alinhar o nome à esquerda, uma vez que estão centralizados e isso está deixando o layout sujo.
3. Acho que há algum cálculo errado, pois as "presenças" estão com quantidades exorbitantes. Por exemplo: um grupo com 18 membros e 4 reuniões tem 918 presenças (!!). Preciso que você revise todos os cálculos dos relatórios. Se estiver certo, precisamos deixar claro no relatório ao que se referem, pois dá a entender, como está agora, que o GF em questão teve quase mil presenças em 4 reuniões, o que é impossível para um GF com 18 membros.
4. As informações do rodapé, "Documento gerado automaticamente pelo JTRO / Página 2 de 2", geraram uma nova página e estão quase no meio da página. Precisamos deixar essas informações somente no rodapé das páginas que já temos, sem criar novas.

No "Termômetro de Sobrecarga", há uma coluna para "Temp. Lid.". Não sei o que é, mas creio que podemos tirar, uma vez que todos os GFs estão zerados.

No "Mapeamento de Assiduidade Global", a segunda página não gerou o cabeçalho.

No "Diagnostico Completo do GF",
1. Vamos tirar os cards de "Novos Membros" e "Saldo do Período". 
2. Vamos incluir "Total de Reuniões no período", "Líderes no GF" e "Filhos (de 0 a 9 anos)", os mesmos cards da tela "Diagnóstico de GFs";
3. Na tabela "4. MAPA DE FREQUÊNCIA INDIVIDUAL POR REUNIÃO", vamos remover a coluna "% Pont.";
4. Na tabela "3. MEMBROS EM SITUAÇÃO DE VULNERABILIDADE PASTORAL", as colunas "Just." e "Max.Consec." parecem desnecessárias, ou o cálculo está errado, não? Verifique. Se essas colunas forem de fato desnecessárias para o cálculo, vamos removê-las.
5. Ainda na tabela "3. MEMBROS EM SITUAÇÃO DE VULNERABILIDADE PASTORAL", as células da coluna "Observações" precisam estar com o texto alinhado à esquerda.

//

Aqui, uma pequena análise técnica desses pontos que levantei:

Ponto 3 — "Presenças exorbitantes" (GF com 18 membros e 4 reuniões = 918 presenças)

Você não entendeu errado — o dado está correto, mas o rótulo está enganoso.
O que o sistema chama de "Presenças" na tabela por GF é na verdade a soma de todos os registros de presença de todos os membros em todas as reuniões. Um GF com 18 membros e 4 reuniões tem capacidade para 72 registros individuais — mas esse GF tem subgrupos (EVERTON E ROSANGELA acumula registros de múltiplos grupos que se reúnem sob o mesmo GF raiz), o que infla o número. Alternativamente, pode ser que a query esteja somando presencas sem filtrar corretamente o GF específico.
O correto é: ou renomear a coluna para "Registros de Presença" e adicionar uma nota explicativa, ou revisar a query para contar apenas membros diretos do GF × reuniões do GF. O número 918 para 18 membros só é possível se a query está trazendo dados de sub-grupos encadeados. Vou corrigir a query para contar apenas os membros cadastrados diretamente no GF e suas reuniões.
Ponto "Temp. Lid." zerada no Termômetro
Você entendeu certo. A coluna usa gf.data_inicio, que provavelmente não existe ainda no seu banco (não foi migrada). Como está sempre –, não agrega valor agora. Vamos remover.
Rodapé criando página extra (Mapeamento, página 3 vazia)
**Bug real.** O loop de rodapé percorre as páginas *depois* de já ter gerado o conteúdo, e se o cursor estiver próximo do fim da última página, o TCPDF pode empurrar uma página extra ao tentar posicionar o rodapé. A correção é usar pdf−>setPage(pdf->setPage(
pdf−>setPage(i) sem adicionar nova página, e garantir que SetAutoPageBreak está com margem suficiente.
Cabeçalho ausente na página 2 do Mapeamento
Bug real. O cabeçalho é renderizado apenas na primeira página (AddPage() inicial). Quando o conteúdo quebra para a página 2 via AddPage() automático do SetAutoPageBreak, o cabeçalho não é replicado. A correção é sobrescrever o método Header() do TCPDF ou chamar _renderCabecalho() manualmente dentro do loop de AddPage — o caminho mais limpo no nosso padrão é interceptar via startPage() ou renderizar o cabeçalho no início de cada AddPage() chamado nas tabelas.
Diagnóstico de GF — coluna "Just." na tabela de Vulnerabilidade
Você tem razão em questionar. A coluna Just. estava mostrando 19 para todos — isso porque o código está usando $kpi['faltas_just'] ?? '–' (dado global do GF) em vez do dado individual do membro. É um bug. A coluna era para mostrar as faltas justificadas do membro específico, que já estão disponíveis no array. Vou corrigir e manter a coluna com o dado correto.
A coluna Max.Consec. é útil pastoralmente (saber se as ausências foram seguidas ou espalhadas), mas se preferir remover, tudo bem. Vou manter na proposta e você decide no prompt.