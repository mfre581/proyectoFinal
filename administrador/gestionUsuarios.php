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

// Verifica que el usuario es administrador, si no redirige a index
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Asignamos el id al usuario que abrió sesión
$usuario_id_sesion = $_SESSION['usuario_id'];

// Conectamos a la base de datos
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

        // Alerts que muestran mensaje de eliminación exitosas o fallidas
        echo "<script>alert('Usuario eliminado correctamente.'); window.location.href='gestionUsuarios.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar el usuario.');</script>";
    }
}

// Obtener usuarios 
$consulta_usuarios = "SELECT * FROM usuarios";
$stmt_usuarios = $conexion->prepare($consulta_usuarios);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Gestionar Usuarios</title>
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
    <div class="card shadow p-4" style="max-width: 900px; width: 100%;">

        <!-- Barra de navegación-->
        <nav class="navbar navbar-dark navbar-expand-lg">
            <div class="container">

                <span class="navbar-brand fs-2 fw-bold">Gestión de Usuarios</span>

                <!-- Botón hamburguesa para móviles -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="administrador.php">Tu panel </a></li>
                        <li class="nav-item"> <a class="nav-link" href="../cerrarSesion/cerrar_sesion.php">Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container my-5">

            <!-- Comprobamos si hay usuarios para mostrar -->
            <?php if ($usuarios): ?>
                <h3 class="mb-4 text-center">Usuarios Registrados</h3>

                <!-- Mensaje para pantallas pequeñas -->
                <div class="text-center text-muted d-block d-md-none mb-2" style="font-style: italic; font-size: 0.9rem;">
                    Desliza hacia la derecha &rarr;
                </div>

                <!-- Tabla responsiva para listar usuarios -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle text-center">
                        <!-- Aplica el estilo especificado en el archivo css -->
                        <thead class="encabezado-tabla">
                            <tr>
                                <th>Rol</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Comenzamos a iterar sobre los usuarios para ir mostrándolos en la tabla -->
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['rol']) ?></td>
                              <!-- Se muestran nombre y apellidos concatenados para ocupar solo una celda-->
                                    <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td>           

                                        <!-- Botones para editar y eliminar -->

                                        <!-- Al pulsar "editar" se redirige a la página de edición con el id del usuario en la URL-->
                                        <a href="editar.php?usuario_id=<?= $usuario['usuario_id'] ?>" class="btn btn-primary btn-sm me-2">Editar</a>
                                        <?php if ($usuario['usuario_id'] == $usuario_id_sesion): ?>
                                            <!-- Se deshabilita la opción de eliminar para el administrador actual para no generar una paradoja espacio-temporal-->
                                            <button class="btn btn-danger btn-sm" disabled>Eliminar</button>
                                        <?php else: ?>
                                            <a href="gestionUsuarios.php?eliminar_id=<?= $usuario['usuario_id'] ?>"
                                                onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario y su galería?');"
                                                class="btn btn-danger btn-sm">Eliminar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <p class="text-center fs-5">No hay usuarios para mostrar.</p>
            <?php endif; ?>

            <!-- Enlace para añadir nuevo usuario nos redirige a nuevo.php-->
            <div class="text-center mt-4">
                <a href="./nuevo.php" class="btn btn-warning">Añadir usuario</a>
            </div>

        </div>
    </div>

        <!-- Bootstrap JS para el navbar colapsable -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>




