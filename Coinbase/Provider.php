<?php 
namespace Core\Coinbase;
use SocialiteProviders\Manager\OAuth2\User;
class Provider extends \SocialiteProviders\Coinbase\Provider
{
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.coinbase.com/v2/user', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true)["data"];
    }
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'], 'nickname' => $user['name'],
            'name' => $user['name'], 'email' => null, 'avatar' => $user["avatar_url"],
        ]);
    }
}