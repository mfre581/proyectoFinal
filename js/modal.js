document.addEventListener("DOMContentLoaded", () => {
  const modalFoto = document.getElementById("modalFoto");
  const modalImagen = document.getElementById("modalImagen");

  modalFoto.addEventListener("show.bs.modal", (event) => {
    const trigger = event.relatedTarget;

    modalImagen.src = trigger.getAttribute("data-foto");
    modalImagen.alt = trigger.getAttribute("data-alt");
  });
});
