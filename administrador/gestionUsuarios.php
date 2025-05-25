<?php
/* PÁGINA GESTIÓN DE USUARIOS
* @author: Michel Freymann
* Permite al administrador:
* - Ver usuarios registrados (excepto a sí mismo)
* - Eliminar usuarios 
* - Acceder al formulario para añadir nuevos usuarios
*/

// Inclusión de variables,funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verifica que el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../principal.php");
    exit();
}

$usuario_id_sesion = $_SESSION['usuario_id'];
$conexion = conectarPDO($host, $user, $password, $bbdd);

// Elimina usuario y sus fotografías si se recibe 'eliminar_id'
if (isset($_GET['eliminar_id'])) {
    $eliminar_id = $_GET['eliminar_id'];
    try {
        $deleteFotos = "DELETE FROM fotografias WHERE usuario_id = :id";
        $stmt = $conexion->prepare($deleteFotos);
        $stmt->bindParam(':id', $eliminar_id);
        $stmt->execute();

        $deleteUsuario = "DELETE FROM usuarios WHERE usuario_id = :id";
        $stmt = $conexion->prepare($deleteUsuario);
        $stmt->bindParam(':id', $eliminar_id);
        $stmt->execute();

        echo "<script>alert('Usuario eliminado correctamente.'); window.location.href='gestionUsuarios.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar el usuario.');</script>";
    }
}

// Obtener usuarios excepto el admin actual para mostrar en la tabla
$consulta_usuarios = "SELECT usuario_id, nombre, apellido, email, rol FROM usuarios WHERE usuario_id != :usuario_id_sesion";
$stmt_usuarios = $conexion->prepare($consulta_usuarios);
$stmt_usuarios->bindParam(':usuario_id_sesion', $usuario_id_sesion);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Gestionar Usuarios</title>
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
            <span class="navbar-brand fs-3 fw-bold">Gestión de Usuarios</span>
            <a href="./administrador.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>


    <!-- Contenedor principal -->
    <div class="container my-5">

        <!-- Comprobamos si hay usuarios para mostrar -->
        <?php if ($usuarios): ?>
            <h3 class="mb-4 text-center">Usuarios Registrados</h3>

            <!-- Tabla responsiva para listar usuarios -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle text-center">
                      <thead class="encabezado-tabla">
                        <tr>
                            <th>Rol</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['rol']) ?></td>
                                <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <!-- Botón eliminar con confirmación -->
                                    <a href="gestionUsuarios.php?eliminar_id=<?= $usuario['usuario_id'] ?>"
                                       onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario y su galería?');"
                                       class="btn btn-danger btn-sm">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p class="text-center fs-5">No hay usuarios para mostrar.</p>
        <?php endif; ?>

        <!-- Enlace para añadir nuevo usuario -->
        <div class="text-center mt-4">
            <a href="./nuevo.php" class="btn btn-warning">Añadir usuario</a>
        </div>

        </div>
    </div>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
