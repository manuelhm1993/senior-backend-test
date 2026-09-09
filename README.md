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
- **Binding:** `client_queue` ← `installations.exchange` con *routing key* = `installation_id` (ULID) — así solo los mensajes dirigidos exactamente a esa instalación llegan a su cola.
- **Justificación completa de la topología:** ver [decisión #6](docs/decisiones.md).

*(Sección a completar conforme avance el Issue 3: cola/binding del lado Server, formato exacto del payload de activación, estrategia de idempotencia y mecanismo anti-loop de la sincronización bidireccional.)*

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

## Verificación end-to-end (estado actual)

Lo que se puede verificar hoy:

1. **Panel de RabbitMQ:** `http://localhost:15672` (credenciales abajo) → pestaña *Queues* → `client_queue` con binding a `installations.exchange` usando el ULID como routing key.
2. **Identidad persistente:** correr `client:pair-info` dos veces debe devolver el mismo ULID/secret, no generar uno nuevo.

*(Pendiente de documentar aquí en cuanto se complete el flujo de pairing/activación del lado Server: comandos exactos para asociar la instalación y confirmar la activación end-to-end.)*

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

## Qué se dejó fuera y por qué ⚠️

*Según Sección 11 del brief, un submission parcial y bien documentado es un resultado aceptable.*

- **Issue 3-4:** Servidor: Asociación y activación, RabbitMQ: Idempotencia y Sincronización
- **Por qué se dejó fuera:** La planificación y la documentación de rabbit ocupó un tiempo considerable
- **Qué se haría con más tiempo:** Terminar con la comunicación

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