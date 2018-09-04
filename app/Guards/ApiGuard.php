<?php

namespace App\Guards;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Tymon\JWTAuth\JWTAuth;

use App\Models\User;

class ApiGuard implements Guard
{
    use GuardHelpers;

    /**
     * The name of the Guard.
     *
     * Corresponds to driver name in authentication configuration.
     *
     * @var string
     */
    protected $name;

    /**
     * The user provider.
     *
     * Gets user from config
     *
     * @var string
     */
    protected $provider;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The user we last attempted to retrieve.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * The JWT instance.
     *
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;


    /**
     * Create a new authentication guard.
     *
     * @param  string  $name
     * @param \Illuminate\Http\Request                $request
     *
     * @return void
     */
    public function __construct(JWTAuth $jwt, $provider, Request $request)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (empty($credentials)) {
            $this->user = $this->jwt->parseToken()->authenticate();
            return $this->user;
        }

        return $this->attempt($credentials, false);
    }

    /**
     * Get the current request instance.
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function once(array $credentials = [])
    {
        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);
            return true;
        }
        return false;
    }

    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param array $credentials
     * @param bool  $login
     *
     * @return mixed
     */
    public function attempt(array $credentials = [], $login = true)
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        if ($this->hasValidCredentials($user, $credentials)) {
            return $login ? $this->login($user) : true;
        }
        return false;
    }

    /**
     * Get the last user we attempted to authenticate.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     * @param array $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Get the raw Payload instance.
     *
     * @return \Tymon\JWTAuth\Payload
     */
    public function getPayload()
    {
        return $this->jwt->getPayload();
    }

    /**
     * Get the token.
     *
     * @return false|Token
     */
    public function getToken()
    {
        return $this->jwt->getToken();
    }

    /**
     * Set the token.
     *
     * @param Token|string $token
     *
     * @return JwtGuard
     */
    public function setToken($token)
    {
        $this->jwt->setToken($token);
        return $this;
    }

    /**
     * Ensure that a token is available in the request.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return \Tymon\JWTAuth\JWT
     */
    protected function requireToken()
    {
        if (!$this->getToken()) {
            throw new BadRequestHttpException('Token could not be parsed from the request.');
        }        

        return $this->jwt;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }
        if ($this->jwt->setRequest($this->request)->getToken() && $this->jwt->check()) {
            $id = $this->jwt->payload()->get('sub');
            return $this->user = $this->provider->retrieveById($id);
        }
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function onceUsingId($id)
    {
        if (!is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);
            return true;
        }
        return false;
    }

    /**
     * Logout the user.
     *
     * @param bool $forceForever
     *
     * @return bool
     */
    public function logout($forceForever = true)
    {
        $this->user = null;
        return $this->invalidate($this->getToken());
    }

    /**
     * Invalidate current token (add it to the blacklist).
     *
     * @param bool $forceForever
     *
     * @return bool
     */
    public function invalidate($forceForever = false)
    {
        return $this->requireToken()->invalidate($forceForever);
    }

    /**
     * Refresh current expired token.
     *
     * @return string
     */
    public function refresh()
    {
        return $this->requireToken()->refresh();
    }

}
