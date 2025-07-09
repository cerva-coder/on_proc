<h4><i class="bi bi-gear-wide-connected"></i> Administração — ON_Proc</h4>

<p>A seção de <strong>Administração</strong> do sistema <strong>ON_Proc</strong> é acessível exclusivamente por usuários com o perfil <code>admin</code> (e parcialmente por <code>gerente</code>). Esta área reúne todas as ferramentas de configuração, controle de acesso, aparência e gerenciamento de dados centrais do sistema.</p>

<hr>

<h5><i class="bi bi-ui-checks-grid"></i> Painel Administrativo — <code>admin.php</code></h5>
<ul>
  <li>Centraliza os acessos às páginas administrativas.</li>
  <li>Exibe botões organizados para cada função disponível: <strong>Usuários, Departamentos, Tipos de Processo, Atribuição de Processos, Aparência</strong> e outros.</li>
  <li>Apenas administradores veem todos os botões; gerentes acessam apenas os que têm permissão.</li>
</ul>

<hr>

<h5><i class="bi bi-people-fill"></i> Gerenciamento de Usuários — <code>usuarios.php</code></h5>
<ul>
  <li>Lista todos os usuários do sistema, com seus dados principais: nome, posto/função, OM, login, status e perfil.</li>
  <li><strong>Admin</strong> pode editar qualquer usuário; <strong>Gerente</strong> pode editar apenas os usuários de seu departamento.</li>
  <li>Funções disponíveis:
    <ul>
      <li>Ativar ou inativar usuários</li>
      <li>Editar nome, posto, e-mail, OM, perfil e senha</li>
      <li>Criar novos usuários</li>
    </ul>
  </li>
</ul>

<hr>

<h5><i class="bi bi-building"></i> Departamentos<code>departamentos.php</code></h5>
<ul>
  <li>Lista todas as unidades/departamentos cadastrados.</li>
  <li>Funções disponíveis (somente para <code>admin</code>):</li>
  <ul>
    <li>Adicionar novos departamentos</li>
    <li>Editar nome e sigla dos departamentos existentes</li>
    <li>Excluir departamentos não utilizados</li>
  </ul>
</ul>

<hr>

<h5><i class="bi bi-check2-square"></i> Tipos de Processo — <code>admin_process_types.php</code></h5>
<ul>
  <li>Lista os tipos de processo disponíveis, organizados por <strong>categoria</strong> (ex: Comum, SFPC, SVP etc.).</li>
  <li><strong>Admin</strong> pode adicionar e excluir tipos; <strong>Gerente</strong> pode apenas adicionar.</li>
  <li>Permite criar classificações que serão utilizadas nos cadastros de novos processos.</li>
</ul>

<hr>

<h5><i class="bi bi-person-check"></i> Atribuição de Processos — <code>atribuir_processos.php</code></h5>
<ul>
  <li>Permite vincular processos a usuários para que possam visualizá-los e protocolar documentos.</li>
  <li>Filtros dinâmicos ajudam a localizar rapidamente processos e usuários.</li>
  <li><strong>Admin</strong> e <strong>Gerente</strong> têm acesso.</li>
</ul>

<hr>

<h5><i class="bi bi-palette"></i> Aparência do Sistema — <code>aparencia.php</code></h5>
<ul>
  <li>Permite editar diretamente o conteúdo do cabeçalho do sistema (<code>header.php</code>).</li>
  <li>Disponível apenas para <code>admin</code>.</li>
  <li>Funciona como um editor HTML integrado, permitindo personalização visual sem acesso direto ao código-fonte.</li>
</ul>

<hr>

<h5><i class="bi bi-bar-chart-line"></i> Estatísticas — <code>estatisticas.php</code></h5>
<ul>
  <li>Exibe dados estatísticos do sistema como:</li>
  <ul>
    <li>Total de processos por status (em andamento, finalizados, arquivados)</li>
    <li>Distribuição de processos por tipo</li>
    <li>Protocolos por mês (últimos 12 meses)</li>
    <li>Usuários com mais processos atribuídos</li>
    <li>Processos com mais documentos anexados</li>
  </ul>
  <li><strong>Admin</strong> vê dados do sistema inteiro; <strong>Gerente</strong> vê apenas sua OM.</li>
</ul>

<hr>

<h5><i class="bi bi-file-earmark-check"></i> Verificação de Documentos — <code>verificar_documentos.php</code></h5>
<ul>
  <li>Página onde o <strong>admin</strong> ou <strong>gerente</strong> do Departamento pode conferir os documentos protocolados.</li>
  <li>Permite marcar documentos como checados para controle interno de providências.</li>
  <li>Inclui o título da movimentação e paginação de 20 itens por vez.</li>
</ul>

<hr>

<p>Essas ferramentas administrativas garantem o controle e a organização de todos os aspectos estruturais do sistema ON_Proc, sendo essenciais para sua manutenção segura e eficiente.</p>
