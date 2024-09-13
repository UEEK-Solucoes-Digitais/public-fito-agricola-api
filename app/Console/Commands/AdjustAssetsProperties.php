<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetProperty;
use Illuminate\Console\Command;

class AdjustAssetsProperties extends Command
{
    protected $signature = 'app:adjust-assets-properties';

    protected $description = 'Responsável por vincular os bens às propriedades na nova tabela';

    public function handle()
    {
        checkSection(1);
        list($assets, $total) = Asset::readAssets(1, null, null, null);

        foreach ($assets as $asset) {
            echo "Vinculando o bem {$asset->name} à propriedade {$asset->property->name}\n";
            AssetProperty::firstOrCreate([
                'asset_id' => $asset->id,
                'property_id' => $asset->property_id,
            ]);
        }
    }
}
