# Bio Links - Clon de Linktree Profesional

Un clon profesional de Linktree desarrollado en PHP y MySQL para centralizar tus enlaces de redes sociales, portafolios y más en una sola página optimizada para móviles.

## Características
- **Recorte de Imágenes en Vivo**: Herramienta de recorte (Cropper.js) integrada para personalizar avatares y miniaturas.
- **Diseño Responsivo**: Totalmente optimizado para dispositivos móviles y escritorio.
- **Instalador Web Automatizado**: Configuración inicial interactiva y rápida.
- **Panel de Administración**: Gestión completa de enlaces, orden de visualización, estadísticas básicas e integraciones.

---

## 🚀 Requisitos del Servidor
- **PHP**: 7.4 o superior (con soporte PDO activo).
- **Base de Datos**: MySQL / MariaDB.
- **Servidor Web**: Apache (con módulo `mod_rewrite` habilitado para rutas limpias).

---

## 🛠️ Instrucciones de Instalación

La instalación es sumamente sencilla y no requiere conocimientos de programación.

### Paso 1: Descargar y subir los archivos
1. Descarga el código fuente de este repositorio.
2. Sube todo el contenido a la carpeta pública de tu hosting web (generalmente llamada `public_html`, `htdocs` o `www`) utilizando el administrador de archivos de tu cPanel o un cliente FTP.

### Paso 2: Crear la Base de Datos
1. Entra a tu panel de control de hosting (ej. cPanel).
2. Ve a la sección **Bases de Datos MySQL** y crea una nueva base de datos.
3. Crea un usuario para esa base de datos y asígnale una contraseña segura.
4. Asocia el usuario a la base de datos otorgándole **TODOS los privilegios**.
*(Guarda estos datos, los necesitarás en el asistente).*

### Paso 3: Asistente de Instalación Automática
1. Abre tu navegador web y ve a tu dominio (ej. `https://tudominio.com`).
2. El sistema detectará que no está configurado y te redirigirá automáticamente al **Asistente de Instalación**.
3. En el asistente, ingresa los datos de la base de datos que creaste en el Paso 2.
4. Define el usuario y contraseña del Administrador.
5. Haz clic en **Instalar Ahora**.

### Paso 4: Seguridad (Muy Importante ⚠️)
Una vez finalizada la instalación correctamente, por razones de seguridad:
- Ingresa al administrador de archivos de tu cPanel o FTP.
- Busca la carpeta llamada **`install/`** en la raíz del proyecto.
- **ELIMINA** la carpeta `install/` por completo para evitar que terceros intenten reinstalar el sistema o cambiar tu configuración.

---

## 🔑 Acceso
Una vez instalado, puedes acceder a tu panel de control desde:
`https://tudominio.com/login` (o `https://tudominio.com/login.php` si la redirección no está activa)

---

## 📄 Licencia
Este proyecto es de distribución libre y abierta para uso personal o comercial bajo tus propios términos.
