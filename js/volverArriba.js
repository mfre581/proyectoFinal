document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnVolverArriba");
  const esMovil = window.matchMedia("(max-width: 767px)").matches;

  if ((esMovil && numFotos > 2) || (!esMovil && numFotos > 8)) {
    btn.classList.remove("d-none");
    btn.classList.add("d-block");
  }
});
