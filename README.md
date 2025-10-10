# Fever Code Challenge Solution

This repository contains the solution for the Fever Code Challenge, developed as a microservice to integrate external provider plans into the Fever marketplace. This document details the architecture, setup, and usage of the solution.

## Table of Contents
- [Fever Code Challenge Solution](#fever-code-challenge-solution)
  - [Table of Contents](#table-of-contents)
  - [Introduction](#introduction)
  - [Setup](#setup)
  - [Architecture](#architecture)
    - [Code Architecture](#code-architecture)
    - [Infrastructure Architecture](#infrastructure-architecture)
    - [Database Schema](#database-schema)
    - [Job Synchronization and Resilience](#job-synchronization-and-resilience)
  - [Usage](#usage)
  - [Testing](#testing)
  - [Load and Stress Testing](#load-and-stress-testing)
  - [Future Improvements](#future-improvements)

## Introduction
This project implements a Laravel-based microservice designed to efficiently synchronize event data from an external XML provider and expose it via a performant API. The solution emphasizes clean architecture, scalability, and maintainability, addressing various real-world challenges such as high traffic, data synchronization, and robust error handling.

## Setup
To get the project up and running on your local machine, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd <project-directory>
    ```
2.  **Create and review the environment file:**
    ```bash
    cp .env.example .env
    # Open .env and review/update database credentials, APP_PORT, etc.
    ```
3.  **Run the automated setup command:**
    This command will build Docker images, install dependencies, generate the application key, install Octane/Horizon, and run database migrations.
    ```bash
    make setup
    ```
    After this, the application should be fully operational.

## Architecture

### Code Architecture
The application's core synchronization logic adheres strictly to SOLID principles, particularly the Single Responsibility Principle. The `ProviderSyncService` acts as an orchestrator, delegating specific tasks to specialized components.

```mermaid
graph TD
    A[SyncProviderEventsJob] --> B(ProviderSyncService)
    B --> C(ProviderApiClient)
    B --> D(XmlEventParser)
    B --> E(EventDataTransformer)
    B --> F(EventRepository)
    C -- Fetches XML --> G(External Provider API)
    D -- Parses XML --> B
    E -- Transforms Data --> B
    F -- Persists Data --> H(Database)
```

### Infrastructure Architecture
The solution leverages Docker Compose to orchestrate multiple services, providing a robust and scalable local development environment.

```mermaid
graph LR
    User --> Web(Web Browser / API Client)
    Web --> Nginx(Nginx / Load Balancer)
    Nginx --> Octane(Laravel Octane - laravel.test)
    Octane --> PgSQL(PostgreSQL)
    Octane --> Redis(Redis)

    subgraph Docker Compose Services
        Octane -- Dispatches Jobs --> Redis
        Scheduler(Scheduler) -- Dispatches Jobs --> Redis
        Horizon(Horizon) -- Processes Jobs --> Redis
        Horizon --> PgSQL
    end

    Scheduler -- Runs every minute --> Octane
    Octane -- Queries --> PgSQL
    Octane -- Caches --> Redis
    Horizon -- Monitors --> Redis
    Horizon -- Stores Failed Jobs --> PgSQL
```

### Database Schema
The primary data store is a PostgreSQL database. The `events` table is designed to store the synchronized plan data. Uniqueness is enforced on a composite key to correctly model the provider's data structure.

```mermaid
erDiagram
    events {
        id INT PK
        base_plan_id INT "Part of Composite Unique Key"
        plan_id INT "Part of Composite Unique Key"
        title VARCHAR
        sell_mode VARCHAR
        starts_at TIMESTAMP
        ends_at TIMESTAMP
        min_price DECIMAL
        max_price DECIMAL
        status VARCHAR
        created_at TIMESTAMP
        updated_at TIMESTAMP
    }
```

### Job Synchronization and Resilience
The `SyncProviderEventsJob` is crucial for maintaining up-to-date event data. This diagram illustrates its interaction with the `ProviderSyncService`, including error handling, retries with backoff, and circuit breaker patterns to ensure resilience and efficient handling of external API interactions.

```mermaid
sequenceDiagram
    autonumber
    participant Scheduler
    participant SyncProviderEventsJob
    participant ProviderSyncService
    participant ProviderApiClient
    participant ExternalProviderAPI
    participant RedisQueue
    participant Database

    Scheduler->>SyncProviderEventsJob: Dispatches job periodically
    SyncProviderEventsJob->>ProviderSyncService: Calls syncEvents()
    ProviderSyncService->>ProviderApiClient: Fetches XML data (with timeout)

    alt API Call Fails (e.g., Timeout, 5xx)
        ProviderApiClient--xSyncProviderEventsJob: Error response
        SyncProviderEventsJob->>RedisQueue: Re-queue job with backoff (retry mechanism)
        Note over SyncProviderEventsJob,RedisQueue: Configured retries and backoff strategy
    else API Call Succeeds
        ProviderApiClient->>ProviderSyncService: XML data
        Note over ProviderSyncService: Handles large XML via streaming
        ProviderSyncService->>Database: Upserts event data
    end

    alt Circuit Breaker Tripped
        ProviderSyncService--xSyncProviderEventsJob: Circuit Breaker open
        Note over SyncProviderEventsJob: Job fails fast, no API call
    end

    SyncProviderEventsJob->>RedisQueue: Marks job as complete/failed
```

## Usage
Once the setup is complete and containers are running (`make run`), the application is fully automated.

-   **Access the Web Application:** `http://localhost:${APP_PORT}` (e.g., `http://localhost:8088`)
-   **Access Horizon Dashboard:** `http://localhost/horizon`
-   **Access Swagger UI:** `http://localhost:${APP_PORT}/api/documentation` (e.g., `http://localhost:8088/api/documentation`)
-   **Automatic Synchronization:** The `SyncProviderEventsJob` runs automatically every hour (or as configured in `.env` via `EVENT_SYNC_SCHEDULE`). You can monitor its execution and status in the Horizon dashboard.
-   **Compare APIs:**
    ```bash
    make compare-apis
    ```
    This command compares the current API specification with a previous version.
-   **Destroy Application:**
    ```bash
    make destroy
    ```
    This command stops and removes all Docker containers, networks, and volumes associated with the application, effectively cleaning up the environment.

## Testing
The project includes a comprehensive testing suite.

-   **Run Unit & Feature Tests:**
    ```bash
    make test
    ```
-   **Run Code Style Check:**
    ```bash
    make lint
    ```
## Load and Stress Testing
The project includes various load and stress testing profiles using k6 to evaluate the microservice's performance under various traffic conditions.

-   Light load:
    ```bash
    make test-load-light
    ```
-   Medium load (default):
    ```bash
    make test-load
    ```
    or
    ```bash
    make test-load-medium
    ```
-   Heavy load:
    ```bash
    make test-load-heavy
    ```
-   Extreme load (RPS-based):
    ```bash
    make test-load-extreme
    ```

## Future Improvements
-   Implement HTTP caching (e.g., Varnish) for the API.
-   Investigate database read replicas for further scalability.
-   Enhance error handling and notifications for job failures.
-   Implement a more granular delisting strategy (currently assumes `plan_id` is sufficient).