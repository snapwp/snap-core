<?php

namespace Snap\Templating\Blade;

trait WordpressDirectives
{
    /**
     * Implement wp_head().
     *
     * @return string
     */
    public function compileWphead(): string
    {
        return '<?php wp_head(); ?>';
    }

    /**
     * Implement wp_footer().
     *
     * @return string
     */
    public function compileWpfooter(): string
    {
        return '<?php wp_footer(); ?>';
    }

    /**
     * Implement dynamic_sidebar().
     *
     * @param string $input
     * @return string
     */
    public function compileSidebar(string $input): string
    {
        return "<?php dynamic_sidebar({$input}); ?>";
    }

    /**
     * Implement do_action().
     *
     * @param string $input
     * @return string
     */
    public function compileAction(string $input): string
    {
        return "<?php do_action({$input}); ?>";
    }

    /**
     * Implement the_content().
     *
     * @return string
     */
    public function compileThecontent(): string
    {
        return "<?php the_content(); ?>";
    }

    /**
     * Implement the_excerpt().
     *
     * @return string
     */
    public function compileTheexcerpt(): string
    {
        return "<?php the_excerpt(); ?>";
    }

    /**
     * Implement wp_nav_menu().
     *
     * @param string $input
     * @return string
     */
    public function compileNavmenu(string $input): string
    {
        return "<?php wp_nav_menu({$input}); ?>";
    }

    /**
     * Implement get_search_form().
     *
     * @return string
     */
    public function compileSearchform(): string
    {
        return "<?php get_search_form(); ?>";
    }

    /**
     * Implement setup_postdata().
     *
     * @param string $input
     * @return string
     */
    public function compileSetpostdata(string $input): string
    {
        return '<?php setup_postdata($GLOBALS[\'post\'] =& ' . $input . '); ?>';
    }

    /**
     * Implement wp_reset_postdata().
     *
     * @return string
     */
    public function compileResetpostdata(): string
    {
        return '<?php wp_reset_postdata(); ?>';
    }
}
