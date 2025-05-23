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
    <meta charset="UTF-8">
    <title>Votaciones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/cssindex.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <h1 class="text-light">Galería</h1>
    <a href="../principal.php" class="btn btn-outline-light">Volver</a>
</nav>

  <!-- Botón para ir a la página de estadísticas -->
<div class="text-center mt-3">
    <a class="btn btn-success" href="../estadisticas/graficos.php">Ver estadísticas de las votaciones</a>
</div>

    <!-- Contenedor principal con las fotos para votar -->
<div class="container mt-4">
    <?php if ($fotosProcesadas): ?>
      <div class="row justify-content-center">
                <!-- Recorremos todas las fotos aprobadas -->
                <?php foreach ($fotosProcesadas as $foto): ?>
                    <div class="col-md-8 mb-5 text-center">
                        <!-- Mostrar la imagen con estilos para que sea responsiva y con borde -->
                        <img src="<?= $foto['imagen'] ?>" alt="Foto participante"
                            class="img-fluid rounded border shadow-sm"
                            style="max-height: 500px; width: auto;">

                        <!-- Mostrar botón diferente si la foto fue votada por esta IP -->
                        <?php if ($foto['foto_id'] == $fotoVotada): ?>
                            <div class="mt-3">
                                <!-- Botón deshabilitado indicando que ya votaste esta foto -->
                                <button class="btn" disabled style="background-color: #e83e8c; color: white; border: none;">Votaste por esta foto</button>
                            </div>
                        <?php else: ?>
                            <!-- Formulario para votar la foto -->
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="fotoVotada" value="<?= $foto['foto_id'] ?>">
                                <button type="submit" class="btn btn-primary">Votar esta foto</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Mensaje si no hay fotos para votar -->
            <p class="text-center">No hay fotos disponibles en este momento.</p>
        <?php endif; ?>

        <!-- Botón para volver arriba de la página -->
        <div class="text-center my-5">
            <a href="#top" class="btn btn-warning btn-lg">Volver arriba</a>
        </div>
    </div>

    <!-- Script de Bootstrap para funcionalidades JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>