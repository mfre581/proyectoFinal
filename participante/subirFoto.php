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

// Identificamos al usuario logeado
$usuario_id = $_SESSION['usuario_id'];

$mensaje = "";  // Mensaje para mostrar al usuario
$errores = [];  // Array para guardar errores

// Conecta a la base de datos usando PDO
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtener de las bases del concurso y el máximo de tamaño permitido y de número de fotos
$consulta = $conexion->query("SELECT max_tamano_mb, max_fotos FROM bases_concurso");
$bases = $consulta->fetch(PDO::FETCH_ASSOC);

// Asignamos los máximos
$maxMB = $bases['max_tamano_mb'];
$numMaxImagenes = $bases['max_fotos'];

// Se convierte MB a bytes
$maxTamano = $maxMB * 1024 * 1024;

// Consulta cuántas imágenes ha subido el usuario para la validación
$consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$numImagenes = $consulta->fetchColumn();

// Calculamos cuantas fotos podemos subir todavía
$fotosPorSubir = $numMaxImagenes - $numImagenes;


/*** PROCESAMIENTO DEL FORMULARIO  ***/

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['imagenBase64'])) {

  // Se recibe la imagen
  $imagenBase64 = $_POST['imagenBase64'];

  // Detecta el tipo MIME (data:image/jpeg;base64, o data:image/png;base64)
  if (preg_match('#^data:image/(jpeg|png);base64,#i', $imagenBase64, $matches)) {
    $mimeType = 'image/' . strtolower($matches[1]);
  } else {
    $errores[] = "Formato de imagen no válido.";
  }

  // Limpiar base64 quitando encabezado y caracteres no válidos
  $imagenBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imagenBase64);
  $imagenBase64 = str_replace([' ', "\n", "\r"], '', $imagenBase64);

  // Obtenemos la longitud en caracteres de la cadena base64 ya limpia
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
  $tamanoEstimado = ($base64Length * 3 / 4) - $padding;

  $base64Length = strlen($imagenBase64);
  $padding = substr_count(substr($imagenBase64, -2), '=');

  // Recalcula el tamaño después de limpiar la cadena base64 para que sea más precisa.
  $tamanoEstimado = ($base64Length * 3 / 4) - $padding;

  // Verifica si el tamaño de la imagen supera el límite permitido
  if ($tamanoEstimado > $maxTamano) {
    $errores[] = "La imagen es demasiado grande. El tamaño máximo permitido es $maxMB MB.";
  } else {
    // Si el tamaño es correcto, consultamos cuántas imágenes ha subido el usuario
    $consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
    $consulta->bindParam(':usuario_id', $usuario_id);
    $consulta->execute();
    $numImagenes = $consulta->fetchColumn();

    // Si no puede subir más imágenes, se crea el mensaje correspondiente
    if ($numImagenes >= $numMaxImagenes) {
      $errores[] = "No puedes subir más de $numMaxImagenes imágenes.";
    } else {

      // Si todo está correcto, insertamos la imagen en la bd
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

// Se consultan de nuevo las imágenes del usuario para actualizar el mensaje tras cada subida
$consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$numImagenes = $consulta->fetchColumn();
$fotosPorSubir = $numMaxImagenes - $numImagenes;

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>Subir foto</title>
  <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Link al archivo css que aplica parte del estilo -->
  <link rel="stylesheet" href="../css/estilo.css">
</head>

<!-- Establece el estilo general de la página -->

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo3">

  <!-- Contenedor principal con estilo de tarjeta -->
  <div class="card shadow p-4" style="max-width: 1000px; width: 100%;">

    <!-- Barra de navegación-->
    <nav class="navbar navbar-dark navbar-expand-lg">
      <div class="container">

        <span class="navbar-brand fs-2 fw-bold">Sube una fotografía</span>

        <!-- Botón hamburguesa para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar colapsable -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav">
            <li class="nav-item"> <a class="nav-link" href="participante.php">Tu panel </a></li>
            <li class="nav-item"> <a class="nav-link" href="../cerrarSesion/cerrar_sesion.php">Cerrar sesión</a></li>
          </ul>
        </div>
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

      <!-- Indicadores de bases -->
      <h5 class="m-4 text-center">Tamaño máximo <?= $maxMB ?> MB</h5>
      <h5 class="m-4 text-center">Fotos que aún puedes subir: <?= $fotosPorSubir ?></h5>

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


  <!-- JS externo que gestiona canvas y filtros-->
  <script src="filtros.js"></script>

  <!-- Bootstrap JS para el navbar colapsable -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>