<?php
/* PÁGINA NUEVO USUARIO (REGISTRO)
* @author: Michel Freymann
* Permite al administrador crear un nuevo usuario con validación de datos (email único, longitud contraseña)
*/

// Inclusión de variables y funciones y abrimos sesión
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

// Iniciamos variables
$errores = [];
$email = $_POST["email"] ?? "";
$clave = $_POST["password"] ?? "";
$nombre = $_POST["nombre"] ?? "";
$apellido = $_POST["apellido"] ?? "";
$rol = $_POST["rol"] ?? "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Validar nombre
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    } elseif (strlen($nombre) > 50) {
        $errores[] = "El nombre no puede tener más de 50 caracteres.";
    }

    // Validar apellido
    if (empty($apellido)) {
        $errores[] = "El apellido es obligatorio.";
    } elseif (strlen($apellido) > 50) {
        $errores[] = "El apellido no puede tener más de 50 caracteres.";
    }

    // Validar email
    if (empty($email)) {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es válido.";
    } else {
        // Verifica si el email ya existe
        $select = "SELECT COUNT(*) as cuenta FROM usuarios WHERE email = :email";
        $consulta = $conexion->prepare($select);
        $consulta->execute(["email" => $email]);
        $resultado = $consulta->fetch();

        if ($resultado["cuenta"] > 0) {
            $errores[] = "La dirección de email ya está registrada.";
        }
    }

    // Validar contraseña 
    if (empty($clave)) {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($clave) < 5 || strlen($clave) > 20) {
        $errores[] = "La contraseña debe tener entre 5 y 20 caracteres.";
    }

    // Si no hay errores, insertar usuario
    if (empty($errores)) {
        try {
            // Hashear la contraseña
            $passwordHash = password_hash($clave, PASSWORD_DEFAULT);

            // Preparar la consulta de inserción
            $insert = "INSERT INTO usuarios (nombre, apellido, email, rol, password, created_at, updated_at)
                       VALUES (:nombre, :apellido, :email, :rol, :password, NOW(), NOW())";

            $insert_usuario = $conexion->prepare($insert);
            $insert_usuario->bindParam(':nombre', $nombre);
            $insert_usuario->bindParam(':apellido', $apellido);
            $insert_usuario->bindParam(':email', $email);
            $insert_usuario->bindParam(':rol', $rol);
            $insert_usuario->bindParam(':password', $passwordHash);

            // Mensajes informativos
            if ($insert_usuario->execute()) {
                echo "<script>alert('Usuario registrado correctamente.'); window.location.href='./gestionUsuarios.php';</script>";
            } else {
                $errores[] = "Error al registrar el usuario.";
            }
        } catch (PDOException $e) {
            error_log("Error en la inserción: " . $e->getMessage());
            $errores[] = "Hubo un error al registrar. Inténtalo más tarde.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Nuevo usuario</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo2">

    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 480px; width: 100%;">

        <!-- Navbar con título y botón para volver -->
        <nav class="navbar navbar-dark">
            <div class="container">
                <span class="navbar-brand fs-3 fw-bold">Añadir usuario</span>
                <ul class="navbar-nav">
                    <li class="nav-item"> <a class="nav-link" href="administrador.php">Principal</a></li>
                </ul>
            </div>
        </nav>

        <!-- Contenedor principal -->
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

            <!-- Formulario de registro -->
            <form action="" method="POST" class="mx-auto" style="max-width: 500px;">

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" name="nombre" id="nombre" class="form-control"
                        value="<?= htmlspecialchars($nombre) ?>">
                </div>

                <div class="mb-3">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" name="apellido" id="apellido" class="form-control"
                        value="<?= htmlspecialchars($apellido) ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico:</label>
                    <input type="email" name="email" id="email" class="form-control"
                        value="<?= htmlspecialchars($email) ?>">
                </div>

                <div class="mb-3">
                    <label for="rol" class="form-label">Rol:</label>
                    <select name="rol" id="rol" class="form-select">
                        <option value="participante" <?= ($rol === "participante") ? "selected" : "" ?>>Participante</option>
                        <option value="admin" <?= ($rol === "admin") ? "selected" : "" ?>>Administrador</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Registrarse</button>
                </div>

            </form>

        </main>
    </div>

</body>

</html>