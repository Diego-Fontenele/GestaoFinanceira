<!-- loading.php -->
<div id="loadingSpinner"
     class="position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
     style="z-index: 1050;">
  <div class="d-flex flex-column align-items-center">
    <div class="spinner-border text-primary mb-3" role="status" style="width: 4rem; height: 4rem;">
      <span class="visually-hidden">Carregando...</span>
    </div>
    <strong class="text-primary">Carregando...</strong>
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