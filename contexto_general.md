# Contexto General del Proyecto

## Nombre del sistema
**Sistema de Control de Asistencia - Iglesia Adventista del Septimo Dia**

## Proposito
Backend API REST para registrar y consultar la asistencia a los tres cultos semanales de la iglesia.
El frontend (por separado) consumira estos endpoints para que un usuario con rol SECRETARIO registre
la asistencia y un ADMIN administre usuarios.

---

## Flujo de uso esperado

1. **Login**: El usuario (ADMIN o SECRETARIO) inicia sesion con usuario y contrasena.
   - Recibe un token Bearer que usara en cada peticion posterior.
   - El token vive hasta que haga logout explicito.

2. **Modulo de Registro de Asistencia**: Al entrar, el usuario ve la opcion de registrar asistencia
   para uno de los tres cultos:
   - **Sabado** (09:00)
   - **Domingo** (18:30)
   - **Miercoles** (18:30)

3. **Seleccion de fecha**: El usuario elige la fecha del culto. El sistema calcula automaticamente:
   - El **anio** (ej: 2026).
   - El **trimestre** (1, 2, 3 o 4).
   - Valida que la fecha corresponda al dia correcto del culto.

4. **Ingreso de datos**: El usuario completa los campos organizados en secciones:

   **Puntualidad**
   - Personas que llegaron antes de la hora
   - Personas que llegaron despues de la hora

   **Asistencia / Composicion**
   - Ninos
   - Jovenes
   - Total de asistentes

   **Procedencia**
   - Personas del Barrio
   - Personas de Guayabo

   **Visitas**
   - Cantidad de visitas del Barrio + nombres
   - Cantidad de visitas de Guayabo + nombres

   **Permanencia**
   - Se retiraron antes de terminar
   - Se quedaron todo el culto

   **Observaciones**
   - Texto libre opcional

5. **Guardar**: Se envia al backend, que valida los datos y los almacena.

---

## Roles del sistema

| Rol         | Permisos                                           |
|-------------|-----------------------------------------------------|
| ADMIN       | Todo: CRUD usuarios + CRUD asistencia + consultas   |
| SECRETARIO  | Registrar asistencia + consultar asistencia          |

---

## Arquitectura tecnica

- **Lenguaje**: PHP 8 puro (sin frameworks, sin Composer)
- **Base de datos**: MariaDB 10.4+ / MySQL 8+ (XAMPP)
- **Patron**: MVC adaptado a API REST
- **Carga de clases**: require_once manual
- **Respuestas**: JSON exclusivamente via JsonResponse
- **Seguridad**: Passwords con bcrypt, tokens SHA-256, prepared statements reales

### Flujo de una peticion

```
.htaccess -> index.php -> ErrorMiddleware -> CorsMiddleware
          -> Router -> (AuthMiddleware) -> Controller
          -> Validator -> Service -> DAO -> Mapper -> JsonResponse
```

### Estructura de carpetas

```
C_ASISTENCIA_BAKCEND/
├── index.php              Front controller
├── .htaccess              Rewrite a index.php
├── Config/                Conexion BD + constantes globales
├── Middleware/             CORS, errores, autenticacion
├── Utils/                 JsonResponse, Sanitizer, AuthContext
├── Modelo/
│   ├── Usuario/           DTO + Mapper + DAO
│   ├── Token/             DTO + Mapper + DAO
│   ├── Culto/             DTO + Mapper + DAO
│   └── Asistencia/        DTO + Mapper + DAO
├── Validator/             Validacion de entrada (sin BD)
├── Services/              Logica de negocio
├── Controller/            Orquestacion (try/catch)
└── Router/                Enrutamiento por metodo + URI
```

---

## Endpoints disponibles

### Autenticacion
| Metodo | Ruta           | Descripcion                    | Auth |
|--------|----------------|--------------------------------|------|
| POST   | /auth/login    | Iniciar sesion                 | No   |
| POST   | /auth/logout   | Cerrar sesion (revocar token)  | Si   |
| GET    | /auth/me       | Datos del usuario autenticado  | Si   |

### Cultos
| Metodo | Ruta    | Descripcion            | Auth |
|--------|---------|------------------------|------|
| GET    | /cultos | Listar los 3 cultos    | Si   |

### Asistencia
| Metodo | Ruta               | Descripcion                      | Auth |
|--------|--------------------|----------------------------------|------|
| GET    | /asistencias       | Listar (filtros: culto, anio, trimestre) | Si |
| GET    | /asistencias/{id}  | Obtener un registro              | Si   |
| POST   | /asistencias       | Crear registro                   | Si   |
| PUT    | /asistencias/{id}  | Actualizar registro              | Si   |
| DELETE | /asistencias/{id}  | Eliminar registro                | Si   |

### Usuarios (solo ADMIN)
| Metodo | Ruta             | Descripcion           | Auth  |
|--------|------------------|-----------------------|-------|
| GET    | /usuarios        | Listar usuarios       | ADMIN |
| GET    | /usuarios/{id}   | Obtener usuario       | ADMIN |
| POST   | /usuarios        | Crear usuario         | ADMIN |
| PUT    | /usuarios/{id}   | Actualizar usuario    | ADMIN |
| DELETE | /usuarios/{id}   | Desactivar usuario    | ADMIN |

---

## Reglas de negocio clave

1. **No duplicados**: No puede existir mas de un registro del mismo culto en la misma fecha.
2. **Fecha valida**: La fecha debe corresponder al dia de la semana del culto (sabado=sabado, etc.).
3. **Consistencia**: `total_asistentes >= ninos + jovenes`.
4. **Permanencia**: `retiros_antes_terminar + se_quedaron_todo <= total_asistentes`.
5. **Anio y trimestre**: Se calculan automaticamente a partir de la fecha (columnas GENERATED).
6. **Soft delete en usuarios**: Los usuarios se desactivan (activo=0), no se borran.
7. **Hard delete en asistencia**: Los registros de asistencia se eliminan fisicamente.

---

## Notas para futuro desarrollo

- El frontend se desarrollara por separado y consumira esta API.
- El sistema esta disenado para ser usado por personas con poca experiencia tecnologica,
  por lo que el frontend debe ser simple y responsivo.
- Si se necesitan mas modulos (reportes, miembros, diezmos, etc.), se deben agregar
  nuevas entidades siguiendo el mismo patron: DTO -> Mapper -> DAO -> Validator -> Service -> Controller -> Router.
