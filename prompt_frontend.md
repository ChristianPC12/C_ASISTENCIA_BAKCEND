# Prompt: Contexto completo del Frontend — Sistema de Asistencia IASD

## Informacion general

Este es el frontend del Sistema de Control de Asistencia de la Iglesia Adventista del Septimo Dia. Es una aplicacion React que consume una API REST en PHP 8 puro (sin frameworks). La aplicacion registra la asistencia de 3 cultos semanales: Sabado 09:00, Domingo 18:30 y Miercoles 18:30. Tiene dos roles: ADMIN (gestiona todo) y SECRETARIO (solo registra asistencia). La aplicacion corre en desarrollo sobre Vite con un proxy que redirige las peticiones /api hacia el backend en http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND.

---

## Versiones y dependencias

### Dependencias de produccion

| Paquete      | Version   | Proposito                                |
|--------------|-----------|------------------------------------------|
| react        | ^19.2.0   | Libreria principal de UI                 |
| react-dom    | ^19.2.0   | Renderizado del DOM                      |
| axios        | ^1.13.5   | Cliente HTTP para consumir la API        |
| bootstrap    | ^5.3.8    | Framework CSS para estilos y componentes |

### Dependencias de desarrollo

| Paquete                      | Version   | Proposito                                     |
|------------------------------|-----------|-----------------------------------------------|
| vite                         | ^7.3.1    | Bundler y servidor de desarrollo              |
| @vitejs/plugin-react         | ^5.1.1    | Plugin de Vite para soporte JSX               |
| eslint                       | ^9.39.1   | Linter para analisis estatico de codigo       |
| @eslint/js                   | ^9.39.1   | Configuracion base de ESLint                  |
| eslint-plugin-react-hooks    | ^7.0.1    | Reglas de ESLint para hooks de React          |
| eslint-plugin-react-refresh  | ^0.4.24   | Reglas para Fast Refresh de Vite              |
| globals                      | ^16.5.0   | Definiciones de variables globales del browser|
| @types/react                 | ^19.2.7   | Tipos TypeScript (solo para autocompletado)   |
| @types/react-dom             | ^19.2.3   | Tipos TypeScript (solo para autocompletado)   |

### Dato importante

- NO se usa TypeScript. El proyecto esta en JavaScript con JSX.
- NO se usa ninguna libreria de estado global (Redux, Zustand, etc.). El estado se maneja con hooks nativos de React.
- NO se usan componentes de Bootstrap para React (react-bootstrap). Se usa Bootstrap puro via clases CSS con personalizacion de colores IASD.
- Las notificaciones usan window.alert y window.confirm nativos del navegador. No hay libreria de toasts.
- SI se usa React Router (react-router-dom) para navegacion entre paginas (login, asistencia, usuarios).

---

## Diseno visual y paleta de colores

La aplicacion DEBE usar la identidad visual de la Iglesia Adventista del Septimo Dia. El diseno debe ser moderno, limpio, responsive (mobile-first) e intuitivo para usuarios no tecnicos.

### Paleta de colores IASD

| Uso                  | Color       | Codigo HEX | Nota                                            |
|----------------------|-------------|-------------|--------------------------------------------------|
| Primario             | Azul IASD   | #003366     | Botones principales, navbar, encabezados         |
| Primario hover       | Azul oscuro | #002244     | Hover de botones y enlaces primarios             |
| Secundario           | Dorado IASD | #C5A028     | Acentos, badges, detalles decorativos            |
| Secundario hover     | Dorado osc. | #A88620     | Hover de elementos dorados                       |
| Fondo principal      | Blanco      | #FFFFFF     | Fondo general de la aplicacion                   |
| Fondo secundario     | Gris claro  | #F4F6F9     | Fondo de cards, secciones alternas               |
| Texto principal      | Gris oscuro | #2D3436     | Texto general del cuerpo                         |
| Texto secundario     | Gris medio  | #636E72     | Labels, placeholders, texto de ayuda             |
| Exito                | Verde       | #27AE60     | Badges de estado activo, mensajes de exito       |
| Peligro              | Rojo        | #E74C3C     | Botones eliminar, mensajes de error              |
| Advertencia          | Naranja     | #F39C12     | Alertas, estados pendientes                      |
| Borde sutil          | Gris borde  | #DEE2E6     | Bordes de cards, inputs, tablas                  |

### Directrices de diseno

- **Mobile-first**: El formulario de asistencia debe ser facil de usar en celular. Inputs grandes, botones accesibles con el pulgar.
- **Navbar**: Fondo azul IASD (#003366) con texto blanco y logo/texto de la iglesia. Responsive con hamburger menu en mobile.
- **Cards**: Bordes redondeados, sombra suave (shadow-sm), fondo blanco sobre fondo gris claro.
- **Formularios**: Labels claros en espanol, inputs con bordes suaves, validacion visual inmediata (bordes rojos en error).
- **Tablas**: Responsive (horizontally scrollable en mobile), filas alternas con fondo sutil, badges de colores para estados.
- **Tipografia**: Usar la fuente del sistema (Bootstrap default) en tamanos legibles. Titulos con el azul IASD.
- **Iconografia**: Opcional — si se usan iconos, preferir Bootstrap Icons (bi) ya incluidos con Bootstrap.
- **Espaciado**: Generoso, no amontonar elementos. Usar las clases de spacing de Bootstrap (py-3, mb-4, etc.).

### Personalizar Bootstrap

Sobrescribir las variables CSS de Bootstrap para aplicar la paleta IASD. Se puede hacer con un archivo CSS personalizado que se importe despues de Bootstrap:

```css
/* src/styles/iasd-theme.css */
:root {
  --bs-primary: #003366;
  --bs-primary-rgb: 0, 51, 102;
  --bs-secondary: #C5A028;
  --bs-secondary-rgb: 197, 160, 40;
  --bs-success: #27AE60;
  --bs-danger: #E74C3C;
  --bs-warning: #F39C12;
  --bs-body-bg: #F4F6F9;
  --bs-body-color: #2D3436;
}

.btn-primary {
  background-color: #003366;
  border-color: #003366;
}
.btn-primary:hover {
  background-color: #002244;
  border-color: #002244;
}
.navbar { background-color: #003366 !important; }
```

---

## Estructura de carpetas y archivos

```
C_ASISTENCIA_FRONTEND/
├── .env                            -- Variables de entorno reales (no se sube a Git)
├── .env.example                    -- Plantilla de variables de entorno
├── .gitignore                      -- Archivos y carpetas ignorados por Git
├── index.html                      -- Punto de entrada HTML (Vite lo usa como template)
├── package.json                    -- Dependencias y scripts del proyecto
├── vite.config.js                  -- Configuracion de Vite (proxy, plugins)
├── eslint.config.js                -- Configuracion de ESLint (flat config)
├── prompt_frontend.md              -- Este archivo (documentacion para el agente)
├── README.md                       -- Descripcion general del proyecto
└── src/                            -- Codigo fuente de la aplicacion
    ├── main.jsx                    -- Entry point de React (monta la app en el DOM)
    ├── App.jsx                     -- Componente raiz (router, layout general)
    ├── styles/
    │   └── iasd-theme.css          -- Tema personalizado con paleta IASD
    ├── config/
    │   ├── api.js                  -- Cliente HTTP (instancia de Axios configurada)
    │   └── constants.js            -- Constantes globales del sistema
    ├── api/                        -- Servicios de comunicacion con el backend
    │   ├── authApi.js              -- Llamadas HTTP para autenticacion
    │   ├── cultoApi.js             -- Llamadas HTTP para cultos
    │   ├── asistenciaApi.js        -- Llamadas HTTP para registros de asistencia
    │   └── usuarioApi.js           -- Llamadas HTTP para gestion de usuarios (ADMIN)
    ├── hooks/                      -- Custom hooks con logica de negocio
    │   ├── useAuth.js              -- Hook de autenticacion (login, logout, token)
    │   ├── useAsistencia.js        -- Hook para CRUD de asistencia
    │   └── useUsuario.js           -- Hook para CRUD de usuarios
    ├── validators/                 -- Validaciones de formulario en el frontend
    │   ├── authValidator.js        -- Validacion del formulario de login
    │   ├── asistenciaValidator.js  -- Validacion del formulario de asistencia
    │   └── usuarioValidator.js     -- Validacion del formulario de usuarios
    ├── utils/                      -- Utilidades reutilizables
    │   ├── notify.js               -- Capa de notificaciones (alert, confirm)
    │   └── sanitizer.js            -- Sanitizacion de texto (trim, eliminar HTML)
    ├── components/                 -- Componentes presentacionales por modulo
    │   ├── layout/
    │   │   ├── Navbar.jsx          -- Barra de navegacion superior (azul IASD)
    │   │   └── ProtectedRoute.jsx  -- Wrapper que redirige si no hay token
    │   ├── auth/
    │   │   └── LoginForm.jsx       -- Formulario de inicio de sesion
    │   ├── asistencia/
    │   │   ├── AsistenciaForm.jsx  -- Formulario de registro de asistencia
    │   │   └── AsistenciaTable.jsx -- Tabla de registros de asistencia
    │   └── usuario/
    │       ├── UsuarioForm.jsx     -- Formulario de crear/editar usuario
    │       └── UsuarioTable.jsx    -- Tabla de listado de usuarios
    └── pages/                      -- Paginas completas (una por modulo)
        ├── LoginPage.jsx           -- Pagina de inicio de sesion
        ├── AsistenciaPage.jsx      -- Pagina del modulo de asistencia
        └── UsuarioPage.jsx         -- Pagina del modulo de usuarios (solo ADMIN)
```

---

## Conexion con el backend

### URL base del backend

- **Desarrollo**: `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND`
- **Variable de entorno**: `VITE_API_BASE_URL=/api`

### Configuracion del proxy en Vite

```js
// vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND')
      }
    }
  }
})
```

Con esto, en el codigo del frontend se usa `/api/auth/login` y Vite lo traduce automaticamente a `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND/auth/login`.

### Cliente HTTP (Axios)

```js
// src/config/api.js
import axios from 'axios';

const ApiCliente = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 10000
});

// Interceptor: inyectar token en cada peticion
ApiCliente.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor: extraer data automaticamente
ApiCliente.interceptors.response.use(
  (res) => res.data,
  (error) => {
    if (error.response) {
      return Promise.reject(error.response.data);
    }
    return Promise.reject(error);
  }
);

export default ApiCliente;
```

### Autenticacion — Flujo completo

1. Usuario introduce usuario + contrasena en LoginForm.
2. Se hace `POST /api/auth/login` con `{ "usuario": "...", "password": "..." }`.
3. El backend responde con un **token** (64 caracteres hexadecimales) y datos del usuario.
4. El frontend guarda el token en `localStorage.setItem('token', datos.token)` y los datos del usuario.
5. Todas las peticiones subsiguientes incluyen el header `Authorization: Bearer <token>`.
6. Al hacer logout, se llama `POST /api/auth/logout` y se limpia localStorage.
7. El token NO expira. Vive hasta que el usuario haga logout explicitamente o se haga un nuevo login (el backend elimina tokens anteriores al crear uno nuevo).

### Manejo de roles

| Rol         | Puede hacer                                                    |
|-------------|----------------------------------------------------------------|
| ADMIN       | Todo: registrar asistencia, ver reportes, gestionar usuarios   |
| SECRETARIO  | Solo: registrar asistencia, ver reportes                       |

- El frontend debe ocultar la seccion de Usuarios si el rol NO es ADMIN.
- El backend valida permisos tambien (retorna 403), pero el frontend debe evitar mostrar lo que no corresponde.

---

## Endpoints del backend — Referencia completa

### Autenticacion (sin token)

#### POST /api/auth/login

Inicia sesion y obtiene un token.

**Request:**
```json
{
  "usuario": "admin",
  "password": "admin123"
}
```
- `usuario`: string, obligatorio, 3-50 caracteres.
- `password`: string, obligatorio.

**Response exitosa (200):**
```json
{
  "exito": true,
  "mensaje": "Inicio de sesion exitoso.",
  "datos": {
    "token": "c0162803bad759b534715d9d15f087877204ed33df903779ee651fc651d095b7",
    "usuario": {
      "id": 1,
      "nombre_completo": "Administrador General",
      "usuario": "admin",
      "rol": "ADMIN"
    }
  }
}
```

**Response error (401):**
```json
{
  "exito": false,
  "mensaje": "Credenciales incorrectas."
}
```

---

### Autenticacion (con token)

#### POST /api/auth/logout

Cierra sesion e invalida el token.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Sesion cerrada correctamente."
}
```

#### GET /api/auth/me

Obtiene los datos del usuario autenticado. Util para verificar si el token sigue valido.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Usuario autenticado.",
  "datos": {
    "id": 1,
    "nombre_completo": "Administrador General",
    "usuario": "admin",
    "rol_id": 1,
    "rol": "ADMIN",
    "activo": true,
    "creado_en": "2026-03-02 15:51:53",
    "actualizado_en": "2026-03-02 15:51:53"
  }
}
```

---

### Cultos

#### GET /api/cultos

Lista los 3 cultos disponibles. Se usa para llenar el selector de culto en el formulario de asistencia.

**Headers:** `Authorization: Bearer <token>`

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Lista de cultos obtenida.",
  "datos": [
    { "id": 1, "codigo": "SABADO",    "nombre": "Culto Sabado",    "dia_semana": 7, "hora_inicio": "09:00:00" },
    { "id": 2, "codigo": "DOMINGO",   "nombre": "Culto Domingo",   "dia_semana": 1, "hora_inicio": "18:30:00" },
    { "id": 3, "codigo": "MIERCOLES", "nombre": "Culto Miercoles", "dia_semana": 4, "hora_inicio": "18:30:00" }
  ]
}
```

**Nota sobre dia_semana**: Usa la convencion MySQL DAYOFWEEK: 1=Domingo, 2=Lunes, 3=Martes, 4=Miercoles, 5=Jueves, 6=Viernes, 7=Sabado.

---

### Asistencia — CRUD completo

#### GET /api/asistencias

Lista registros de asistencia con filtros opcionales.

**Headers:** `Authorization: Bearer <token>`

**Query params opcionales:**

| Parametro  | Tipo   | Descripcion                                      | Ejemplo                    |
|------------|--------|--------------------------------------------------|----------------------------|
| culto      | string | Codigo del culto (SABADO, DOMINGO, MIERCOLES)    | ?culto=SABADO              |
| culto_id   | int    | ID del culto (alternativa a codigo)              | ?culto_id=1                |
| anio       | int    | Filtrar por anio                                 | ?anio=2026                 |
| trimestre  | int    | Filtrar por trimestre (1-4)                      | ?trimestre=1               |

Se pueden combinar: `?culto=SABADO&anio=2026&trimestre=1`

Si se envian `culto` y `culto_id` al mismo tiempo, `culto` (codigo) tiene prioridad.

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Lista de registros de asistencia.",
  "datos": [
    {
      "id": 1,
      "culto_id": 1,
      "culto_codigo": "SABADO",
      "culto_nombre": "Culto Sabado",
      "fecha": "2026-02-28",
      "anio": 2026,
      "trimestre": 1,
      "llegaron_antes_hora": 30,
      "llegaron_despues_hora": 10,
      "ninos": 8,
      "jovenes": 12,
      "total_asistentes": 40,
      "proc_barrio": 25,
      "proc_guayabo": 15,
      "visitas_barrio": 3,
      "nombres_visitas_barrio": "Juan, Maria, Pedro",
      "visitas_guayabo": 2,
      "nombres_visitas_guayabo": "Ana, Luis",
      "retiros_antes_terminar": 5,
      "se_quedaron_todo": 35,
      "observaciones": "Culto normal",
      "registrado_por": 1,
      "registrado_por_nombre": "Administrador General",
      "creado_en": "2026-03-02 16:20:55",
      "actualizado_en": "2026-03-02 16:20:55"
    }
  ]
}
```

#### GET /api/asistencias/{id}

Obtiene un registro de asistencia por ID.

**Headers:** `Authorization: Bearer <token>`

**Response (200):** Misma estructura que un elemento del array anterior, dentro de `datos`.

**Error (404):**
```json
{ "exito": false, "mensaje": "Registro de asistencia no encontrado." }
```

#### POST /api/asistencias

Crea un nuevo registro de asistencia.

**Headers:** `Authorization: Bearer <token>`, `Content-Type: application/json`

**Request:**
```json
{
  "culto_id": 1,
  "fecha": "2026-02-28",
  "llegaron_antes_hora": 30,
  "llegaron_despues_hora": 10,
  "ninos": 8,
  "jovenes": 12,
  "total_asistentes": 40,
  "proc_barrio": 25,
  "proc_guayabo": 15,
  "visitas_barrio": 3,
  "nombres_visitas_barrio": "Juan, Maria, Pedro",
  "visitas_guayabo": 2,
  "nombres_visitas_guayabo": "Ana, Luis",
  "retiros_antes_terminar": 5,
  "se_quedaron_todo": 35,
  "observaciones": "Culto normal"
}
```

**Campos obligatorios y reglas de validacion:**

| Campo                    | Tipo   | Obligatorio | Regla                                           |
|--------------------------|--------|:-----------:|--------------------------------------------------|
| culto_id                 | int    | SI          | Debe existir en la tabla cultos                  |
| fecha                    | string | SI          | Formato YYYY-MM-DD; debe corresponder al dia del culto |
| llegaron_antes_hora      | int    | SI          | >= 0                                             |
| llegaron_despues_hora    | int    | SI          | >= 0                                             |
| ninos                    | int    | SI          | >= 0                                             |
| jovenes                  | int    | SI          | >= 0                                             |
| total_asistentes         | int    | SI          | >= 0; debe ser >= ninos + jovenes                |
| proc_barrio              | int    | SI          | >= 0                                             |
| proc_guayabo             | int    | SI          | >= 0                                             |
| visitas_barrio           | int    | SI          | >= 0                                             |
| nombres_visitas_barrio   | string | NO          | Nombres separados por coma; null si vacio        |
| visitas_guayabo          | int    | SI          | >= 0                                             |
| nombres_visitas_guayabo  | string | NO          | Nombres separados por coma; null si vacio        |
| retiros_antes_terminar   | int    | SI          | >= 0; retiros + se_quedaron <= total_asistentes  |
| se_quedaron_todo         | int    | SI          | >= 0; retiros + se_quedaron <= total_asistentes  |
| observaciones            | string | NO          | Texto libre; null si vacio                       |

**Validaciones de negocio que hace el backend:**

1. El culto debe existir.
2. La fecha debe coincidir con el dia de la semana del culto (ej: culto SABADO solo acepta fechas que caigan en sabado).
3. No puede existir otro registro para el mismo culto en la misma fecha (duplicado).
4. `total_asistentes >= ninos + jovenes`.
5. `retiros_antes_terminar + se_quedaron_todo <= total_asistentes`.

**Response exitosa (201):**
```json
{
  "exito": true,
  "mensaje": "Registro de asistencia creado correctamente.",
  "datos": { /* registro completo con id, anio, trimestre autocalculados */ }
}
```

**Nota importante**: Los campos `anio` y `trimestre` se calculan automaticamente en la base de datos a partir de la fecha. NO se envian en el request; se reciben en la respuesta.

**Errores posibles:**

| HTTP | Mensaje ejemplo                                                              |
|------|------------------------------------------------------------------------------|
| 400  | "Errores de validacion: ..." (campos invalidos)                             |
| 409  | "La fecha 2026-02-28 no corresponde al dia del culto Culto Sabado."        |
| 409  | "Ya existe un registro de asistencia para el culto X en la fecha Y."       |

#### PUT /api/asistencias/{id}

Actualiza un registro existente. Mismos campos y reglas que POST. El campo `registrado_por` NO se modifica.

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Registro de asistencia actualizado correctamente.",
  "datos": { /* registro completo actualizado */ }
}
```

#### DELETE /api/asistencias/{id}

Elimina un registro de asistencia (eliminacion permanente).

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Registro de asistencia eliminado correctamente."
}
```

---

### Usuarios — CRUD (solo ADMIN)

Todos los endpoints de usuarios requieren rol ADMIN. Si un SECRETARIO intenta acceder, recibe 403.

#### GET /api/usuarios

Lista todos los usuarios.

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Lista de usuarios.",
  "datos": [
    {
      "id": 1,
      "nombre_completo": "Administrador General",
      "usuario": "admin",
      "rol_id": 1,
      "rol": "ADMIN",
      "activo": true,
      "creado_en": "2026-03-02 15:51:53",
      "actualizado_en": "2026-03-02 15:51:53"
    }
  ]
}
```

#### GET /api/usuarios/{id}

Obtiene un usuario por ID.

#### POST /api/usuarios

Crea un nuevo usuario.

**Request:**
```json
{
  "nombre_completo": "Maria Perez",
  "usuario": "mperez",
  "password": "clave123",
  "rol_id": 2
}
```

| Campo            | Tipo   | Obligatorio | Regla                              |
|------------------|--------|:-----------:|-------------------------------------|
| nombre_completo  | string | SI          | 3-120 caracteres                    |
| usuario          | string | SI          | 3-50 caracteres, unico en el sistema |
| password         | string | SI          | Minimo 6 caracteres                 |
| rol_id           | int    | SI          | 1 (ADMIN) o 2 (SECRETARIO)         |

**Error de duplicado (409):**
```json
{ "exito": false, "mensaje": "El nombre de usuario ya esta registrado." }
```

#### PUT /api/usuarios/{id}

Actualiza un usuario existente.

**Request:**
```json
{
  "nombre_completo": "Maria Perez Actualizada",
  "usuario": "mperez",
  "rol_id": 2,
  "activo": true,
  "password": "nuevaClave123"
}
```

| Campo            | Tipo   | Obligatorio | Regla                              |
|------------------|--------|:-----------:|-------------------------------------|
| nombre_completo  | string | SI          | 3-120 caracteres                    |
| usuario          | string | SI          | 3-50 caracteres, unico             |
| rol_id           | int    | SI          | 1 o 2                              |
| activo           | bool   | NO          | Default true si no se envia        |
| password         | string | NO          | Si se envia, minimo 6 caracteres   |

#### DELETE /api/usuarios/{id}

Desactiva un usuario (soft delete, no lo borra de la base de datos, solo pone activo=false).

**Response (200):**
```json
{
  "exito": true,
  "mensaje": "Usuario desactivado correctamente."
}
```

---

## Formato de respuesta del backend

Todas las respuestas del backend tienen esta estructura:

```json
// Exito
{
  "exito": true,
  "mensaje": "Descripcion del resultado.",
  "datos": { ... }
}

// Exito sin datos
{
  "exito": true,
  "mensaje": "Operacion completada."
}

// Error
{
  "exito": false,
  "mensaje": "Descripcion del error."
}
```

- `exito` (boolean): siempre presente. Indica si la operacion fue exitosa.
- `mensaje` (string): siempre presente. Mensaje descriptivo en espanol.
- `datos` (mixed): solo presente cuando hay datos que retornar. Puede ser un objeto o un array.

**IMPORTANTE**: El campo se llama `exito`, NO `ok`. El campo de error esta en `mensaje`, NO en `error`. Verificar siempre `res.exito` (no `res.ok`).

---

## Codigos HTTP que devuelve el backend

| Codigo | Significado                    | Cuando ocurre                                          |
|--------|----------------------------------|-------------------------------------------------------|
| 200    | OK                               | Operacion exitosa (listar, obtener, actualizar, etc.) |
| 201    | Creado                           | Registro creado exitosamente                          |
| 204    | Sin contenido                    | Respuesta a OPTIONS (preflight CORS)                  |
| 400    | Solicitud invalida               | Errores de validacion (campos faltantes o invalidos)  |
| 401    | No autorizado                    | Token faltante, invalido o credenciales incorrectas   |
| 403    | Prohibido                        | Rol insuficiente (secretario intenta acceder a ADMIN) |
| 404    | No encontrado                    | Recurso no existe o ruta no definida                  |
| 409    | Conflicto                        | Duplicado o error de regla de negocio                 |
| 500    | Error interno                    | Error no controlado en el servidor                    |

---

## Arquitectura y patron de diseno

El proyecto sigue una arquitectura de separacion por responsabilidades:

```
Pagina (Page)
  └── usa un Custom Hook (logica de negocio, estado)
        ├── llama al Servicio API (comunicacion HTTP)
        │     └── usa ApiCliente (instancia Axios configurada)
        ├── valida con Validator (reglas del formulario)
        ├── sanitiza con Sanitizer (limpieza de inputs)
        └── notifica con Notify (mensajes al usuario)
  └── renderiza Componentes (presentacionales puros)
        └── reciben datos y callbacks via props
```

### Flujo de datos

1. La Page monta el Hook.
2. El Hook carga datos del backend al montar (useEffect).
3. Los datos fluyen hacia abajo a los Components via props.
4. Los eventos del usuario suben desde los Components hacia el Hook via callbacks.
5. El Hook valida, sanitiza, llama a la API, actualiza estado y notifica.

### Reglas generales

- Los componentes de components/ son presentacionales puros. No importan hooks de negocio ni llaman a la API directamente.
- Los hooks de hooks/ son la unica fuente de logica de negocio. Manejan estado, validacion, sanitizacion, llamadas API y notificaciones.
- Los archivos de api/ solo contienen llamadas HTTP. No manejan estado ni UI.
- Los archivos de validators/ solo contienen funciones de validacion. Retornan { valido, errores }.
- Los archivos de utils/ son funciones puras reutilizables. No dependen de React.
- Las constantes de config/constants.js se usan en toda la aplicacion. No se hardcodean strings.

---

## Flujo funcional de la aplicacion

### 1. Login

- Al abrir la app, si no hay token en localStorage, mostrar LoginPage.
- El formulario pide usuario y contrasena.
- Al hacer login exitoso, guardar token y datos del usuario en localStorage.
- Redirigir a la pagina principal (AsistenciaPage).

### 2. Registro de asistencia (flujo principal)

Este es el flujo mas importante de la aplicacion:

1. El usuario selecciona un **culto** del dropdown (Sabado, Domingo, Miercoles).
2. El usuario selecciona una **fecha** con un date picker.
3. El frontend puede validar opcionalmente que la fecha corresponda al dia del culto (pero el backend lo valida siempre).
4. El usuario llena los campos numericos:
   - Llegaron antes de la hora / Llegaron despues de la hora
   - Ninos / Jovenes / Total de asistentes
   - Procedentes del barrio / Procedentes de Guayabo
   - Visitas del barrio (cantidad + nombres) / Visitas de Guayabo (cantidad + nombres)
   - Se retiraron antes de terminar / Se quedaron todo el culto
5. Opcionalmente escribe observaciones.
6. Al guardar, el backend calcula automaticamente el **anio** y **trimestre** a partir de la fecha.
7. El registro aparece en la tabla de asistencias.

### 3. Consulta de asistencias

- Tabla con todos los registros, con filtros por culto, anio y trimestre.
- Boton para editar cada registro (carga datos en el formulario).
- Boton para eliminar (con confirmacion).

### 4. Gestion de usuarios (solo ADMIN)

- Tabla con todos los usuarios.
- Formulario para crear/editar usuarios (nombre, usuario, contrasena, rol).
- Boton para desactivar usuarios.
- Esta seccion debe estar OCULTA para usuarios con rol SECRETARIO.

---

## Consideraciones de UX importantes

### Formulario de asistencia

- El formulario debe ser **intuitivo y facil de usar en celular**. Es el formulario que mas se usara.
- Los campos numericos deben tener `type="number"` y `min="0"` para facilitar la entrada en mobile.
- El campo de fecha debe ser `type="date"`.
- Los campos de nombres de visitas (`nombres_visitas_barrio`, `nombres_visitas_guayabo`) son textarea o input de texto libre donde se escriben nombres separados por coma.
- El selector de culto debe ser claro y mostrar el nombre + dia + hora.
- Agrupar los campos logicamente con secciones visuales (cards o fieldsets):
  - **Informacion del culto**: Culto + Fecha
  - **Puntualidad**: Antes de hora + Despues de hora
  - **Composicion**: Ninos + Jovenes + Total
  - **Procedencia**: Barrio + Guayabo
  - **Visitas**: Visitas barrio (cantidad + nombres) + Visitas Guayabo (cantidad + nombres)
  - **Permanencia**: Retiros + Se quedaron
  - **Observaciones**: Campo de texto

### Navbar

- Mostrar: nombre de la iglesia, nombre del usuario logueado, rol, boton de logout.
- En mobile: hamburger menu con las opciones de navegacion.
- Links: Asistencia (siempre visible), Usuarios (solo si ADMIN).

### Tabla de asistencias

- Mostrar columnas principales: Fecha, Culto, Total asistentes, Ninos, Jovenes, Acciones.
- En mobile: hacer la tabla scrollable horizontalmente o usar un layout de cards en vez de tabla.
- Filtros arriba de la tabla: dropdown de culto, selector de anio, selector de trimestre.
- Los datos se cargan automaticamente al cambiar un filtro.

---

## Variables de entorno

| Variable            | Valor en desarrollo | Proposito                          |
|---------------------|---------------------|------------------------------------|
| VITE_API_BASE_URL   | /api                | URL base para todas las peticiones |

El proxy de Vite traduce `/api/auth/login` a `http://localhost/C_ASISTENCIA_BACKEND/C_ASISTENCIA_BAKCEND/auth/login`.

---

## Idioma y convenciones

- El codigo usa espanol para nombres de variables, funciones, constantes y comentarios.
- Los nombres de componentes React usan PascalCase en espanol: AsistenciaForm, LoginPage.
- Los nombres de hooks usan camelCase con prefijo use: useAsistencia, useAuth.
- Los nombres de archivos de servicio usan camelCase: asistenciaApi.js, authApi.js.
- Los nombres de constantes usan UPPER_SNAKE_CASE: CULTOS, ASISTENCIA_FORM_INICIAL.
- Las funciones utilitarias usan camelCase: sanitizar, notificarError.
- No se usan emojis en la documentacion ni en el codigo.
- Los comentarios son concisos y en espanol.

---

## Credenciales de prueba

| Usuario | Contrasena | Rol   |
|---------|------------|-------|
| admin   | admin123   | ADMIN |

---

## Resumen de archivos API por modulo

### authApi.js

```js
const authApi = {
  login:  (payload) => ApiCliente.post('/auth/login', payload),   // sin token
  logout: ()        => ApiCliente.post('/auth/logout'),            // con token
  me:     ()        => ApiCliente.get('/auth/me')                  // con token
};
```

### cultoApi.js

```js
const cultoApi = {
  listar: () => ApiCliente.get('/cultos')    // con token
};
```

### asistenciaApi.js

```js
const asistenciaApi = {
  listar:       (params) => ApiCliente.get('/asistencias', { params }),   // con token, params opcionales
  obtenerPorId: (id)     => ApiCliente.get(`/asistencias/${id}`),         // con token
  crear:        (data)   => ApiCliente.post('/asistencias', data),        // con token
  actualizar:   (id, data) => ApiCliente.put(`/asistencias/${id}`, data), // con token
  eliminar:     (id)     => ApiCliente.delete(`/asistencias/${id}`)       // con token
};
```

### usuarioApi.js

```js
const usuarioApi = {
  listar:       ()         => ApiCliente.get('/usuarios'),                 // ADMIN
  obtenerPorId: (id)       => ApiCliente.get(`/usuarios/${id}`),           // ADMIN
  crear:        (data)     => ApiCliente.post('/usuarios', data),          // ADMIN
  actualizar:   (id, data) => ApiCliente.put(`/usuarios/${id}`, data),     // ADMIN
  eliminar:     (id)       => ApiCliente.delete(`/usuarios/${id}`)         // ADMIN
};
```

---

## Constantes sugeridas para constants.js

```js
// Roles del sistema
export const ROLES = {
  ADMIN: 'ADMIN',
  SECRETARIO: 'SECRETARIO'
};

export const ROL_OPCIONES = [
  { valor: 1, etiqueta: 'Administrador' },
  { valor: 2, etiqueta: 'Secretario/a' }
];

// Cultos (se cargan del backend, pero utiles para referencia)
export const CULTO_CODIGOS = {
  SABADO: 'SABADO',
  DOMINGO: 'DOMINGO',
  MIERCOLES: 'MIERCOLES'
};

// Trimestres
export const TRIMESTRE_OPCIONES = [
  { valor: 1, etiqueta: '1er Trimestre (Ene-Mar)' },
  { valor: 2, etiqueta: '2do Trimestre (Abr-Jun)' },
  { valor: 3, etiqueta: '3er Trimestre (Jul-Sep)' },
  { valor: 4, etiqueta: '4to Trimestre (Oct-Dic)' }
];

// Formulario inicial de asistencia
export const ASISTENCIA_FORM_INICIAL = {
  culto_id: '',
  fecha: '',
  llegaron_antes_hora: 0,
  llegaron_despues_hora: 0,
  ninos: 0,
  jovenes: 0,
  total_asistentes: 0,
  proc_barrio: 0,
  proc_guayabo: 0,
  visitas_barrio: 0,
  nombres_visitas_barrio: '',
  visitas_guayabo: 0,
  nombres_visitas_guayabo: '',
  retiros_antes_terminar: 0,
  se_quedaron_todo: 0,
  observaciones: ''
};

// Formulario inicial de usuario
export const USUARIO_FORM_INICIAL = {
  id: null,
  nombre_completo: '',
  usuario: '',
  password: '',
  rol_id: 2   // Default: Secretario
};

// Limites de campos
export const LIMITES = {
  NOMBRE_COMPLETO_MAX: 120,
  USUARIO_MAX: 50,
  PASSWORD_MIN: 6
};
```
