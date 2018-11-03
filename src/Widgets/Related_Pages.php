<?php

namespace Snap\Widgets;

use Snap\Utils\View_Utils;
use WP_Widget;

/**
 * Outputs a one dimensional list of related pages.
 *
 * Related pages are all sibling, parent, and child pages within the current hierarchical tree.
 *
 * @since  1.0.0
 */
class Related_Pages extends WP_Widget
{
    /**
     * Related_Pages arguments array.
     *
     * @since 1.0.0
     * @var array
     */
    private $args = [];

    /**
     * The top level parent page ID.
     *
     * @since 1.0.0
     * @var int|null
     */
    private $parent_page_id = null;

    /**
     * Array of pages to display.
     *
     * @since 1.0.0
     * @var array
     */
    private $pages = [];

    /**
     * Boot up and declare this class as a widget.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct(
            'snap_related_pages',
            __('Related Pages', 'snap'),
            [
                'description' => __('Output list of all pages in the current hierarchy. Outputs nothing if none are found.', 'snap'),
            ]
        );
    }

    /**
     * The widget output.
     *
     * @since 1.0.0
     *
     * @param  array $args     The sidebar args.
     * @param  array $instance The instance args.
     */
    public function widget($args, $instance)
    {
        $this->parent_page_id = View_Utils::get_top_level_parent_id();

        // We are on a 404 or search route.
        if ($this->parent_page_id === null) {
            return;
        }

        $this->args = wp_parse_args(
            $instance,
            /**
             * Edit the default Related_Pages default arguments.
             *
             * @since  1.0.0
             *
             * @param  array $defaults {
             *     The default arguments.
             *
             *     @type string $container_start   Container start HTML. Default '<ul role="navigation">'.
             *     @type string $container_end     Container end HTML. Default '</ul>'.
             *     @type string $li_class          Classes to be applied to all <li> tags. Default ''.
             *     @type string $li_active_class   Classes to be applied to the active <li> tag. Default ''.
             *     @type string $link_class        Classes to be applied to all <a> tags. Default ''.
             *     @type string $link_active_class Classes to be applied to the active <a> tag. Default 'active'.
             *     @type string $before_link       Additional content before each <a> tag. Default ''.
             *     @type string $after_link        Additional content after each <a> tag. Default ''.
             *     @type string $before_text       Additional content before each <a> tag's content. Default ''.
             *     @type string $after_text        Additional content after each <a> tag's content. Default ''.
             *     @type bool   $show_parent       Whether to show the top level page in the output. Default true.
             * }
             * @return array
             */
            apply_filters('snap_related_pages_widget_defaults', $this->get_defaults())
        );

        // Populate $pages array.
        $this->get_pages();

        if (\count($this->pages) > 0) {
            $title = apply_filters('widget_title', $instance['title']);
     
            echo $args['before_widget'];

            if (! empty($title)) {
                echo $args['before_title'] . $title . $args['after_title'];
            }
             
            $this->render();

            echo $args['after_widget'];
        }
    }

    /**
     * Output the admin widget form.
     *
     * @since 1.0.0
     *
     * @param  array $instance The current instance args.
     */
    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'wpb_widget_domain');
        }

        if (isset($instance['show_parent'])) {
            $show_parent = $instance['show_parent'];
        } else {
            $show_parent = 0;
        }
        ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>
            <p>
                <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('show_parent'); ?>" name="<?php echo $this->get_field_name('show_parent'); ?>" <?php checked($show_parent); ?>>
                &nbsp;<label for="<?php echo $this->get_field_id('show_parent'); ?>"><?php _e('Include the top level page', 'snap'); ?></label>
            </p>
        <?php
    }
         
    /**
     * Update the widget upon saving.
     *
     * @since  1.0.0
     *
     * @param  array $new_instance Submitted instance args.
     * @param  array $old_instance Old instance args.
     * @return array New instance args to save.
     */
    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = ( ! empty($new_instance['title']) ) ? \strip_tags($new_instance['title']) : '';
        $instance['show_parent'] = ( ! empty($new_instance['show_parent']) ) ? true : false;
        return $instance;
    }

    /**
     * Populate $this->pages.
     *
     * @since  1.0.0
     */
    private function get_pages()
    {
        $this->pages = get_pages(['child_of' => $this->parent_page_id, 'sort_column' => 'menu_order']);
    }

    /**
     * Output the related pages list HTML.
     *
     * @since  1.0.0
     */
    private function render()
    {
        $str = $this->args['container_start'];

        if ($this->args['show_parent'] && $this->parent_page_id !== false) {
            $str .= $this->get_parent_link_html();
        }

        foreach ($this->pages as $page) {
            $str .= $this->get_link_html($page);
        }

        $str .= $this->args['container_end'];
        echo $str;
    }

    /**
     * Construct the HTML for the parent link.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_parent_link_html()
    {
        global $post;

        if (! empty($post) && $this->parent_page_id == $post->ID) {
            $li_class = $this->args['li_active_class'] . ' ' . $this->args['li_class'];
            $link_class = $this->args['link_active_class'] . ' ' . $this->args['link_class'];
        } else {
            $li_class = $this->args['li_class'];
            $link_class = $this->args['link_class'];
        }

        return \sprintf(
            '<li class="%s">%s<a class="%s" href="%s">%s%s%s</a>%s</li>',
            $li_class,
            $this->args['before_link'],
            $link_class,
            get_the_permalink($this->parent_page_id),
            $this->args['before_text'],
            get_the_title($this->parent_page_id),
            $this->args['after_text'],
            $this->args['after_link']
        );
    }

    /**
     * Construct the HTML for the a non-parent link.
     *
     * @since  1.0.0
     *
     * @param \WP_Post $page The current post object.
     * @return string
     */
    private function get_link_html($page)
    {
        global $post;

        if (! empty($post) && $page->ID == $post->ID) {
            $li_class = $this->args['li_active_class'] . ' ' . $this->args['li_class'];
            $link_class = $this->args['link_active_class'] . ' ' . $this->args['link_class'];
        } else {
            $li_class = $this->args['li_class'];
            $link_class = $this->args['link_class'];
        }

        return \sprintf(
            '<li class="%s">%s<a class="%s" href="%s">%s%s%s</a>%s</li>',
            $li_class,
            $this->args['before_link'],
            $link_class,
            get_the_permalink($page),
            $this->args['before_text'],
            get_the_title($page),
            $this->args['after_text'],
            $this->args['after_link']
        );
    }

    /**
     * Returns the widget default arguments.
     *
     * @since  1.0.0
     *
     * @return array
     */
    private function get_defaults()
    {
        return [
            'container_start' => '<ul role="navigation">',
            'container_end' => '</ul>',
            'li_class' => '',
            'li_active_class' => '',
            'link_class' => '',
            'link_active_class' => 'active',
            'before_link' => '',
            'after_link' => '',
            'before_text' => '',
            'after_text' => '',
            'show_parent' => true,
        ];
    }
}
