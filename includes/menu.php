<!-- Botão visível só em dispositivos móveis -->
<button class="btn btn-primary d-md-none m-2" id="menuToggle">
  <i class="bi bi-list"></i> Menu
</button>

<!-- Menu lateral dentro de um container que pode ser ocultado -->
<div id="mobileMenu" class="bg-dark text-white p-3 position-fixed top-0 start-0 h-100" style="width: 250px; display: none; z-index: 1050;">
  <!-- Botão para fechar -->
  <button class="btn btn-outline-light mb-3 d-md-none" id="closeMenu">
    <i class="bi bi-x-lg"></i> Fechar
  </button>

<nav class="bg-dark text-white p-3">
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
    <!-- Gamificação: visível para todos, mas com funções diferentes -->
    <li class="nav-item mb-2">
      <a class="nav-link text-white d-flex align-items-center gap-2" href="gamificacao.php">
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
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMenu = document.getElementById('closeMenu');

    if (menuToggle && mobileMenu && closeMenu) {
      menuToggle.addEventListener('click', () => {
        mobileMenu.style.display = 'block';
      });

      closeMenu.addEventListener('click', () => {
        mobileMenu.style.display = 'none';
      });
    }
  });
</script>