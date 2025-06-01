<?php
/* PÁGINA DE REGISTRO
* @author: Michel Freymann
* Permite a los usuarios registrarse creando una cuenta con nombre, email y contraseña.
* Realiza validaciones básicas y guarda el usuario en la base de datos.
*/

// Incluye las variables de conexión y funciones reutilizables
require_once("../utiles/variables.php");
require_once("../utiles/funciones.php");

session_start();

$conexion = conectarPDO($host, $user, $password, $bbdd);

$errores = [];
$email = $_POST["email"] ?? "";
$contrasena = $_POST["password"] ?? "";
$nombre = $_POST["nombre"] ?? "";
$apellido = $_POST["apellido"] ?? "";

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

        // Verifica si el email ya está registrado
        $select = "SELECT COUNT(*) as cuenta FROM usuarios WHERE email = :email";
        $consulta = $conexion->prepare($select);
        $consulta->execute(["email" => $email]);
        $resultado = $consulta->fetch();

        if ($resultado["cuenta"] > 0) {
            $errores[] = "La dirección de email ya está registrada.";
        }
    }

    // Validar contraseña
    if (empty($contrasena)) {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($contrasena) < 5 || strlen($contrasena) > 20) {
        $errores[] = "La contraseña debe tener entre 5 y 20 caracteres.";
    }


    if (empty($errores)) {
        try {
            // Hashear la contraseña para seguridad
            $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Preparar la consulta para insertar el nuevo usuario
            $insert = "INSERT INTO usuarios (nombre, apellido, email, password, created_at, updated_at)
                       VALUES (:nombre, :apellido, :email, :password, NOW(), NOW())";

            $insert_usuario = $conexion->prepare($insert);
            $insert_usuario->bindParam(':nombre', $nombre);
            $insert_usuario->bindParam(':apellido', $apellido);
            $insert_usuario->bindParam(':email', $email);
            $insert_usuario->bindParam(':password', $passwordHash);

            if ($insert_usuario->execute()) {
                // Registro exitoso: aviso y redirección al login
                echo "<script>alert('Te has registrado correctamente. Ya puedes acceder como usuario!'); window.location.href='../login/login.php';</script>";
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
    <meta charset="UTF-8">
    <title>Registro</title>
    <!-- Meta etiqueta para diseño responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Link al archivo css que aplica parte del estilo -->
    <link rel="stylesheet" href="../css/estilo.css">
    <!-- Carga de Bootstrap desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex justify-content-center align-items-center min-vh-100 fondo1">


    <!-- Contenedor principal en forma de tarjeta -->
    <div class="card shadow p-4" style="max-width: 480px; width: 100%;">

        <!-- Barra de navegación-->
        <nav class="navbar navbar-dark navbar-expand-lg">
            <div class="container">
        
                <span class="navbar-brand fs-4 fw-bold">Introduce tus datos</span>

                <!-- Botón hamburguesa para móviles -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navbar colapsable -->
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"> <a class="nav-link" href="../index.php">Principal</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenido de la tarjeta -->
        <div class="mt-4">

     
            <!-- Mostrar errores si existen -->
            <?php if (!empty($errores)) : ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err) : ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario de registro -->
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" class="form-control" name="nombre" id="nombre"
                        value="<?= htmlspecialchars($nombre) ?>">
                </div>
                <div class="mb-3">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" class="form-control" name="apellido" id="apellido"
                        value="<?= htmlspecialchars($apellido) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico:</label>
                    <input type="email" class="form-control" name="email" id="email"
                        value="<?= htmlspecialchars($email) ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" class="form-control" name="password" id="password">
                    <div class="form-text">Debe tener entre 5 y 20 caracteres.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrarse</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS para el navbar colapsable -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>