<?php

namespace Tests\Unit\Services;

use App\Repositories\EventRepository;
use App\Services\ProviderSync\EventDataTransformer;
use App\Services\ProviderSync\ProviderApiClient;
use App\Services\ProviderSync\XmlEventParser;
use App\Services\ProviderSyncService;
use Generator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ProviderSyncServiceTest extends TestCase
{
    private MockObject|ProviderApiClient $apiClientMock;

    private MockObject|XmlEventParser $parserMock;

    private MockObject|EventDataTransformer $transformerMock;

    private MockObject|EventRepository $repositoryMock;

    private ProviderSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClientMock = $this->createMock(ProviderApiClient::class);
        $this->parserMock = $this->createMock(XmlEventParser::class);
        $this->transformerMock = $this->createMock(EventDataTransformer::class);
        $this->repositoryMock = $this->createMock(EventRepository::class);

        $this->service = new ProviderSyncService(
            $this->apiClientMock,
            $this->parserMock,
            $this->transformerMock,
            $this->repositoryMock
        );
    }

    public function test_sync_events_orchestrates_correctly_on_success(): void
    {
        // 1. Preparación (Arrange)
        // Simulate raw nodes from parser, including a plan_id duplicate under different base_plan_id
        $rawNodes = [
            ['plan_node' => new \SimpleXMLElement('<plan plan_id="291"/>'), 'base_plan_node' => new \SimpleXMLElement('<base_plan base_plan_id="291" title="Camela"/>')],
            ['plan_node' => new \SimpleXMLElement('<plan plan_id="1642"/>'), 'base_plan_node' => new \SimpleXMLElement('<base_plan base_plan_id="322" title="Pantomima"/>')],
            ['plan_node' => new \SimpleXMLElement('<plan plan_id="1643"/>'), 'base_plan_node' => new \SimpleXMLElement('<base_plan base_plan_id="322" title="Pantomima"/>')],
            ['plan_node' => new \SimpleXMLElement('<plan plan_id="1642"/>'), 'base_plan_node' => new \SimpleXMLElement('<base_plan base_plan_id="1591" title="Morancos"/>')],
        ];

        // Simulate transformed data from transformer
        $transformedData = [
            ['plan_id' => 291, 'base_plan_id' => 291, 'title' => 'Camela'],
            ['plan_id' => 1642, 'base_plan_id' => 322, 'title' => 'Pantomima'],
            ['plan_id' => 1643, 'base_plan_id' => 322, 'title' => 'Pantomima'],
            ['plan_id' => 1642, 'base_plan_id' => 1591, 'title' => 'Morancos'],
        ];

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('successful')->willReturn(true);

        $this->apiClientMock->expects($this->once())
            ->method('fetchEventsTo')
            ->willReturn($responseMock);

        // Mock the parser to return a generator of raw nodes
        $this->parserMock->expects($this->once())
            ->method('parse')
            ->willReturn((function () use ($rawNodes) {
                foreach ($rawNodes as $node) {
                    yield $node;
                }
            })());

        // Mock the transformer to return the final data structure for each raw node
        $this->transformerMock->expects($this->exactly(count($rawNodes)))
            ->method('transform')
            ->willReturnOnConsecutiveCalls(...$transformedData);

        // Expect the repository to be called with ALL transformed data (no de-duplication by service)
        $this->repositoryMock->expects($this->once())
            ->method('upsertBatch')
            ->with($transformedData);

        // Expect delist to be called with unique plan_ids
        $this->repositoryMock->expects($this->once())
            ->method('delistEventsNotIn')
            ->with([291, 1642, 1643]); // Unique plan_ids from the transformed data

        // 2. Actuación (Act)
        $this->service->syncEvents();

        // 3. Aserción (Assert)
        // Assertions are handled by the mock expectations.
    }

    public function test_sync_events_handles_provider_failure_gracefully(): void
    {
        // 1. Preparación (Arrange)
        $responseMock = $this->createMock(Response::class);
        $responseMock->method('successful')->willReturn(false);

        $realGuzzleResponse = new \GuzzleHttp\Psr7\Response(500);
        $realResponseForException = new Response($realGuzzleResponse);
        $exception = new RequestException($realResponseForException);

        $responseMock->method('throw')->will($this->throwException($exception));

        $this->apiClientMock->expects($this->once())
            ->method('fetchEventsTo')
            ->willReturn($responseMock);

        // Ensure we never try to parse, transform or save data if the API fails
        $this->parserMock->expects($this->never())->method('parse');
        $this->transformerMock->expects($this->never())->method('transform');
        $this->repositoryMock->expects($this->never())->method('upsertBatch');

        // 2. Actuación (Act) y Aserción (Assert)
        $this->expectException(RequestException::class);

        $this->service->syncEvents();
    }
}
