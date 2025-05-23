<?php
/* PÁGINA DE SUBIDA DE FOTOGRAFÍAS
 * @author: Michel Freymann
 * Permite subir las fotos para el concurso.
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Comprobamos el usuario 
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    header("Location: ../principal.php");  // Redirige si no está logueado
    exit();
}

$mensaje = "";
$errores = [];

$conexion = conectarPDO($host, $user, $password, $bbdd);

// Definir el tamaño máximo permitido para las imágenes (en bytes)
$maxSize = 2 * 1024 * 1024; // 2 MB

// SUBIR IMAGEN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["imagen"])) {

    // Asignamos la ruta temporal del archivo cargado
    $imagenTmp = $_FILES["imagen"]["tmp_name"];
    /*Usamos tmp_name porque es el lugar donde PHP guarda el archivo subido antes de procesarlo. $imagenTmp es la
     ruta del archivo temporal de la imagen en el servidor, que es proporcionada por PHP cuando se sube un archivo. 
     Esta ruta temporal apunta a un archivo que contiene los datos binarios de la imagen que el usuario seleccionó.*/

    $imagenInfo = getimagesize($imagenTmp);
    /*getimagesize() no solo obtiene el tamaño de la imagen, también proporciona información adicional como el tipo 
     (JPEG, PNG, GIF, etc.), las dimensiones (ancho y alto) y, opcionalmente, otros datos como la 
    profundidad de color y el tipo de la imagen. */

    if ($imagenInfo !== false) {
        $mimeType = $imagenInfo['mime']; // Esto obtiene el tipo de la imagen (jpeg, png, gif, etc.)

        // Solo aceptamos imágenes JPEG y PNG
        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {

            // Verificar el tamaño de la imagen
            if ($_FILES["imagen"]["size"] > $maxSize) {
                $errores[] = "La imagen es demasiado grande. El tamaño máximo permitido es 2 MB.";
            } else {
                // Si todo es correcto, asignamos la imagen a la varibale
                $imagen = file_get_contents($imagenTmp);
                /*file_get_contents Lee todo el contenido del archivo especificado ($imagenTmp) 
                Devuelve el contenido del archivo como una cadena de texto que no es una cadena normal, sino una 
                cadena binaria que contiene los datos crudos de la imagen.*/

                $imagenBase64 = base64_encode($imagen); // Y lo convertimos a Base64

                //Comprobamos el número de imágenes que ya tiene el usuario
                $consulta = $conexion->prepare("SELECT COUNT(*) FROM fotografias WHERE usuario_id = :usuario_id");
                $consulta->bindParam(':usuario_id', $usuario_id);
                $consulta->execute(); 
                $numImagenes = $consulta->fetchColumn();

                //Comprobamos el número máximo de imágenes permitidas por participante
                $consultaMax = $conexion->prepare("SELECT max_fotos FROM bases_concurso");
                $consultaMax->execute(); 
                $numMaxImagenes = $consultaMax->fetchColumn();

                //Si no se permiten más imágenes mostramos mensaje de error
                if ($numImagenes >= $numMaxImagenes) {
                    $errores[] = "No puedes subir más de $numMaxImagenes imágenes.";
                } else {
                    // Insertar en la base de datos
                    $insert = $conexion->prepare("INSERT INTO fotografias (usuario_id, imagen, tipo_imagen, created_at, updated_at) VALUES (:usuario_id, :imagen, :tipo_imagen, NOW(), NOW())");
                    $insert->bindParam(':usuario_id', $usuario_id);
                    $insert->bindParam(':imagen', $imagenBase64);
                    $insert->bindParam(':tipo_imagen', $mimeType);

                    if ($insert->execute()) {
                        $mensaje = "Imagen subida correctamente";
                    } else {
                        echo "<p style='color:red'>Error al subir imagen.</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:red'>Solo se permiten imágenes en formato JPEG o PNG.</p>";
        }
    } else {
        echo "<p style='color:red'>El archivo no es una imagen válida.</p>";
    }
}
// Buscar en la tabla de usuarios para extraer su nombre para el mensaje de bienvenida
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../css/cssindex.css">
    <title>Subir foto</title>
</head>

<body class="bg-light">

    <!-- Navbar  -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand fs-1 fw-bold">Subida de fotos</span>
            <a href="./participante.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

     <!-- Formulario de subida de fotografías  -->
    <main class="container my-5">
        <h2 class="mb-4">Subir una Fotografía</h2>
         <h5 class="mb-4">Tamaño máximo 2 MB</h5>
        <form action="" method="post" enctype="multipart/form-data" class="mb-4">
            <input class="form-control mb-3" type="file" name="imagen" accept="image/jpeg,image/png" required>
            <button type="submit" class="btn btn-primary">Subir Imagen</button>
        </form>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>