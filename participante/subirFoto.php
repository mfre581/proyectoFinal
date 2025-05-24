<?php
// Incluye variables y funciones necesarias para la conexión y utilidades
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Inicia sesión para manejar usuario autenticado
session_start();

// Si no hay usuario logueado, redirige a página principal
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../principal.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";  // Mensaje para mostrar al usuario
$errores = [];  // Array para guardar errores

// Conecta a la base de datos usando PDO
$conexion = conectarPDO($host, $user, $password, $bbdd);
$maxSize = 2 * 1024 * 1024; // Tamaño máximo permitido para la imagen: 2 MB

// Procesa el formulario al enviar (POST) y si llega la imagen en base64
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['imagenBase64'])) {
    $imagenBase64 = $_POST['imagenBase64'];

    // Quita el prefijo data:image/png;base64, para obtener sólo los datos base64
    $imagenBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imagenBase64);

    // Decodifica la imagen base64 a binario
    $imagen = base64_decode($imagenBase64);

    // Define el tipo MIME (el canvas siempre genera PNG)
    $mimeType = 'image/png';

    // Comprueba que la imagen no supere el tamaño máximo permitido
    if (strlen($imagen) > $maxSize) {
        $errores[] = "La imagen es demasiado grande. El tamaño máximo permitido es 2 MB.";
    } else {
        // Consulta cuántas imágenes ha subido ya el usuario
        $consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
        $consulta->bindParam(':usuario_id', $usuario_id);
        $consulta->execute();
        $numImagenes = $consulta->fetchColumn();

        // Consulta el número máximo de fotos permitidas en el concurso
        $consultaMax = $conexion->prepare("SELECT max_fotos FROM bases_concurso");
        $consultaMax->execute();
        $numMaxImagenes = $consultaMax->fetchColumn();

        // Si el usuario ya llegó al límite, añade error
        if ($numImagenes >= $numMaxImagenes) {
            $errores[] = "No puedes subir más de $numMaxImagenes imágenes.";
        } else {
            // Codifica de nuevo la imagen para guardarla en base64 en la BD
            $imagenBase64 = base64_encode($imagen);

            // Prepara la inserción de la foto en la base de datos
            $insert = $conexion->prepare("INSERT INTO fotografias (usuario_id, imagen, tipo_imagen, created_at, updated_at) VALUES (:usuario_id, :imagen, :tipo_imagen, NOW(), NOW())");
            $insert->bindParam(':usuario_id', $usuario_id);
            $insert->bindParam(':imagen', $imagenBase64);
            $insert->bindParam(':tipo_imagen', $mimeType);

            // Ejecuta la inserción y muestra mensaje de éxito o error
            if ($insert->execute()) {
                $mensaje = "Imagen subida correctamente.";
            } else {
                $errores[] = "Error al subir la imagen.";
            }
        }
    }
}

// Consulta el nombre del usuario para mostrarlo si se desea
$select = "SELECT nombre FROM usuarios WHERE usuario_id = :usuario_id";
$consulta = $conexion->prepare($select);
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$nombre = $consulta->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Subir foto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/cssindex.css">
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100">

  <!-- Contenedor principal en forma de tarjeta -->
  <div class="card shadow p-4" style="max-width: 900px; width: 100%;">
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-dark bg-dark rounded mb-4">
      <div class="container-fluid">
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
      <h5 class="mb-4 text-center">Tamaño máximo 2 MB</h5>

      <!-- Formulario para subir la imagen -->
      <form id="uploadForm" action="" method="post" enctype="multipart/form-data" class="mb-4">
        <!-- Input para seleccionar imagen -->
        <input class="form-control mb-4" type="file" id="imagenInput" accept="image/jpeg,image/png" required>

        <div class="row">
          <!-- Vista previa de la imagen en un canvas -->
          <div class="col-md-8 mb-3 d-flex justify-content-center align-items-center">
            <canvas id="previewCanvas" class="border rounded w-100" style="max-width: 100%; height: auto; max-height: 400px;"></canvas>
          </div>

          <!-- Botones para aplicar filtros -->
          <div class="col-md-4 d-flex flex-column gap-2">
            <button type="button" class="btn btn-outline-dark" onclick="applyFilter('original')">Original</button>
            <button type="button" class="btn btn-outline-info" onclick="applyFilter('brightness')">Brillo</button>
            <button type="button" class="btn btn-outline-success" onclick="applyFilter('contrast')">Contraste</button>
            <button type="button" class="btn btn-outline-danger" onclick="applyFilter('redTint')">Tono rojizo</button>
            <button type="button" class="btn btn-outline-secondary" onclick="applyFilter('grayscale')">Blanco y negro</button>
            <button type="button" class="btn btn-outline-warning" onclick="applyFilter('sepia')">Sepia</button>
            <button type="button" class="btn btn-outline-primary" onclick="applyFilter('invert')">Invertir colores</button>
          </div>
        </div>

        <!-- Botón para subir la imagen -->
        <div class="text-center mt-4">
          <button type="button" class="btn btn-primary" onclick="prepareImage()">Subir Imagen</button>
        </div>

        <!-- Campo oculto para enviar la imagen en base64 -->
        <input type="hidden" name="imagenBase64" id="imagenBase64">
      </form>
    </main>
  </div>

  <script>
      // Obtiene referencias al canvas y su contexto para dibujar la imagen
      let canvas = document.getElementById('previewCanvas');
      let ctx = canvas.getContext('2d');
      let originalImage = null;  // Guarda la imagen original sin filtros

      // Cuando el usuario selecciona un archivo, se carga y se muestra en el canvas
      document.getElementById('imagenInput').addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file && file.type.startsWith('image/')) {
              const reader = new FileReader();
              reader.onload = function(event) {
                  const img = new Image();
                  img.onload = function() {
                      // Ajusta el tamaño del canvas a la imagen y la dibuja
                      canvas.width = img.width;
                      canvas.height = img.height;
                      ctx.drawImage(img, 0, 0);
                      // Guarda la imagen original para aplicar filtros después
                      originalImage = ctx.getImageData(0, 0, canvas.width, canvas.height);
                  };
                  img.src = event.target.result;
              };
              reader.readAsDataURL(file);
          }
      });

      // Aplica filtros a la imagen en el canvas según el botón presionado
      function applyFilter(type) {
          if (!originalImage) return;  // No hacer nada si no hay imagen cargada
          ctx.putImageData(originalImage, 0, 0);  // Restaurar imagen original antes de aplicar filtro
          let imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          let data = imageData.data;

          switch (type) {
              case 'grayscale':  // Blanco y negro
                  for (let i = 0; i < data.length; i += 4) {
                      let avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
                      data[i] = data[i + 1] = data[i + 2] = avg;
                  }
                  break;

              case 'brightness':  // Aumenta brillo
                  for (let i = 0; i < data.length; i += 4) {
                      data[i] += 40;     // R
                      data[i + 1] += 40; // G
                      data[i + 2] += 40; // B
                  }
                  break;

              case 'contrast':  // Ajusta contraste
                  let contrast = 40; // valor entre -255 a 255
                  let factor = (259 * (contrast + 255)) / (255 * (259 - contrast));
                  for (let i = 0; i < data.length; i += 4) {
                      data[i] = factor * (data[i] - 128) + 128;
                      data[i + 1] = factor * (data[i + 1] - 128) + 128;
                      data[i + 2] = factor * (data[i + 2] - 128) + 128;
                  }
                  break;

              case 'redTint':  // Tono rojizo
                  for (let i = 0; i < data.length; i += 4) {
                      data[i] += 50; // R
                  }
                  break;

              case 'sepia':  // Filtro sepia clásico
                  for (let i = 0; i < data.length; i += 4) {
                      let r = data[i], g = data[i + 1], b = data[i + 2];
                      data[i] = Math.min(255, r * .393 + g * .769 + b * .189);
                      data[i + 1] = Math.min(255, r * .349 + g * .686 + b * .168);
                      data[i + 2] = Math.min(255, r * .272 + g * .534 + b * .131);
                  }
                  break;

              case 'invert':  // Invierte colores
                  for (let i = 0; i < data.length; i += 4) {
                      data[i] = 255 - data[i];       // R
                      data[i + 1] = 255 - data[i + 1]; // G
                      data[i + 2] = 255 - data[i + 2]; // B
                  }
                  break;

              case 'original':  // Vuelve a la imagen original sin filtro
                  ctx.putImageData(originalImage, 0, 0);
                  return;
          }
          // Dibuja la imagen modificada en el canvas
          ctx.putImageData(imageData, 0, 0);
      }

      // Prepara la imagen para subir: pasa la imagen del canvas a base64 y envía el formulario
      function prepareImage() {
          if (!originalImage) {
              alert("Debes seleccionar una imagen antes de subir.");
              return;
          }

          // Convierte el canvas a imagen PNG en base64
          const imagenBase64 = canvas.toDataURL("image/png");
          // Coloca el valor en el campo oculto para enviarlo al servidor
          document.getElementById('imagenBase64').value = imagenBase64;
          // Limpia el input de archivo para evitar reenvíos accidentales
          document.getElementById('imagenInput').value = "";
          // Envía el formulario
          document.getElementById('uploadForm').submit();
      }
  </script>

</body>

</html>
