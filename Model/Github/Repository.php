<?php
namespace Core\Model\Github;

use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Core\Model\Webhook;
use GrahamCampbell\GitHub\Facades\GitHub as  GithubAPI;
class Repository extends Model
{
    use SoftDeletes;
    public $timestamps = false; 
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_time';


    protected $table = 'github_repository';
    protected $primaryKey = 'id_github_repository';

    protected $fillable = ['id','owner','name','full_name','description','url','private','push_time','created_at','updated_at'];

    public function webhook()
    {
        return $this->morphOne('Core\Model\Webhook', 'external');
    }
    public function createWebhook($url, $events = ["push"])
    {
        $githubWebhook = GithubApi::createRepositoryHook($this->owner, $this->name,$url, $events);
        $webhook = new Webhook;
        $webhook->external()->associate($this);
        $webhook->url = $url;
        $webhook->config = implode(",",$events);
        $webhook->id = $githubWebhook["id"];
        $webhook->id_str = $githubWebhook["id"];
        $webhook->return_content = json_encode($githubWebhook);
        $webhook->save();
    }
     public function removeWebhook()
    {
        $webhook = $this->webhook;
        if(!isset($webhook))
            return;
        $id_webhook = $webhook->id_str;
        GitHubApi::repo()->hooks()->remove($this->owner, $this->name, $id_webhook);
        $this->webhook->delete();
    }
    public function hasWebhook()
    {
        return $this->webhook !== NULL;
    }
}