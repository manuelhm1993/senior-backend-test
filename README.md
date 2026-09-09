# MH Prueba Técnica — Cliente - Servidor con RabbitMQ

Aplicación cliente servidor que utiliza [RabbitMQ](https://www.rabbitmq.com/), para comunicar dos aplicaciones laravel.

🌐 [Demo en vivo](-) · 📦 Prueba técnica — Septiembre 2026 · 📝 [GitHub Project](https://github.com/users/manuelhm1993/projects/14/views/1) · ⛓️ [GitHub Repository](https://github.com/manuelhm1993/senior-backend-test)

---

### Stack tecnológico 💻

![Laravel](https://img.shields.io/badge/laravel-%23FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white) ![RabbitMQ](https://img.shields.io/badge/rabbitmq-%23FF6600.svg?style=for-the-badge&logo=rabbitmq&logoColor=white) ![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white) ![MySQL](https://img.shields.io/badge/mysql-%234479A1.svg?style=for-the-badge&logo=mysql&logoColor=white) ![Docker](https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white) ![Git](https://img.shields.io/badge/git-%23F03C2E.svg?style=for-the-badge&logo=git&logoColor=white) ![GitHub](https://img.shields.io/badge/github-%23181717.svg?style=for-the-badge&logo=github&logoColor=white)

<details>
<summary>Ranking por uso 📈</summary>

| Ranking | Tecnología |
|--------:|------------|
| 1 | **Laravel 13** |
| 2 | **PHP 8.3** |
| 3 | **Mysql 8.4.3** |
| 4 | RabbitMQ |
| 5 | Docker |
| 6 | Git / GitHub |

</details>

---

## Funcionalidades ✨

- **Funcionalidad:** descripción.

---

## Setup local ⚙️

**Requisitos:** Docker + WSL2/Ubuntu (proyecto vive en el filesystem nativo de Linux, nunca en `/mnt/c/`) — no requiere Node ni Composer instalados en el host.

```bash
git clone https://github.com/manuelhm1993/senior-backend-test.git
cd senior-backend-test
```

Instalar dependencias vía contenedor efímero:
```bash
docker run --rm -it -u $(id -u):$(id -g) -v $(pwd):/app -w /app composer:2.9.4 composer install
docker run --rm -it -u $(id -u):$(id -g) -v $(pwd):/app -w /app node:22.22.0-slim npm install
```

Levantar servidor de desarrollo:
```bash
docker compose up -d
```

App disponible en `http://localhost:8000`.

> Si prefieres Composer/Node instalados directo en el host: `composer install`, `npm install` y `php artisan serve` funcionan igual, sin Docker.

---

## Variables de entorno 🔑

```env
DB_HOST=mysql
DB_USER=root
DB_PASSWORD=password
```

---

## Credenciales de prueba 🔐

```
usuario:  admin@technical-assessment.test
password: password
```

---

## Estructura del proyecto 📁

```
senior-backend-test/
├── client-app/
├── docs/
├── server-app/
└── docker-compose.yml
```

---

## Decisiones técnicas 🧠

Registro completo, issue por issue, en [`docs/decisiones.md`](docs/decisiones.md). Resumen de las decisiones de mayor peso:

- **Feature:** description.

---

## Deploy 🚀

Desplegado en **Hosting**, conectado directo al repositorio de GitHub — build y deploy automático en cada push a `master`.

Build de producción:
```bash
docker run --rm -it -u $(id -u):$(id -g) -v $(pwd):/app -w /app node:22.22.0-slim npm run build
```

> Notas importantes.

---

## Flujo de trabajo 🔄

Desarrollo en un único sprint, issues con checklist en [GitHub Projects](https://github.com/manuelhm1993/senior-backend-test/issues), ramas por feature, PRs con merge a `master`.

### Convención de ramas

| Prefijo | Uso |
|---------|-----|
| `feature/` | Nueva funcionalidad |
| `fix/` | Corrección de bug |
| `chore/` | Mantenimiento, configs, documentación |

### Commits semánticos (español)

`feat:` `fix:` `refactor:` `chore:` `docs:` `style:`

---

## Colección Postman 📬

Endpoints documentados en [`-`](-) — importable directo en Postman/VS Code.

---

Desarrollado por [Manuel Henriquez](https://mhenriquez.com) · Maracaibo, Venezuela