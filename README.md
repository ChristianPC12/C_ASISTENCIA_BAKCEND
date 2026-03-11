# C_ASISTENCIA_BACKEND

Backend PHP para control de asistencia y gestion multitenant (superadmin).

## Correo Brevo (ADMIN temporal)

El flujo `Crear ADMIN temporal` ya esta integrado para enviar credenciales por correo con Brevo.

### 1) Configurar credenciales locales

1. Copia `Config/Global.local.example.php` a `Config/Global.local.php`.
2. Completa estas llaves:
   - `BREVO_API_KEY`
   - `BREVO_SENDER_EMAIL`
   - `BREVO_SENDER_NAME`
3. Reinicia Apache en XAMPP.

Tambien puedes usar variables de entorno con los mismos nombres.

### 2) Requisitos en Brevo

1. Tener API key activa con permiso para envio transaccional.
2. Tener verificado el remitente usado en `BREVO_SENDER_EMAIL` (single sender o dominio).

### 3) Probar envio

1. Desde el modulo superadmin, abre `Crear ADMIN temporal`.
2. Selecciona una organizacion con `correo_contacto`.
3. Activa `Enviar credenciales por correo`.
4. Crea el admin temporal y revisa el estado devuelto en la tabla.

### 4) Errores comunes

- `configuracion Brevo incompleta`: falta `BREVO_API_KEY` o `BREVO_SENDER_EMAIL`.
- `proveedor rechazo la solicitud (HTTP 4xx/5xx)`: revisar remitente verificado, cuota diaria, o permisos de API key.
