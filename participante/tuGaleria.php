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
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo3">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Navbar  -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <span class="navbar-brand fs-1">Tu Galería</span>
                <a href="./participante.php" class="btn btn-outline-light">Volver</a>
            </div>
        </nav>

        <div class="container mt-4">

            <div class="text-center">
                <a class="btn btn-success" href="./tuGaleriaTabla.php">Detalles</a>
            </div>

            <?php if ($fotos): ?>
                <div class="row mt-4">
                    <?php foreach ($fotos as $foto): ?>
                        <div class="col-sm-12 col-md-6 col-lg-4 mb-4 d-flex justify-content-center">
                            <div class="text-center">
                                <img src="data:<?= $foto['tipo_imagen'] ?>;base64,<?= $foto['imagen'] ?>"
                                    style="width: 100%; max-width: 550px; height: auto; border: 1px solid #ddd; padding: 5px;"
                                    alt="Foto">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay nada que mostrar.</p>
            <?php endif; ?>
        </div>

        <!-- Botón de volver arriba -->
        <div class="text-center my-5">
            <a href="#top" class="btn btn-warning">Volver arriba</a>
        </div>
    </div>
</body>

</html>