<?php

namespace Snap\Templating\Blade;

use Illuminate\View\Compilers\BladeCompiler;

/**
 * Blade compiler which swaps Laravel framework helpers for WordPress aware handlers.
 *
 * Laravel compiles directives such as @auth and @can into calls to auth(), app() etc. which only exist within
 * the full framework. These are instead compiled into calls to the handlers provided by the view factory.
 *
 * @see \Snap\Templating\Blade\Concerns\ProvidesHandlers
 */
class Compiler extends BladeCompiler
{
    /**
     * Compile the auth statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileAuth($guard = null)
    {
        $guard = \is_null($guard) ? '()' : $guard;

        return "<?php if(\$__env->authHandler{$guard}): ?>";
    }

    /**
     * Compile the else-auth statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileElseAuth($guard = null)
    {
        $guard = \is_null($guard) ? '()' : $guard;

        return "<?php elseif(\$__env->authHandler{$guard}): ?>";
    }

    /**
     * Compile the guest statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileGuest($guard = null)
    {
        $guard = \is_null($guard) ? '()' : $guard;

        return "<?php if(! \$__env->authHandler{$guard}): ?>";
    }

    /**
     * Compile the else-guest statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileElseGuest($guard = null)
    {
        $guard = \is_null($guard) ? '()' : $guard;

        return "<?php elseif(! \$__env->authHandler{$guard}): ?>";
    }

    /**
     * Compile the can statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileCan($expression)
    {
        return "<?php if (\$__env->canHandler{$expression}): ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileCannot($expression)
    {
        return "<?php if (! \$__env->canHandler{$expression}): ?>";
    }

    /**
     * Compile the canany statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileCanany($expression)
    {
        return "<?php if (\$__env->canAnyHandler{$expression}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileElsecan($expression)
    {
        return "<?php elseif (\$__env->canHandler{$expression}): ?>";
    }

    /**
     * Compile the else-cannot statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileElsecannot($expression)
    {
        return "<?php elseif (! \$__env->canHandler{$expression}): ?>";
    }

    /**
     * Compile the else-canany statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileElsecanany($expression)
    {
        return "<?php elseif (\$__env->canAnyHandler{$expression}): ?>";
    }

    /**
     * Compile the inject statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileInject($expression)
    {
        $segments = \explode(',', \preg_replace("/[\(\)]/", '', $expression));

        $variable = \trim($segments[0], " '\"");

        $service = \trim($segments[1]);

        return "<?php \${$variable} = \$__env->injectHandler({$service}); ?>";
    }

    /**
     * Compile the error statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileError($expression)
    {
        $expression = $this->stripParentheses($expression);

        return '<?php if ($__env->errorHandler(' . $expression . ')) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__env->errorHandler(' . $expression . '); ?>';
    }

    /**
     * Compile the enderror statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileEnderror($expression)
    {
        return '<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif; ?>';
    }

    /**
     * Compile the CSRF statements into valid PHP.
     *
     * @return string
     */
    protected function compileCsrf()
    {
        return '<?php echo \'<input type="hidden" name="_token" value="\' . \esc_attr($__env->getCsrfToken()) . \'">\'; ?>';
    }

    /**
     * Compile the env statements into valid PHP.
     *
     * Compares against the WordPress environment type (WP_ENVIRONMENT_TYPE).
     *
     * @param string $environments
     * @return string
     */
    protected function compileEnv($environments)
    {
        $environments = $this->stripParentheses($environments);

        return "<?php if(\in_array(\wp_get_environment_type(), \Illuminate\Support\Arr::flatten([{$environments}]), true)): ?>";
    }

    /**
     * Compile the production statements into valid PHP.
     *
     * @return string
     */
    protected function compileProduction()
    {
        return "<?php if(\wp_get_environment_type() === 'production'): ?>";
    }

    /**
     * Compile the vite statements into valid PHP.
     *
     * Rather than printing tags, entries are enqueued through WordPress so they are output by wp_head/wp_footer.
     * The directive must therefore be used before @wphead.
     *
     * @param string|null $arguments
     * @return string
     */
    protected function compileVite($arguments)
    {
        $arguments = \trim((string) $arguments);

        if ($arguments === '' || $arguments === '()') {
            throw new \InvalidArgumentException('The @vite directive requires at least one entry point.');
        }

        return "<?php \Snap\Utils\Vite::register{$arguments}; ?>";
    }

    /**
     * Add an instance of the blade echo handler to the start of the compiled string.
     *
     * @param string $result
     * @return string
     */
    protected function addBladeCompilerVariable($result)
    {
        return "<?php \$__bladeCompiler = \$__env->getCompiler(); ?>" . $result;
    }

    /**
     * Compile the component tags using the Snap component tag compiler.
     *
     * @param string $value
     * @return string
     */
    protected function compileComponentTags($value)
    {
        if (! $this->compilesComponentTags) {
            return $value;
        }

        return (new ComponentTagCompiler(
            $this->classComponentAliases,
            $this->classComponentNamespaces,
            $this
        ))->compile($value);
    }
}
