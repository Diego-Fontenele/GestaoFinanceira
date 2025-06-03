

<nav class="bg-dark text-white p-3 h-100 d-flex flex-column">
  <h4 class="mb-4"><i class="bi bi-piggy-bank"></i> Financeiro</h4>
  <ul class="nav flex-column">
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="area_logada.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="add_receita.php"><i class="bi bi-wallet2"></i> Receitas</a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="add_despesa.php"><i class="bi bi-cash-stack"></i> Despesas</a>
    </li>
    <li class="nav-item mb-2">
    <a class="nav-link text-white d-flex align-items-center gap-2" href="meta.php"><i class="bi bi-flag"></i> Metas</a>
    </li>
    <li class="nav-item mb-2">
    <a class="nav-link text-white d-flex align-items-center gap-2" href="add_investimento.php">
      <i class="bi bi-bar-chart-line"></i> Investimentos
    </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2 text-nowrap" href="configuracoes.php"><i class="bi bi-folder-plus"></i> Cadastrar Categorias</a>
    </li>
    <!-- Menu exclusivo para mentores -->
    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'mentor'): ?>
        <li class="nav-item">
        <a class="nav-link text-white d-flex align-items-center gap-2 text-nowrap" href="add_valor_esperado.php"><i class="bi bi-tags"></i> Vincular Categoria</a>
        </li>
      <?php endif; ?>
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="fechamento.php"><i class="bi bi-archive"></i> Fechamento</a>
    </li>
    <li class="nav-item mb-2">
    <a class="nav-link text-white d-flex align-items-center gap-2 text-nowrap" href="orcamento.php"><i class="bi bi-wallet2"></i> Orçamento</a>
    </li>
    <li class="nav-item mb-2">
    <a class="nav-link text-white d-flex align-items-center gap-2 text-nowrap" href="mentor_virtual.php"><i class="bi bi-robot"></i> Mentor Virtual</a>
    </li>
    <!-- Gamificação: visível para todos, mas com funções diferentes -->
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="conquista_usuario.php">
        <i class="bi bi-trophy"></i> Gamificação
      </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="fale_conosco.php"><i class="bi bi-envelope"></i> Fale Conosco</a>
    </li>
    <!-- Menu exclusivo para mentores -->
    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'mentor'): ?>
        <li class="nav-item mb-2">
        <a class="nav-link text-white d-flex align-items-center gap-2 text-nowrap" href="mentor_dashboard.php"><i class="bi bi-people"></i> Área do Mentor</a>
        </li>
      <?php endif; ?>
    <li class="nav-item mt-5">
      <a class="nav-link text-danger d-flex align-items-center gap-2" href="logout.php">
        <i class="bi bi-box-arrow-left"></i> Sair
      </a>
    </li>
  </ul>
</nav>
