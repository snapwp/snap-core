<?php

namespace Snap\Tests\Templating\Blade;

use PHPUnit\Framework\TestCase;
use Snap\Templating\Blade\Concerns\ProvidesHandlers;

class ProvidesHandlersTest extends TestCase
{
    private object $handlers;

    protected function setUp(): void
    {
        $this->handlers = new class {
            use ProvidesHandlers;
        };
    }

    public function testCanAnyUsesTheCanHandler(): void
    {
        $this->handlers->setCanHandler(static fn ($ability) => $ability === 'edit_posts');

        $this->assertTrue($this->handlers->canAnyHandler(['manage_options', 'edit_posts']));
        $this->assertFalse($this->handlers->canAnyHandler(['manage_options', 'delete_users']));
        $this->assertTrue($this->handlers->canAnyHandler('edit_posts'));
    }

    public function testCustomHandlersAreUsed(): void
    {
        $this->handlers->setCsrfHandler(static fn () => 'token');
        $this->handlers->setAuthHandler(static fn ($guard) => $guard === 'admin');
        $this->handlers->setCanHandler(static fn ($ability, $arguments) => $arguments === 5);
        $this->handlers->setInjectHandler(static fn ($service) => "service:$service");
        $this->handlers->setErrorHandler(static fn ($error) => $error === 'email' ? 'Invalid email' : false);

        $this->assertSame('token', $this->handlers->getCsrfToken());
        $this->assertTrue($this->handlers->authHandler('admin'));
        $this->assertFalse($this->handlers->authHandler());
        $this->assertTrue($this->handlers->canHandler('edit_post', 5));
        $this->assertSame('service:request', $this->handlers->injectHandler('request'));
        $this->assertSame('Invalid email', $this->handlers->errorHandler('email'));
        $this->assertFalse($this->handlers->errorHandler('name'));
    }
}
