<?php
namespace Core\Model\Github;

use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Model
{
    public $timestamps = false; 
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    const DELETED_AT = 'deleted_time';


    protected $table = 'github_user';
    protected $primaryKey = 'id_github_user';

    protected $fillable = ['id_user','id','login','picture','url','email'];
    public function user()
    {
        return $this->belongsTo('App\User','id_user','id_user');
    }
}