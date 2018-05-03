<?php

namespace Core\Model\Connector;

use Illuminate\Database\Eloquent\Model;

class Linkedin extends \Tables\Model\Connector\Linkedin
{
    protected $fillable = ['user_id', 'id', 'headline', 'first_name', 'last_name', 'email', 'link'];
}
