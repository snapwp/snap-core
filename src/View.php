<?php

namespace Snap\Core;

class View
{
    private $current_view = null;

	public function render($slug, $name = '')
	{
		// When Snap first boots up, it starts the output buffer. Now we have a matched view, we can flush any modules (such as the page <head>).
	    ob_end_flush();

        $this->current_view = $this->get_template_name($slug, $name);

	    include(locate_template('templates/views/' . $this->current_view));
	}

	/**
     * Fetch and display a template module.
     *
     * @since  1.0.0
     *
     * @param  string $slug     The slug for the generic template.
     * @param  string $name     Optional. The name of the specialised template.
     * @param  mixed  $data     Optional. Additional data to pass to a module. Available in the module as $data. Useful for PHP loops.
     *                          It is important to note that nothing is done to destroy/restore the current wordpress loop.
     * @param  bool   $extract  Optional. Whether to extract() $data or not.
     */
    public function module($slug, $name = '', $data = null, $extract = false)
    {
        if ($data !== null) {
            if (is_array($data) && $extract === true) {
                extract($data);
            }

            include(locate_template('templates/modules/' . $this->get_template_name($slug, $name));
        } else {
            unset($data, $extract);
            get_template_part('templates/modules/' . $slug, $name);
        }
    }

    /**
     * Runs the standard WP loop, and renders a module for each post.
     *
     * A replacement for the standard have_posts loop that also works on custom WP_Query objects,
     * and allows easy module choice for each iteration.
     *
     * @since 1.0.0
     *
     * @param  string   $module   Optional. The module name to render for each post.
     *                            If null, then defaults to post-type/{post type}.php.
     * @param  array    $module   Optional. An array of overrides.
     *                            Keys are the iteration index to apply the override, and values are the module to load instead of $module.
     *                            There is also a special key 'alternate' which will load the value on every odd iteration.
     * @param  WP_Query $wp_query Optional. An optional custom WP_Query to loop through. Defaults to the global WP_Query instance.
     */
    public function loop($module = null, $module_overrides = null, $wp_query = null)
    {
        // Use either the global or supplied WP_Query object.
        if ($wp_query instanceof WP_Query) {
            $wp_query = $wp_query;
        } else {
            global $wp_query;
        }

        $count = 0;

        // Render normal loop using our $wp_query value.
        if ($wp_query->have_posts()) {
            while ($wp_query->have_posts()) {
                $wp_query->the_post();

                // Work out what module to render.
                if (is_array($module_overrides) && isset($module_overrides[ $count ])) {
                    // An override is present, so load that instead.
                    $this->module($module_overrides[ $count ]);
                } elseif (is_array($module_overrides)
                    && isset($module_overrides['alternate'])
                    && $count % 2 !== 0
                ) {
                    // An override is present, so load that instead.
                    $this->module($module_overrides['alternate']);
                } elseif ($module === null) {
                    // Load the default module for this content type.
                    $this->module('post-type/' . get_post_type());
                } else {
                    // Load the supplied default module.
                    $this->module($module);
                }

                $count++;
            }
        } else {
            $this->module('post-type/none');
        }

        wp_reset_postdata();
    }

    /**
     * Returns the current tevie wtemplate name.
     *
     * @since 1.0.0
     * 
     * @return string|null Returns null if called before a view has been dispatched.
     */
    public function get_current_view()
    {
        return $this->current_view;
    }

    /**
     * Generate the template file name from the slug and name.
     *
     * @since 1.0.0
     * 
     * @param  string $slug The slug for the generic template.
     * @param  string $name Optional. The name of the specialised template.
     * @return string
     */
    private function get_template_name($slug, $name = '')
    {
        $name = (string) $name;
        $slug = str_replace([ 'templates/views/', '.php' ], '', $slug);

        $template = "{$slug}.php";

        if ( '' !== $name ) {
            $template = "{$slug}-{$name}.php";
        }

        return $template;
    }
}