/*JAVASCRIPT

    Este script permite al usuario cargar una imagen y verla en una vista previa (canvas)
    Además, antes de enviarla al servidor:
     - La convierte a formato Base64 comprimido en JPEG para reducir tamaño
     - Ajusta la vista previa a un tamaño máximo sin deformarla (respetando la proporción)
     - Inserta la imagen codificada en un campo oculto del formulario para que PHP la procese.
    
      También contiene la función que gestiona el proceso de edición */

// Obtenemos el elemento <canvas> donde se mostrará la imagen
const canvas = document.getElementById("previewCanvas");

// Obtenemos el contexto 2D del canvas, que permite dibujar imágenes, formas, etc.
const ctx = canvas.getContext("2d");

// Variable que almacenará una copia de la imagen original cargada en el canvas.
// Esto es necesario para poder restaurar la imagen original cada vez que se aplique un nuevo filtro.
let originalImage = null;

// Escuchar evento al cargar imagen
document.getElementById("imagenInput").addEventListener("change", function (e) {
  const file = e.target.files[0];

  //Comprueba que el archivo cargado es una imagen válida (jpeg, png…)
  if (file && file.type.startsWith("image/")) {
    // Creamos un FileReader para leer el contenido del archivo como base64
    const reader = new FileReader();

    // Definimos qué hacer cuando el archivo se haya leído completamente
    reader.onload = function (event) {
      // Creamos una nueva imagen HTML que cargará la imagen del archivo
      const img = new Image();

      // Cuando la imagen se cargue completamente obtenemos el tamaño original de la imagen
      img.onload = function () {
        const originalWidth = img.width;
        const originalHeight = img.height;

        // Definimos un tamaño máximo permitido para mostrar en el canvas
        const MAX_WIDTH = 800;
        const MAX_HEIGHT = 400;

        // Calculamos la proporción para redimensionar la imagen sin deformarla
        const ratio = Math.min(
          MAX_WIDTH / originalWidth,
          MAX_HEIGHT / originalHeight,
          1
        );

        // Establecemos el tamaño interno real del canvas (sin escalado)
        canvas.width = originalWidth;
        canvas.height = originalHeight;

        // Dibujamos la imagen original en el canvas
        ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

        // ESCALADO VISUAL para que sea responsivo y no distorsione
        canvas.style.width = "100%"; // que ocupe el ancho del contenedor padre
        canvas.style.height = "auto"; // mantenga proporción

        // Guardamos los datos de la imagen original tal como se cargó en el canvas.
        // Así podremos volver a este estado inicial antes de aplicar cada filtro.
        originalImage = ctx.getImageData(0, 0, canvas.width, canvas.height);
      };
      // Asignamos la imagen cargada desde el FileReader como fuente de la imagen HTML
      img.src = event.target.result;
    };
    // Iniciamos la lectura del archivo como una URL en base64
    reader.readAsDataURL(file);
  }
});

// Cuando se envía el formulario, generar imagen base64 comprimida y ponerla en el input oculto
document.getElementById("formulario").addEventListener("submit", function (e) {
  const calidad = 0.8; // Ajusta la calidad según sea necesario
  const imagenBase64 = canvas.toDataURL("image/jpeg", calidad); // Comprime a JPEG
  document.getElementById("imagenBase64").value = imagenBase64;
});

// Aplica filtros a la imagen en el canvas según el botón presionado
function applyFilter(type) {
  if (!originalImage) return; // No hacer nada si no hay imagen cargada
  ctx.putImageData(originalImage, 0, 0); // Restaurar imagen original antes de aplicar filtro
  let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  let data = imageData.data;

  switch (type) {
    case "grayscale": // Blanco y negro
      for (let i = 0; i < data.length; i += 4) {
        let avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
        data[i] = data[i + 1] = data[i + 2] = avg;
      }
      break;

    case "brightness": // Aumenta brillo
      for (let i = 0; i < data.length; i += 4) {
        data[i] += 40; // R
        data[i + 1] += 40; // G
        data[i + 2] += 40; // B
      }
      break;

    case "contrast": // Ajusta contraste
      let contrast = 40; // valor entre -255 a 255
      let factor = (259 * (contrast + 255)) / (255 * (259 - contrast));
      for (let i = 0; i < data.length; i += 4) {
        data[i] = factor * (data[i] - 128) + 128;
        data[i + 1] = factor * (data[i + 1] - 128) + 128;
        data[i + 2] = factor * (data[i + 2] - 128) + 128;
      }
      break;

    case "redTint": // Tono rojizo
      for (let i = 0; i < data.length; i += 4) {
        data[i] += 50; // R
      }
      break;

    case "sepia": // Filtro sepia clásico
      for (let i = 0; i < data.length; i += 4) {
        let r = data[i],
          g = data[i + 1],
          b = data[i + 2];
        data[i] = Math.min(255, r * 0.393 + g * 0.769 + b * 0.189);
        data[i + 1] = Math.min(255, r * 0.349 + g * 0.686 + b * 0.168);
        data[i + 2] = Math.min(255, r * 0.272 + g * 0.534 + b * 0.131);
      }
      break;

    case "invert": // Invierte colores
      for (let i = 0; i < data.length; i += 4) {
        data[i] = 255 - data[i]; // R
        data[i + 1] = 255 - data[i + 1]; // G
        data[i + 2] = 255 - data[i + 2]; // B
      }
      break;

    case "original": // Vuelve a la imagen original sin filtro
      ctx.putImageData(originalImage, 0, 0);
      return;
  }
  // Dibuja la imagen modificada en el canvas
  ctx.putImageData(imageData, 0, 0);
}
