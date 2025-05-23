<?php
/* PÁGINA DE GESTIÓN DE BASES DEL CONCURSO
 * @author: Michel Freymann
 * Permite al administrador modificar los parámetros clave del concurso:
 * - Número máximo de fotos por persona
 * - Fechas de inicio y fin de participación
 * - Fecha de inicio de votaciones
 */

// Carga variables y funciones
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

// Iniciar sesión
session_start();

// Comprobar que el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../principal.php");
    exit();
}

$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtener datos actuales de las bases del concurso
$select = "SELECT * FROM bases_concurso"; // Asumimos solo una fila
$consulta = $conexion->prepare($select);
$consulta->execute();
$base = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$base) {
    echo "No se encontraron datos para las bases del concurso.";
    exit();
}

$errores = [];

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $max_fotos = $_POST['max_fotos'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $fecha_votacion = $_POST['fecha_votacion'] ?? '';

    // Validaciones
    $fecha_actual = date('Y-m-d'); // Fecha actual

    if ($fecha_fin < $fecha_actual) {
        $errores[] = "La fecha de fin de participación no puede ser anterior a la fecha actual.";
    }

    if ($max_fotos > 10) {
        $errores[] = "El número máximo de fotos es 10.";
    }

    // Actualizar si no hay errores
    if (empty($errores)) {
        $update = "UPDATE bases_concurso 
                   SET max_fotos = :max_fotos,
                       fecha_inicio = :fecha_inicio, 
                       fecha_fin = :fecha_fin, 
                       fecha_votacion = :fecha_votacion";
        $stmt = $conexion->prepare($update);
        $stmt->bindParam(':max_fotos', $max_fotos);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->bindParam(':fecha_votacion', $fecha_votacion);

        if ($stmt->execute()) {
            echo "<script>alert('Bases del concurso actualizadas correctamente.'); window.location.href='administrador.php';</script>";
        } else {
            $errores[] = "Error al actualizar las bases del concurso.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Gestión de Bases del Concurso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light">

    <!-- Navbar con título y botón para volver -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand fs-3 fw-bold">Gestión de Bases</span>
            <a href="./administrador.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <main class="container my-5">

        <!-- Título centrado -->
        <h2 class="mb-4 text-center">Datos modificables</h2>

        <!-- Mostrar errores si existen -->
        <?php if (!empty($errores)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Formulario para modificar las bases -->
        <form action="" method="POST" class="mx-auto" style="max-width: 600px;">

            <div class="mb-3">
                <label for="max_fotos" class="form-label">Máximo de fotos por persona:</label>
                <input type="number" name="max_fotos" id="max_fotos" class="form-control" required
                    value="<?= htmlspecialchars($base['max_fotos']) ?>" max="10" min="1">
            </div>

            <div class="mb-3">
                <label for="fecha_inicio" class="form-label">Inicio de participación:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required
                    value="<?= htmlspecialchars($base['fecha_inicio']) ?>">
            </div>

            <div class="mb-3">
                <label for="fecha_fin" class="form-label">Fin de participación:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required
                    value="<?= htmlspecialchars($base['fecha_fin']) ?>">
            </div>

            <div class="mb-3">
                <label for="fecha_votacion" class="form-label">Inicio de votaciones:</label>
                <input type="date" name="fecha_votacion" id="fecha_votacion" class="form-control" required
                    value="<?= htmlspecialchars($base['fecha_votacion']) ?>">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Actualizar bases</button>
            </div>

        </form>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>