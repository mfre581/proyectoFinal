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

    <!-- Meta para hacer la web responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Carga de Bootstrap desde CDN (estilos) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light text-center">


    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!-- Título destacado del sitio -->
            <a class="navbar-brand fs-1 fw-bold" href="#">Rally Fotográfico</a>

            <!-- Botón para móviles (hamburguesa) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú de navegación colapsable -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="./login.php">Acceder</a></li>
                    <li class="nav-item"><a class="nav-link" href="./registro.php">Registro</a></li>
                    <li class="nav-item"><a class="nav-link" href="#bases">Bases</a></li>
                </ul>
            </div>
        </div>
    </nav>



    <!-- Botón para acceder a la votación -->
    <div class="my-4">
        <a class="btn btn-success" href="./votaciones/votoIP.php">Entra a la galería para votar</a>

    </div>

    <!-- Imagen representativa del concurso -->
    <div class="container mb-4">
        <div class="card shadow-sm overflow-hidden">
            <!-- Imagen con altura máxima limitada para evitar que ocupe demasiado espacio -->
            <img src="./imgPortada/imagen.jpg" alt="imagen" class="card-img-top img-fluid rounded"
                style="max-height: 400px; object-fit: cover;">
        </div>
        <br>
   
    </div>

    <!-- Sección con las bases del concurso -->
    <div class="container mb-5" id="bases">
            <div class="card p-4 shadow-sm" style="max-width: 600px; margin: 0 auto;">


            <h4 class="mb-4">Reglas del concurso</h4>

            <!-- Datos extraídos dinámicamente desde la base de datos -->
            <p><strong>Máximo de fotos por persona:</strong> <?= $bases['max_fotos'] ?></p>
            <p><strong>Tamaño máximo de foto:</strong> 2MB</p>
            <p><strong>Inicio de participación:</strong> <?= $bases['fecha_inicio'] ?></p>
            <p><strong>Fin de participación:</strong> <?= $bases['fecha_fin'] ?></p>
            <p><strong>Inicio de votaciones:</strong> <?= $bases['fecha_votacion'] ?></p>
                <a class="btn btn-success" href="./registro.php" style="max-width: 300px; margin: 0 auto;">Regístrate para participar! 📸</a>

        </div>
        
    </div>

    <!-- Carga del JS de Bootstrap (necesario para menú responsive y otros componentes) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>