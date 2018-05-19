<?php
namespace Core\Model;
use Core\Traits\CachedAuto;

class UserLoginToken extends \Tables\Model\User\Login\Token
{
    use CachedAuto;

    static public function renew( $user )
    {
        $class = new UserLoginToken();
        $token = $class->find($user->getKey());

        $token->token = generate_token();

        $token->save();

        return $token;
    }

    static public function renewExpire( $user, $seconds )
    {
        $class = new UserLoginToken();
        $token = $class->find($user->getKey());

        if ($token->updated_time->diffInSeconds(\Carbon\Carbon::now()->subSeconds($seconds), false) >= 0)
        {
            $user->invalidate();
            return self::renew($user);
        }

        return $token;
    }

    
}
