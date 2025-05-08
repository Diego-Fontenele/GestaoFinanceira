const CACHE_NAME = "app-cache-v3"; // Atualize a versão quando mudar arquivos

const URLS_TO_CACHE = [
  "/index.php",
  "/add_despesa.php",
  "/add_investimento.php",
  "/add_receita.php",
  "/ajax_despesas.php",
  "/ajax_receitas.php",
  "/area_logada.php",
  "/configuracoes.php",
  "/esqueceu.php",
  "/fechamento.php",
  "/gamificacao.php",
  "/gamificacao_mentor.php",
  "/gamificacao_relatorio.php",
  "/gamificacao_usuario.php",
  "/icons/icon-192.png",
  "/icons/icon-512.png",
  "/manifest.json",
  "/login.php",
  "/logout.php",
  "/meta.php",
  "/mentor_dashboard.php"
  // Inclua também seus arquivos CSS, JS, etc, se estiverem em subpastas como /css/style.css ou /js/app.js
];

self.addEventListener("install", (e) => {
  console.log("Service Worker instalado");
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
});

self.addEventListener("activate", (e) => {
  console.log("Service Worker ativado");
  e.waitUntil(
    caches.keys().then((keyList) =>
      Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key); // Limpa versões antigas
          }
        })
      )
    )
  );
});

self.addEventListener("fetch", (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});