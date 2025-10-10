<?php

namespace App\Services\ProviderSync;

use SimpleXMLElement;

class EventDataTransformer
{
    /**
     * Transforms a raw SimpleXMLElement node into a structured array for the database.
     */
    public function transform(SimpleXMLElement $planNode, SimpleXMLElement $basePlanNode): array
    {
        $minPrice = null;
        $maxPrice = null;

        if (count($planNode->zone) > 0) {
            $prices = [];
            foreach ($planNode->zone as $zone) {
                $prices[] = (float) $zone['price'];
            }
            $minPrice = min($prices);
            $maxPrice = max($prices);
        }

        return [
            'base_plan_id' => (int) $basePlanNode['base_plan_id'],
            'plan_id' => (int) $planNode['plan_id'],
            'title' => (string) $basePlanNode['title'],
            'sell_mode' => (string) $basePlanNode['sell_mode'],
            'starts_at' => (string) $planNode['plan_start_date'],
            'ends_at' => (string) $planNode['plan_end_date'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
