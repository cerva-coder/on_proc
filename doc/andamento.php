<h4><i class="bi bi-clock-history"></i> Andamento do Processo</h4>

<p>A página <strong>andamento.php</strong> exibe o histórico completo de documentos enviados e ações realizadas em um processo específico. Ela oferece uma visão cronológica dos eventos do processo administrativo.</p>

<h5><i class="bi bi-shield-lock"></i> Controle de Acesso</h5>
<ul>
  <li>O acesso é permitido apenas se o processo <strong>pertence ao usuário</strong> (diretamente ou via atribuição).</li>
  <li>É verificada a existência do <code>process_id</code> via GET e a sessão do usuário.</li>
</ul>

<h5><i class="bi bi-info-circle"></i> Informações do Processo</h5>
<p>Na parte superior da página são exibidas as principais informações do processo:</p>
<ul>
  <li><strong>NUP</strong> — Número único do processo</li>
  <li><strong>Assunto, Polo Passivo, Encarregado, Tipo e Status</strong></li>
  <li>Botão para copiar NUP e polo passivo com <code>clipboard API</code></li>
</ul>

<h5><i class="bi bi-mailbox"></i> Ação de Protocolar</h5>
<ul>
  <li>Se o processo estiver <strong>Em Andamento</strong>, é exibido um botão para protocolar documentos.</li>
  <li>Este botão redireciona para <code>protocol.php</code> com o ID do processo.</li>
</ul>

<h5><i class="bi bi-files"></i> Listagem dos Andamentos</h5>
<p>Abaixo das informações principais, é exibida uma <strong>linha do tempo dos documentos enviados</strong>:</p>
<ul>
  <li>Cada item representa um protocolo/documento.</li>
  <li>Inclui título do documento, descrição, data/hora, autor (nome, patente), e ações disponíveis.</li>
  <li>As ações incluem:
    <ul>
      <li><strong>Visualizar PDF</strong> (abre em nova aba)</li>
      <li><strong>Baixar PDF</strong></li>
    </ul>
  </li>
</ul>

<h5><i class="bi bi-archive"></i> Baixar Todo o Processo</h5>
<ul>
  <li>É exibido um botão para baixar todos os documentos do processo em um único arquivo <strong>.ZIP</strong>.</li>
  <li>Esse botão chama <code>download_zip.php</code>, passando o <code>process_id</code>.</li>
</ul>

<h5><i class="bi bi-exclamation-triangle"></i> Casos sem Andamento</h5>
<ul>
  <li>Se não houver documentos vinculados ao processo, é exibido um alerta <strong>"Sem andamentos registrados"</strong>.</li>
</ul>

<h5><i class="bi bi-code-slash"></i> Considerações Técnicas</h5>
<ul>
  <li>Os dados são carregados usando JOIN entre <code>documents</code> e <code>users</code>, permitindo exibir informações completas do autor de cada protocolo.</li>
  <li>Os documentos são ordenados do mais recente para o mais antigo (<code>uploaded_at DESC</code>).</li>
  <li>Os identificadores de documento são formatados com 4 dígitos (<code>ID: 0001</code>).</li>
  <li>A interface utiliza <strong>Bootstrap 5</strong> e ícones da <strong>Bootstrap Icons</strong> para melhor usabilidade.</li>
</ul>
