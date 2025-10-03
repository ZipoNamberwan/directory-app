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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasUuids;
    use Impersonate;

    use HasRoles {
        HasRoles::hasRole as traitHasRole;
        HasRoles::hasAnyRole as traitHasAnyRole;
        HasRoles::getRoleNames as traitGetRoleNames;
        HasRoles::hasPermissionTo as traitHasPermissionTo;
    }
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
        'organization_id',
        'must_change_password',
        'is_wilkerstat_user',
        'is_kendedes_user',
        'is_kenarok_user',
        'is_allowed_swmaps',
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
            if ($user->getConnectionName() === DatabaseSelector::getDefaultConnection()) {
                $user->syncToOtherDatabases();
            }
        });

        static::updated(function ($user) {
            if ($user->getConnectionName() === DatabaseSelector::getDefaultConnection()) {
                $user->syncToOtherDatabases();
            }
        });

        static::deleted(function ($user) {
            if ($user->getConnectionName() === DatabaseSelector::getDefaultConnection()) {
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

    public function setPermissionAllDatabase(bool $replace = false, ...$permissions)
    {
        $databases = DatabaseSelector::getListConnections();

        foreach ($databases as $db) {
            $userInOtherDb = self::on($db)->find($this->id);

            if ($userInOtherDb) {
                if ($replace) {
                    // Replace all existing permissions with the new ones
                    $userInOtherDb->syncPermissions($permissions);
                } else {
                    // Add new permissions, keep the old ones
                    $userInOtherDb->givePermissionTo($permissions);
                }
            }
        }
    }

    public function markets()
    {
        return $this->belongsToMany(Market::class)
            ->withPivot('user_firstname', 'market_name')
            ->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function wilkerstatSls()
    {
        return $this->belongsToMany(
            Sls::class,
            'sls_user_wilkerstat', // Pivot table name
            'user_id',              // Foreign key on pivot table pointing to this model
            'sls_id',               // Foreign key on pivot table pointing to the related model
            'id',                   // Local key on the User model (default is 'id')
            'id'                    // Local key on the Sls model (default is 'id')
        );
    }


    // --------------------------------------------------
    // Acting Context
    // --------------------------------------------------
    public function actingContexts()
    {
        return $this->hasMany(UserActingContext::class);
    }

    public function activeActingContext()
    {
        return $this->hasOne(UserActingContext::class)->where('active', true);
    }

    public function getActingContextAttribute()
    {
        if ($this->relationLoaded('activeActingContext')) {
            return $this->getRelation('activeActingContext');
        }

        return $this->activeActingContext()->first();
    }

    // Organization masking
    public function getOrganizationIdAttribute($value)
    {
        return $this->actingContext?->acting_org_id ?? $value;
    }

    public function realOrganizationId()
    {
        return $this->getRawOriginal('organization_id');
    }

    public function getRealOrganization()
    {
        return Organization::find($this->realOrganizationId());
    }

    // Regency masking
    public function getRegencyIdAttribute($value)
    {
        return $this->actingContext?->acting_reg_id ?? $value;
    }

    public function realRegencyId()
    {
        return $this->getRawOriginal('regency_id');
    }

    public function getRealRegency()
    {
        return Regency::find($this->realRegencyId());
    }

    // --------------------------------------------------
    // Role masking (Spatie aware)
    // --------------------------------------------------
    public function actingRole()
    {
        return $this->actingContext?->acting_role;
    }

    public function hasRole($roles, $guard = null): bool
    {
        $rolesArr = is_array($roles) ? $roles : explode('|', (string) $roles);

        if ($this->actingRole() && in_array($this->actingRole(), $rolesArr, true)) {
            return true;
        }

        return $this->traitHasRole($roles, $guard);
    }

    public function hasAnyRole(...$roles): bool
    {
        $flat = collect($roles)
            ->flatMap(fn($r) => is_array($r) ? $r : explode('|', (string) $r))
            ->map(fn($r) => trim($r))
            ->filter()
            ->values()
            ->all();

        if ($this->actingRole() && in_array($this->actingRole(), $flat, true)) {
            return true;
        }

        return $this->traitHasAnyRole(...$flat);
    }

    public function getRoleNames()
    {
        if ($this->actingRole()) {
            return collect([$this->actingRole()]);
        }

        return $this->traitGetRoleNames();
    }

    public function getDirectRoleNames()
    {
        return $this->traitGetRoleNames();
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // If acting role is active
        if ($this->actingRole()) {
            // Always allow these permissions
            if (in_array($permission, ['edit_business', 'delete_business'], true)) {
                return true;
            }

            // Otherwise, check if acting role grants it
            $roleModel = Role::findByName(
                $this->actingRole(),
                $guardName ?? $this->getDefaultGuardName()
            );

            if ($roleModel && $roleModel->hasPermissionTo($permission)) {
                return true;
            }
        }

        // Fallback: check user's real roles/permissions
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    // --------------------------------------------------
    // Authorization helper
    // --------------------------------------------------
    public function canActAs(int $orgId, string $role): bool
    {
        // Replace with your own logic / pivot table check
        if ($this->getDirectRoleNames()->contains('adminprov')) {
            return false;
        }

        if ($this->hasPermissionTo('act-as')) {
            return true;
        }

        return false; // default deny
    }
}
