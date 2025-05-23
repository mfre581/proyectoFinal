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

// Verifica si el usuario está logueado y es administrador
if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    // Si no es administrador, redirige a la página principal
    header("Location: ../principal.php");
    exit();
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light text-center">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand fs-1 fw-bold">Área de Administrador</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarAdmin">
                <span class="navbar-text text-white fs-4 me-3">
                    Bienvenido, <?= htmlspecialchars($nombre) ?>
                </span>
                <a href="../cerrarSesion/cerrar_sesion.php" class="btn btn-outline-light">Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container my-5">
        <h3 class="mb-4">Elige qué deseas hacer</h3>
        <div class="d-grid gap-3 col-6 mx-auto">
            <a href="./gestionFotos.php" class="btn btn-primary btn-lg">Gestionar estado de fotografías</a>
            <a href="./gestionUsuarios.php" class="btn btn-success btn-lg">Gestionar usuarios</a>
            <a href="./gestionBases.php" class="btn btn-warning btn-lg">Modificar bases del concurso</a>
        </div>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>