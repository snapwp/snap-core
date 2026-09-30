<?php

namespace Snap\Tests\Templating\Blade;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Snap\Templating\Blade\Compiler;
use Snap\Templating\Blade\ComponentTagCompiler;

class CompilerTest extends TestCase
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler(new Filesystem(), \sys_get_temp_dir());
    }

    public function testAuthUsesHandler(): void
    {
        $this->assertSame(
            '<?php if($__env->authHandler()): ?>yes <?php elseif($__env->authHandler(\'edit_posts\')): ?>editor <?php endif; ?>',
            $this->compiler->compileString("@auth()yes @elseauth('edit_posts')editor @endauth")
        );
    }

    public function testGuestUsesHandler(): void
    {
        $this->assertSame(
            '<?php if(! $__env->authHandler()): ?>guest <?php endif; ?>',
            $this->compiler->compileString('@guest()guest @endguest')
        );
    }

    public function testCanUsesHandler(): void
    {
        $this->assertSame(
            '<?php if ($__env->canHandler(\'edit_post\', $post)): ?>a <?php elseif ($__env->canAnyHandler([\'x\', \'y\'])): ?>b <?php endif; ?>',
            $this->compiler->compileString("@can('edit_post', \$post)a @elsecanany(['x', 'y'])b @endcan")
        );

        $this->assertSame(
            '<?php if (! $__env->canHandler(\'edit_post\')): ?>c <?php endif; ?>',
            $this->compiler->compileString("@cannot('edit_post')c @endcannot")
        );
    }

    public function testInjectUsesHandler(): void
    {
        $this->assertSame(
            '<?php $request = $__env->injectHandler(\'request\'); ?>',
            $this->compiler->compileString("@inject('request', 'request')")
        );
    }

    public function testErrorUsesHandler(): void
    {
        $compiled = $this->compiler->compileString("@error('email'){{ \$message }}@enderror");

        $this->assertStringContainsString("if (\$__env->errorHandler('email')) :", $compiled);
        $this->assertStringContainsString("\$message = \$__env->errorHandler('email');", $compiled);
        $this->assertStringNotContainsString('$errors', $compiled);
    }

    public function testCsrfUsesHandler(): void
    {
        $compiled = $this->compiler->compileString('@csrf');

        $this->assertStringContainsString('$__env->getCsrfToken()', $compiled);
        $this->assertStringNotContainsString('csrf_field', $compiled);
    }

    public function testEnvUsesWordpressEnvironment(): void
    {
        $this->assertSame(
            "<?php if(\\in_array(\\wp_get_environment_type(), \\Illuminate\\Support\\Arr::flatten(['local', 'staging']), true)): ?>x <?php endif; ?>",
            $this->compiler->compileString("@env('local', 'staging')x @endenv")
        );

        $this->assertSame(
            "<?php if(\\wp_get_environment_type() === 'production'): ?>x <?php endif; ?>",
            $this->compiler->compileString('@production()x @endproduction')
        );
    }

    public function testViteRegistersEntriesThroughSnap(): void
    {
        $this->assertSame(
            "<?php \\Snap\\Utils\\Vite::register(['resources/assets/js/theme.js', 'resources/assets/css/main.css']); ?>",
            $this->compiler->compileString("@vite(['resources/assets/js/theme.js', 'resources/assets/css/main.css'])")
        );

        $this->assertSame(
            "<?php \\Snap\\Utils\\Vite::register('resources/assets/js/theme.js'); ?>",
            $this->compiler->compileString("@vite('resources/assets/js/theme.js')")
        );
    }

    public function testViteRequiresAnEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->compiler->compileString('@vite');
    }

    public function testNoFrameworkHelpersAreCompiled(): void
    {
        $compiled = $this->compiler->compileString(
            "@auth @endauth @guest @endguest @can('a') @endcan @csrf @inject('a', 'b') @error('a') @enderror @env('a') @endenv @vite('a.js')"
        );

        $this->assertStringNotContainsString('app(', $compiled);
        $this->assertStringNotContainsString('auth()', $compiled);
    }

    public function testComponentClassesAreGuessedFromThemeNamespace(): void
    {
        $tags = new ComponentTagCompiler([], [], $this->compiler);

        $this->assertSame('Theme\\Components\\NoContent', $tags->guessClassName('no-content'));

        ComponentTagCompiler::setComponentNamespace('\\Other\\Components');
        $this->assertSame('Other\\Components\\Forms\\Input', $tags->guessClassName('forms.input'));

        ComponentTagCompiler::setComponentNamespace('\\Theme\\Components\\');
    }
}
