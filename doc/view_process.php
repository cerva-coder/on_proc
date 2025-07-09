<h4><i class="bi bi-file-earmark-text"></i> Anexos do Processo</h4>

<p>A página <strong>view_process.php</strong> permite visualizar, abrir e (se autorizado) excluir documentos PDF vinculados a um processo administrativo. Ela é usada para fins de consulta documental detalhada.</p>

<h5><i class="bi bi-shield-lock"></i> Controle de Acesso</h5>
<ul>
  <li>É necessário estar autenticado e fornecer <code>process_id</code> via <code>GET</code>.</li>
  <li>Usuários sem permissão são barrados (verificação de sessão e role).</li>
</ul>

<h5><i class="bi bi-clipboard-data"></i> Informações do Processo</h5>
<ul>
  <li>Exibe os dados básicos do processo: <strong>NUP, Assunto, Polo Passivo, Encarregado, Data de Criação</strong>.</li>
</ul>

<h5><i class="bi bi-files"></i> Listagem de Documentos</h5>
<ul>
  <li>Os documentos são buscados da tabela <code>documents</code> com JOIN na tabela <code>users</code> para mostrar o responsável pelo envio.</li>
  <li>São ordenados da data mais recente para a mais antiga (<code>uploaded_at DESC</code>).</li>
  <li>A tabela apresenta:
    <ul>
      <li><strong>Nome do Arquivo</strong></li>
      <li><strong>Usuário que enviou</strong></li>
      <li><strong>Data de envio</strong></li>
      <li><strong>Ações</strong>: abrir em nova aba e/ou excluir</li>
    </ul>
  </li>
</ul>

<h5><i class="bi bi-eye-fill"></i> Abrir Documento</h5>
<ul>
  <li>Todos os documentos têm botão <strong>Abrir</strong>, que abre o PDF em nova aba.</li>
</ul>

<h5><i class="bi bi-trash"></i> Exclusão de Documento (Admin)</h5>
<p>Usuários com perfil <strong>admin</strong> podem excluir um documento, desde que ele não tenha sido previamente marcado como "excluído".</p>

<p><strong>Fluxo técnico da exclusão:</strong></p>
<ol>
  <li>O admin fornece um motivo via campo de texto obrigatório.</li>
  <li>O arquivo original é removido do disco (com verificação de segurança no caminho).</li>
  <li>Um novo <strong>PDF de justificativa</strong> é gerado com a biblioteca <code>FPDF</code>, contendo:
    <ul>
      <li>Título informando a exclusão</li>
      <li>Motivo informado e data/hora</li>
    </ul>
  </li>
  <li>Este novo PDF é criptografado com <code>AES-256-CBC</code> usando a constante <code>ENCRYPTION_KEY</code>.</li>
  <li>O novo arquivo é salvo na mesma pasta do processo, com prefixo <code>excluido_</code>.</li>
  <li>O banco de dados é atualizado com o novo caminho e descrição de exclusão.</li>
</ol>

<h5><i class="bi bi-lock-fill"></i> Segurança na Exclusão</h5>
<ul>
  <li>Verifica se o caminho do arquivo a ser excluído está contido dentro da pasta <code>/uploads</code>.</li>
  <li>Evita qualquer tentativa de acesso a caminhos fora da estrutura esperada.</li>
  <li>Aplica criptografia simétrica com IV aleatório gerado por <code>openssl_random_pseudo_bytes</code>.</li>
</ul>

<h5><i class="bi bi-arrow-left-circle"></i> Retorno</h5>
<ul>
  <li>Botão no final da página permite retornar ao Painel principal.</li>
</ul>
