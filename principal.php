<?php
/* PÁGINA PRINCIPAL
* @author: Michel Freymann
* Desde aquí podemos:
* Acceder al área votación - Acceder al login - Registrarnos - Ver bases del concurso
* */

// Inclusión de variables y funciones 
require_once("./utiles/variables.php");
require_once("./utiles/funciones.php");

// Conexión a la base de datos mediante PDO
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtiene las bases del concurso desde la base de datos
$consulta = $conexion->query("SELECT * FROM bases_concurso");
$bases = $consulta->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Principal</title>
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="./css/estilo.css">
    <!-- Meta para hacer la web responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Carga de Bootstrap desde CDN (estilos) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo1">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Barra de navegación superior -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <!-- Título destacado del sitio -->
                <span class="navbar-brand fs-1 fw-bold mx-auto">Retales Urbanos</span>

                <!-- Botón para móviles (hamburguesa) -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menú de navegación colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="./login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="./registro.php">Registro</a></li>
                        <li class="nav-item"><a class="nav-link" href="#bases">El concurso</a></li>
                    </ul>
                </div>
            </div>
        </nav>



        <!-- Botón para acceder a la votación -->
        <div class="my-4 text-center">
            <a class="btn btn-primary" href="./votaciones/votoIP.php">Entra a la galería para votar</a>

        </div>



        <!-- Imagen representativa del concurso -->
        <div class="container mb-4">
            <div class="card shadow-sm overflow-hidden">
                <!-- Imagen con altura máxima limitada para evitar que ocupe demasiado espacio -->
                <img src="./img/fotoPortada.jpg" alt="imagen" class="card-img-top img-fluid rounded"
                    style="max-height: 400px; object-fit: cover;">
            </div>
        </div>

        <!-- Sección con el texto de introducción al concurso -->
        <div class="container mb-5 " id="bases">
            <div class="card p-4 shadow-sm text-center card-intro" style="max-width: 600px; margin: 0 auto;">
                <p>Retales Urbanos es un concurso de fotografía que invita a <strong>capturar la ciudad desde una mirada personal y única.</strong> No buscamos postales perfectas, sino fragmentos auténticos: una esquina olvidada, una escena inesperada, una sombra que cuenta una historia. Cada imagen debe ser un retal —pequeño pero significativo— que revele cómo ves y sientes tu entorno urbano. <strong>Tu cámara es tu mirada. Tu ciudad, tu expresión.</strong></p>
            </div>
        </div>

        <!-- Sección con las bases del concurso -->
        <div class="container mb-5" id="bases">
            <div class="card p-4 shadow-sm text-center card-intro" style="max-width: 600px; margin: 0 auto;">

                <h4 class="mb-4">Reglas del concurso</h4>
                <p class="mt-3">
                    📸 <strong>Sube tu mejor foto</strong> y compártela con el mundo. Una vez aprobada, aparecerá en la galería principal donde podrá ser votada por todos los visitantes. ¡Demuestra tu talento!
                </p>

                <!-- Datos extraídos dinámicamente desde la base de datos -->
                <p><strong>Máximo de fotos por persona:</strong> <?= $bases['max_fotos'] ?></p>
                <p><strong>Tamaño máximo de foto:</strong> <?= $bases['max_tamano_mb'] ?> MB</p>
                <p><strong>Inicio de participación:</strong> <?= $bases['fecha_inicio'] ?></p>
                <p><strong>Fin de participación:</strong> <?= $bases['fecha_fin'] ?></p>
                <p><strong>Inicio de votaciones:</strong> <?= $bases['fecha_votacion'] ?></p>
                <div class="text-center mt-3">
                    <a class="btn btn-primary" href="./registro.php" style="max-width: 300px; width: 100%;">Regístrate para participar! 📸</a>
                </div>


            </div>
            <!-- Botón para volver arriba de la página -->
            <div class="text-center my-4">
                <a href="#top" class="btn btn-success">Volver arriba</a>
            </div>
        </div>
    </div>
    <!-- Carga del JS de Bootstrap (necesario para menú responsive y otros componentes) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>