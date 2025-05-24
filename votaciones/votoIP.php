<?php

/**
 * PÁGINA DE VOTACIONES
 * @author: Michel Freymann
 * Esta página permite visualizar las fotos participantes aprobadas y votar por una.
 * Tras votar, si pulsamos en "votar" en una foto disinta, se cambia el estado de la foto votada anteriormente a "no votada"
 * ya que solo se permite un voto por IP
 */

// Inclusión de variables y funciones 
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

$conexion = conectarPDO($host, $user, $password, $bbdd);

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
$fotoVotada = $stmt->fetchColumn(); // Puede ser null si no ha votado

// Procesamiento del voto
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['fotoVotada'])) {
    $nuevaFotoId = (int)$_POST['fotoVotada'];

    if ($fotoVotada) {
        // Ya ha votado → actualizar voto
        if ($fotoVotada == $nuevaFotoId) {
            echo "<script>alert('Ya votaste por esta foto.'); window.location.href='votoIP.php';</script>";
            exit();
        }

        // Reducir voto de la foto anterior
        $conexion->prepare("UPDATE fotografias SET votos = votos - 1 WHERE foto_id = :id")
            ->execute(['id' => $fotoVotada]);

        // Aumentar voto a nueva foto
        $conexion->prepare("UPDATE fotografias SET votos = votos + 1 WHERE foto_id = :id")
            ->execute(['id' => $nuevaFotoId]);

        // Actualizar IP con nueva foto_id
        $conexion->prepare("UPDATE ip_votos SET foto_id = :nueva WHERE direccionIP = :ip")
            ->execute(['nueva' => $nuevaFotoId, 'ip' => $ip_usuario]);

        echo "<script>alert('Has cambiado tu voto.'); window.location.href='votoIP.php';</script>";
        exit();
    } else {
        // Primer voto
        $conexion->prepare("UPDATE fotografias SET votos = votos + 1 WHERE foto_id = :id")
            ->execute(['id' => $nuevaFotoId]);

        $conexion->prepare("INSERT INTO ip_votos (direccionIP, foto_id) VALUES (:ip, :foto_id)")
            ->execute(['ip' => $ip_usuario, 'foto_id' => $nuevaFotoId]);

        echo "<script>alert('¡Gracias por tu voto!'); window.location.href='votoIP.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Votaciones - Rally Fotográfico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/estilo.css" />
    <style>
        /* Cursor pointer para las imágenes en tarjetas */
        .foto-card-img {
            cursor: pointer;
            object-fit: cover;
            height: 300px;
            width: 100%;
        }
    </style>
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo1">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Barra de navegación -->
        <nav class="navbar navbar-dark">
            <div class="container-fluid">
                  <h1 class="text-light fs-2 my-0">Galería</h1>
                <a href="../principal.php" class="btn btn-outline-light btn-sm">Volver</a>
            </div>
        </nav>

        <!-- Botón para ir a la página de estadísticas -->
        <div class="text-center mt-3">
            <a class="btn btn-success" href="../estadisticas/graficos.php">Ver estadísticas de las votaciones</a>
        </div>

        <!-- Contenedor principal con tarjetas -->
        <div class="container my-4">
            <?php if ($fotosProcesadas): ?>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($fotosProcesadas as $foto): ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card shadow-sm h-100">
                                <!-- Imagen clicable para abrir modal -->
                                <img src="<?= $foto['imagen'] ?>" alt="Foto participante"
                                    class="card-img-top foto-card-img"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalFoto"
                                    data-foto="<?= htmlspecialchars($foto['imagen'], ENT_QUOTES) ?>"
                                    data-alt="Foto participante">

                                <div class="card-body d-flex flex-column">
                                    <?php if ($foto['foto_id'] == $fotoVotada): ?>
                                        <button class="btn btn-pink disabled mt-auto" style="background-color: #e83e8c; color: white; border: none;">
                                            Votaste por esta foto
                                        </button>
                                    <?php else: ?>
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
        </div>

        <!-- Modal para mostrar la foto grande -->
        <div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white" id="modalFotoLabel">Foto ampliada</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" alt="" id="modalImagen" class="img-fluid rounded" style="max-height: 80vh; width: auto;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS y script para actualizar la imagen del modal -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Cuando se abre el modal, cambiamos la imagen según la que se clicó
            const modalFoto = document.getElementById('modalFoto')
            const modalImagen = document.getElementById('modalImagen')

            modalFoto.addEventListener('show.bs.modal', event => {
                const imagenClicada = event.relatedTarget
                const src = imagenClicada.getAttribute('data-foto')
                const alt = imagenClicada.getAttribute('data-alt')
                modalImagen.src = src
                modalImagen.alt = alt
            })
        </script>
    </div>
</body>

</html>