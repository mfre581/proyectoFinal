<?php
/* PÁGINA DEL ADMINISTRADOR
* @author: Michel Freymann
* Desde aquí el administrador puede acceder a las páginas para:
*   - Gestionar estado de fotografías
*   - Gestionar usuarios
*   - Modificar bases del concurso
* */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verifica que el usuario es administrador, si no redirige a index
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

//Asignamos el nombre del usuario para la bienvenida
$usuario_id = $_SESSION['usuario_id'];

// Conectar base de datos y obtener nombre del usuario admin
$conexion = conectarPDO($host, $user, $password, $bbdd);
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
    <title>Área Administrador</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<!-- Establece el estilo general de la página -->

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo2">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Encabezado y navbar -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <h2 class="text-light fs-3 my-0">Bienvenid@ a tu panel, <?= htmlspecialchars($nombre) ?></h2>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../cerrarSesion/cerrar_sesion.php">Cerrar sesión</a></li>
                </ul>
            </div>
        </nav>

        <!-- Contenido principal, donde el administrador puede escoger entre diferentes opciones -->
        <main class="container my-5">
            <h3 class="mb-5">Elige qué deseas hacer</h3>
            <div class="d-grid gap-3 col-12 col-sm-10 col-md-8 col-lg-6 mx-auto">
                <a href="./gestionFotos.php" class="btn btn-primary btn-lg">Gestionar estado de fotografías</a>
                <a href="./gestionUsuarios.php" class="btn btn-success btn-lg">Gestionar usuarios</a>
                <a href="./gestionBases.php" class="btn btn-warning btn-lg">Modificar bases del concurso</a>
            </div>
        </main>
    </div>

</body>

</html>