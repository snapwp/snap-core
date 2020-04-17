<?php

namespace Snap\Templating\Blade;

trait SnapDirectives
{
    /**
     * A wrapper for the Menu::getNavMenu method which allows foreach syntax in a template.
     *
     * @param string $expression
     * @return string
     * @see \Snap\Utils\Menu::getNavMenu()
     *
     */
    public function compileSimplemenu(string $expression): string
    {
        \preg_match('/([^\s]*)\s?as\s?(.*)/', $expression, $matches);

        $iteratee = \trim($matches[1]);
        $iteration = \trim($matches[2]);
        $init_loop = "\$__currentLoopData = \Snap\Utils\Menu::getNavMenu($iteratee); \$__env->addLoop(\$__currentLoopData);";
        $iterate_loop = '$__env->incrementLoopIndices(); $loop = $__env->getLastLoop();';

        return "<?php {$init_loop} foreach(\$__currentLoopData as {$iteration}): {$iterate_loop} ?>";
    }

    /**
     * Close simplemenu.
     *
     * @return string
     */
    public function compileEndsimplemenu(): string
    {
        return '<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>';
    }

    /**
     * Add pagination class render to Blade.
     *
     * @param string $input
     * @return string
     * @see \Snap\Templating\Pagination
     *
     */
    public function compilePaginate(string $input): string
    {
        $input = empty($input) ? '[]' : $input;

        return '<?php $pagination = \Snap\Services\Container::resolve(
            \Snap\Templating\Pagination::class,
            [\'args\' => ' . $input . ',]
	    );

        echo $pagination->get();
        ?>';
    }

    /**
     * Add a directive to mimic the loop.
     *
     * @param $input
     * @return string
     */
    public function compileLoop($input)
    {
        $init_loop = '$__loop_query = $wp_query;';

        if (!empty($input)) {
            $init_loop = ' $__loop_query = ' . $input . ';';
        }

        $init_loop .= '$__currentLoopData = $__loop_query->posts; $__env->addLoop($__currentLoopData); global $post;';
        $iterate_loop = '$__env->incrementLoopIndices(); $loop = $__env->getLastLoop();';

        return "<?php {$init_loop} while (\$__loop_query->have_posts()): \$__loop_query->the_post(); {$iterate_loop} ?>";
    }

    /**
     * End loop directive.
     *
     * @return string
     */
    public function compileEndloop()
    {
        return '<?php wp_reset_postdata(); endwhile; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>';
    }

    /**
     * Shortcut to partials/post-type/get_post_type().
     *
     * @return string
     */
    public function compilePosttypepartial()
    {
        return '<?php global $post; echo $__env->make(\'partials.post-type.\' . \get_post_type(), \Tightenco\Collect\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\', \'__loop_query\', \'__currentLoopData\', \'obLevel\'])); ?>';
    }
}
