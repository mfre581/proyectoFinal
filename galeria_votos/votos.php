<?php

/**
 * PÁGINA DE ESTADÍSTICAS Y GRÁFICO
 * @author: Michel Freymann
 * - Muestra las imágenes aprobadas en formato galería junto con su número de votos.
 * - Genera un gráfico de barras con Chart.js representando los votos de cada imagen.
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtenemos todas las fotos aprobadas con sus votos y datos del usuario (nombre, apellido)
$consulta = "SELECT f.foto_id, f.usuario_id, f.imagen, f.tipo_imagen, f.votos, u.nombre, u.apellido 
             FROM fotografias f
             JOIN usuarios u ON f.usuario_id = u.usuario_id
             WHERE f.estado = 'aprobada'";
$consulta = $conexion->prepare($consulta);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Preprocesamos las imágenes para asegurarnos de que tienen el formato base64 correcto
$fotosProcesadas = [];
foreach ($fotos as $foto) {
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $foto['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $foto;
}

// Preparamos los datos para el gráfico
$labels = [];
$votos = [];

foreach ($fotosProcesadas as $foto) {
    // Consulta para obtener nombre y apellido del usuario
    $consultaUsuario = $conexion->prepare("SELECT nombre, apellido FROM usuarios WHERE usuario_id = :usuario_id");
    $consultaUsuario->execute(['usuario_id' => $foto['usuario_id']]);
    $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

    // Agregar nombre completo al label
    $labels[] = $usuario ? $usuario['nombre'] . ' ' . $usuario['apellido'] : 'Usuario desconocido';
    $votos[] = $foto['votos'];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo1">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Barra de navegación-->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <h1 class="text-light fs-2 my-0">Fotos y votos actuales</h1>

                <!-- Botón para móviles (hamburguesa) -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menú de navegación colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="#grafico">Ver Gráfico</a></li>
                        <li class="nav-item"><a class="nav-link" href="galeria.php">Galería</a></li>
                        <li class="nav-item"><a class="nav-link" href="../index.php">Principal</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container my-4">
            <!-- Galería de fotos con votos -->
            <?php if ($fotosProcesadas): ?>
                <div class="row g-4 justify-content-center">
                    <?php foreach ($fotosProcesadas as $foto): ?>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card shadow-sm h-100">

                                <!-- 'card-img-top' clase bootsdtrap, posiciona la imagen en la parte superior de la tarjeta
                                'ajustaFoto2' es la clase personalizada que controla el tamaño de la imagen en el css (sin pointer)-->
                                <img src="<?= $foto['imagen'] ?>" class="card-img-top ajustaFoto2" alt="Foto <?= $foto['foto_id'] ?>">

                                <div class="card-body text-center d-flex flex-column">
                                    <p class="card-text mb-1 fw-semibold">
                                        <?= htmlspecialchars($foto['nombre'] . ' ' . $foto['apellido']) ?>
                                    </p>
                                    <p class="card-text text-primary">
                                        <strong>Votos:</strong> <?= $foto['votos'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">No hay fotos aprobadas para mostrar.</div>
            <?php endif; ?>


            <!-- Gráfico de votos -->
            <h2 class="mt-5 text-center" id="grafico">Gráfico de votaciones</h2>

            <div class="my-4 d-flex justify-content-center">
                <div style="width: 100%; max-width: 800px; height: 400px;">
                    <canvas id="graficoVotos"></canvas>
                </div>
            </div>

            <!-- Botón para volver al principio de la página -->
            <div class="text-center my-4">
                <a href="#top" class="btn estiloBoton2">Subir</a>
            </div>

        </div>
    </div>
    <!-- Script para generar el gráfico con Chart.js -->
    <script>
        const ctx = document.getElementById('graficoVotos').getContext('2d');
        const graficoVotos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Votos por foto',
                    data: <?= json_encode($votos) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>



    <!-- Bootstrap JS para el navbar colapsable -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>