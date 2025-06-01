<?php
/* PÁGINA DE GESTIÓN DE BASES DEL CONCURSO
 * @author: Michel Freymann
 * Permite al administrador modificar los parámetros clave del concurso:
 * - Número máximo de fotos por persona
 * - Fechas de inicio y fin de participación
 * - Fecha de inicio de votaciones
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verifica que el usuario es administrador, si no redirige a index
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Conectamos a la base de datos
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

    // Asignamos las variables
    $max_fotos = $_POST['max_fotos'] ?? '';
    $max_tamano_mb = $_POST['max_tamano_mb'] ?? 2;
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $fecha_votacion = $_POST['fecha_votacion'] ?? '';
    $fecha_fin_votacion = $_POST['fecha_fin_votacion'] ?? '';

    // Asignamos fecha actual
    $fecha_actual = date('Y-m-d');


    // No se permite establecer como máximo de fotos un valor mayor a 10
    if ($max_fotos > 10) {
        $errores[] = "El número máximo de fotos es 10.";
    }


    // VALIDACIONES EN FECHAS

    // No se permite establecer una fecha de fin de participación anterior a la fecha actual
    if ($fecha_fin < $fecha_actual) {
        $errores[] = "La fecha de fin de participación no puede ser anterior a la fecha actual.";
    }

    // No se permite establecer una fecha de votación fuera del rango de participación
    if ($fecha_votacion < $fecha_inicio || $fecha_votacion > $fecha_fin) {
        $errores[] = "La fecha de inicio de votaciones debe estar dentro del rango de participación.";
    }

    // La fecha de inicio de votación no puede ser anterior al inicio de participación
    if ($fecha_votacion < $fecha_inicio) {
        $errores[] = "La fecha de inicio de votación no puede ser anterior al inicio de participación.";
    }

    // La fecha de fin de votación debe ser igual o posterior a la fecha de inicio de votación
    if ($fecha_fin_votacion < $fecha_votacion) {
        $errores[] = "La fecha de fin de votación no puede ser anterior a la fecha de inicio de votación.";
    }

    // La fecha de fin de votación no puede ser anterior al fin de participación
    if ($fecha_fin_votacion < $fecha_fin) {
        $errores[] = "La fecha de fin de votación no puede ser anterior a la fecha de fin de participación.";
    }

    // Actualizar si no hay errores
    if (empty($errores)) {
        $update = "UPDATE bases_concurso 
                   SET max_fotos = :max_fotos,
                       max_tamano_mb = :max_tamano_mb,
                       fecha_inicio = :fecha_inicio, 
                       fecha_fin = :fecha_fin, 
                       fecha_votacion = :fecha_votacion";
        $stmt = $conexion->prepare($update);
        $stmt->bindParam(':max_fotos', $max_fotos);
        $stmt->bindParam(':max_tamano_mb', $max_tamano_mb);
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
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
</head>

<!-- Establece el estilo general de la página -->

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo2">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 480px; width: 100%;">

        <!-- Barra de navegación-->
        <nav class="navbar navbar-dark">
            <div class="container">

                <span class="navbar-brand fs-2 fw-bold">Gestión de bases</span>

                <!-- Botón hamburguesa -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
               <!-- Navbar colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="administrador.php">Tu panel</a></li>
                        <li class="nav-item"> <a class="nav-link" href="../cerrarSesion/cerrar_sesion.php">Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="container my-5">
            <h3 class="mb-4 text-center">Datos modificables</h3>

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

                <!-- Campo para definir el número máximo de fotos que puede subir cada participante -->
                <div class="mb-3">
                    <label for="max_fotos" class="form-label">Máximo de fotos por persona:</label>
                    <input type="number" name="max_fotos" id="max_fotos" class="form-control" required
                        value="<?= htmlspecialchars($base['max_fotos']) ?>" max="10" min="1">
                </div>

                <!-- Selector desplegable para elegir el tamaño máximo permitido por imagen -->
                <div class="mb-3">
                    <label for="max_tamano_mb" class="form-label">Tamaño máximo de imagen (MB):</label>
                    <select name="max_tamano_mb" id="max_tamano_mb" class="form-select" required>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= $base['max_tamano_mb'] == $i ? 'selected' : '' ?>>
                                <?= $i ?> MB
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Campo de fecha para establecer el inicio del periodo de participación -->
                <div class="mb-3">
                    <label for="fecha_inicio" class="form-label">Inicio de participación:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required
                        value="<?= htmlspecialchars($base['fecha_inicio']) ?>">
                </div>

                <!-- Campo de fecha para establecer el fin del periodo de participación -->
                <div class="mb-3">
                    <label for="fecha_fin" class="form-label">Fin de participación:</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required
                        value="<?= htmlspecialchars($base['fecha_fin']) ?>">
                </div>

                <!-- Campo de fecha para definir cuándo comienzan las votaciones -->
                <div class="mb-3">
                    <label for="fecha_votacion" class="form-label">Inicio de votaciones:</label>
                    <input type="date" name="fecha_votacion" id="fecha_votacion" class="form-control" required
                        value="<?= htmlspecialchars($base['fecha_votacion']) ?>">
                </div>

                <!-- Campo de fecha para definir cuándo terminan las votaciones -->
                <div class="mb-3">
                    <label for="fecha_fin_votacion" class="form-label">Fin de votaciones:</label>
                    <input type="date" name="fecha_fin_votacion" id="fecha_fin_votacion" class="form-control" required
                        value="<?= htmlspecialchars($base['fecha_fin_votacion'] ?? '') ?>">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Actualizar bases</button>
                </div>

            </form>
        </main>
    </div>

    <!-- Bootstrap JS para el navbar colapsable -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>