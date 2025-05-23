<?php
/*
 * LOGIN DE USUARIOS
 * @author: Michel Freymann
 * Permite a usuarios registrados iniciar sesión y ser redirigidos según su rol.
 */

require_once("./utiles/variables.php");
require_once("./utiles/funciones.php");

// Procesar datos enviados mediante POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Recoger email y contraseña enviados por el formulario
    $email = isset($_REQUEST["email"]) ? $_REQUEST["email"] : null;
    $contrasena = isset($_REQUEST["password"]) ? $_REQUEST["password"] : null;

    // Conexión a la base de datos
    $conexion = conectarPDO($host, $user, $password, $bbdd);

    // Preparar consulta para buscar usuario por email
    $select = "SELECT usuario_id, rol, password FROM usuarios WHERE email = :email";
    $consulta = $conexion->prepare($select);
    $consulta->bindParam(':email', $email);
    $consulta->execute();
    $usuario = $consulta->fetch();

    // Validación del usuario
    if (!$usuario) {
        // Email no encontrado
        $errores[] = "El email o la contraseña es incorrecta.";
    } else {
        // Verificar la contraseña con password_verify
        if (password_verify($contrasena, $usuario["password"])) {

            // Iniciar sesión y establecer variables de sesión
            session_start();
            $_SESSION['usuario_id'] = $usuario['usuario_id'];
            $_SESSION['rol'] = $usuario['rol'];

            // Redirigir según rol
            switch ($usuario["rol"]) {
                case 'admin':
                    header("Location: ./administrador/administrador.php");
                    break;
                case 'participante':
                    header("Location: ./participante/participante.php");
                    break;
                default:
                    $errores[] = "Rol desconocido";
                    break;
            }
            exit();
        } else {
            // Contraseña incorrecta
            $errores[] = "El email o la contraseña es incorrecta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Meta para hacer la página responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Rally Fotográfico</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <!-- Barra de navegación sencilla -->
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fs-1 fw-bold" href="./principal.php">Rally Fotográfico</a>
            <a href="./principal.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <!-- Contenedor central para el formulario -->
    <div class="container" style="max-width: 450px;">
        <div class="card shadow-sm p-4">
            <h2 class="mb-4 text-center">Iniciar sesión</h2>

            <!-- Mostrar errores si existen -->
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form action="" method="POST" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico:</label>
                    <input type="email" name="email" id="email" class="form-control" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Acceder</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS para funcionalidades -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>