<?php
namespace Core\Model\Github;

use Core\Database\Eloquent\Model;
class Commit extends Model
{
    public $timestamps = false; 
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_time';


    protected $table = 'github_repository_commit';
    protected $primaryKey = 'id_github_repository_commit';

    protected $fillable = ['id_github_repository','sha','url','id_github_user','message','created_at','updated_at'];
    public function repository()
    {
        return $this->belongsTo('Core\Model\Github\Repository','id_github_repository','id_github_repository');
    }
    public function user()
    {
        return $this->belongsTo('Core\Model\Github\User','id_github_user','id_github_user');
    }
}