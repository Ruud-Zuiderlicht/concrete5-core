<?php

namespace Concrete\Core\API;

use Concrete\Core\API\OAuth\Validator\DefaultValidator;
use Concrete\Core\Entity\OAuth\AccessToken;
use Concrete\Core\Entity\OAuth\AuthCode;
use Concrete\Core\Entity\OAuth\Client;
use Concrete\Core\Entity\OAuth\RefreshToken;
use Concrete\Core\Entity\OAuth\Scope;
use Concrete\Core\Entity\OAuth\UserRepository;
use Concrete\Core\Entity\User\User;
use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use Concrete\Core\Routing\Router;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use phpseclib\Crypt\RSA;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;

class APIServiceProvider extends ServiceProvider
{

    const KEY_PRIVATE = 'privatekey';
    const KEY_PUBLIC = 'publickey';

    private $keyPair;

    /**
     * Register API related stuff
     *
     * @return void
     */
    public function register()
    {
        $config = $this->app->make("config");
        if ($this->app->isInstalled() && $config->get('concrete.api.enabled')) {
            $router = $this->app->make(Router::class);
            $list = new APIRouteList();
            $list->loadRoutes($router);
            $this->registerAuthorizationServer();
        }
    }

    private function repositoryFactory($factoryClass, $entityClass)
    {
        return function () use ($factoryClass, $entityClass) {
            $em = $this->app->make(EntityManagerInterface::class);
            $metadata = $em->getClassMetadata($entityClass);

            return $this->app->make($factoryClass, [
                $em,
                $metadata
            ]);
        };
    }

    private function repositoryFor($class)
    {
        return function () use ($class) {
            $em = $this->app->make(EntityManagerInterface::class);
            return $em->getRepository($class);
        };
    }

    /**
     * Generate new RSA keys if needed
     * @return string[] ['privatekey' => '...', 'publickey' => '...']
     */
    private function getKeyPair()
    {
        $config = $this->app->make('config/database');

        // Seee if we already have a kypair
        $keyPair = $config->get('api.keypair');

        if (!$keyPair) {
            $rsa = $this->app->make(RSA::class);

            // Generate a new RSA key
            $keyPair = $rsa->createKey(2048);

            foreach ($keyPair as &$item) {
                $item = str_replace("\r\n", "\n", $item);
            }

            // Save the keypair
            $config->save('api.keypair', $keyPair);
        }

        return $keyPair;
    }

    /**
     * Get a key by handle
     * @param $handle privatekey | publickey
     * @return string|null
     */
    private function getKey($handle)
    {
        if (!$this->keyPair) {
            $this->keyPair = $this->getKeyPair();
        }

        return isset($this->keyPair[$handle]) ? $this->keyPair[$handle] : null;
    }

    /**
     * Register the authorization and authentication server classes
     */
    protected function registerAuthorizationServer()
    {
        // The ResourceServer deals with authenticating requests, in other words validating tokens
        $this->app->when(ResourceServer::class)->needs('$publicKey')->give($this->getKey(self::KEY_PUBLIC));
        $this->app->bind(ResourceServer::class, function() {
            return $this->app->build(ResourceServer::class, [
                $this->app->make(AccessTokenRepositoryInterface::class),
                $this->getKey(self::KEY_PUBLIC),
                $this->app->make(DefaultValidator::class)
            ]);
        });

        // AuthorizationServer on the other hand deals with authorizing a session with a username and password and key and secret
        $this->app->when(AuthorizationServer::class)->needs('$privateKey')->give($this->getKey(self::KEY_PRIVATE));
        $this->app->when(AuthorizationServer::class)->needs('$publicKey')->give($this->getKey(self::KEY_PUBLIC));
        $this->app->extend(AuthorizationServer::class, function (AuthorizationServer $server) {
            $server->setEncryptionKey($this->app->make('config/database')->get('concrete.security.token.encryption'));

            $oneHourTTL = new \DateInterval('PT1H');

            // Enable client_credentials grant type with 1 hour ttl
            $server->enableGrantType($this->app->make(ClientCredentialsGrant::class), $oneHourTTL);

            return $server;
        });

        // Register OAuth stuff
        $this->app->bind(AccessTokenRepositoryInterface::class, $this->repositoryFor(AccessToken::class));
        $this->app->bind(AuthCodeRepositoryInterface::class, $this->repositoryFor(AuthCode::class));
        $this->app->bind(ClientRepositoryInterface::class, $this->repositoryFor(Client::class));
        $this->app->bind(RefreshTokenRepositoryInterface::class, $this->repositoryFor(RefreshToken::class));
        $this->app->bind(ScopeRepositoryInterface::class, $this->repositoryFor(Scope::class));
        $this->app->bind(UserRepositoryInterface::class, $this->repositoryFactory(UserRepository::class, User::class));
    }

}
