<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Core\Model\Traits\HasCompositePrimaryKey;
class ConnectorUser extends \Tables\Model\Connector\User
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','user_id','connector_id','access_token','refresh_token','expires_in','scopes','email'
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
        return static::leftJoin("connector","connector.connector_id","=","user_connector.connector_id")
        ->where("user_id","=",$id_user)
        ->where("connector.name","=",$connector_name)->first();
    }
}
