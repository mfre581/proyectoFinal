<?php
/* PÁGINA DE REGISTRO
* @author: Michel Freymann
* Permite a los usuarios registrarse creando una cuenta con nombre, email y contraseña.
* Realiza validaciones básicas y guarda el usuario en la base de datos.
*/

// Incluye las variables de conexión y funciones reutilizables
require_once("./utiles/variables.php");
require_once("./utiles/funciones.php");

session_start();

$conexion = conectarPDO($host, $user, $password, $bbdd);

$errores = [];
$email = $_POST["email"] ?? "";
$contrasena = $_POST["password"] ?? "";
$nombre = $_POST["nombre"] ?? "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verifica si el email ya está registrado
    $select = "SELECT COUNT(*) as cuenta FROM usuarios WHERE email = :email";
    $consulta = $conexion->prepare($select);
    $consulta->execute(["email" => $email]);
    $resultado = $consulta->fetch();

    if ($resultado["cuenta"] > 0) {
        $errores[] = "La dirección de email ya está registrada.";
    }

    // Comprobar longitud de la contraseña (entre 5 y 20 caracteres)
    if (strlen($contrasena) < 5 || strlen($contrasena) > 20) {
        $errores[] = "La contraseña debe tener entre 5 y 20 caracteres.";
    }

    if (empty($errores)) {
        try {
            // Hashear la contraseña para seguridad
            $passwordHash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Preparar la consulta para insertar el nuevo usuario
            $insert = "INSERT INTO usuarios (nombre, email, password, created_at, updated_at)
                       VALUES (:nombre, :email, :password, NOW(), NOW())";

            $insert_usuario = $conexion->prepare($insert);
            $insert_usuario->bindParam(':nombre', $nombre);
            $insert_usuario->bindParam(':email', $email);
            $insert_usuario->bindParam(':password', $passwordHash);

            if ($insert_usuario->execute()) {
                // Registro exitoso: aviso y redirección al login
                echo "<script>alert('Te has registrado correctamente. Ya puedes acceder como usuario!'); window.location.href='./login.php';</script>";
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
    <title>Registro - Rally Fotográfico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Carga de CSS de Bootstrap desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <!-- Navbar Bootstrap -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fs-1 fw-bold" href="./principal.php">Rally Fotográfico</a>
            <a href="./principal.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <div class="container">
        <!-- Contenedor central -->
        <div class="card p-4 shadow-sm mx-auto" style="max-width: 480px;">
            <h2 class="mb-4 text-center">Registro</h2>

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
                    <input type="text" class="form-control" name="nombre" id="nombre" required value="<?= htmlspecialchars($nombre) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico:</label>
                    <input type="email" class="form-control" name="email" id="email" required value="<?= htmlspecialchars($email) ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                    <div class="form-text">Debe tener entre 5 y 20 caracteres.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrarse</button>
            </form>
        </div>
    </div>

    <!-- Script de Bootstrap para funcionalidades como menú responsive -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
