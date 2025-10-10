<?php

namespace App\Services\ProviderSync;

use Generator;
use Illuminate\Support\Facades\Log;

class XmlEventParser
{
    /**
     * Parses the XML file and yields raw plan and base_plan nodes.
     */
    public function parse(string $filePath): Generator
    {
        $reader = new \XMLReader;
        if (! $reader->open($filePath)) {
            Log::error("Failed to open XML file for reading: {$filePath}");

            return;
        }

        Log::info("Starting XML stream processing from {$filePath}");

        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'base_plan') {
                if ($reader->getAttribute('sell_mode') !== 'online') {
                    $reader->next(); // Skip to the next sibling element

                    continue;
                }

                $dom = new \DOMDocument;
                $node = $reader->expand($dom);
                $basePlanNode = simplexml_import_dom($node);

                foreach ($basePlanNode->plan as $planNode) {
                    yield [
                        'plan_node' => $planNode,
                        'base_plan_node' => $basePlanNode,
                    ];
                }
            }
        }

        $reader->close();
        Log::info('Finished XML stream processing.');
    }
}
