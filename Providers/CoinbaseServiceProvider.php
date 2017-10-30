<?php
namespace Core\Providers;
/*
 * This file is part of Laravel GitHub.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use GrahamCampbell\GitHub\GitHubServiceProvider as ParentGitHubServiceProvider;

use Github\Client;
use GrahamCampbell\GitHub\Authenticators\AuthenticatorFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use GrahamCampbell\GitHub\GitHubFactory;
use Core\Github\GitHubManager;

use SocialiteProviders\Manager\SocialiteWasCalled;
/**
 * This is the github service provider class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class CoinbaseServiceProvider
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('coinbase', \Core\Coinbase\Provider::class);
    }
}
