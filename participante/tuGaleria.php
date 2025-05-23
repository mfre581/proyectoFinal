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
    header("Location: ../principal.php");
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Galería</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/cssindex.css">
</head>

<body>
      <!-- Navbar  -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand fs-1 fw-bold">Tu Galería</span>
            <a href="./participante.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h5><a class="btn btn-success" href="./tuGaleriaTabla.php">Visualizar en tabla</a></h5>
        <?php if ($fotos): ?>
            <div class="row mt-4">
                <?php foreach ($fotos as $foto): ?>
                    <div class="col-sm-12 col-md-6 col-lg-4 mb-4 d-flex justify-content-center">
                        <div class="text-center">
                            <img src="data:<?= $foto['tipo_imagen'] ?>;base64,<?= $foto['imagen'] ?>"
                                 style="width: 100%; max-width: 550px; height: auto; border: 1px solid #ddd; padding: 5px;"
                                 alt="Foto">
                            <div class="mt-2">
                                <p><strong>Estado:</strong> <?= $foto['estado'] ?></p>
                                <p><strong>Votos:</strong> <?= $foto['votos'] ?></p>
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta foto?');">
                                    <input type="hidden" name="eliminar_id" value="<?= $foto['foto_id'] ?>">
                                    <button type="submit" class="btn btn-link text-danger p-0">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay nada que mostrar.</p>
        <?php endif; ?>
    </div>
</body>

</html>
