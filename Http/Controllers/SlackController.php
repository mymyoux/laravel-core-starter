<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Core\Model\Event;
use App\Events\CompanySignupEvent;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Core\Api\Annotations as ghost;
use App;
use Notification;
use Db;
use Sheets;
use Google;
use Job;
use Core\Jobs\Test;
use Logger;
use Apiz;

use Core\Model\Slack;
use Core\Model\Slack\Attachment;
use Core\Model\Slack\Answer;
use Illuminate\Support\Facades\Redis;
class SlackController extends Controller
{
    
    public function handle(Request $request)
    {
        
        $payload = json_decode($request->input('payload'));
        $token = $payload->token;

        //avoid false services request
        if($token !== config("services.slack.verification_token"))
        {
            return $this->error("bad token");
        }

        $response_url = $payload->response_url;
        $action = $payload->actions[0];
        $callback_id = $payload->callback_id;
        $team = $payload->team;
        $channel = $payload->channel;
        $user = $payload->user;
        $message_ts = $payload->message_ts;

        $slack = Slack::where(["ts"=>$message_ts,"slack_channel"=>$channel->name])->first();
        $slack->loadFromDatabase();




        

        $attachment = Attachment::where(["id_callback"=>$callback_id,"id_slack"=>$slack->id_slack])->first();
        if(!isset($attachment))
        {
            return $this->error("no attachment");
        }

        $answer = new Answer;
        $answer->id_slack = $attachment->id_slack;
        $answer->id_slack_attachment = $attachment->id_slack_attachment;
        $answer->payload = $request->input('payload');
        if(isset($action->selected_options))
        {
            $action = $action->selected_options[0];
        }
        $answer->action  =$action->value;
        $answer->response_url  = $response_url;
        $answer->id_slack_team = $team->id;
        $answer->id_slack_user = $user->id;
        $answer->id_slack_user_name = $user->name;
        $answer->id_slack_channel = $channel->id;
        $answer->save();


        $parts = explode("@", $slack->answer_handler);
        $cls = $parts[0];
        if(count($parts)>1)
            $method = $parts[1];
        else
            $method = "handle";
        $instance = resolve($cls);
        try
        {
            $result = $instance->$method($slack, $attachment, $answer);
            if($result === True)
                return $this->ok();
            if($result instanceof Slack)
            {
                //return ["ok"=>True];
                $result->saveAll();
                $result = $result->toSlackJSON();
            }
            if(is_array($result) && isset($result["attachments"]))
            {
                $result["attachments"] = json_decode($result["attachments"]);
                $result["app_unfurl_url"] = "https://app.yborder.com/pdfs/13448-contacts-14948577089056cee19abebc3f4b.pdf";
                $result["is_app_unfurl"] = True;
            }
            return response()->json($result);
        }catch(\Exception $e)
        {
            return $this->error($e->getMessage());
        }
    }
    public function handlerTest($slack, $answer)
    {
        dd('ok');
    }
    public function error($error = "Sorry an error occurred")
    {
        return response()->json(["response_type"=>"ephemeral","replace_original"=>false,"text"=>$error]);
    } 
    public function ok($text = "Your answer is processing")
    {
        return response()->json(["response_type"=>"ephemeral","replace_original"=>false,"text"=>$text]);
        exit();
    }
    public function postpone()
    {
        
        return $this->ok();
    }
}
