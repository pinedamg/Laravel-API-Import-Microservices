<?php

namespace Tests\Unit\Services\ProviderSync;

use App\Services\ProviderSync\EventDataTransformer;
use SimpleXMLElement;
use Tests\TestCase;

class EventDataTransformerTest extends TestCase
{
    public function test_it_transforms_raw_xml_nodes_to_structured_array(): void
    {
        // 1. Preparación (Arrange)
        $transformer = new EventDataTransformer;

        $basePlanXml = '<base_plan base_plan_id="291" sell_mode="online" title="Camela en concierto" />';
        $planXml = '
            <plan plan_start_date="2021-06-30T21:00:00" plan_end_date="2021-06-30T21:30:00" plan_id="291">
                <zone price="20.00" />
                <zone price="15.00" />
                <zone price="30.00" />
            </plan>';

        $basePlanNode = new SimpleXMLElement($basePlanXml);
        $planNode = new SimpleXMLElement($planXml);

        // 2. Actuación (Act)
        $transformedData = $transformer->transform($planNode, $basePlanNode);

        // 3. Aserción (Assert)
        $this->assertIsArray($transformedData);
        $this->assertEquals(291, $transformedData['plan_id']);
        $this->assertEquals('Camela en concierto', $transformedData['title']);
        $this->assertEquals(15.00, $transformedData['min_price']);
        $this->assertEquals(30.00, $transformedData['max_price']);
        $this->assertEquals('active', $transformedData['status']);
    }

    public function test_it_handles_nodes_with_no_zones(): void
    {
        // 1. Preparación (Arrange)
        $transformer = new EventDataTransformer;

        $basePlanXml = '<base_plan base_plan_id="123" sell_mode="online" title="Event without zones" />';
        $planXml = '<plan plan_id="456" plan_start_date="2025-01-01T10:00:00" plan_end_date="2025-01-01T11:00:00" />';

        $basePlanNode = new SimpleXMLElement($basePlanXml);
        $planNode = new SimpleXMLElement($planXml);

        // 2. Actuación (Act)
        $transformedData = $transformer->transform($planNode, $basePlanNode);

        // 3. Aserción (Assert)
        $this->assertNull($transformedData['min_price']);
        $this->assertNull($transformedData['max_price']);
    }
}
