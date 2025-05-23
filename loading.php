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
      "Coletando seus dados...",
      "Salvando suas informações...",
      "Finalizando..."
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
    ],
    padrao: [
      "Carregando...",
      "Processando informações...",
      "Aguarde só um instante..."
    ]
};

  let animacaoAtiva = false;

  function mostrarLoading(contexto = "padrao") {
    const mensagens = mensagensPorContexto[contexto] || mensagensPorContexto["padrao"];
    const el = document.getElementById("loadingMessage");
    const spinner = document.getElementById("loadingSpinner");

    if (!el || !spinner) return;

    spinner.classList.remove("d-none");
    let index = 0, char = 0, isDeleting = false;
    let mensagemAtual = mensagens[index];
    animacaoAtiva = true;

    function digitarMensagem() {
      if (!animacaoAtiva) return;

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
  }

  function esconderLoading() {
    animacaoAtiva = false;
    const spinner = document.getElementById("loadingSpinner");
    if (spinner) {
      spinner.classList.add("d-none");
    }
  }

  // Ocultar ao carregar completamente a página
  window.addEventListener("load", esconderLoading);
</script>