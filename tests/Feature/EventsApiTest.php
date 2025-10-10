<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test for the search endpoint.
     */
    public function test_search_endpoint_returns_correct_events_in_date_range(): void
    {
        // 1. Preparación (Arrange)
        // Crear eventos de prueba en la base de datos de testing
        Event::factory()->create([
            'status' => 'active',
            'starts_at' => '2025-11-10 10:00:00',
            'ends_at' => '2025-11-10 12:00:00',
            'title' => 'Evento Dentro de Rango',
        ]);

        Event::factory()->create([
            'status' => 'active',
            'starts_at' => '2025-12-25 10:00:00',
            'ends_at' => '2025-12-25 12:00:00',
            'title' => 'Evento Fuera de Rango',
        ]);

        Event::factory()->create([
            'status' => 'delisted',
            'starts_at' => '2025-11-15 10:00:00',
            'ends_at' => '2025-11-15 12:00:00',
            'title' => 'Evento Inactivo',
        ]);

        $starts_at = '2025-11-01T00:00:00Z';
        $ends_at = '2025-11-30T23:59:59Z';

        // 2. Actuación (Act)
        $response = $this->getJson("/api/search?starts_at={$starts_at}&ends_at={$ends_at}");

        // 3. Aserción (Assert)
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'start_date', 'start_time', 'end_date', 'end_time', 'min_price', 'max_price'],
            ],
        ]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['title' => 'Evento Dentro de Rango']);
        $response->assertJsonMissing(['title' => 'Evento Fuera de Rango']);
        $response->assertJsonMissing(['title' => 'Evento Inactivo']);
    }

    public function test_search_endpoint_returns_validation_error_for_missing_parameters(): void
    {
        // 1. Preparación (Arrange)
        $ends_at = '2025-11-30T23:59:59Z';

        // 2. Actuación (Act)
        // Hacemos una petición sin el parámetro `starts_at`
        $response = $this->getJson("/api/search?ends_at={$ends_at}");

        // 3. Aserción (Assert)
        // Verificamos que la respuesta sea un error de validación 422
        $response->assertStatus(422);
        // Verificamos que el JSON de error contenga la clave 'starts_at'
        $response->assertJsonValidationErrors(['starts_at']);
    }
}
