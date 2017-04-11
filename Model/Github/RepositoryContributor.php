<?php
namespace Core\Model\Github;

use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Core\Model\Traits\HasCompositePrimaryKey;
class RepositoryContributor extends Model
{
     use HasCompositePrimaryKey;
    public $timestamps = false; 
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    const DELETED_AT = 'deleted_time';


    protected $table = 'github_repository_contributor';
    protected $primaryKey = ['id_github_repository','id_github_user'];

    protected $fillable = ['id_github_repository','id_github_user','can_pull','can_push','is_admin'];
    public function repository()
    {
        return $this->belongsTo('Core\Model\Github\Repository','id_github_repository','id_github_repository');
    }
     public function user()
    {
        return $this->belongsTo('Core\Model\Github\User','id_github_user','id_github_user');
    }
}