const CACHE_NAME = "app-cache-v8"; // Atualize a versão sempre que fizer mudanças

// Apenas arquivos estáticos e públicos devem ser cacheados
const URLS_TO_CACHE = [
  "/",
  "/index.php",
  "/login.php",
  "/esqueceu.php",
  "/manifest.json",
  "/img/icon.png"
];

self.addEventListener("install", (event) => {
  console.log("Service Worker instalado");
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(URLS_TO_CACHE);
    })
  );
});

self.addEventListener("activate", (event) => {
  console.log("Service Worker ativado");
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key); // Limpa caches antigos
          }
        })
      );
    })
  );
});

// Estratégia inteligente: network-first para PHP, cache-first para estáticos
self.addEventListener("fetch", (event) => {
  const request = event.request;

  // Para arquivos .php, usa network-first
  if (request.url.endsWith(".php")) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          return response;
        })
        .catch(() => {
          return caches.match(request); // fallback se offline
        })
    );
  } else {
    // Para arquivos estáticos, usa cache-first
    event.respondWith(
      caches.match(request).then((response) => {
        return response || fetch(request);
      })
    );
  }
});

// Atualização imediata quando o usuário confirmar
self.addEventListener("message", (event) => {
  if (event.data === "SKIP_WAITING") {
    self.skipWaiting();
  }
});