<h4><i class="bi bi-kanban"></i> Painel Principal</h4>

<p>O <strong>Painel Principal</strong> é a primeira tela exibida após o login e concentra a visão geral e gestão dos processos administrativos. Seu conteúdo varia conforme o papel (role) do usuário.</p>

<h5><i class="bi bi-person-check"></i> Informações do Usuário</h5>
<ul>
  <li>Exibe nome completo, função, nome de usuário e departamento.</li>
  <li>Apresenta um badge com o nível de acesso do usuário: <code>admin</code>, <code>gerente</code>, <code>protocolador</code> ou <code>visualizador</code>.</li>
</ul>

<h5><i class="bi bi-ui-radios-grid"></i> Navegação Rápida</h5>
<p>Abaixo das informações, há uma barra com botões para ações rápidas:</p>
<ul>
  <li><strong>Administração</strong> – Acesso para gerenciar usuários, OM, tipos de processo, aparência etc. (somente <code>admin</code> e <code>gerente</code>).</li>
  <li><strong>Criar Processo</strong> – Abre a tela de cadastro de novo processo.</li>
  <li><strong>Consultar Processo</strong> – Exibe filtros avançados.</li>
  <li><strong>FAQ</strong> – Link para a página de perguntas frequentes.</li>
  <li><strong>Sair</strong> – Finaliza a sessão.</li>
</ul>

<h5><i class="bi bi-funnel"></i> Filtro de Processos</h5>
<p>Ao clicar em <strong>Consultar Processo</strong>, o formulário de filtro é exibido:</p>
<ul>
  <li><strong>NUP</strong> – Número Único de Protocolo (com máscara automática).</li>
  <li><strong>Polo Passivo</strong> – Nome do investigado ou envolvido.</li>
  <li><strong>Encarregado</strong> – Pessoa responsável pelo processo.</li>
</ul>

<h5><i class="bi bi-table"></i> Tabela de Processos</h5>
<p>Exibe os processos conforme o nível de permissão:</p>
<ul>
  <li><code>admin</code> – Todos os processos do sistema.</li>
  <li><code>gerente</code> – Apenas processos de seu departamento.</li>
  <li><code>protocolador</code> e <code>visualizador</code> – Apenas processos atribuídos a ele.</li>
</ul>
<p>Cada linha exibe:</p>
<ul>
  <li>NUP e Status (Andamento, Finalizado, Arquivado)</li>
  <li>Tipo, assunto, polo passivo, encarregado</li>
  <li>OM de origem e número de anexos</li>
</ul>

<h5><i class="bi bi-tools"></i> Ações Disponíveis</h5>
<p>As ações disponíveis dependem da role do usuário:</p>
<ul>
  <li><strong>Protocolar</strong> – Adiciona documentos ao processo (<code>admin</code>, <code>gerente</code> e <code>protocolador</code>).</li>
  <li><strong>Andamento</strong> – Visualiza movimentações.</li>
  <li><strong>Anexos</strong> – Lista os documentos enviados.</li>
  <li><strong>Editar</strong> – Permite editar os dados do processo (<code>admin</code> e <code>gerente</code>).</li>
  <li><strong>Excluir</strong> – Somente o <code>admin</code> pode excluir processos.</li>
</ul>

<h5><i class="bi bi-layers-half"></i> Paginação</h5>
<p>A tabela suporta paginação, exibindo 30 processos por página. O número total de páginas é calculado com base no resultado da consulta SQL.</p>

<h5><i class="bi bi-code-slash"></i> Observações Técnicas</h5>
<ul>
  <li>O sistema utiliza <code>SQL_CALC_FOUND_ROWS</code> e <code>FOUND_ROWS()</code> para obter o total de processos filtrados.</li>
  <li>A ordenação padrão é do mais recente para o mais antigo (<code>ORDER BY created_at DESC</code>).</li>
  <li>O sistema utiliza `JOIN` com a tabela <code>process_assignments</code> para aplicar filtros por atribuição de usuário.</li>
</ul>
