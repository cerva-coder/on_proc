<h4><i class="bi bi-file-earmark-plus"></i> Criar Processo</h4>

<p>A página <strong>Criar Processo</strong> permite registrar novos processos administrativos no sistema ON_Proc, vinculando-os a um departamento e preenchendo as principais informações processuais.</p>

<h5><i class="bi bi-person-badge"></i> Permissões</h5>
<ul>
  <li><strong>Admin:</strong> Pode criar processos para qualquer departamento (OM).</li>
  <li><strong>Gerente:</strong> Pode criar apenas para sua OM.</li>
</ul>

<h5><i class="bi bi-file-text"></i> Campos do Formulário</h5>
<ul>
  <li><strong>Departamento:</strong> (apenas para admin) – Lista todos os departamentos existentes.</li>
  <li><strong>Assunto:</strong> Tema do processo ou fato apurado.</li>
  <li><strong>Polo Passivo:</strong> Nome do(s) investigado(s) ou envolvidos.</li>
  <li><strong>Encarregado:</strong> Pessoa designada para conduzir o processo.</li>
  <li><strong>Tipo de Processo:</strong> Selecionado entre os tipos cadastrados, agrupados por categoria. Há também a opção <code>Outros</code>, que libera um campo de texto livre para especificar.</li>
</ul>

<h5><i class="bi bi-hash"></i> Geração do NUP</h5>
<p>O <strong>NUP (Número Único de Protocolo)</strong> é gerado automaticamente com base na estrutura:</p>
<pre>
[ordem anual do Departamento]-[código do Departamento].[ano]
Exemplo: 0000123-002.2025
</pre>
<p>Esse número garante unicidade por OM e ano, e é calculado com base no total de processos já existentes naquele ano para a OM selecionada.</p>

<h5><i class="bi bi-exclamation-triangle"></i> Validações</h5>
<ul>
  <li>Todos os campos são obrigatórios.</li>
  <li>Se o tipo de processo for "Outros", o campo de texto deve ser preenchido.</li>
  <li>O formulário bloqueia o envio caso algum campo esteja em branco.</li>
</ul>

<h5><i class="bi bi-magic"></i> Funcionalidades Técnicas</h5>
<ul>
  <li>Os tipos de processo são carregados dinamicamente com <code>optgroup</code> por categoria.</li>
  <li>É usada a biblioteca <code>Select2</code> para melhorar a usabilidade do campo de seleção.</li>
  <li>Em caso de sucesso no cadastro, o usuário é redirecionado de volta ao <strong>Painel</strong>.</li>
</ul>

<h5><i class="bi bi-arrow-left-circle"></i> Retorno</h5>
<p>O botão <strong>Voltar ao Painel</strong> permite retornar à listagem de processos sem submeter o formulário.</p>
