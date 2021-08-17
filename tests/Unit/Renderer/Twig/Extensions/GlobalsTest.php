<?php

namespace Engelsystem\Test\Unit\Renderer\Twig\Extensions;

use Engelsystem\Config\Config;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Request;
use Engelsystem\Models\User\Settings;
use Engelsystem\Models\User\User;
use Engelsystem\Renderer\Twig\Extensions\Globals;
use Engelsystem\Test\Unit\HasDatabase;
use PHPUnit\Framework\MockObject\MockObject;

class GlobalsTest extends ExtensionTest
{
    use HasDatabase;

    /**
     * @covers \Engelsystem\Renderer\Twig\Extensions\Globals::__construct
     * @covers \Engelsystem\Renderer\Twig\Extensions\Globals::getGlobals
     */
    public function testGetGlobals()
    {
        $this->initDatabase();

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $request = new Request();
        $user = new User();
        $theme2 = ['name' => 'Bar'];
        $theme3 = ['name' => 'Lorem'];
        $user = new User(['name' => '', 'email' => '', 'password' => '', 'api_key' => '']);
        $userSettings = new Settings(['theme' => 42, 'language' => '']);
        $config = new Config(['theme' => 23, 'themes' => [42 => $theme, 23 => $theme2, 1337 => $theme3]]);

        $auth->expects($this->exactly(4))
            ->method('user')
            ->willReturnOnConsecutiveCalls(
                null,
                $user,
                $user,
                null
            );

        $user->save();

        $userSettings->user()
            ->associate($user)
            ->save();

        $this->app->instance('config', $config);

        $extension = new Globals($auth, $request);

        // No user
        $globals = $extension->getGlobals();
        $this->assertGlobalsExists('user', [], $globals);
        $this->assertGlobalsExists('request', $request, $globals);
        $this->assertGlobalsExists('themeId', 23, $globals);
        $this->assertGlobalsExists('theme', $theme2, $globals);

        // User
        $globals = $extension->getGlobals();
        $this->assertGlobalsExists('user', $user, $globals);
        $this->assertGlobalsExists('themeId', 42, $globals);
        $this->assertGlobalsExists('theme', $theme, $globals);

        // User with not available theme configured
        $user->settings->theme = 9999;
        $globals = $extension->getGlobals();
        $this->assertGlobalsExists('themeId', 42, $globals);

        // Request query parameter
        $request->query->set('theme', 1337);
        $globals = $extension->getGlobals();
        $this->assertGlobalsExists('user', [], $globals);
        $this->assertGlobalsExists('themeId', 1337, $globals);
        $this->assertGlobalsExists('theme', $theme3, $globals);
    }
}
