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

// Obtenemos todas las fotos aprobadas con sus votos
$consulta = "SELECT foto_id, usuario_id, imagen, tipo_imagen, votos FROM fotografias WHERE estado = 'aprobada'";
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
    $labels[] = "Foto " . $foto['foto_id'];
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
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">

    <!-- Barra de navegación -->
    <nav class="navbar navbar-dark bg-dark mb-4 p-3">
        <div class="container-fluid">
            <a class="navbar-brand fs-3 fw-bold" href="#">Estadísticas</a>
            <a href="../votaciones/votoIP.php" class="btn btn-outline-light">Ir a votaciones</a>
        </div>
    </nav>

    <div class="container">

        <!-- Galería de fotos con votos -->
        <h2 class="mb-4 text-center">Fotos y votos actuales</h2>
        <?php if ($fotosProcesadas): ?>
            <div class="row justify-content-center">
                <?php foreach ($fotosProcesadas as $foto): ?>
                    <div class="col-md-3 mb-4 text-center">
                        <div class="card shadow-sm">
                            <img src="<?= $foto['imagen'] ?>" class="card-img-top" alt="Foto <?= $foto['foto_id'] ?>">
                            <div class="card-body">
                                <p class="card-text"><strong>ID:</strong> <?= $foto['foto_id'] ?></p>
                                <p class="card-text"><strong>Votos:</strong> <?= $foto['votos'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No hay fotos aprobadas para mostrar.</div>
        <?php endif; ?>

        <!-- Gráfico de votos -->
        <h2 class="mt-5 text-center" id="grafico">Gráfico de votaciones</h2>

        <div class="my-4 d-flex justify-content-center">
            <div style="width: 100%; max-width: 800px; height: 400px;">
                <canvas id="graficoVotos"></canvas>
            </div>
        </div>

        <!-- Botón de volver arriba -->
        <div class="text-center my-5">
            <a href="#top" class="btn btn-warning btn-lg">Volver arriba</a>
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



    <!-- Bootstrap JS (opcional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>