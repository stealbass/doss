<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnterpriseSubAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_account_id',
        'sub_account_id',
        'organization_role',
        'permissions',
        'can_create_sub_accounts',
        'can_manage_billing',
        'can_access_analytics',
        'can_download_templates',
        'can_use_calculators',
        'is_active',
        'invited_at',
        'activated_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'can_create_sub_accounts' => 'boolean',
        'can_manage_billing' => 'boolean',
        'can_access_analytics' => 'boolean',
        'can_download_templates' => 'boolean',
        'can_use_calculators' => 'boolean',
        'is_active' => 'boolean',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function mainAccount()
    {
        return $this->belongsTo(User::class, 'main_account_id');
    }

    public function subAccount()
    {
        return $this->belongsTo(User::class, 'sub_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('organization_role', $role);
    }
}
