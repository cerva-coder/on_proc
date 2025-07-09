<h4><i class="bi bi-box-arrow-in-right"></i> Página de Login</h4>

<p>A página de login do sistema ON_Proc é responsável por autenticar os usuários cadastrados antes que possam acessar o painel principal. Ela implementa boas práticas de segurança para evitar acessos indevidos e ataques por força bruta.</p>

<h5><i class="bi bi-lock-fill"></i> Autenticação</h5>
<ul>
  <li>O usuário informa seu <strong>nome de usuário</strong> e <strong>senha</strong>.</li>
  <li>O sistema busca o usuário no banco de dados utilizando o campo <code>username</code>.</li>
  <li>Se encontrado, o sistema verifica se o usuário está <code>ativo</code> e se a <strong>senha corresponde</strong> ao hash armazenado (<code>password_verify()</code>).</li>
  <li>Em caso de sucesso, os dados principais são armazenados na sessão e o usuário é redirecionado ao <strong>Painel</strong>.</li>
</ul>

<h5><i class="bi bi-shield-exclamation"></i> Proteção contra múltiplas tentativas</h5>
<p>Para evitar tentativas de login automatizadas (ataques de força bruta), o sistema implementa um mecanismo simples de bloqueio baseado em sessão:</p>
<ul>
  <li>São permitidas até <strong>5 tentativas consecutivas</strong> de login por sessão.</li>
  <li>Após isso, o sistema bloqueia novas tentativas por <strong>5 minutos</strong> (300 segundos).</li>
  <li>Esse controle é feito utilizando as variáveis de sessão <code>login_attempts</code> e <code>last_attempt_time</code>.</li>
  <li>Após um login bem-sucedido, o contador de tentativas é resetado.</li>
</ul>

<h5><i class="bi bi-x-circle"></i> Mensagens de erro</h5>
<p>O sistema exibe mensagens claras para auxiliar o usuário:</p>
<ul>
  <li><strong>"Preencha usuário e senha"</strong> — campos obrigatórios em branco.</li>
  <li><strong>"Usuário ou senha inválidos"</strong> — credenciais incorretas ou usuário inativo.</li>
  <li><strong>"Muitas tentativas"</strong> — se excedido o limite e não passou o tempo de bloqueio.</li>
</ul>

<h5><i class="bi bi-ui-checks"></i> Considerações técnicas</h5>
<ul>
  <li>As senhas são armazenadas com <code>password_hash()</code> e verificadas com <code>password_verify()</code>.</li>
  <li>O redirecionamento para <code>painel.php</code> impede acesso repetido à tela de login já autenticado.</li>
  <li>O layout utiliza <strong>Bootstrap 5</strong> com ícones da <strong>Bootstrap Icons</strong>.</li>
</ul>
