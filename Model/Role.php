<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Core\Model\Traits\HasCompositePrimaryKey;


class Role extends \Tables\Model\User\Role
{
    use HasCompositePrimaryKey;
    
	protected $primaryKey = ['user_id', 'role'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'role','user_id'
    ];
}
