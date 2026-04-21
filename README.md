# 📦 Sistema de Ventas e Inventario - Licorería POS

Este es un sistema de gestión comercial integral diseñado para el control de inventarios, procesos de ventas y auditoría de caja. Ideal para negocios tipo licorería o puntos de venta minoristas.

<img width="1200" height="1600" alt="WhatsApp Image 2026-04-06 at 9 44 15 PM" src="https://github.com/user-attachments/assets/1197208e-7128-469b-b4ad-c25e1abe3640" />
> *Vista del Historial de Ventas y Panel Administrativo.*

## 🚀 Características Principales

* **Dashboard Informativo:** Resumen de métricas clave del negocio.
* **Gestión de Ventas:** Interfaz rápida para registrar transacciones.
* **Historial de Ventas:** Filtros avanzados por cajero, fecha y turno con auditoría detallada.
* **Control de Inventario:** Gestión de stock, categorías y productos/combos.
* **Módulo de Compras:** Registro de entrada de mercadería y gestión de proveedores.
* **Gestión de Clientes:** Base de datos de clientes recurrentes.
* **Caja y Reportes:** Control de efectivo en turno y generación de reportes detallados.
* **Seguridad:** Sistema de login con hashing de contraseñas y roles de usuario (Administrador/Cajero).

## 🛠️ Tecnologías Utilizadas

El proyecto ha sido desarrollado utilizando un stack web moderno y eficiente:

* **Lenguaje:** PHP 94.9%
* **Frontend:** JavaScript 4.9%, CSS 0.2%, HTML5.
* **Base de Datos:** MySQL.
* **Servidor Local:** XAMPP.
* **Librerías:** * `dompdf` para la generación de facturas y reportes en PDF.

## 📂 Estructura del Proyecto

```text
├── activos/        # Recursos estáticos (Imágenes, JS, CSS)
├── configuración/  # Archivos de conexión y parámetros globales
├── centro/         # Lógica central del sistema
├── dompdf/         # Librería para reportes PDF
├── incluye/        # Componentes reutilizables (Headers, footers)
├── módulos/        # Funcionalidades específicas por sección
├── generar_hash.php # Utilidad de seguridad para contraseñas
└── index.php       # Punto de entrada al sistema

⚙️ Instalación
Clonar el repositorio:

Bash
git clone [https://github.com/JorgeYT20/sistema_ventas_inventario.git](https://github.com/JorgeYT20/sistema_ventas_inventario.git)
Configurar el entorno:

Mover la carpeta del proyecto a C:/xampp/htdocs/.

Importar la base de datos SQL (adjunta en los archivos del proyecto) desde phpMyAdmin.

Configurar conexión:

Editar el archivo de configuración en la carpeta configuracion/ con tus credenciales locales de MySQL.

Acceso:

Abrir en el navegador: http://localhost/sistema_ventas_inventario/

👤 Autor
Jorge Yataco - JorgeYT20 - Desarrollador Full Stack

© 2026 Sistema de Gestión Comercial.
