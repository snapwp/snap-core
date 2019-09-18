<?php

namespace Snap\Hookables;

use Snap\Core\Hookable;
use Snap\Exceptions\HookableException;

/**
 * A simple wrapper for auto registering shortcodes.
 *
 */
class Shortcode extends Hookable
{
    /**
     * Actions to add on init.
     *
     * @var array
     */
    protected $actions = [
        'init' => 'registerShortcode',
    ];

    /**
     * Whether to run this shortcode on admin requests or not.
     *
     * @var boolean
     */
    protected $admin = false;

    /**
     * The tag to register the shortcode with.
     *
     * If not present, then the snake cased class name is used instead.
     *
     * @var null|string
     */
    protected $tag = null;

    /**
     * The allowed attributes for this shortcode.
     *
     * You can set defaults by providing values for your keys.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Register the child class as a shortcode and call it's handle() method.
     *
     * @throws HookableException
     */
    final public function registerShortcode()
    {
        if (\method_exists($this, 'handle') === false) {
            throw new HookableException(\get_class($this) . ' needs to declare a handle() method');
        }

        \add_shortcode($this->getShortcodeName(), [$this, 'handler']);
    }

    /**
     * Run arguments through shortcode_atts, using a modified $attributes array, and pass to the child handle() method.
     *
     * @param  array  $atts    Shortcode attributes.
     * @param  string $content Any encapsulated shortcode content.
     * @return string Shortcode output.
     */
    public function handler($atts, $content)
    {
        $atts = \shortcode_atts($this->parseAttributes(), $atts, $this->getShortcodeName());
        return $this->handle($atts, $content);
    }

    /**
     * Get the unqualified name of the current class and convert it to snake case for the shortcode name.
     *
     * Can be overwritten by setting the $tag property.
     *
     * @return string
     */
    private function getShortcodeName()
    {
        if ($this->tag === null) {
            return $this->getClassname();
        }

        return $this->tag;
    }

    /**
     * Ensure that any $attributes without provided defaults, return as null if not provided by the user.
     *
     * @return array Normalised $attributes array.
     */
    private function parseAttributes()
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
