<?php

/**
 * PÁGINA DE VOTACIONES
 * @author: Michel Freymann
 * Esta página permite visualizar las fotos participantes aprobadas y votar por una.
 * Solo se permite un voto por IP.
 */

// Incluye las variables de conexión y funciones reutilizables
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Conexión con la base de datos
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Consulta para obtener todas las fotos aprobadas
$select = "SELECT foto_id, usuario_id, imagen, tipo_imagen, estado, votos 
            FROM fotografias 
            WHERE estado = 'aprobada'";
$consulta = $conexion->prepare($select);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Preparamos las imágenes para mostrarlas correctamente con base64
$fotosProcesadas = [];
foreach ($fotos as $foto) {
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $foto['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $foto;
}

// Función para obtener la dirección IP del usuario
function obtenerIP()
{
    return $_SERVER['HTTP_CLIENT_IP'] ??
        $_SERVER['HTTP_X_FORWARDED_FOR'] ??
        $_SERVER['REMOTE_ADDR'];
}

// Procesamiento del voto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['votacion'])) {
    $foto_id = $_POST['fotoVotada'];
    $ip_usuario = obtenerIP();

    // Comprobamos si esta IP ya ha votado
    $stmt = $conexion->prepare("SELECT * FROM ip_votos WHERE direccionIP = :ip");
    $stmt->execute(['ip' => $ip_usuario]);

    if ($stmt->rowCount() > 0) {
        // IP ya registrada: no se permite votar de nuevo
        echo "<script>alert('Ya has votado. Solo se permite un voto por IP.'); window.location.href='../principal.php';</script>";
        exit();
    }

    // Incrementamos los votos para la foto seleccionada
    $votosActuales = $conexion->prepare("SELECT votos FROM fotografias WHERE foto_id = :id");
    $votosActuales->execute(['id' => $foto_id]);
    $votos = $votosActuales->fetchColumn() + 1;

    // Actualizamos el contador de votos
    $update = $conexion->prepare("UPDATE fotografias SET votos = :votos WHERE foto_id = :id");
    $update->execute(['votos' => $votos, 'id' => $foto_id]);

    // Registramos la IP que ha votado
    $insertIP = $conexion->prepare("INSERT INTO ip_votos (direccionIP) VALUES (:ip)");
    $insertIP->execute(['ip' => $ip_usuario]);

    // Mensaje de éxito y redirección
    echo "<script>alert('¡Gracias por tu voto!'); window.location.href='../principal.php';</script>";
    exit();
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Votaciones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/cssindex.css">
</head>

<body>

    <nav class="navbar navbar-dark bg-dark px-3">
        <h1 class="text-light" id="principio">Galería</h1>
        <a href="../principal.php" class="btn btn-outline-light">Volver</a>
    </nav>

    <div class="text-center mt-3">
        <h5><a class="btn btn-success" href="../estadisticas/graficos.php">Ver estadísticas de las votaciones</a></h5>
    </div>

    <div class="container mt-4">
        <?php if ($fotosProcesadas): ?>
            <div class="row justify-content-center">
                <?php foreach ($fotosProcesadas as $foto): ?>
                    <div class="col-md-8 mb-5 text-center">
                        <!-- Imagen -->
                        <img src="<?= $foto['imagen'] ?>"
                            alt="Foto participante"
                            class="img-fluid rounded border shadow-sm"
                            style="max-height: 500px; width: auto;">

                        <!-- Botón de votación -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="fotoVotada" value="<?= $foto['foto_id'] ?>">
                            <button type="submit" name="votacion" class="btn btn-primary">Votar esta foto</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">No hay fotos disponibles en este momento.</p>
        <?php endif; ?>
        <div class="text-center my-5">
            <a href="#top" class="btn btn-warning btn-lg">Volver arriba</a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>