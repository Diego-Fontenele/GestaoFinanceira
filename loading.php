<!-- loading.php -->
<!-- loading.php -->
<div id="loadingSpinner"
     class="position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
     style="z-index: 1050;">
  <div class="d-flex flex-column align-items-center">
    <div class="d-flex gap-2">
      <div class="spinner-grow text-primary" style="width: 1.5rem; height: 1.5rem;" role="status"></div>
      <div class="spinner-grow text-primary" style="width: 1.5rem; height: 1.5rem;" role="status"></div>
      <div class="spinner-grow text-primary" style="width: 1.5rem; height: 1.5rem;" role="status"></div>
    </div>
    <small class="text-muted mt-3">Carregando inteligência financeira...</small>
  </div>
</div>


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