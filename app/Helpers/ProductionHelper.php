<?php

namespace App\Helpers;

use App\Models\ProductionLog;

class ProductionHelper
{
    public static function logProductionActivity($product, $fromStage, $toStage, $remark = null)
    {
        return ProductionLog::create([
            'po_id'                 => $product->po_id,
            'production_product_id' => $product->id,
            'status'                => 1,
            'remark'                => $remark ?? 'Updated',
            'from_stage'            => $fromStage,
            'to_stage'              => $toStage,
            'action_by'             => auth()->id() ?? 0,
        ]);
    }
}
