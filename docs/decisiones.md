# Decisiones en tiempo de desarrollo

---

1. Crear dos bases de datos independientes ya que laravel crea por defecto la tabla users,
esto evita colisiones y encapsula la lógica interna de ambas aplicaciones para que ninguna conozca
el funcionamiento interno de la otra
2. Creación de 6 servicios en el docker-compose:
    2.1. mysql_shared: gestiona la comunicación con db
    2.2. rabbitmq: gestiona la comunicación con rabbitmq
    2.3. client_web: sirve el CLI/página del cliente
    2.4. client_worker: escucha RabbitMQ del lado Client
    2.5. server_web: sirve el CLI/página del Server
    2.6. server_worker: escucha RabbitMQ del lado Server
3. Variables de entorno .env en la raíz para alimentar al docker-compose que se inyectarán en interpolación
4. Se requiere sockets para rabbit, se compiló en la imagen php
    4.1. docker build -t mh_php:8.3-dev - <<EOF
        FROM mh_php:8.3-dev
        RUN docker-php-ext-install bcmath sockets
        EOF
    4.2. Anclar la plataforma a PHP 8.3.33 en ambos contenedores
        docker run --rm -u $(id -u):$(id -g) -v $(pwd)/client-app:/app -w /app composer:2.9.4 composer config platform.php 8.3.33
        docker run --rm -u $(id -u):$(id -g) -v $(pwd)/server-app:/app -w /app composer:2.9.4 composer config platform.php 8.3.33

    4.3. Requerir la librería ignorando los requisitos de sockets y bcmath del contenedor efímero
        docker run --rm -u $(id -u):$(id -g) -v $(pwd)/client-app:/app -w /app composer:2.9.4 composer require vladimir-yuldashev/laravel-queue-rabbitmq -W --ignore-platform-req=ext-sockets --ignore-platform-req=ext-bcmath
        docker run --rm -u $(id -u):$(id -g) -v $(pwd)/server-app:/app -w /app composer:2.9.4 composer require vladimir-yuldashev/laravel-queue-rabbitmq -W --ignore-platform-req=ext-sockets --ignore-platform-req=ext-bcmath
