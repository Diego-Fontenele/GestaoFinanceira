<!DOCTYPE html>
<html lang="pt-br">

<head>
  <!-- PWA -->
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0d6efd">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Domine Seu Bolso</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f9f9f9;
    }

    .navbar {
      padding: 1rem 0;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: #0d6efd;
    }

    .hero-image {
      width: 100%;
      max-width: 1000px;   /* Tamanho máximo que você deseja */
      height: auto;       /* Mantém a proporção da imagem */
      display: block;
      margin: 0 auto;     /* Centraliza horizontalmente */
      border-radius: 10px;
      transition: transform 0.4s ease;
  }
    .hero-image:hover {
      transform: scale(1.01);
    }

    .hero-section {
      background-color: #fff;
      padding: 3rem 1rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .hero-text h2 {
      font-weight: 700;
      color: #0d6efd;
    }

    .hero-text p {
      font-size: 1.1rem;
      color: #555;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
      <a class="navbar-brand" href="#">DSB</a>
      <a href="login.php" class="btn btn-outline-primary">
        <i class="bi bi-box-arrow-in-right"></i> Área Logada
      </a>
    </div>
  </nav>

  <!-- Conteúdo principal -->
  <div class="container mt-5 hero-section">
    <img src="img/principalIndex.jpg" alt="Imagem sobre finanças" class="hero-image mb-4">

    <div class="hero-text">
      <h2>Por que a Gestão Financeira é tão importante?</h2>
      <p>
        Uma gestão financeira eficiente é essencial para garantir a saúde econômica, tanto de empresas quanto de pessoas.
        Com controle adequado dos gastos, planejamento estratégico e disciplina, é possível alcançar objetivos, evitar dívidas
        e conquistar estabilidade e crescimento financeiro.
      </p>
    </div>
  </div>

  <!-- Service Worker -->
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log('Service Worker registrado'))
        .catch(err => console.error('Erro ao registrar o Service Worker:', err));

      navigator.serviceWorker.register("/sw.js").then((registration) => {
        registration.onupdatefound = () => {
          const installingWorker = registration.installing;
          installingWorker.onstatechange = () => {
            if (installingWorker.state === "installed") {
              if (navigator.serviceWorker.controller) {
                if (confirm("Uma nova versão do sistema está disponível. Deseja atualizar agora?")) {
                  registration.waiting.postMessage("SKIP_WAITING");
                  window.location.reload();
                }
              } else {
                console.log("Conteúdo está em cache para uso offline.");
              }
            }
          };
        };
      });
    }
  </script>
</body>

</html>