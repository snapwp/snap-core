<?php

namespace Snap\Utils;

class Vite
{
    private static bool $isActive = false;
    private static bool $enqueuedVite = false;
    private static ?string $viteServer = null;
    private static bool $addedHooks = false;
    private static bool $isDev = false;

    /**
     * Add a script src.
     */
    public static function registerScript(string $path): void
    {
        self::$isActive = true;
        self::enqueueVite();
        self::addActions();

        $url = self::$isDev ? self::$viteServer . $path : snap_get_asset_url($path);

        wp_enqueue_script('module/' . $path, $url, [], null, true);

        // If the styles were imported directly into js, include them when building
        if (!self::$isDev && Theme::getManifest() && isset(Theme::getManifest()[$path]->css)) {
            foreach (Theme::getManifest()[$path]->css as $stylesheet) {
                wp_enqueue_style('module/' . $path, snap_get_asset_url($stylesheet), [], null);
            }
        }
    }

    /**
     * Add a css/scss/less src.
     */
    public static function registerStyle(string $path): void
    {
        self::$isActive = true;
        self::enqueueVite();
        self::addActions();

        $url = self::$viteServer ? self::$viteServer . $path : snap_get_asset_url($path);

        wp_enqueue_style('module/' . $path, $url, [], null);
    }

    /**
     * Add editor css/scss/less src.
     */
    public static function registerEditorStyle(string $path): void
    {
        self::$isActive = true;
        self::enqueueVite();
        self::addActions();

        $url = self::$viteServer ? self::$viteServer . $path : snap_get_asset_url($path);

        add_editor_style($url);
    }

    /**
     * Return the Vite server URL if it is running.
     * @return string|null
     */
    public static function getViteServerUrl(): ?string
    {
        return self::$viteServer;
    }

    /**
     * Whether Vite is being used or not.
     */
    public static function isActive(): bool
    {
        return self::$isActive;
    }

    /**
     * Enqueue Vite client if the dev server is active.
     */
    private static function enqueueVite(): void
    {
        $hotFilePath = get_theme_file_path('/public/hot');

        if (!self::$enqueuedVite && file_exists($hotFilePath)) {
            self::$viteServer = trailingslashit(file_get_contents($hotFilePath));
            self::$isDev = true;

            add_action('wp_print_styles', function () {
                echo '<script type="module" src="' . Vite::getViteServerUrl() . '@vite/client"></script>';
            });

            self::$enqueuedVite = true;
        }

    }

    /**
     * Add WP actions
     */
    private static function addActions(): void
    {
        if (!self::$addedHooks) {
            add_action('script_loader_tag', static function($tag, $scriptPath) {
                if (str_starts_with($scriptPath, 'module/')) {
                    return str_replace('<script', '<script type="module"', $tag);
                }

                return $tag;
            }, 10, 2);
			
			add_action('style_loader_tag', static function($tag, $scriptPath) {
				if (str_starts_with($scriptPath, 'module/')) {
					return str_replace('<link', '<link crossorigin="anonymous"', $tag);
				}

				return $tag;
			}, 10, 2);
		}

		self::$addedHooks = true;
    }
}
