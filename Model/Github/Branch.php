<?php
namespace Core\Model\Github;

use Core\Database\Eloquent\Model;
class Branch extends Model
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $table = 'github_repository_branch';
    protected $primaryKey = 'id_github_repository_branch';

    protected $fillable = ['id_github_repository','name','sha','protected','created_at','updatd_time'];
    public function repository()
    {
        return $this->belongsTo('Core\Model\Github\Repository','id_github_repository','id_github_repository');
    }
}