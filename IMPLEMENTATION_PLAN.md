# Plan de Implementación: Microservicio de Eventos (Fever)

## Arquitectura y Stack Tecnológico

- **Arquitectura:** Sistema desacoplado con API pública y Sincronizador en background.
- **Framework:** Laravel
- **Base de Datos:** PostgreSQL
- **Colas (Queues) y Caché:** Redis
- **Entorno de Desarrollo:** Laravel Sail (Docker)
- **Monitoreo de Colas:** Laravel Horizon
- **Calidad de Código:** Laravel Pint
- **Estrategia de Alto Tráfico:** Diseño compatible con Laravel Octane y verificación con k6.

## Pasos de Desarrollo

### 1. Inicialización y Configuración del Proyecto
- [x] **Instalar Laravel:** Usar `composer` para instalar los archivos base.
- [x] **Instalar Sail:** Ejecutar `sail artisan sail:install` con PostgreSQL y Redis.
- [x] **Configurar `.env` (Puertos):** Ajustar los puertos (`APP_PORT`, `FORWARD_DB_PORT`, etc.) para evitar conflictos.
- [x] **Configurar `.env` (Servicios):** Añadir la URL del proveedor al archivo `.env`.
- [x] **Crear Archivo de Configuración:** Usar un archivo en `config/` para leer la variable `PROVIDER_API_URL`.
- [x] **Primer Arranque:** Ejecutar `sail up -d`.

### 2. Instalación de Paquetes Adicionales
- [x] Instalar y configurar Horizon y Pint.

### 3. Creación del Modelo y Migración
- [x] Crear el modelo `Event` y su migración.
- [x] Definir el esquema y los índices de la tabla `events`.
- [x] Ejecutar la migración.

### 4. Lógica de Sincronización (`ProviderSyncService`)
- [x] Crear la clase de servicio.
- [x] **Refactorizar:** Modificar el servicio para que obtenga la URL desde el archivo de configuración.
- [x] Implementar el método `syncEvents()` con la lógica de fetching, parseo y `upsert`.

### 5. Job en Background y Planificador
- [x] Crear el `SyncProviderEventsJob`.
- [x] Configurar el job para que llame al `ProviderSyncService`.
- [x] Configurar el `Kernel.php` para ejecutar el job periódicamente.

### 6. Manejo de Errores y Resiliencia del Sincronizador
- [x] Configurar Timeouts en el cliente HTTP.
- [x] Configurar Reintentos y `backoff` en el `SyncProviderEventsJob`.
- [x] Implementar la lógica de Circuit Breaker.

### 7. API Endpoint (`GET /search`) y Caché
- [x] Definir la ruta, crear el `EventController` y el `EventResource`.
- [x] Implementar la consulta con Eloquent.
- [x] **(Optimización)** Implementar una capa de caché con `Cache::remember()` y Redis.
- [x] **(Configuración)** Cambiar `CACHE_DRIVER` a `redis` en el `.env`.

### 8. Documentación
- [x] Crear un `Makefile`.
- [x] Actualizar el `README.md` principal.
- [x] Añadir referencias a la carpeta `/docs`.

### 9. Pruebas (Testing)
- [x] **Unit Test:** Crear un test unitario para `ProviderSyncService` (caso exitoso).
- [x] **(Casos de Fallo)** Añadir tests para casos de error (ej. la API del proveedor devuelve un 500, el XML está malformado).
- [x] **Feature Test:** Crear un test de integración para el endpoint `GET /api/search` (caso exitoso).
- [x] **(Casos de Fallo)** Añadir tests para casos de error (ej. los parámetros de fecha son inválidos, no se encuentran eventos).

### 10. Pruebas de Carga (Extra Mile)
- [x] Crear y ejecutar un script de k6 para validar el rendimiento bajo carga.

### 11. Estrategias Avanzadas de Escalabilidad (Extra Mile)
- [x] **(Escalabilidad Sincronizador)** Investigar/Implementar un parser de XML en modo streaming.
- [ ] **(Alto Tráfico API)** Investigar/Implementar una estrategia de HTTP Caching (ej. Varnish).
- [ ] **(Escalabilidad Base de Datos)** Investigar la configuración de réplicas de lectura.