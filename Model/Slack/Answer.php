<?php
namespace Core\Model\Slack;


use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Core\Model\Slack as SlackModel;

class Answer extends \Tables\Model\Slack\Answer
{

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

	protected $table = 'slack_answer';
	protected $primaryKey = 'id_slack_answer';
    protected $fillable = [
        "id_slack","id_slack_attachment","action","response_url","payload","id_slack_team","id_slack_user","id_slack_channel"
    ];

    
}