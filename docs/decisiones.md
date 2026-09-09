# Registro de Decisiones de Arquitectura (ADR)

---

### 1. Aislamiento de Esquemas de Base de Datos (`client_db` y `server_db`)
* **Decisión:** Alojar dos esquemas separados (`client_db` y `server_db`) dentro de un único motor MySQL (`mysql_shared` en puerto 33060).
* **Justificación:** 
  * **Independencia de dominio:** El brief exige que Cliente y Servidor no compartan memoria ni estado interno. Una sola base de datos acoplaría los esquemas y crearía colisiones en tablas por defecto de Laravel (como `users`, `jobs` y `cache`).
  * **Eficiencia de recursos:** Levantar dos contenedores de base de datos independientes aumentaría el consumo de memoria innecesariamente en desarrollo; dos esquemas en una misma instancia garantizan separación lógica estricta con impacto operativo mínimo.

---

### 2. Topología de Contenedores en `docker-compose.yml` (6 Servicios)
* **Decisión:** Desplegar 6 servicios diferenciados: `mysql_shared`, `rabbitmq`, `client_web`, `client_worker`, `server_web` y `server_worker`.
* **Justificación:**
  * **Separación de paradigmas (Síncrono vs. Asíncrono):** Los contenedores web ejecutan `artisan serve` para tráfico HTTP síncrono. RabbitMQ requiere procesos demonio en segundo plano (`artisan queue:work`) conectados de forma persistente al broker mediante sockets TCP.
  * **Aislamiento de ejecución:** Mantener los workers separados evita que una carga pesada en la cola degrade el servicio HTTP y garantiza que el reinicio de código en la aplicación no interrumpa la recepción de mensajes.

---

### 3. Gestión de Secretos e Interpolación de Entorno
* **Decisión:** Separar las variables del orquestador (`.env` raíz) de las variables internas de Laravel (`client-app/.env` y `server-app/.env`), excluyendo los `.env` reales del repositorio y versionando únicamente plantillas `.env.example`.
* **Justificación:**
  * **Prevención de fugas de seguridad:** Evita exponer credenciales maestras en el historial de Git.
  * **DNS interno de Docker:** Dentro de la red de contenedores, los hosts deben resolverse por el nombre del servicio (`mysql_shared`, `rabbitmq`), mientras que hacia el host anfitrión se exponen puertos diferenciados (33060, 8081, 8082) para evitar colisiones.

---

### 4. Entorno PHP, Extensiones y Compatibilidad de Dependencias
* **Decisión:** Compilar `bcmath` y `sockets` en la imagen base `mh_php:8.3-dev`, fijar `platform.php: 8.3.33` y resolver dependencias con `-W` (with-all-dependencies).
* **Justificación:**
  * **Protocolo AMQP:** El driver `php-amqplib` requiere `sockets` para la comunicación TCP de bajo nivel con RabbitMQ y `bcmath` para aritmética de 64 bits en los marcos de mensajería.
  * **Evitar desalineación de versiones:** Laravel 11/13 con Composer puede resolver librerías que exigen PHP 8.4+; forzar la plataforma a 8.3.33 y permitir degradación de dependencias bloquea el árbol en versiones estables y compatibles con el contenedor de ejecución.

---

### 5. Identidad Pública (ULID) vs. Credencial Secreta (Pairing Secret)
* **Decisión:** Distinguir conceptual y técnicamente entre un `installation_id` (ULID) y un `pairing_secret` (token aleatorio criptográfico).
* **Justificación:**
  * **El ULID no es un secreto:** Los ULID son ordenables en el tiempo; su componente temporal inicial es predecible y analizable, lo que permitiría a un atacante inferir identidades de otras instalaciones. Se utiliza exclusivamente como identificador público y clave de enrutamiento (*routing key*).
  * **Autenticación en el emparejamiento:** El `pairing_secret` actúa como prueba de posesión (*proof of possession*). Solo quien tiene acceso a la consola/interfaz local del Cliente conoce el secreto, evitando que un actor malicioso asocie una instalación ajena con solo adivinar su ULID.

---

### 6. Topología RabbitMQ: Exchange Direct + Cola Fija + Binding Dinámico por ULID
* **Decisión:** Usar un único `Exchange` de tipo `direct` (`installations.exchange`), con una cola de nombre **fijo** (`client_queue`) cuyo *binding* al exchange usa el `installation_id` (ULID) como *routing key*.
* **Justificación:**
  * **Terminología AMQP correcta:** un *channel* es la conexión lógica multiplexada sobre el socket TCP — no debe confundirse con *queue* (buzón de mensajes) ni con *routing key* (criterio de enrutamiento). El aislamiento por instalación se logra mediante el *binding*, no mediante el nombre de la cola.
  * **Por qué cola fija y no `client_queue_{ULID}`:** el alcance de este ejercicio contempla un solo Client corriendo en el entorno de demo (ver Sección 11 del brief, "Support for more than one Client instance" es una Optional Extension). Con un solo consumidor, el aislamiento real —que sí es requisito obligatorio— lo garantiza el *binding* con la *routing key* exacta del ULID, no el nombre físico de la cola. El diseño escala sin reescritura: para múltiples clientes bastaría con parametrizar el nombre de la cola por instalación.
  * **Exchange `direct` vs `fanout`/`topic`:** se descartó `fanout` porque transmitiría a todos los consumidores sin distinción (viola el requisito "y solo a ese Client"). Se descartó `topic` por ser innecesariamente flexible para un enrutamiento de clave exacta 1 a 1.
  * **Declaración explícita y separada (`client:setup-queue`):** la topología (exchange/cola/binding) se declara en un comando independiente del de identidad (`client:pair-info`), ejecutado una sola vez tras generar el ULID. Esto evita acoplar la generación de identidad con efectos secundarios de infraestructura de mensajería, y hace explícito el orden de dependencia: sin ULID no hay binding posible.
  * **Durabilidad:** tanto el exchange como la cola se declaran `durable: true` para sobrevivir a un reinicio del broker sin perder la topología — relevante porque en este entorno de desarrollo el contenedor de RabbitMQ puede reiniciarse con frecuencia.

---

### 7. Alcance recortado por límite de tiempo (4 horas)
* **Contexto:** el ejercicio fue diseñado como *time-boxed* a 4 horas. Se documenta aquí, honestamente, qué quedó dentro del **Core Requirement** y qué no se alcanzó a completar, siguiendo el criterio de la Sección 11 del brief.
* **Completado:**
  * Infraestructura Docker completa (6 servicios, healthchecks, dos esquemas de base de datos aislados).
  * Identidad del Client: generación/persistencia de ULID + `pairing_secret`, comando `client:pair-info`.
  * Topología RabbitMQ del lado Client: exchange, cola y binding por ULID declarados y verificados en el panel de administración.
* **No completado (y por qué):**
  * *(completar aquí según lo que realmente falte al momento de cerrar: Server-side workspace/facility/pairing, Job de activación, consumidor de activación en el Client con idempotencia, sync bidireccional de settings, anti-loop, tests automatizados, GitHub Actions)*
  * Causa principal: la mayor parte del tiempo se invirtió en resolver incompatibilidades de plataforma (PHP 8.5 del contenedor efímero de Composer vs. PHP 8.3 objetivo, extensiones `sockets`/`bcmath` faltantes, Symfony 8 vs. 7 en el `composer.lock` inicial) antes de poder instalar la librería de colas. Este tipo de fricción de entorno es exactamente el tipo de decisión de "qué priorizar bajo deadline real" que el ejercicio busca evaluar.