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