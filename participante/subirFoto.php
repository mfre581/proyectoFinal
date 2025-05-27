<?php
/* PAGINA DE SUBIDAS DE FOTOGRAFÍAS
 * @author: Michel Freymann
 * Permite al participante seleccionar una foto, editarla y subirla.
 * Tras la subida, se muestra en su galería, y queda a la espera de ser aprobada por el administrador
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Si no hay usuario logueado, redirige a página principal
if (!isset($_SESSION['usuario_id'])) {
  header("Location: ../index.php");
  exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";  // Mensaje para mostrar al usuario
$errores = [];  // Array para guardar errores

// Conecta a la base de datos usando PDO
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtener máximo de tamaño permitido y de número de fotos
$consulta = $conexion->query("SELECT max_tamano_mb, max_fotos FROM bases_concurso");
$config = $consulta->fetch(PDO::FETCH_ASSOC);
$maxMB = (int)$config['max_tamano_mb'];
$numMaxImagenes = (int)$config['max_fotos'];
$maxSize = $maxMB * 1024 * 1024; // Convertir MB a bytes

// Procesa el formulario al enviar (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['imagenBase64'])) {
  $imagenBase64 = $_POST['imagenBase64'];

  // Detecta tipo MIME (data:image/jpeg;base64, o data:image/png;base64)
  if (preg_match('#^data:image/(jpeg|png);base64,#i', $imagenBase64, $matches)) {
    $mimeType = 'image/' . strtolower($matches[1]);
  } else {
    $errores[] = "Formato de imagen no válido.";
  }

  // Limpiar base64 (quitar encabezado y caracteres no válidos)
  $imagenBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imagenBase64);
  $imagenBase64 = str_replace([' ', "\n", "\r"], '', $imagenBase64);

  // Obtiene la longitud en caracteres de la cadena base64 ya limpia
  $base64Length = strlen($imagenBase64);


  /* Pasamos a calcular el tamaño aproximado real de la imagen
 * Esto es necesario porque el navegador nos envía la imagen como una cadena Base64, que es más larga que los datos 
 * binarios reales. Para aplicar correctamente la validación del tamaño máximo permitido (en bytes), debemos estimar
 *  cuánto ocupará la imagen una vez decodificada, sin haberla decodificada aún
 */

  /*Busca cuántos `=` hay al final de la cadena; estos indican relleno(padding) y son importantes
   para calcular el tamaño exacto de los datos decodificados, ya que por defecto los datos en base64 son múltiplos
   de 4, y si no encaja lo rellena con padding*/
  $padding = substr_count(substr($imagenBase64, -2), '=');

  /*Esta fórmula calcula el tamaño estimado (en bytes) de los datos originales binarios, ya que la codificación Base64
   convierte cada 3 bytes de datos binarios en 4 caracteres de texto (y le restamos el padding)
 */
  $estimatedSize = ($base64Length * 3 / 4) - $padding;

  // Validar tamaño estimado contra límite máximo
  if ($estimatedSize > $maxSize) {
    $errores[] = "La imagen es demasiado grande. El tamaño máximo permitido es $maxMB MB.";
  } else {
    // Decodificar imagen
    $imagen = base64_decode($imagenBase64);

    // Validación adicional de tamaño real decodificado
    if (strlen($imagen) > $maxSize) {
      $errores[] = "La imagen es demasiado grande después de decodificar.";
    } else {
      // Consultar cuántas imágenes ha subido el usuario (dinámico)
      $consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
      $consulta->bindParam(':usuario_id', $usuario_id);
      $consulta->execute();
      $numImagenes = $consulta->fetchColumn();

      // Validar límite de imágenes del usuario
      if ($numImagenes >= $numMaxImagenes) {
        $errores[] = "No puedes subir más de $numMaxImagenes imágenes.";
      } else {
        /*Si hemos pasado todas las validaciones, podemos codificar la imagen para almacenarla en base64 en la bd*/
        $imagenBase64 = base64_encode($imagen);

        $insert = $conexion->prepare("
          INSERT INTO fotografias (usuario_id, imagen, tipo_imagen, created_at, updated_at)
          VALUES (:usuario_id, :imagen, :tipo_imagen, NOW(), NOW())
        ");

        $insert->bindParam(':usuario_id', $usuario_id);
        $insert->bindParam(':imagen', $imagenBase64);
        $insert->bindParam(':tipo_imagen', $mimeType);

        if ($insert->execute()) {
          $mensaje = "Imagen subida correctamente.";
        } else {
          $errores[] = "Error al subir la imagen.";
        }
      }
    }
  }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>Subir foto</title>
  <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Link al archivo css que aplica parte del estilo -->
  <link rel="stylesheet" href="../css/estilo.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo3">

  <div class="card shadow p-4" style="max-width: 1000px; width: 100%;">
    <nav class="navbar navbar-dark">
      <div class="container">
        <span class="navbar-brand fs-1 fw-bold">Sube una fotografía</span>
        <a href="./participante.php" class="btn btn-outline-light">Volver</a>
      </div>
    </nav>

    <main>
      <!-- Muestra mensaje de éxito si lo hay -->
      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <!-- Muestra errores si los hay -->
      <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Indicador de tamaño máximo permitido -->
      <h5 class="m-4 text-center">Tamaño máximo <?= $maxMB ?> MB</h5>

      <!-- Formulario para subir la imagen -->
      <form id="formulario" action="" method="post" class="mb-4">
        <input class="form-control mb-4" type="file" id="imagenInput" accept="image/jpeg,image/png" required>

        <!-- Aquí agregamos el input oculto que enviará la imagen comprimida -->
        <input type="hidden" name="imagenBase64" id="imagenBase64">

        <div class="d-flex align-items-start gap-4">
          <!-- Canvas para previsualizar -->
          <div class="d-flex justify-content-center mb-3">
            <canvas id="previewCanvas" width="600" height="400" class="border bg-white"></canvas>
          </div>

          <!-- Botones de filtros -->
          <div class="d-flex justify-content-center gap-2 flex-column">
            <button type="button" class="btn btn-outline-dark" onclick="applyFilter('original')">Original</button>
            <button type="button" class="btn btn-outline-info" onclick="applyFilter('brightness')">Brillo</button>
            <button type="button" class="btn btn-outline-success" onclick="applyFilter('contrast')">Contraste</button>
            <button type="button" class="btn btn-outline-danger" onclick="applyFilter('redTint')">Tono rojizo</button>
            <button type="button" class="btn btn-outline-secondary" onclick="applyFilter('grayscale')">Blanco y negro</button>
            <button type="button" class="btn btn-outline-warning" onclick="applyFilter('sepia')">Sepia</button>
            <button type="button" class="btn btn-outline-primary" onclick="applyFilter('invert')">Invertir colores</button>
          </div>
        </div>
        <div class="text-center my-2">
          <!-- Botón de envío del formulario -->
          <button type="submit" class="btn btn-success">Subir Imagen</button>
        </div>
      </form>


    </main>
  </div>


  <!------ JS 

    Este script permite al usuario cargar una imagen y verla en una vista previa (canvas)
    Además, antes de enviarla al servidor:
     - La convierte a formato Base64 comprimido en JPEG para reducir tamaño
     - Ajusta la vista previa a un tamaño máximo sin deformarla (respetando la proporción)
     - Inserta la imagen codificada en un campo oculto del formulario para que PHP la procese.
    
      También contiene la función que gestiona el proceso de edición ------>


  <script>
    // Obtenemos el elemento <canvas> donde se mostrará la imagen
    const canvas = document.getElementById('previewCanvas');

    // Obtenemos el contexto 2D del canvas, que permite dibujar imágenes, formas, etc.
    const ctx = canvas.getContext('2d');

    // Variable que almacenará una copia de la imagen original cargada en el canvas.
    // Esto es necesario para poder restaurar la imagen original cada vez que se aplique un nuevo filtro.
    let originalImage = null;

    // Escuchar evento al cargar imagen
    document.getElementById('imagenInput').addEventListener('change', function(e) {
      const file = e.target.files[0];

      //Comprueba que el archivo cargado es una imagen válida (jpeg, png…)
      if (file && file.type.startsWith('image/')) {

        // Creamos un FileReader para leer el contenido del archivo como base64
        const reader = new FileReader();

        // Definimos qué hacer cuando el archivo se haya leído completamente
        reader.onload = function(event) {

          // Creamos una nueva imagen HTML que cargará la imagen del archivo
          const img = new Image();

          // Cuando la imagen se cargue completamente obtenemos el tamaño original de la imagen
          img.onload = function() {
            const originalWidth = img.width;
            const originalHeight = img.height;

            // Definimos un tamaño máximo permitido para mostrar en el canvas
            const MAX_WIDTH = 800;
            const MAX_HEIGHT = 400;

            // Calculamos la proporción para redimensionar la imagen sin deformarla
            const ratio = Math.min(MAX_WIDTH / originalWidth, MAX_HEIGHT / originalHeight, 1);

            // Establecemos el tamaño interno real del canvas (sin escalado)
            canvas.width = originalWidth;
            canvas.height = originalHeight;

            // Dibujamos la imagen original en el canvas
            ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

            // ESCALADO VISUAL para que sea responsivo y no distorsione
            canvas.style.width = '100%'; // que ocupe el ancho del contenedor padre
            canvas.style.height = 'auto'; // mantenga proporción

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
    document.getElementById('formulario').addEventListener('submit', function(e) {
      const calidad = 0.8; // Ajusta la calidad según sea necesario
      const imagenBase64 = canvas.toDataURL("image/jpeg", calidad); // Comprime a JPEG
      document.getElementById('imagenBase64').value = imagenBase64;
    });

    // Aplica filtros a la imagen en el canvas según el botón presionado
    function applyFilter(type) {
      if (!originalImage) return; // No hacer nada si no hay imagen cargada
      ctx.putImageData(originalImage, 0, 0); // Restaurar imagen original antes de aplicar filtro
      let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      let data = imageData.data;

      switch (type) {
        case 'grayscale': // Blanco y negro
          for (let i = 0; i < data.length; i += 4) {
            let avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
            data[i] = data[i + 1] = data[i + 2] = avg;
          }
          break;

        case 'brightness': // Aumenta brillo
          for (let i = 0; i < data.length; i += 4) {
            data[i] += 40; // R
            data[i + 1] += 40; // G
            data[i + 2] += 40; // B
          }
          break;

        case 'contrast': // Ajusta contraste
          let contrast = 40; // valor entre -255 a 255
          let factor = (259 * (contrast + 255)) / (255 * (259 - contrast));
          for (let i = 0; i < data.length; i += 4) {
            data[i] = factor * (data[i] - 128) + 128;
            data[i + 1] = factor * (data[i + 1] - 128) + 128;
            data[i + 2] = factor * (data[i + 2] - 128) + 128;
          }
          break;

        case 'redTint': // Tono rojizo
          for (let i = 0; i < data.length; i += 4) {
            data[i] += 50; // R
          }
          break;

        case 'sepia': // Filtro sepia clásico
          for (let i = 0; i < data.length; i += 4) {
            let r = data[i],
              g = data[i + 1],
              b = data[i + 2];
            data[i] = Math.min(255, r * .393 + g * .769 + b * .189);
            data[i + 1] = Math.min(255, r * .349 + g * .686 + b * .168);
            data[i + 2] = Math.min(255, r * .272 + g * .534 + b * .131);
          }
          break;

        case 'invert': // Invierte colores
          for (let i = 0; i < data.length; i += 4) {
            data[i] = 255 - data[i]; // R
            data[i + 1] = 255 - data[i + 1]; // G
            data[i + 2] = 255 - data[i + 2]; // B
          }
          break;

        case 'original': // Vuelve a la imagen original sin filtro
          ctx.putImageData(originalImage, 0, 0);
          return;
      }
      // Dibuja la imagen modificada en el canvas
      ctx.putImageData(imageData, 0, 0);
    }
  </script>

</body>

</html>