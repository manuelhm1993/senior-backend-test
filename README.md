# MH Prueba Técnica — Cliente / Servidor con RabbitMQ

Sistema distribuido de dos aplicaciones Laravel independientes (**Client** y **Server**) que se comunican exclusivamente vía RabbitMQ, simulando el emparejamiento y activación de una instalación remota ("Input Manager") desde un backoffice central.

📦 Prueba técnica — Septiembre 2026 · ⛓️ [Repositorio](https://github.com/manuelhm1993/senior-backend-test)

---

## Stack tecnológico 💻

![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white) ![RabbitMQ](https://img.shields.io/badge/rabbitmq-%23FF6600.svg?style=for-the-badge&logo=rabbitmq&logoColor=white) ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/mysql-%234479A1.svg?style=for-the-badge&logo=mysql&logoColor=white) ![Docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white)

| Componente | Versión |
|---|---|
| Laravel | 13.x |
| PHP | 8.3.33 |
| MySQL | 8.4.3 |
| RabbitMQ | 3-management |
| Driver de colas | `vladimir-yuldashev/laravel-queue-rabbitmq` v15.0.2 |

No hay frontend con build step (sin Vite/npm) — toda interacción es vía comandos Artisan (CLI), según lo permitido explícitamente por el brief ("No graphical interface is required").

---

## Arquitectura

Dos aplicaciones Laravel completamente independientes, sin conocimiento mutuo de código interno:

- **`client-app/`** — simula el "Input Manager" instalado en campo. Genera su propia identidad (ULID + secreto de emparejamiento) al primer uso, y escucha RabbitMQ esperando su mensaje de activación.
- **`server-app/`** — simula el backoffice cloud. Gestiona workspaces/facilities y, al asociar una instalación, dispara la activación por mensaje asíncrono.

Cada aplicación tiene su propio esquema de base de datos (`client_db` / `server_db`) dentro de la misma instancia MySQL — ver [decisión #1](docs/decisiones.md).

```
┌─────────────┐      RabbitMQ (AMQP)      ┌─────────────┐
│  client-app │ ◄───────────────────────► │  server-app │
│  (8081)     │   installations.exchange   │  (8082)     │
└─────────────┘                            └─────────────┘
       │                                          │
       └──────────────┬───────────────────────────┘
                mysql_shared (client_db / server_db)
```

---

## Flujo RabbitMQ

- **Exchange:** `installations.exchange`, tipo `direct`, durable.
- **Cola del Client:** `client_queue`, durable.
- **Binding:** `client_queue` ← `installations.exchange` con *routing key* = `installation_id` (ULID público) para garantizar aislamiento estricto por instalación.
- **Fundamentación arquitectónica:** Ver [decisión #7 (Topología Híbrida)](docs/decisiones.md) y [decisión #8 (Desacoplamiento de Carga JSON vs Jobs de Laravel)](docs/decisiones.md).

---

## Setup local ⚙️

**Requisitos:** Docker + WSL2/Ubuntu — no requiere PHP, Composer ni MySQL instalados en el host.

```bash
git clone https://github.com/manuelhm1993/senior-backend-test.git
cd senior-backend-test
cp .env.example .env
```

Levantar toda la infraestructura con un solo comando:

```bash
docker compose up -d
```

Verificar que todos los servicios estén sanos:

```bash
docker compose ps
```
`mysql_shared` y `rabbitmq` deben mostrar `(healthy)`; los cuatro servicios de aplicación, `Up`.

Generar la identidad del Client y declarar su cola en RabbitMQ:

```bash
docker compose exec client_web php artisan client:pair-info
docker compose exec client_web php artisan client:setup-queue
```

---

## Verificación end-to-end

Pasos verificables del alcance completado:

1. **Topología en RabbitMQ:** Entrar a `http://localhost:15672` (credenciales abajo) → pestaña *Queues* → verificar existencia de `client_queue` y su enlace en *Bindings* a `installations.exchange` mediante el ULID asignado.
2. **Persistencia e idempotencia de identidad local:**
   ```bash
   docker compose exec client_web php artisan client:pair-info
   ```
   Al ejecutar el comando de forma repetida, el sistema garantiza la persistencia devolviendo exactamente el mismo ULID y secreto, sin duplicar registros en client_db.installations.

---

## Variables de entorno 🔑

**Raíz (`.env`, alimenta a `docker-compose.yml`, nunca se sube al repo):**
```env
DB_ROOT_PASSWORD=devsecret
RABBITMQ_USER=devuser
RABBITMQ_PASSWORD=devpass
```

**`client-app/.env` / `server-app/.env` (config interna de cada Laravel):**
```env
DB_CONNECTION=mysql
DB_HOST=mysql_shared
DB_PORT=3306
DB_DATABASE=client_db   # server_db en server-app

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=devuser
RABBITMQ_PASSWORD=devpass
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=client_queue
```

Plantilla completa sin valores reales en `.env.example`.

---

## Credenciales de acceso 🔐

```
Panel de administración RabbitMQ (http://localhost:15672)
usuario: devuser
password: devpass
```

No hay sistema de login en `client-app`/`server-app` en el estado actual del ejercicio — toda la interacción es vía comandos Artisan.

---

## Público-identificador vs. secreto de emparejamiento 🔒

Decisión de seguridad explícita, requerida por el brief: ver [decisión #5 en `docs/decisiones.md`](docs/decisiones.md#5-identidad-pública-ulid-vs-credencial-secreta-pairing-secret).

Resumen: el ULID es un identificador **público y predecible** (ordenable temporalmente) — se usa como *routing key* de enrutamiento, nunca como credencial. La asociación de una instalación requiere, además del ULID, un `pairing_secret` aleatorio de 40 caracteres generado por el Client, que actúa como prueba de posesión.

---

## Alcance del Timebox y Próximos Pasos ⚠️

*Siguiendo la Sección 11 del brief, se priorizó un diseño distribuido sólido, desacoplado y exhaustivamente documentado dentro del límite de 4 horas.*

* **Foco completado:** Arquitectura de infraestructura (6 contenedores), aislamiento de esquemas de BD, compilación de extensiones de bajo nivel (`sockets`/`bcmath`), protocolo de autenticación asimétrica (ULID vs Secret) y topología de RabbitMQ.
* **Componentes diferidos:** Cierre del consumidor desacoplado en el Client e ingesta de mensajes con tabla de idempotencia (Issues 3 y 4).
* **Por qué se priorizó así:** Enfrentar el acoplamiento predeterminado de `queue:work` de Laravel (que asume Jobs serializados del framework en lugar de JSON agnóstico) evidenció que un parche rápido rompería el principio de responsabilidad única. Se prefirió documentar la arquitectura correcta (ADR #8 y #9) antes que dejar un flujo frágil en ejecución.
* **Plan de implementación con tiempo adicional:**
  1. Completar el comando `client:consume-activation` utilizando `php-amqplib` para parseo directo de JSON crudo.
  2. Migrar la tabla `processed_messages` en `client_db` para control estricto de deduplicación antes de mutar a `status = active`.
  3. Tests de integración que simulen el ciclo completo publicando payloads en `installations.exchange`.

---

## Estructura del proyecto 📁

```
senior-backend-test/
├── client-app/                   ← Laravel independiente, Input Manager
│   ├── app/Console/Commands/
│   │   ├── ShowPairingInfoCommand.php   (client:pair-info)
│   │   └── SetupClientQueueCommand.php  (client:setup-queue)
│   ├── app/Models/Installation.php
│   ├── database/migrations/
│   └── .env
├── server-app/                   ← Laravel independiente, backoffice
│   └── .env
├── docs/
│   └── decisiones.md             ← ADR completo con justificaciones
├── mysql-init/
│   └── init.sql                  ← crea client_db y server_db
├── .env                          ← variables de orquestación (no versionado)
├── .env.example
└── docker-compose.yml
```

---

## Flujo de trabajo 🔄

Desarrollo en sprint único de 4 horas, issues con checklist en GitHub Projects, ramas por feature, commits semánticos en español.

### Convención de ramas
| Prefijo | Uso |
|---|---|
| `feature/` | Nueva funcionalidad |
| `fix/` | Corrección de bug |
| `chore/` | Mantenimiento, configuración, documentación |

### Commits semánticos
`feat:` `fix:` `refactor:` `chore:` `docs:`

---

Desarrollado por [Manuel Henriquez](https://mhenriquez.com) · Maracaibo, Venezuela