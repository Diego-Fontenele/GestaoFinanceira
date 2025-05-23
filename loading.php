<!-- loading.php -->
<div id="loadingSpinner" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: sans-serif;">
  <div style="margin-bottom: 1rem; font-size: 1.2rem;">Analisando sua evolução financeira...</div>
  <div id="bars" style="display: flex; gap: 5px; align-items: flex-end; height: 100px;">
    <div style="width: 10px; background: #0d6efd; height: 10px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 20px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 30px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 40px;"></div>
    <div style="width: 10px; background: #0d6efd; height: 50px;"></div>
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
</script>


<script>
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