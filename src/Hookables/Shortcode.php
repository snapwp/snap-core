<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use Snap\Exceptions\Shortcode_Exception;

/**
 * A simple wrapper for auto registering shortcodes.
 */
class Shortcode extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @since 1.0.0
     * @var array
     */
    protected $actions = [
        'init' => 'register_shortcode',
    ];

    /**
     * Whether to run this shortcode on admin requests or not.
     *
     * @since 1.0.0
     * @var boolean
     */
    protected $admin = false;

    /**
     * The tag to register the shortcode with.
     *
     * If not present, then the snake cased class name is used instead.
     *
     * @since 1.0.0
     * @var null|string
     */
    protected $tag = null;

    /**
     * The allowed attributes for this shortcode.
     *
     * You can set defaults by providing values for your keys.
     *
     * @since 1.0.0
     * @var array
     */
    protected $attributes = [];

    /**
     * Register the child class as a shortcode and call it's handle() method.
     *
     * @since 1.0.0
     */
    final public function register_shortcode()
    {
        if (\method_exists($this, 'handle') === false) {
            throw new Shortcode_Exception(\get_class($this) . ' needs to declare a handle() method');
        }

        \add_shortcode($this->get_shortcode_name(), [$this, 'handler']);
    }

    /**
     * Run arguments through shortcode_atts, using a modified $attributes array, and pass to the child handle() method.
     *
     * @since  1.0.0
     *
     * @param  array $atts    Shortcode attributes.
     * @param  sting $content Any encapsulated shortcode content.
     * @return string Shortcode output.
     */
    public function handler($atts, $content)
    {
        $atts = \shortcode_atts($this->parse_attributes(), $atts);

        return $this->handle($atts, $content);
    }

    /**
     * Get the unqualified name of the current class and convert it to snake case for the shortcode name.
     *
     * Can be overwritten by setting the $tag property.
     *
     * @since  1.0.0
     *
     * @return string
     */
    private function get_shortcode_name()
    {
        if ($this->tag === null) {
            return $this->get_classname();
        }

        return $this->tag;
    }

    /**
     * Ensure that any $attributes without provided defaults, return as null if not provided by the user.
     *
     * @since  1.0.0
     *
     * @return array Normalised $attributes array.
     */
    private function parse_attributes()
    {
        foreach ($this->attributes as $key => $value) {
            if (\is_int($key)) {
                $this->attributes[ $value ] = null;
                unset($this->attributes[ $key ]);
            }
        }

        return $this->attributes;
    }
}
