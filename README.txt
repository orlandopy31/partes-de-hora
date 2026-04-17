SISTEMA: Partes de Trabajo (PHP + MySQL) - Bootstrap + PDF (Dompdf)

1) Requisitos
   - PHP 7.4+ (recomendado 8.x) con extensiones: mysqli
   - MySQL/MariaDB
   - Composer (para instalar dompdf)

2) Instalación
   a) Copiá esta carpeta a tu servidor web, por ejemplo:
      /var/www/html/partes/

   b) Creá la base de datos e importá el SQL:
      - Abrí el archivo: database.sql
      - Ejecutalo en MySQL (phpMyAdmin o consola)

   c) Configurá credenciales en config.php:
      $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME

   d) Instalá Dompdf con Composer (dentro de la carpeta del proyecto):
      composer install

   e) Accedé desde el navegador:
      http://TU_DOMINIO/partes/

3) Primeros pasos
   - Cargá clientes (Clientes)
   - Cargá técnicos (Técnicos)
   - Registrá trabajos (Trabajos)
   - Generá PDF por cliente y mes (Reportes)

Nota:
  Si no querés usar PDF todavía, podés igual usar el sistema.
  Para el PDF es obligatorio correr 'composer install'.
