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
use Symfony\Component\Console\Application;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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

    /** @var SessionInterface */
    protected $session;

    /** @var array */
    protected $permissions = ["user_settings"];

    /** @var Application */
    protected $app;

    /** @var string */
    protected $telegram_base;

    /** @var string */
    protected $telegram_api_key;

    /**
     * @param Authenticator   $auth
     * @param LoggerInterface $log
     * @param Redirector      $redirect
     * @param Response        $response
     * @param HttpClientServiceProvider $guzzle
     * @param SessionInterface $session
     * @param Application $app
     */

    public function __construct(
        Authenticator $auth,
        LoggerInterface $log,
        Redirector $redirect,
        Response $response,
        GuzzleClient $guzzle,
        SessionInterface $session,
        Application $app
    ) {
        $this->auth = $auth;
        $this->log = $log;
        $this->redirect = $redirect;
        $this->response = $response;
        $this->guzzle = $guzzle;
        $this->session = $session;
        $this->app = $app;

        /** @var Config $config */
        $config = $app->get("config");
        $emailConfig = $config->get("email");

        $this->telegram_base = $emailConfig["telegram_base_url"];
        $this->telegram_api_key = $emailConfig["telegram_api_key"];
    }

    /**
     * @param Response $response
     * @return Response
     */
    public function register(Request $request): Response
    {
        $user = $this->auth->user();

        if (!$this->auth->can("user_settings")) {
            $this->session->set("previous_page", $request->getUri());
            return $this->redirect->to("login");
        }

        $token = $request->getAttribute("token");
        $uri = "{$this->telegram_base}/bot/register/";
        $response = $this->guzzle->get($uri . $user->id . "/" . $token, [
            "headers" => ["x-api-key" => $this->telegram_api_key],
        ]);

        if ($response->getStatusCode() == 202) {
            //$user->settings->bot_chatid = $response->getBody()->getContents();
            //$user->save();
            //
            print_r($response->getBody()->getContents());
        }

        return $this->response->withView("pages/bot/register.twig");
    }
}
