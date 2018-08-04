<?php
namespace Core\Model;
use Core\Traits\CachedAuto;
use CacheManager;

class UserLoginToken extends \Tables\Model\User\Login\Token
{
    use CachedAuto;
    protected $primaryKey = 'user_id';

    static public function renew( $user )
    {
        $class = new UserLoginToken();
        $token = $class->find($user->getKey());

        $key = str_replace("%token", $token->token, "user-token:%token");
        CacheManager::forget($key);

        $token->token = generate_token();
        $token->save();
        $user->invalidate();

        return $token;
    }

    static public function renewExpire( $user, $seconds )
    {
        $class = new UserLoginToken();
        $token = $class->find($user->getKey());

        if ($token->updated_at->diffInSeconds(\Carbon\Carbon::now()->subSeconds($seconds), false) >= 0)
        {
            return self::renew($user);
        }

        return $token;
    }

    
}
