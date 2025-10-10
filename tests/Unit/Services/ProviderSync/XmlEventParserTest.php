<?php

namespace Tests\Unit\Services\ProviderSync;

use App\Services\ProviderSync\XmlEventParser;
use Tests\TestCase;

class XmlEventParserTest extends TestCase
{
    public function test_it_correctly_parses_and_yields_raw_nodes(): void
    {
        // 1. Preparación (Arrange)
        $parser = new XmlEventParser;
        $xmlFilePath = base_path('tests/Fixtures/events.xml');

        // 2. Actuación (Act)
        $result = $parser->parse($xmlFilePath);
        $parsedNodes = iterator_to_array($result);

        // 3. Aserción (Assert)

        // It should only parse the 2 "online" events
        $this->assertCount(2, $parsedNodes);

        // Check the first node collection
        $firstNodeSet = $parsedNodes[0];
        $this->assertIsArray($firstNodeSet);
        $this->assertArrayHasKey('plan_node', $firstNodeSet);
        $this->assertArrayHasKey('base_plan_node', $firstNodeSet);
        $this->assertInstanceOf(\SimpleXMLElement::class, $firstNodeSet['plan_node']);
        $this->assertInstanceOf(\SimpleXMLElement::class, $firstNodeSet['base_plan_node']);

        // Verify content of the first parsed node
        $this->assertEquals('291', (string) $firstNodeSet['plan_node']['plan_id']);
        $this->assertEquals('291', (string) $firstNodeSet['base_plan_node']['base_plan_id']);
        $this->assertEquals('Camela en concierto', (string) $firstNodeSet['base_plan_node']['title']);
    }
}
