<?php
/* ÁREA PRIVADA DE PARTICIPANTE
 * @author: Michel Freymann
 * Permite al participante visualizar sus fotografías en modo tabla
 */

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Comprobamos el usuario 
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../principal.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Buscar en la tabla de fotografias para extraer sus datos
$select = "SELECT foto_id, imagen, tipo_imagen, estado, votos FROM fotografias WHERE usuario_id = :usuario_id";
$consulta = $conexion->prepare($select);
$consulta->bindParam(':usuario_id', $usuario_id);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Preprocesar las imágenes antes del HTML

$fotosProcesadas = []; // Nueva variable para almacenar las fotos procesadas
foreach ($fotos as $foto) {
    // Crear una copia de la foto y procesarla
    $fotoProcesada = $foto;
    if (!str_starts_with($foto['imagen'], "data:image")) { // Verificar si ya está en formato correcto
        $fotoProcesada['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    // Agregar la foto procesada al array
    $fotosProcesadas[] = $fotoProcesada;
}

// Procesar eliminación de una foto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $foto_id = $_POST['eliminar_id'];
    $deleteQuery = "DELETE FROM fotografias WHERE foto_id = :foto_id AND usuario_id = :usuario_id";
    $deleteStmt = $conexion->prepare($deleteQuery);
    $deleteStmt->bindParam(':foto_id', $foto_id);
    $deleteStmt->bindParam(':usuario_id', $usuario_id);

    if ($deleteStmt->execute()) {
        header("Location: tuGaleria.php"); // Recargar la página después de eliminar
        exit();
    } else {
        echo "<p style='color:red'>Error al eliminar la foto.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tu hoja de estilos personalizada -->
    <link rel="stylesheet" href="../css/cssindex.css">

    <title>Tu Galería</title>
</head>

<body>
    <!-- Barra de navegación simple -->
    <nav class="navbar navbar-dark bg-dark px-3 mb-4">
        <span class="navbar-brand mb-0 h1">Tu Galería</span>
        <a href="./tuGaleria.php" class="btn btn-outline-light btn-sm">Volver a modo normal</a>
    </nav>

    

    <div class="container">
        <?php if ($fotosProcesadas): ?>
            <!-- Tabla responsive con Bootstrap -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Imagen</th>
                            <th>Estado</th>
                            <th>Votos</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fotosProcesadas as $foto): ?>
                            <tr>
                                <!-- Imagen en tamaño pequeño -->
                                <td class="text-center">
                                    <img src="<?= $foto['imagen'] ?>" class="img-thumbnail" style="max-width: 350px; height: auto;" alt="Foto subida">
                                </td>
                                <!-- Estado -->
                                <td><?= htmlspecialchars($foto['estado']) ?></td>
                                <!-- Votos -->
                                <td><?= htmlspecialchars($foto['votos']) ?></td>
                                <!-- Botón eliminar -->
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta foto?');">
                                        <input type="hidden" name="eliminar_id" value="<?= $foto['foto_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">❌</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Mensaje cuando no hay imágenes -->
            <div class="alert alert-info text-center" role="alert">
                No hay fotografías para mostrar.
            </div>
        <?php endif; ?>
    </div>
    <div class="text-center my-5">
            <a href="#top" class="btn btn-warning btn-lg">Volver arriba</a>
        </div>
       

    <!-- Bootstrap 5 JS (opcional para algunos componentes) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
