<!-- loading.php -->
<div id="loadingSpinner" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: sans-serif;">
  <div style="font-size: 1.2rem; margin-bottom: 10px;">Economizando para o futuro...</div>
  <div id="piggy" style="font-size: 3rem; line-height: 3rem;">🐷</div>
  <div id="coins" style="font-size: 2rem; margin-top: 10px; height: 2rem; overflow: hidden;"></div>
</div>

<script>
  const spinner = document.getElementById("loadingSpinner");
  const coins = document.getElementById("coins");
  let coinStr = "";
  let progress = 0;

  const interval = setInterval(() => {
    if (progress >= 100) {
      clearInterval(interval);
      spinner.style.display = "none";
    } else {
      progress += Math.floor(Math.random() * 10) + 5;
      if (progress > 100) progress = 100;
      coinStr += "💰 ";
      coins.innerText = coinStr;
    }
  }, 300);

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