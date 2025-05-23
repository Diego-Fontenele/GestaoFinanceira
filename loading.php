<!-- loading.php -->
<!-- loading.php -->
<div id="loadingSpinner"
     class="position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
     style="z-index: 1050;">
  <div class="d-flex flex-column align-items-center">
    <div class="d-flex gap-2">
    <div style="width: 10px; background: #0d6efd; height: 10px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 20px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 30px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 40px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 50px;"></div>
    </div>
    <small class="text-muted mt-3">Carregando inteligência financeira...</small>
  </div>
</div>
<script>
  const spinner = document.getElementById("loadingSpinner");
  const bars = document.getElementById("bars").children;

  let heights = [10, 20, 30, 40, 50];
  let dir = 1;

  const interval = setInterval(() => {
    for (let i = 0; i < bars.length; i++) {
      let h = parseInt(bars[i].style.height);
      h += dir * 10;
      if (h > 100) h = 10;
      bars[i].style.height = h + "px";
    }
  }, 200);

  // Simula carregamento de 4 segundos
  setTimeout(() => {
    clearInterval(interval);
    spinner.style.display = "none";
  }, 4000);

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