<?php
// Inicia la sesión
session_start();
// Destruye cualquier sesión del usuario
session_destroy();
// Redirecciona a la página principa
header('Location: ../principal.php');
?>