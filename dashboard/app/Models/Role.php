<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Collection $permissions
 * @method static where(array $where)
 * @method static orderBy($a, $b)
 */
class Role extends Model
{
    const string ROLE_ADMIN_SLUG = 'admin';
    const string ROLE_USER_SLUG = 'user';
    const string ROLE_COMPANY_SLUG = 'company';
    const string ROLE_BILLING_USER_SLUG = 'billing_user';
    const string ROLE_MANAGER = 'manager';
    const string ROLE_MANUFACTURER = 'manufacturer';
    const string ROLE_CONTRACTOR = 'contractor';
    const string ROLE_BRANCH_MANAGER = 'branch_manager';
    const string ROLE_CUSTOMER_SUCCESS_ADMIN = 'customer_success_admin';
    const string ROLE_PARTNER = 'partner';
    const string ROLE_FRAMEWORK = 'framework';
    const string ROLE_CLIENT = 'client';
    const string ROLE_LOGISTICS = 'logistics';
    const string ROLE_CONSULTANT = 'consultant';

    protected $table = 'roles';
    protected $guarded = [];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
