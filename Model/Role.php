<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Core\Model\Traits\HasCompositePrimaryKey;


class Role extends Model
{
	use HasCompositePrimaryKey;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'user_role';
	protected $primaryKey = ['id_user', 'role'];
    protected $fillable = [
        'role','id_user'
    ];
}
