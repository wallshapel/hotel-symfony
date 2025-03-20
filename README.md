Escenario API RESTful "Hotel"

Sistema de gestión para una cadena hotelera que permitirá a los usuarios reservar habitaciones, administrar sus cuentas y realizar pagos. Además, los administradores podrán subir imágenes de los hoteles y habitaciones.

📌 Características clave del proyecto:

1️⃣ Persistencia con MySQL:

Base de datos con entidades para usuarios, hoteles, habitaciones y reservas. Se usará Doctrine ORM para manejar las relaciones.

2️⃣ Seguridad con JWT:

Autenticación: Inicio de sesión con email/contraseña y obtención de token JWT. Roles: ROLE_USER (Clientes, pueden ver hoteles, habitaciones y hacer reservas). ROLE_ADMIN (Puede crear y administrar hoteles, habitaciones, reservas y subir imágenes). Protección de rutas: Algunas rutas serán públicas y otras protegidas por roles.

3️⃣ Seeds y Factories:

Se usarán fixtures para cargar datos iniciales (hoteles y habitaciones). Se usarán factories y Faker para poblar la base de datos con datos de prueba.

4️⃣ Entidades y relaciones:

User: Usuarios registrados. Relación uno a muchos con reservas. Hotel: Información de cada hotel. Relación uno a muchos con habitaciones. Room: Habitaciones de cada hotel. Relación uno a muchos con reservas. Booking: Reservas de usuarios. Relación muchos a uno con User y Room. Image: Imágenes subidas por administradores. Relación muchos a uno con Hotel y Room.

5️⃣ Gestión de imágenes:

Solo los administradores pueden subir imágenes de hoteles y habitaciones. Las imágenes se almacenarán en el servidor o en un servicio de almacenamiento en la nube. Se podrán obtener mediante una API para ser usadas en aplicaciones frontend o móviles. Ejemplo de flujo en la API: Un usuario se registra y obtiene un token JWT. Consulta los hoteles disponibles y sus habitaciones. Reserva una habitación. Un administrador puede agregar hoteles, habitaciones y subir imágenes. Los usuarios pueden ver las imágenes de los hoteles y habitaciones antes de reservar.