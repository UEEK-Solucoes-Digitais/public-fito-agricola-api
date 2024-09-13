<?php

namespace App\Imports;

use App\Models\Admin;
use App\Models\ContentTypeAccess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ImportMAUsers implements ToCollection
{

    public function collection(Collection $collection)
    {
        ini_set('memory_limit', '-1');

        checkSection(1);

        foreach ($collection as $key => $row) {
            if ($key === 0) continue;

            if ($row[0] && $row[1]) {
                $admin = Admin::where('email', trim($row[1]))->first();

                if ($admin && !ContentTypeAccess::where('admin_id', $admin->id)->where('type', 2)->first()) {
                    ContentTypeAccess::create([
                        'admin_id' => $admin->id,
                        'type' => 2,
                    ]);
                }
            }
        }
    }
}
