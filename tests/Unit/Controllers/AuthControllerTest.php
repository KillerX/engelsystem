<?php

namespace Engelsystem\Test\Unit\Controllers;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Engelsystem\Config\Config;
use Engelsystem\Controllers\AuthController;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Exceptions\ValidationException;
use Engelsystem\Http\Redirector;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Http\Validation\Validator;
use Engelsystem\Models\User\Settings;
use Engelsystem\Models\User\User;
use Engelsystem\Test\Unit\HasDatabase;
use Engelsystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AuthControllerTest extends TestCase
{
    use ArraySubsetAsserts;
    use HasDatabase;

    /**
     * @covers \Engelsystem\Controllers\AuthController::__construct
     * @covers \Engelsystem\Controllers\AuthController::login
     * @covers \Engelsystem\Controllers\AuthController::showLogin
     */
    public function testLogin()
    {
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);
        /** @var SessionInterface|MockObject $session */
        /** @var Redirector|MockObject $redirect */
        /** @var Config $config */
        /** @var Authenticator|MockObject $auth */
        list(, $session, $redirect, $config, $auth) = $this->getMocks();

        $session->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnCallback(function ($type) {
                return $type == 'errors' ? ['foo' => 'bar'] : [];
            });
        $response->expects($this->once())
            ->method('withView')
            ->with('pages/login')
            ->willReturn($response);

        $controller = new AuthController($response, $session, $redirect, $config, $auth);
        $controller->login();
    }

    /**
     * @covers \Engelsystem\Controllers\AuthController::postLogin
     */
    public function testPostLogin()
    {
        $this->initDatabase();

        $request = new Request();
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);
        /** @var Redirector|MockObject $redirect */
        /** @var Config $config */
        /** @var Authenticator|MockObject $auth */
        list(, , $redirect, $config, $auth) = $this->getMocks();
        $session = new Session(new MockArraySessionStorage());
        /** @var Validator|MockObject $validator */
        $validator = new Validator();
        $session->set('errors', [['bar' => 'some.bar.error']]);
        $this->app->instance('session', $session);

        $user = new User([
            'name'          => 'foo',
            'password'      => '',
            'email'         => '',
            'api_key'       => '',
            'last_login_at' => null,
        ]);
        $user->forceFill(['id' => 42]);
        $user->save();

        $settings = new Settings(['language' => 'de_DE', 'theme' => '']);
        $settings->user()
            ->associate($user)
            ->save();

        $auth->expects($this->exactly(2))
            ->method('authenticate')
            ->with('foo', 'bar')
            ->willReturnOnConsecutiveCalls(null, $user);

        $response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function ($view, $data = []) use ($response) {
                $this->assertEquals('pages/login', $view);
                $this->assertArraySubset(['errors' => collect(['some.bar.error', 'auth.not-found'])], $data);
                return $response;
            });
        $redirect->expects($this->once())
            ->method('to')
            ->with('news')
            ->willReturn($response);

        // No credentials
        $controller = new AuthController($response, $session, $redirect, $config, $auth);
        $controller->setValidator($validator);
        try {
            $controller->postLogin($request);
            $this->fail('Login without credentials possible');
        } catch (ValidationException $e) {
        }

        // Missing password
        $request = new Request([], ['login' => 'foo']);
        try {
            $controller->postLogin($request);
            $this->fail('Login without password possible');
        } catch (ValidationException $e) {
        }

        // No user found
        $request = new Request([], ['login' => 'foo', 'password' => 'bar']);
        $controller->postLogin($request);
        $this->assertEquals([], $session->all());

        // Authenticated user
        $controller->postLogin($request);

        $this->assertNotNull($user->last_login_at);
        $this->assertEquals(['user_id' => 42, 'locale' => 'de_DE'], $session->all());
    }

    /**
     * @covers \Engelsystem\Controllers\AuthController::logout
     */
    public function testLogout()
    {
        /** @var Response $response */
        /** @var SessionInterface|MockObject $session */
        /** @var Redirector|MockObject $redirect */
        /** @var Config $config */
        /** @var Authenticator|MockObject $auth */
        list($response, $session, $redirect, $config, $auth) = $this->getMocks();

        $session->expects($this->once())
            ->method('invalidate');

        $redirect->expects($this->once())
            ->method('to')
            ->with('/')
            ->willReturn($response);

        $controller = new AuthController($response, $session, $redirect, $config, $auth);
        $return = $controller->logout();

        $this->assertEquals($response, $return);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $response = new Response();
        /** @var SessionInterface|MockObject $session */
        $session = $this->getMockForAbstractClass(SessionInterface::class);
        /** @var Redirector|MockObject $redirect */
        $redirect = $this->createMock(Redirector::class);
        $config = new Config(['home_site' => 'news']);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);

        $this->app->instance('session', $session);

        return [$response, $session, $redirect, $config, $auth];
    }
}
