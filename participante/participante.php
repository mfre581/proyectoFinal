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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilo personalizado -->
    <link rel="stylesheet" type="text/css" href="../css/cssindex.css">
</head>

<body class="bg-light">

        <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
          
            <span class="navbar-brand fs-1 fw-bold">Área Participante</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarParticipante">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarParticipante">
                <span class="navbar-text text-white fs-4 me-3">
                    Bienvenido, <?= htmlspecialchars($nombre) ?>
                </span>
                <a href="../cerrarSesion/cerrar_sesion.php" class="btn btn-outline-light">Cerrar sesión</a>
            </div>
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
    
        
    <!-- Bootstrap JS (opcional si se usa JavaScript interactivo) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
