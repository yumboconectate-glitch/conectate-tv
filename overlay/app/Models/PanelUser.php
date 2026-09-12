<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelUser extends Model
{
    public const ROLES = ['admin', 'tecnico', 'comercial'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'last_login_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTechnical(): bool
    {
        return $this->role === 'tecnico';
    }

    public function isCommercial(): bool
    {
        return $this->role === 'comercial';
    }
}
