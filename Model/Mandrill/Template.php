<?php

namespace Core\Model\Mandrill;

use Core\Database\Eloquent\Model;

class Template extends \Tables\Model\Mandrill\Template
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    protected $table = 'mandrill_template';
    protected $primaryKey = 'id_template';

    static public function getTemplate( $slug, $language )
	{
        $data = Template::where('slug', '=', $slug . '-' . $language)
                        ->where('language', '=', $language)
                        ->where('is_published', '=', true)
                        ->first();
        return $data;
	}
}