<?php

namespace App\Models;

use App\Helpers\DatabaseSelector;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasUuids, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'firstname',
        'email',
        'password',
        'regency_id',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            if ($user->getConnectionName() === env('MAIN_DB_CONNECTION', 'mysql_main')) {
                $user->syncToOtherDatabases();
            }
        });

        static::updated(function ($user) {
            if ($user->getConnectionName() === env('MAIN_DB_CONNECTION', 'mysql_main')) {
                $user->syncToOtherDatabases();
            }
        });

        static::deleted(function ($user) {
            if ($user->getConnectionName() === env('MAIN_DB_CONNECTION', 'mysql_main')) {
                $user->deleteFromOtherDatabases();
            }
        });
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_id');
    }

    public function slsBusiness()
    {
        return $this->hasMany(SlsBusiness::class, 'pcl_id');
    }

    public function nonSlsBusiness()
    {
        return $this->hasMany(NonSlsBusiness::class, 'pml_id');
    }

    /**
     * Sync the user to all secondary databases.
     */
    public function syncToOtherDatabases()
    {
        $databases = DatabaseSelector::getSupportConnections();

        foreach ($databases as $db) {
            // Check if user already exists in the target DB
            $existingUser = self::on($db)->find($this->id);

            if ($existingUser) {
                // Update existing user
                $existingUser->update($this->toArray());
            } else {
                // Create new user
                $data = $this->toArray();
                $data['password'] = $this->password;
                $data['id'] = $this->id;
                $data['created_at'] = now();
                $data['updated_at'] = now();
                
                DB::connection($db)->table('users')->insert($data);
            }
        }
    }

    /**
     * Delete the user from all secondary databases.
     */
    public function deleteFromOtherDatabases()
    {
        $databases = DatabaseSelector::getSupportConnections();

        foreach ($databases as $db) {
            self::on($db)->where('id', $this->id)->delete();
        }
    }

    /**
     * Override assignRole() to sync roles across all databases.
     */
    public function assignRoleAllDatabase(...$roles)
    {
        $databases = DatabaseSelector::getListConnections();

        foreach ($databases as $db) {
            $userInOtherDb = self::on($db)->find($this->id);

            if ($userInOtherDb) {
                // Assign role in the other databases
                $userInOtherDb->syncRoles($roles);
            }
        }
    }
}
