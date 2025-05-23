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
  const mensagensPorContexto = {
    insercao: [
      "Guardando seus dados...",
      "organizar seus dados é uma ótima escolha para seu sucesso...",
      "finalizando..."
    ],
    metas: [
      "Carregando metas financeiras...",
      "Analisando seus objetivos...",
      "Ajustando planos mensais..."
    ],
    dashboard: [
      "Montando seu painel inteligente...",
      "Coletando dados financeiros...",
      "Preparando resumos e gráficos..."
    ],
    sincronizacao: [
      "Sincronizando dados com o servidor...",
      "Atualizando informações...",
      "Verificando integridade dos dados..."
    ]
  };

  function iniciarLoading(contexto = "dashboard") {
    const mensagens = mensagensPorContexto[contexto] || mensagensPorContexto.dashboard;
    let index = 0, char = 0, mensagemAtual = mensagens[0], isDeleting = false;
    const el = document.getElementById("loadingMessage");

    function digitarMensagem() {
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

    digitarMensagem();
    document.getElementById('loadingSpinner').classList.remove('d-none');
  }


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