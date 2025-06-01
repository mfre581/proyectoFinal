<?php
/* GALERÍA DEL PARTICIPANTE
 * @author: Michel Freymann
 * Permite al participante ver sus fotografías y acceder a la misma galería pero en modo tabla
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Comprobamos el usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$conexion = conectarPDO($host, $user, $password, $bbdd);

// Buscar las fotos del usuario
$select = "SELECT foto_id, imagen, tipo_imagen, estado, votos FROM fotografias WHERE usuario_id = :usuario_id";
$consulta = $conexion->prepare($select);
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Procesar imágenes de base64
$fotosProcesadas = [];
foreach ($fotos as $foto) {
    $fotoProcesada = $foto;
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $fotoProcesada['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $fotoProcesada;
}

// Procesar la eliminación de una foto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $foto_id = $_POST['eliminar_id'];
    $consultaBorrar = "DELETE FROM fotografias WHERE foto_id = :foto_id AND usuario_id = :usuario_id";
    $delete = $conexion->prepare($consultaBorrar);
    $delete->bindParam(':foto_id', $foto_id);
    $delete->bindParam(':usuario_id', $usuario_id);

    if ($delete->execute()) {
        header("Location: tuGaleria.php"); // Recargar la página después de eliminar
        exit();
    } else {
        echo "<p style='color:red'>Error al eliminar la foto.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Tu Galería</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
</head>

<!-- Establece el estilo general de la página -->

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo3">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">


        <!-- Barra de navegación-->
        <nav class="navbar navbar-dark navbar-expand-lg">
            <div class="container">

                <span class="navbar-brand fs-2 fw-bold">Tu galería</span>

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

        <div class="container">

            <main class="container my-5">

                <!-- Galería de fotos si hay imágenes disponibles -->
                <?php if ($fotosProcesadas): ?>
                    <div class="row g-4 justify-content-center">
                        <?php foreach ($fotosProcesadas as $foto): ?>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card shadow-sm h-100 text-center">
                                    <!-- Imagen de la foto con activador de modal al hacer clic -->
                                    <img src="<?= $foto['imagen'] ?>" alt="Foto subida"
                                        class="card-img-top ajustaFoto"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalFoto"
                                        data-foto="<?= htmlspecialchars($foto['imagen'], ENT_QUOTES) ?>"
                                        data-alt="Foto subida">

                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <!-- Estado y cantidad de votos -->
                                        <p class="mb-1"><strong>Estado:</strong> <?= htmlspecialchars($foto['estado']) ?></p>
                                        <p class="mb-1 text-primary"><strong>Votos:</strong> <?= htmlspecialchars($foto['votos']) ?></p>

                                        <!-- Botón para eliminar foto con confirmación -->
                                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta foto?');">
                                            <input type="hidden" name="eliminar_id" value="<?= $foto['foto_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        No hay fotografías para mostrar.
                    </div>
                <?php endif; ?>

                <!-- Botón para volver al principio de la página -->
                <div class="text-center my-4">
                    <a href="#top" class="btn estiloBoton2">Subir</a>
                </div>
        </div>

        <!-- Modal para mostrar la foto grande -->
        <div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark">

                    <!-- Encabezado del modal con botón para cerrar -->
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white">Foto ampliada</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <!-- Cuerpo del modal donde se muestra la imagen ampliada -->
                    <div class="modal-body text-center">
                        <img src="" alt="" id="modalImagen" class="img-fluid rounded" style="max-height: 80vh; width: auto;">
                    </div>
                </div>
            </div>
        </div>


        <!-- Bootstrap JS para la imagen del modal y el menú colapsable -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Referencias al modal y a su imagen 
            const modalFoto = document.getElementById('modalFoto');
            const modalImagen = document.getElementById('modalImagen');

            // Evento que se ejecuta cuando se va a mostrar el modal
            modalFoto.addEventListener('show.bs.modal', event => {
                const trigger = event.relatedTarget;

                // Establece la imagen y su alt desde los atributos del elemento clicado
                modalImagen.src = trigger.getAttribute('data-foto');
                modalImagen.alt = trigger.getAttribute('data-alt');
            });
        </script>

</body>

</html>