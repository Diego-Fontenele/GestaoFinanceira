<!-- loading.php -->
<div id="loadingSpinner"
     class="position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
     style="z-index: 1050;">
  <div class="text-center">
    <div class="fs-5 font-monospace text-dark" id="loadingMessage">_</div>
    <div class="spinner-border text-primary mt-3" role="status" style="width: 2rem; height: 2rem;">
      <span class="visually-hidden">Carregando...</span>
    </div>
  </div>
</div>

<script>
  const mensagens = [
    "Processando sua informação...",
    "Trabalhando para o seu futuro financeiro...",
    "Finalizando o processo...",
  ];

  let index = 0, char = 0, mensagemAtual = mensagens[0], isDeleting = false;

  function digitarMensagem() {
    const el = document.getElementById("loadingMessage");
    if (!el) return;

    if (char <= mensagemAtual.length && !isDeleting) {
      el.textContent = mensagemAtual.substring(0, char++) + "_";
    } else if (isDeleting) {
      el.textContent = mensagemAtual.substring(0, char--) + "_";
      if (char < 0) {
        isDeleting = false;
        index = (index + 1) % mensagens.length;
        mensagemAtual = mensagens[index];
      }
    }

    setTimeout(digitarMensagem, isDeleting ? 50 : 80);
    if (char === mensagemAtual.length + 1) isDeleting = true;
  }

  document.addEventListener("DOMContentLoaded", digitarMensagem);

  function mostrarLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
      spinner.classList.remove('d-none');
      spinner.classList.add('d-flex');
    }
  }

  function esconderLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
      spinner.classList.add('d-none');
      spinner.classList.remove('d-flex');
    }
  }

  window.addEventListener("load", function () {
    esconderLoading();
  });
</script>