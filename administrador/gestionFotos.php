<?php
/* PÁGINA QUE GESTIONA FOTOGRAFÍAS
* @author: Michel Freymann
* En esta página el administrador puede aceptar, rechazar y eliminar fotográfias subidas por los participantes
* */


// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();


// Verifica que el usuario es administrador, si no redirige a index
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");  // Redirigimos si no tiene permiso
    exit();
}

// Conectamos a la base de datos 
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtener las fotografías en el orden determinado, y junto con los datos del usuario que las subió
$select = "SELECT f.foto_id, f.usuario_id, f.imagen, f.estado, f.tipo_imagen, f.votos, u.nombre, u.apellido, u.email 
           FROM fotografias f 
           JOIN usuarios u ON f.usuario_id = u.usuario_id
           ORDER BY FIELD(f.estado, 'Pendiente', 'Aprobada', 'Rechazada')";
$consulta = $conexion->prepare($select);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Creamos un array para almacenar las fotos procesadas (añadiendo el prefijo base64 cuando falte)
$fotosProcesadas = [];

foreach ($fotos as $foto) {
    // Si la imagen no tiene el prefijo "data:image" (base64), lo añadimos
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $foto['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $foto;
}

// PROCESO DE CAMBIO DE ESTADO (Aceptar / Rechazar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Comprobamos qué botón se ha pulsado para determinar el nuevo estado
    if (isset($_POST['aceptar'])) {
        $foto_id = $_POST['aceptar'];  // Id de la foto a aceptar
        $nuevo_estado = "Aprobada";    // Nuevo estado
    } elseif (isset($_POST['rechazar'])) {
        $foto_id = $_POST['rechazar']; // Id de la foto a rechazar
        $nuevo_estado = "Rechazada";   // Nuevo estado
    }

    if (isset($foto_id)) {
        // Actualizamos el estado en la base de datos
        $consulta = "UPDATE fotografias SET estado = :nuevo_estado WHERE foto_id = :foto_id";
        $update = $conexion->prepare($consulta);
        $update->bindParam(':nuevo_estado', $nuevo_estado);
        $update->bindParam(':foto_id', $foto_id);

        if ($update->execute()) {
            // Recargamos la página para mostrar los cambios
            header("Location: gestionFotos.php");
            exit();
        } else {
            echo "<p style='color:red'>Error al actualizar el estado.</p>";
        }
    }
}

// PROCESO DE ELIMINACIÓN DE FOTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $foto_id = $_POST['eliminar_id'];

    // Eliminamos los votos asociados a la foto
    $deleteVotos = $conexion->prepare("DELETE FROM ip_votos WHERE foto_id = :foto_id");
    $deleteVotos->execute(['foto_id' => $foto_id]);

    // Eliminamos la foto de la base de datos
    $consulta = "DELETE FROM fotografias WHERE foto_id = :foto_id";
    $delete = $conexion->prepare($consulta);
    $delete->bindParam(':foto_id', $foto_id);

    if ($delete->execute()) {
        // Recargamos la página para actualizar el listado
        header("Location: gestionFotos.php");
        exit();
    } else {
        echo "<p style='color:red'>Error al eliminar la foto.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Gestión de Fotografías</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
</head>

<!-- Establece el estilo general de la página -->
<body class="bg-light fondo2">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4 mx-auto w-100" style="max-width: 780px;">
 
        <!-- Barra de navegación-->
        <nav class="navbar navbar-dark navbar-expand-lg">
            <div class="container">

                <span class="navbar-brand fs-2 fw-bold">Gestión de Fotografías</span>

                <!-- Botón hamburguesa para móviles -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="administrador.php">Tu panel </a></li>
                        <li class="nav-item"> <a class="nav-link" href="../cerrarSesion/cerrar_sesion.php">Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenido principal -->
        <main class="container my-4">

            <!-- Botones para saltar a las secciones -->
            <div class="mb-4 d-flex justify-content-center gap-2">
                <a href="#aprobadas" class="btn btn-primary">Ver Fotos Aprobadas</a>
                <a href="#rechazadas" class="btn btn-warning">Ver Fotos Rechazadas</a>
            </div>

            <!-- Comprueba si hay fotos procesadas para mostrar -->
            <?php if ($fotosProcesadas): ?>
                <div class="d-flex flex-column gap-4">

                    <?php
                    // Establecemos las variables que determinan la primera foto con el estado correspondientes
                    $primerAprobada = true;
                    $primerRechazada = true;
                    ?>

                    <?php foreach ($fotosProcesadas as $foto):

                        // Asignamos el ID solo a la primera foto de cada estado relevante
                        $id = "";
                        if ($foto['estado'] === 'aprobada' && $primerAprobada) {
                            $id = "aprobadas";   // Ancla para fotos aprobadas
                            $primerAprobada = false;
                        } elseif ($foto['estado'] === 'rechazada' && $primerRechazada) {
                            $id = "rechazadas";  // Ancla para fotos rechazadas
                            $primerRechazada = false;
                        }
                    ?>

                        <!-- Tarjeta para mostrar cada foto con su información -->
                        <div class="card p-3 d-flex flex-column flex-md-row align-items-center" style="gap: 1rem; min-height: 250px;"
                            <?php if ($id !== "") echo 'id="' . $id . '"'; ?>><!-- Aquí añade el id al div generado 
                                                                                 para el ancla -->

                            <!-- Imagen de la foto subida -->
                            <img src="<?= $foto['imagen'] ?>" alt="Foto" class="img-fluid rounded" style="width: 100%; max-width: 400px; height: auto; object-fit: cover; flex-shrink: 0"; loading="lazy">

                            <!-- Información del usuario y acciones -->
                            <div class="d-flex flex-column flex-grow-1 justify-content-between" style="min-width: 0;">
                                <div>
                                    <p><strong>Estado:</strong> <?= htmlspecialchars($foto['estado']) ?></p>
                                    <p><strong>Usuario:</strong> <?= htmlspecialchars($foto['nombre'] . ' ' . $foto['apellido']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($foto['email']) ?></p>
                                    <p><strong>Votos:</strong> <?= (int)$foto['votos'] ?></p>
                                </div>

                                <!-- Área de botones para aceptar, rechazar o eliminar la foto -->
                                <div class="d-flex justify-content-start gap-2 flex-wrap mt-3">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="aceptar" value="<?= $foto['foto_id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Aceptar</button>
                                    </form>
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="rechazar" value="<?= $foto['foto_id'] ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">Rechazar</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta foto?');" class="m-0">
                                        <input type="hidden" name="eliminar_id" value="<?= $foto['foto_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center fs-5 mt-4">No hay fotografías para mostrar.</p>
            <?php endif; ?>
        </main>

        <!-- Botón para volver al principio de la página -->
        <div id="btnVolverArriba" class="text-center my-4 d-none">
            <a href="#top" class="btn btn-sm estiloBoton2">↑ Volver arriba</a>
        </div>
    </div>

    <!-- Bootstrap JS para el navbar colapsable -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script que muestra el botón "volverArriba" si hay más de 2 fotos en móviles o más de 8 en escritorio -->
    <script>
        const numFotos = <?= count($fotosProcesadas) ?>;
    </script>
    <script src="../js/volverArriba.js"></script>

</body>

</html>