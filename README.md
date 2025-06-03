# Retales Urbanos - Aplicación de Votación de Fotografías

Una plataforma web sencilla que permite mostrar, gestionar y votar fotografías urbanas. Diseñada con PHP, HTML y JavaScript, simula un concurso fotográfico accesibles desde cualquier navegador.

## Tecnologías utilizadas

- Visual Studio Code 1.100.2
- PHP 8.2.12
- Apache HTTP Server versión 2.4.62 (Debian)
- Adminer 4.8.1
- HTML5
- CSS / Bootstrap 5.3.3
- JavaScript ES6

## Instalación

Despliegue local usando XAMPP

Requisitos previos:
- Un equipo con sistema operativo Windows, macOS o Linux.
- Conexión a internet para descargar XAMPP.

1. Descargar e instalar XAMPP
- Accede a la página oficial de XAMPP: https://www.apachefriends.org/es/index.html
- Descarga la versión adecuada para tu sistema operativo.
- Ejecuta el instalador y sigue las instrucciones. Puedes dejar la configuración por defecto.
- Una vez instalado, abre el Panel de control de XAMPP y arranca los servicios de Apache y MySQL.

2. Configurar la base de datos
- Abre tu navegador y ve a http://localhost/phpmyadmin.
- En phpMyAdmin, crea una nueva base de datos con el nombre retales_urbanos_bd.
- Pulsa en “Nueva” en el menú lateral.
- Escribe retales_urbanos_bd en el campo de nombre y haz clic en “Crear”.
(Opcional: si tienes un archivo .sql con la estructura y datos, importa la base de datos usando la pestaña “Importar”.)

3. Configurar la aplicación
- Copia los archivos de la aplicación en la carpeta htdocs de XAMPP.
- En Windows suele estar en C:\xampp\htdocs\
- En macOS/Linux, en /opt/lampp/htdocs/ o similar.

Asegúrate de que en el archivo variables.php tienes estos parámetros:

4. Probar la aplicación
Abre el navegador y navega a http://localhost/tu_carpeta_de_app (reemplaza tu_carpeta_de_app con el nombre de la carpeta donde pusiste los archivos).
$host = "localhost";
$user = "root";   // Usuario por defecto en XAMPP
$password = "";   // Sin contraseña por defecto en XAMPP
$bbdd   = "retales_urbanos_bd";
$conn = new mysqli($host, $user, $password, $bbdd);

Verifica que la aplicación carga sin errores y que puedes interactuar con la base de datos (por ejemplo, registrarte, subir fotos, votar, etc.).

Notas adicionales
- Si quieres modificar la configuración de PHP o Apache, usa el panel de XAMPP para abrir los archivos de configuración.
- Para detener el servidor, cierra Apache y MySQL desde el panel de control.

## Estructura

├── index.php                      # Página principal de la aplicación
├── utiles/
│   ├── funciones.php             # Funciones reutilizables en todo el proyecto
│   └── variables.php             # Variables de configuración, como conexión a la BD
├── registro/
│   └── registro.php              # Formulario y lógica para registrar nuevos usuarios
├── participante/
│   ├── participante.php          # Página principal del usuario participante
│   ├── subirFoto.php             # Funcionalidad para subir fotografías
│   └── tuGaleria.php             # Muestra las fotos subidas por el usuario
├── login/
│   └── login.php                 # Página de inicio de sesión
├── js/
│   ├── filtros.js                # Scripts para aplicar filtros en la galería
│   ├── graficos.js               # Generación de gráficos estadísticos
│   ├── modal.js                  # Ampliación de imágenes con modal
│   └── volverArriba.js           # Gestiona lógica de botón de volver arriba
├── img/                          # Carpeta para las imágenes usadas en el sitio
├── galeria_votos/
│   ├── galeria.php              # Galería pública de fotografías para votar
│   └── votos.php                # Muestra detalles de fotos y gráfico de votaciones
├── css/
│   └── estilos.css              # Hoja de estilos que aplica el sitio 
├── cerrarSesion/
│   └── cerrar_sesion.php        # Cierra la sesión del usuario
├── administrador/
│   ├── administrador.php        # Panel principal del administrador
│   ├── editar.php               # Edición de datos de usuarios
│   ├── gestionBases.php         # Gestión de las reglas del concurso
│   ├── gestionFotos.php         # Gestión de fotos subidas por los participantes
│   ├── gestionUsuarios.php      # Gestión de usuarios registrados
│   └── nuevo.php                # Añadir nuevos usuarios

## Autoría
Desarrollado por Michel Freymann como proyecto final del grado superior DAW.

