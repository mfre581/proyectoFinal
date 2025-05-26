<?php
/* ÁREA PRIVADA DE PARTICIPANTE
 * @author: Michel Freymann
 * Permite al participante acceder a la zona de subida de foto y a su propia galería
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verificamos que el usuario haya iniciado sesión
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    header("Location: ../principal.php");  // Redirige si no está logueado
    exit();
}

// Conexión a la base de datos
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtenemos el nombre del usuario que ha iniciado sesión
$select = "SELECT nombre FROM usuarios WHERE usuario_id = :usuario_id";
$consulta = $conexion->prepare($select);
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$nombre = $consulta->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Participante</title>
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo3">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Navbar -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <h2 class="text-light fs-2 my-0">Bienvenid@, <?= htmlspecialchars($nombre) ?></h2>
                <a href="../cerrarSesion/cerrar_sesion.php" class="btn btn-outline-light">Cerrar sesión</a>
            </div>
        </nav>


        <!-- Contenido principal -->
        <div class="container mt-5">
            <div class="text-center">
                <h3 class="mb-4">¿Qué deseas hacer?</h3>

                <!-- Opciones disponibles para el participante -->
                <div class="d-grid gap-3 col-6 mx-auto">
                    <a href="./subirFoto.php" class="btn btn-primary btn-lg">Añadir una fotografía</a>
                    <a href="./tuGaleria.php" class="btn btn-warning btn-lg">Ir a tu galería</a>
                </div>
            </div>
        </div>

    </div>

</body>

</html>