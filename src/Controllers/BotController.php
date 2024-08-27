<?php

namespace Engelsystem\Controllers;

use Engelsystem\Controllers\BaseController;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Redirector;
use Engelsystem\Mail\EngelsystemMailer;
use Illuminate\Database\Capsule\Manager as DB;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Models\Faq;
use Engelsystem\Models\User\User;
use Psr\Log\LoggerInterface;
use Engelsystem\Models\AngelType;
use GuzzleHttp\Client as GuzzleClient;


class BotController
{
    /** @var Authenticator */
    protected $auth;

    /** @var LoggerInterface */
    protected $log;

    /** @var Redirector */
    protected $redirect;

    /** @var Response */
    protected $response;

    /** @var GuzzleClient */
    protected $guzzle;

    /** @var array */
    protected $permissions = [
        'user_settings',
    ];

    /**
     * @param Authenticator   $auth
     * @param LoggerInterface $log
     * @param Redirector      $redirect
     * @param Response        $response
     * @param HttpClientServiceProvider $guzzle
     */

    public function __construct(
        Authenticator $auth,
        LoggerInterface $log,
        Redirector $redirect,
        Response $response,
        GuzzleClient $guzzle,
    ) {
        $this->auth = $auth;
        $this->log = $log;
        $this->redirect = $redirect;
        $this->response = $response;
        $this->guzzle = $guzzle;
    }

    /**
     * @param Response $response
     * @return Response
     */
    public function register(Request $request) : Response
    {
        $user = $this->auth->user();
        print_r($user);

        if (!$this->auth->can('user_settings')) {
            return $this->redirect->to('login');
        }

        $token = $request->getAttribute('token');
        $response = $this->guzzle->get('http://192.168.0.181:8080/bot/register/' . $user->id . '/' . $token);

        if ($response->getStatusCode() == 200) {
            //$user->settings->bot_chatid = $response->getBody()->getContents();
            //$user->save();
            //
            print_r($response->getBody()->getContents());
        }

        return $this->response->withView('pages/bot/register.twig');
    }
}

