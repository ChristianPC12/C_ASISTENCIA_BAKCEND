# Contexto Actual de la Base de Datos

**Base de datos**: `iglesia_asistencia`
**Motor**: MariaDB 10.4+ / MySQL 8+
**Charset**: utf8mb4, Collation: utf8mb4_unicode_ci
**Ultima actualizacion**: 02-03-2026

---

## Diagrama de relaciones

```
roles (1) ──────────< usuarios (1) ──────────< user_tokens
                         │
                         │ registrado_por
                         ▼
cultos (1) ─────────< asistencia_registro
```

---

## Tablas

### 1. roles
Almacena los roles del sistema. Son fijos y no se administran por API.

| Columna  | Tipo               | Descripcion              |
|----------|--------------------|--------------------------|
| id       | TINYINT(3) PK AI   | Identificador            |
| nombre   | VARCHAR(20) UNIQUE | Nombre del rol           |

**Datos fijos**:
- 1 = ADMIN
- 2 = SECRETARIO

**Indices**: PK(id), UNIQUE(nombre)

---

### 2. usuarios
Usuarios del sistema con autenticacion.

| Columna          | Tipo                | Descripcion                      |
|------------------|---------------------|----------------------------------|
| id               | BIGINT(20) PK AI    | Identificador                    |
| nombre_completo  | VARCHAR(120)        | Nombre completo del usuario      |
| usuario          | VARCHAR(50) UNIQUE  | Nombre de usuario (login)        |
| password_hash    | VARCHAR(255)        | Hash bcrypt del password         |
| password_actualizada_en | TIMESTAMP    | Ultimo cambio de password        |
| rol_id           | TINYINT(3) FK       | Referencia a roles.id            |
| activo           | TINYINT(1) DEFAULT 1| 1=activo, 0=desactivado          |
| creado_en        | TIMESTAMP           | Fecha de creacion                |
| actualizado_en   | TIMESTAMP           | Ultima actualizacion             |

**FK**: rol_id -> roles(id)
**Indices**: PK(id), UNIQUE(usuario), KEY(rol_id)

**Seed inicial**: usuario `admin`, password `admin123`, rol ADMIN

---

### 3. user_tokens
Tokens de autenticacion Bearer. Un usuario tiene maximo un token activo.

| Columna      | Tipo              | Descripcion                          |
|--------------|-------------------|--------------------------------------|
| id           | BIGINT(20) PK AI  | Identificador                        |
| usuario_id   | BIGINT(20) FK     | Referencia a usuarios.id             |
| token_hash   | VARCHAR(64) UNIQUE| SHA-256 del token (nunca se guarda plano) |
| ultimo_uso_en| TIMESTAMP         | Ultima actividad de la sesion        |
| expira_en    | TIMESTAMP         | Expiracion absoluta del token        |
| creado_en    | TIMESTAMP         | Fecha de creacion                    |

**FK**: usuario_id -> usuarios(id) ON DELETE CASCADE
**Indices**: PK(id), UNIQUE(token_hash), KEY(usuario_id)

**Nota**:
- Expiracion por inactividad: 15 minutos sin actividad (`ultimo_uso_en`).
- Expiracion absoluta: 8 horas desde login (`expira_en`).
- El password expira a 30 dias desde `password_actualizada_en`; al vencer, el usuario se desactiva.

---

### 4. cultos
Los tres cultos fijos de la iglesia. No se modifican por API.

| Columna      | Tipo               | Descripcion                                 |
|--------------|--------------------|---------------------------------------------|
| id           | TINYINT(3) PK AI   | Identificador                               |
| codigo       | VARCHAR(20) UNIQUE | Codigo (SABADO, DOMINGO, MIERCOLES)         |
| nombre       | VARCHAR(60)        | Nombre para mostrar                         |
| dia_semana   | TINYINT(1)         | Dia DAYOFWEEK MySQL: 1=Dom,...,7=Sab        |
| hora_inicio  | TIME               | Hora de inicio del culto                    |

**Datos fijos**:
- 1 = SABADO, dia 7, 09:00
- 2 = DOMINGO, dia 1, 18:30
- 3 = MIERCOLES, dia 4, 18:30

**Indices**: PK(id), UNIQUE(codigo)

---

### 5. asistencia_registro
Tabla principal. Cada fila es un registro de asistencia de un culto en una fecha especifica.

| Columna                  | Tipo            | Descripcion                                    |
|--------------------------|-----------------|------------------------------------------------|
| id                       | BIGINT(20) PK AI | Identificador                                |
| culto_id                 | TINYINT(3) FK   | Referencia a cultos.id                         |
| fecha                    | DATE            | Fecha del culto                                |
| anio                     | SMALLINT GENERATED | YEAR(fecha), calculado automaticamente       |
| trimestre                | TINYINT GENERATED  | QUARTER(fecha), calculado automaticamente    |
| llegaron_antes_hora      | INT UNSIGNED    | Personas que llegaron puntual                  |
| llegaron_despues_hora    | INT UNSIGNED    | Personas que llegaron tarde                    |
| ninos                    | INT UNSIGNED    | Cantidad de ninos                              |
| jovenes                  | INT UNSIGNED    | Cantidad de jovenes                            |
| total_asistentes         | INT UNSIGNED    | Total de personas presentes                    |
| proc_barrio              | INT UNSIGNED    | Asistentes procedentes del Barrio              |
| proc_guayabo             | INT UNSIGNED    | Asistentes procedentes de Guayabo              |
| visitas_barrio           | INT UNSIGNED    | Cantidad de visitas del Barrio                 |
| nombres_visitas_barrio   | TEXT NULL       | Nombres de las visitas del Barrio              |
| visitas_guayabo          | INT UNSIGNED    | Cantidad de visitas de Guayabo                 |
| nombres_visitas_guayabo  | TEXT NULL       | Nombres de las visitas de Guayabo              |
| retiros_antes_terminar   | INT UNSIGNED    | Personas que se fueron antes de terminar       |
| se_quedaron_todo         | INT UNSIGNED    | Personas que se quedaron todo el culto         |
| observaciones            | TEXT NULL       | Observaciones generales del registro           |
| registrado_por           | BIGINT(20) FK   | Usuario que registro (usuarios.id)             |
| creado_en                | TIMESTAMP       | Fecha de creacion                              |
| actualizado_en           | TIMESTAMP       | Ultima actualizacion                           |

**FK**:
- culto_id -> cultos(id)
- registrado_por -> usuarios(id)

**Indices**:
- PK(id)
- UNIQUE(culto_id, fecha) -- evita duplicados
- KEY(registrado_por)
- KEY(fecha)
- KEY(anio, trimestre, culto_id) -- para filtros rapidos

**CHECK constraints**:
- `total_asistentes >= ninos + jovenes`
- `retiros_antes_terminar + se_quedaron_todo <= total_asistentes`

---

## Vistas

Se crearon 3 vistas que filtran `asistencia_registro` por culto. Son de solo lectura
y sirven para consultas rapidas sin necesidad de filtrar manualmente.

| Vista                  | Filtro       |
|------------------------|--------------|
| asistencia_sabado      | culto_id = 1 |
| asistencia_domingo     | culto_id = 2 |
| asistencia_miercoles   | culto_id = 3 |

Las vistas tienen las mismas columnas que `asistencia_registro`.

---

## Notas para futuras ampliaciones

- Si se necesita un modulo de **miembros**, crear tabla `miembros` con datos personales
  y posiblemente una FK desde `asistencia_registro` o una tabla intermedia.
- Si se requieren **reportes trimestrales/anuales**, las columnas GENERATED `anio` y `trimestre`
  ya estan optimizadas con indice compuesto para consultas rapidas.
- Si se necesitan **diezmos/ofrendas**, agregar una tabla `ofrendas` con FK a `cultos` y `usuarios`.
- Si se quiere **historial de cambios**, considerar una tabla `auditoria` con el registro anterior
  antes de cada UPDATE/DELETE.
- Los nombres de visitas se guardan como texto libre. Si en el futuro se necesita un catalogo
  de visitantes frecuentes, se puede crear una tabla `visitantes` y una tabla puente
  `asistencia_visitantes`.
