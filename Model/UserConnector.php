<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Tables\USER_CONNECTOR;
use Core\Model\Traits\HasCompositePrimaryKey;
class UserConnector extends \Tables\Model\User\Connector
{
    use HasCompositePrimaryKey;


    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = USER_CONNECTOR::TABLE;
    protected $primaryKey = ['id_user','id_connector']; 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','id_user','id_connector','access_token','refresh_token','expires_in','scopes','email'
    ];

    

    /**
     * Type
     * @var string
     */
    protected static function boot()
    {
        parent::boot();
    }
    public function connector()
    {
        return $this->belongsTo('Core\Connector\Connector', 'id_connector','id_connector');
    }
    public function getScopes()
    {
        return isset($this->scopes)?explode(',', $this->scopes):[];
    }
    public function setScopes($scopes)
    {
        if(empty($scopes))
        {
            $this->scopes = "";
        }else
        {
            $this->scopes = implode(',', $scopes);
        }
    }
    public static function getByConnectorName($id_user, $connector_name)
    {
        return static::leftJoin("connector","connector.id_connector","=","user_connector.id_connector")
        ->where("id_user","=",$id_user)
        ->where("connector.name","=",$connector_name)->first();
    }
}
