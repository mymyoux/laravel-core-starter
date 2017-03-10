<?php

namespace Core\Queue;

use Illuminate\Database\Eloquent\Model;
use DB;

class Slack
{
    public $data;

    public function __construct( $channel, $message, $attachments = [], $bot_name = null, $icon = null )
    {
    	if (!$bot_name)
    		$bot_name = 'SOTF';
    	if (!$icon)
    		$icon = ':deciduous_tree:';

    	if (env('APP_ENV') === 'local')
    		$channel = 'test_yb';

        $this->data     = [
        	'channel'     => (mb_strpos($channel, '#') === false ? '#' : '') . $channel,
            'username'    => $bot_name,
            'text'        => $message,
            'icon_emoji'  => $icon,
            'attachments' => $attachments
        ];
    }
}
