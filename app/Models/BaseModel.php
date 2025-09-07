<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    protected static function booted()
    {
        static::updating(function ($model) {
            $dirty = $model->getDirty();

            $ignore = ['updated_at'];

            foreach ($dirty as $column => $newValue) {
                if (in_array($column, $ignore)) {
                    continue;
                }

                $oldValue = $model->getOriginal($column);

                if ($oldValue == $newValue) {
                    continue;
                }

                DB::table('audits')->insert([
                    'model_type'  => get_class($model),
                    'table_name'  => $model->getTable(),
                    'model_id'    => $model->getKey(),
                    'column_name' => $column,
                    'old_value'   => $oldValue,
                    'new_value'   => $newValue,
                    'edited_by'   => auth()->id(),
                    'medium'      => null,
                    'edited_at'   => now(),
                ]);
            }
        });
    }
}
