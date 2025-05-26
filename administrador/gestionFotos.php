<?php
/* PÁGINA QUE GESTIONA FOTOGRAFÍAS
* @author: Michel Freymann
* En esta página el administrador puede aceptar, rechazar y eliminar fotográfias subidas por los participantes
* */


// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verificamos que el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");  // Redirigimos si no tiene permiso
    exit();
}

// Conectamos a la base de datos con PDO
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Obtener las fotografías en el orden determinado, y junto con los datos del usuario que las subió
$select = "SELECT f.foto_id, f.usuario_id, f.imagen, f.estado, f.tipo_imagen, f.votos, u.nombre, u.apellido, u.email 
           FROM fotografias f 
           JOIN usuarios u ON f.usuario_id = u.usuario_id
           ORDER BY FIELD(f.estado, 'Pendiente', 'Aprobada', 'Rechazada')";
$consulta = $conexion->prepare($select);
$consulta->execute();
$fotos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Creamos un array para almacenar las fotos procesadas (añadiendo el prefijo base64 cuando falte)
$fotosProcesadas = [];

foreach ($fotos as $foto) {
    // Si la imagen no tiene el prefijo "data:image" (base64), lo añadimos
    if (!str_starts_with($foto['imagen'], "data:image")) {
        $foto['imagen'] = "data:{$foto['tipo_imagen']};base64," . $foto['imagen'];
    }
    $fotosProcesadas[] = $foto;
}

// PROCESO DE CAMBIO DE ESTADO (Aceptar / Rechazar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Comprobamos qué botón se ha pulsado para determinar el nuevo estado
    if (isset($_POST['aceptar'])) {
        $foto_id = $_POST['aceptar'];  // Id de la foto a aceptar
        $nuevo_estado = "Aprobada";    // Nuevo estado
    } elseif (isset($_POST['rechazar'])) {
        $foto_id = $_POST['rechazar']; // Id de la foto a rechazar
        $nuevo_estado = "Rechazada";   // Nuevo estado
    }

    if (isset($foto_id)) {
        // Actualizamos el estado en la base de datos
        $consulta = "UPDATE fotografias SET estado = :nuevo_estado WHERE foto_id = :foto_id";
        $update = $conexion->prepare($consulta);
        $update->bindParam(':nuevo_estado', $nuevo_estado);
        $update->bindParam(':foto_id', $foto_id);

        if ($update->execute()) {
            // Recargamos la página para mostrar los cambios
            header("Location: gestionFotos.php");
            exit();
        } else {
            echo "<p style='color:red'>Error al actualizar el estado.</p>";
        }
    }
}

// PROCESO DE ELIMINACIÓN DE FOTO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_id'])) {
    $foto_id = $_POST['eliminar_id'];

    // Eliminamos la foto de la base de datos
    $consulta = "DELETE FROM fotografias WHERE foto_id = :foto_id";
    $delete = $conexion->prepare($consulta);
    $delete->bindParam(':foto_id', $foto_id);

    if ($delete->execute()) {
        // Recargamos la página para actualizar el listado
        header("Location: gestionFotos.php");
        exit();
    } else {
        echo "<p style='color:red'>Error al eliminar la foto.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Gestión de Fotografías</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo2">

    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Navbar con título y botón para volver -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <span class="navbar-brand fs-3 fw-bold">Gestión de Fotografías</span>
                <a href="./administrador.php" class="btn btn-outline-light">Volver</a>
            </div>
        </nav>

        <!-- Contenido principal centrado-->
        <main class="container my-5">
            <?php if ($fotosProcesadas): ?>
                <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center">
                        <thead class="encabezado-tabla">
                            <tr>
                                <th>Imagen</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Votos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fotosProcesadas as $foto): ?>
                                <tr> <!-- Mostramos la imagen usando base64 -->
                                    <td><img src="<?= $foto['imagen'] ?>" alt="Foto" class="img-thumbnail" style="max-width: 350px;"></td>
                                    <td><strong><?= htmlspecialchars($foto['estado']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($foto['nombre'] . ' ' . $foto['apellido'])?><br>
                                        <small class="text-muted"><?= htmlspecialchars($foto['email']) ?></small>
                                    </td>
                                    <td><?= (int)$foto['votos'] ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-2 justify-content-center h-100">
                                            <!-- Formulario para aceptar la foto -->
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="aceptar" value="<?= $foto['foto_id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Aceptar</button>
                                            </form>
                                            <!-- Formulario para rechazar la foto -->
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="rechazar" value="<?= $foto['foto_id'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm">Rechazar</button>
                                            </form>
                                            <!-- Formulario para eliminar la foto -->
                                            <form method="POST" onsubmit="return confirm('¿Eliminar esta foto?');" class="m-0">
                                                <input type="hidden" name="eliminar_id" value="<?= $foto['foto_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center fs-5 mt-4">No hay fotografías para mostrar.</p>
            <?php endif; ?>
        </main>
        <div class="text-center my-5">
            <a href="#top" class="btn btn-warning">Volver arriba</a>
        </div>
    </div>

</body>

</html>