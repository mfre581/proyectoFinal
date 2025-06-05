<?php
// Inclusión de variables y funciones y abrimos sesión
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");
session_start();

// Verifica que el usuario está logueado, si no redirige a index
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Conectamos a la base de datos
$conexion = conectarPDO($host, $user, $password, $bbdd);

$errores = [];

// Obtenemos el id del usuario y lo asignamos
$usuario_id = $_GET['usuario_id'] ?? $_POST['usuario_id'] ?? null;

// Se cargan datos actuales para mostrar en formulario
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conexion->prepare("SELECT nombre, apellido, email, rol FROM usuarios WHERE usuario_id = :id");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo "<script>alert('Usuario no encontrado'); window.location.href='gestionUsuarios.php';</script>";
        exit();
    }

    // Se asignan los datos para mostrar en formulario
    $nombre = $usuario['nombre'];
    $apellido = $usuario['apellido'];
    $email = $usuario['email'];
}

// Se procesa envío del formulario con los datos editados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $contrasena = $_POST['password'] ?? '';

    // Validaciones para que no se pueda actualizar al usario con datos vacíos o en formato erróneo
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (strlen($nombre) > 50) {
        $errores[] = "El nombre no puede tener más de 50 caracteres.";
    }

    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio.";
    } elseif (strlen($apellido) > 50) {
        $errores[] = "El apellido no puede tener más de 50 caracteres.";
    }

    if (empty($email)) {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es válido.";
    } else {
        // Verificar si el email ya existe y que no sea el del mismo usuario
        $stmt = $conexion->prepare("SELECT usuario_id FROM usuarios WHERE email = :email AND usuario_id != :id");
        $stmt->execute([':email' => $email, ':id' => $usuario_id]);
        if ($stmt->fetch()) {
            $errores[] = "La dirección de email ya está registrada por otro usuario.";
        }
    }

    // Si hay contraseña, validarla
    if (!empty($contrasena)) {
        if (empty($contrasena)) {
            $errores[] = "La contraseña es obligatoria.";
        } elseif (strlen($contrasena) < 5 || strlen($contrasena) > 20) {
            $errores[] = "La contraseña debe tener entre 5 y 20 caracteres.";
        } elseif (!preg_match('/[A-Z]/', $contrasena)) {
            $errores[] = "La contraseña debe contener al menos una letra mayúscula.";
        } elseif (!preg_match('/[a-z]/', $contrasena)) {
            $errores[] = "La contraseña debe contener al menos una letra minúscula.";
        } elseif (!preg_match('/\d/', $contrasena)) {
            $errores[] = "La contraseña debe contener al menos un número.";
        }
    }

    if (empty($errores)) {
        try {
            if (!empty($contrasena)) {
                // Si se ha ingresado una nueva contraseña, se hashea y se actualiza la tabla
                $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, password = :password, updated_at = NOW() WHERE usuario_id = :id";
            } else {
                $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, email = :email, updated_at = NOW() WHERE usuario_id = :id";
            }
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);

            // Si se ha ingresado una nueva contraseña, se incluye en el update correspondiente
            if (!empty($contrasena)) {
                $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
            }

            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Obtener el rol del usuario actualizado
                $rol = $_SESSION['rol'] ?? null;
                // Si no tienes el rol en sesión, puedes obtenerlo de la BD
                if (!$rol) {
                    $stmtRol = $conexion->prepare("SELECT rol FROM usuarios WHERE usuario_id = :id");
                    $stmtRol->execute([':id' => $usuario_id]);
                    $rol = $stmtRol->fetchColumn();
                }

                // Redireccionar según rol
                if ($rol === 'participante') {
                    echo "<script>alert('Usuario actualizado correctamente.'); window.location.href='../participante/participante.php';</script>";
                } else {
                    // admin u otros roles
                    echo "<script>alert('Usuario actualizado correctamente.'); window.location.href='gestionUsuarios.php';</script>";
                }
                exit();
            } else {
                $errores[] = "Error al actualizar el usuario.";
            }
        } catch (PDOException $e) {
            error_log("Error en la actualización: " . $e->getMessage());
            $errores[] = "Hubo un error al actualizar. Inténtalo más tarde.";
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
                <span class="navbar-brand fs-4 fw-bold">Editar usuario</span>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="gestionUsuarios.php">Volver</a></li>
                </ul>
            </div>
        </nav>

        <main class="container my-4">

            <!-- Mostrar errores si existen -->
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario de actualización -->
            <form action="" method="POST" class="mx-auto" style="max-width: 500px;">
                <!-- Este input oculto envía el ID del usuario con el formulario para identificarlo -->
                <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <!-- Se inserta el dato obtenido del usuario_id en cada campo correspondiente -->
                    <input type="text" name="nombre" id="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>">
                </div>
                <div class="mb-3">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" name="apellido" id="apellido" class="form-control" value="<?= htmlspecialchars($apellido) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico:</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña (opcional):</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Dejar vacío para no cambiar">
                    <div class="form-text">Mínimo 5 caracteres, mayúscula, minúscula y número</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>