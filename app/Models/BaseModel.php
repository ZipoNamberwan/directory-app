<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    protected $deletionSource = null;

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

        // Log when model is soft deleted
        static::deleting(function ($model) {
            DB::table('audits')->insert([
                'model_type'  => get_class($model),
                'table_name'  => $model->getTable(),
                'model_id'    => $model->getKey(),
                'column_name' => 'deleted_at',
                'old_value'   => null,
                'new_value'   => now(),
                'edited_by'   => auth()->id(),
                'medium'      => $model->deletionSource ?? 'mobile',
                'edited_at'   => now(),
            ]);
        });

        // Log when model is restored from soft delete
        static::restoring(function ($model) {
            DB::table('audits')->insert([
                'model_type'  => get_class($model),
                'table_name'  => $model->getTable(),
                'model_id'    => $model->getKey(),
                'column_name' => 'deleted_at',
                'old_value'   => $model->deleted_at,
                'new_value'   => null,
                'edited_by'   => auth()->id(),
                'medium'      => 'restoration',
                'edited_at'   => now(),
            ]);
        });
    }

    public function deleteWithSource(string $source)
    {
        $this->deletionSource = $source;
        return $this->delete();
    }
}
