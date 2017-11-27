<?php
namespace Core\Github;

use GrahamCampbell\GitHub\GitHubManager as ParentGitHubManager;
use InvalidArgumentException;
/**
 * This is the github manager class.
 *
 * @method \Github\Api\CurrentUser currentUser()
 * @method \Github\Api\CurrentUser me()
 * @method \Github\Api\Enterprise ent()
 * @method \Github\Api\Enterprise enterprise()
 * @method \Github\Api\GitData git()
 * @method \Github\Api\GitData gitData()
 * @method \Github\Api\Gists gist()
 * @method \Github\Api\Gists gists()
 * @method \Github\Api\Issue issue()
 * @method \Github\Api\Issue issues()
 * @method \Github\Api\Markdown markdown()
 * @method \Github\Api\Notification notification()
 * @method \Github\\Github\Api\Notification notifications()
 * @method \Github\Api\Organization organization()
 * @method \Github\Api\Organization organizations()
 * @method \Github\Api\Organization\Projects orgProject()
 * @method \Github\Api\Organization\Projects orgProjects()
 * @method \Github\Api\Organization\Projects organizationProject()
 * @method \Github\Api\Organization\Projects organizationProjects()
 * @method \Github\Api\PullRequest pr()
 * @method \Github\Api\PullRequest pullRequest()
 * @method \Github\Api\PullRequest pullRequests()
 * @method \Github\Api\RateLimit rateLimit()
 * @method \Github\Api\Repo repo()
 * @method \Github\Api\Repo repos()
 * @method \Github\Api\Repo repository()
 * @method \Github\Api\Repo repositories()
 * @method \Github\Api\Search search()
 * @method \Github\Api\Organization team()
 * @method \Github\Api\Organization teams()
 * @method \Github\Api\User user()
 * @method \Github\Api\User users()
 * @method \Github\Api\Authorizations authorization()
 * @method \Github\Api\Authorizations authorizations()
 * @method \Github\Api\Meta meta()
 * @method \Github\Api\ApiInterface api(string $name)
 * @method void authenticate(string $tokenOrLogin, string|null $password = null, string|null $authMethod = null)
 * @method void setEnterpriseUrl(string $enterpriseUrl)
 * @method \Github\HttpClient\HttpClientInterface getHttpClient()
 * @method void setHttpClient(\Github\HttpClient\HttpClientInterface $httpClient)
 * @method void clearHeaders()
 * @method void setHeaders(array $headers)
 * @method mixed getOption(string $name)
 * @method void setOption(string $name, mixed $value)
 * @method array getSupportedApiVersions()
 */
class GitHubManager extends ParentGitHubManager
{
    protected $user_token;
    public function setUserToken($token)
    {
        $this->user_token = $token;
        $this->authenticate($this->user_token, NULL, 'http_token');
    }
    public function getConnectionConfig(string $name = null)
    {
        if($name == "main")
        {
            $config = [];
            $config["name"] = $name;
            $config["method"] = "token";
            $config["token"] = $this->user_token;
            return $config;
        }
        $name = $name ?: $this->getDefaultConnection();

        $connections = $this->config->get($this->getConfigName().'.connections');

        if (!is_array($config = array_get($connections, $name)) && !$config) {
            throw new InvalidArgumentException("Connection [$name] not configured.");
        }

        $config['name'] = $name;
        return $config;
    }
    public function createRepositoryHook($username, $repository, $url, $events = ["push"])
    {
        $params = [
            "name" => "web",
            "events" => $events,
            "active"=>True,
            "config" => 
            [
                "content_type"=>"form",
                "url"=>$url,
                "insecure_ssl"=>"1"
            ]
        ];
        return $this->repo()->hooks()->create($username, $repository, $params);
    }
}