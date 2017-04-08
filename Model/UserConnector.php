<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Tables\USER_CONNECTOR;
class UserConnector extends Model
{



    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = USER_CONNECTOR::TABLE;
    protected $primaryKey = 'id_user'; 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_user','id_connector','access_token','refresh_token','expires_in','scopes',
    ];

    

    /**
     * Type
     * @var string
     */
    protected static function boot()
    {
        parent::boot();
    }
}
