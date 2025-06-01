<?php

/**
 * PÁGINA DE VOTACIONES
 * @author: Michel Freymann
 * Esta página permite visualizar las fotos participantes aprobadas y votar por una.
 * Tras votar, si lo hacemos en otra foto, nos salta mensaje de aviso de un voto por IP
 */

// Inclusión de variables y funciones 
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Conectamos con la base de datos
$conexion = conectarPDO($host, $user, $password, $bbdd);

$mensaje = "";

// Obtenemos las fechas de inicio y fin de votación
$stmt = $conexion->query("SELECT fecha_votacion, fecha_fin_votacion FROM bases_concurso LIMIT 1");
$fechas = $stmt->fetch(PDO::FETCH_ASSOC);
$fecha_votacion = $fechas['fecha_votacion'];
$fecha_fin_votacion = $fechas['fecha_fin_votacion'];

// Asignamos la fecha actual
$fecha_actual = date('Y-m-d');

// Definimos si la votación es posible según los parámetros
$votacion_activa = ($fecha_actual >= $fecha_votacion) && ($fecha_actual <= $fecha_fin_votacion);

if (!$votacion_activa) {
    if ($fecha_actual < $fecha_votacion) {
        $mensaje = "Lo sentimos, la votación comienza el " . date("d/m/Y", strtotime($fecha_votacion)) . ".";
    } elseif ($fecha_actual > $fecha_fin_votacion) {
        $mensaje = "La votación finalizó el " . date("d/m/Y", strtotime($fecha_fin_votacion)) . ". Gracias por participar.";
    }
}

// Obtener todas las fotos aprobadas
$select = "SELECT foto_id, usuario_id, imagen, tipo_imagen, estado, votos 
           FROM fotografias 
           WHERE estado = 'aprobada'";
$consulta = $conexion->prepare($select);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Procesar imágenes
$fotosProcesadas = [];
foreach ($fotos as $foto) {
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $foto['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $foto;
}

// Función que obtiene IP
function obtenerIP()
{
    return $_SERVER['HTTP_CLIENT_IP'] ??
        $_SERVER['HTTP_X_FORWARDED_FOR'] ??
        $_SERVER['REMOTE_ADDR'];
}

//Asignamos la ip del usuario para poder identificarlo
$ip_usuario = obtenerIP();

// Obtener la foto ya votada (si existe)
$stmt = $conexion->prepare("SELECT foto_id FROM ip_votos WHERE direccionIP = :ip");
$stmt->execute(['ip' => $ip_usuario]);
$fotoVotada = $stmt->fetchColumn();



// Procesamiento del voto
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Solo se permite votar si estamos dentro del rango establecido
    if ($fecha_actual < $fecha_votacion) {
        $mensaje = "Lo sentimos, la votación comienza el " . date("d/m/Y", strtotime($fecha_votacion)) . ".";
        exit();
    }
    if ($fecha_actual > $fecha_fin_votacion) {
        $mensaje = "La votación finalizó el " . date("d/m/Y", strtotime($fecha_fin_votacion)) . ". No se aceptan más votos.";
        exit();
    }


    // Si se pulsa para desvotar
    if (isset($_POST['desvotar']) && $fotoVotada) {

        // Restar el voto de la foto votada
        $conexion->prepare("UPDATE fotografias SET votos = votos - 1 WHERE foto_id = :id")
            ->execute(['id' => $fotoVotada]);

        // Eliminar registro de voto en ip_votos
        $conexion->prepare("DELETE FROM ip_votos WHERE direccionIP = :ip")
            ->execute(['ip' => $ip_usuario]);

        echo "<script>alert('Has cancelado tu voto. Ahora puedes votar por otra foto.'); window.location.href='galeria.php';</script>";
        exit();
    }

    // Si se pulsa para votar una nueva foto
    if (isset($_POST['fotoVotada'])) {

        // Obtener la foto seleccionada
        $nuevaFotoId = $_POST['fotoVotada'];

        if ($fotoVotada) {
            // Si ya se votó antes, no se permite votar de nuevo sin cancelar el anterior
            echo "<script>alert('Solo se permite un voto por IP. Si deseas cambiar tu voto, primero debes cancelarlo.'); window.location.href='galeria.php';</script>";
            exit();

            // Si es el primer voto, se suma y se registra la IP
        } else {
            $conexion->prepare("UPDATE fotografias SET votos = votos + 1 WHERE foto_id = :id")
                ->execute(['id' => $nuevaFotoId]);

            $conexion->prepare("INSERT INTO ip_votos (direccionIP, foto_id) VALUES (:ip, :foto_id)")
                ->execute(['ip' => $ip_usuario, 'foto_id' => $nuevaFotoId]);

            echo "<script>alert('¡Gracias por tu voto!'); window.location.href='galeria.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Votaciones</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Carga de Bootstrap desde CDN (estilos) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo1">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-warning text-center mt-3">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <nav class="navbar navbar-dark navbar-expand-lg">
            <div class="container">
                <h1 class="text-light fs-2 my-0">Galería</h1>

                <!-- Botón hamburguesa para móviles -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="votos.php">Estadísticas</a> </li>
                        <li class="nav-item"> <a class="nav-link" href="../index.php">Principal</a></li>
                    </ul>
                </div>
            </div>
        </nav>


        <!-- Contenedor principal con tarjetas -->
        <div class="container my-4">
            <?php if ($fotosProcesadas): ?>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($fotosProcesadas as $foto): ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card shadow-sm h-100">

                                <!-- Imagen clicable para abrir modal. Se aplica clase 'ajustafoto' para
                                 controlar tamaño y añadir pointer desde el css-->
                                <img src="<?= $foto['imagen'] ?>" alt="Foto participante"
                                    class="card-img-top ajustaFoto"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalFoto"
                                    data-foto="<?= htmlspecialchars($foto['imagen'], ENT_QUOTES) ?>"
                                    data-alt="Foto participante">

                                <div class="card-body d-flex flex-column">

                                    <!-- Si la votación no está activa, se deshabilitan todos los botones -->
                                    <?php if (!$votacion_activa): ?>
                                        <button class="btn btn-secondary w-100 mt-auto" disabled>Votación no disponible</button>

                                    <?php elseif ($foto['foto_id'] == $fotoVotada): ?>
                                        <!-- Botón para cancelar voto si es la foto votada -->
                                        <form method="POST" class="mt-auto">
                                            <input type="hidden" name="desvotar" value="1">
                                            <button type="submit" class="btn btn-danger w-100">Cancelar voto</button>
                                        </form>

                                    <?php elseif ($fotoVotada): ?>
                                        <!-- Botón activo pero bloqueado con aviso si ya se votó otra foto -->
                                        <form class="mt-auto bloqueado" onsubmit="return mostrarAviso();">
                                            <button type="submit" class="btn btn-primary w-100">Votar esta foto</button>
                                        </form>

                                    <?php else: ?>
                                        <!-- Botón para votar si no ha votado aún -->
                                        <form method="POST" class="mt-auto">
                                            <input type="hidden" name="fotoVotada" value="<?= $foto['foto_id'] ?>">
                                            <button type="submit" class="btn btn-primary w-100">Votar esta foto</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No hay fotos disponibles en este momento.</p>
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
                        <h5 class="modal-title text-white" id="modalFotoLabel">Foto ampliada</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <!-- Cuerpo del modal donde se muestra la imagen ampliada -->
                    <div class="modal-body text-center">
                        <img src="" alt="" id="modalImagen" class="img-fluid rounded" style="max-height: 80vh; width: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS para la imagen del modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Gestión de la ampliación de la imagen en el modal -->
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

    <!-- Muestra mensaje si se intentar votar otra foto -->
    <script>
        function mostrarAviso() {
            alert('Solo se permite un voto por IP. Si deseas cambiar tu voto, primero debes cancelarlo.');
            return false;
        }
    </script>
</body>

</html>