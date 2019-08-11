<?php

namespace Snap\Commands;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Snap\Core\Snap;
use Symfony\Component\Process\Process;

/**
 * Installs additional features after a snap is pulled in via composer.
 */
class Install
{
    /**
     * The Composer IO instance.
     *
     * @var \Composer\IO\ConsoleIO
     */
    private static $io;

    /**
     * The Composer instance.
     *
     * @var \Composer\Composer
     */
    private static $composer;

    /**
     * The strategies to choose from.
     *
     * @var string
     */
    private static $root;

    /**
     * The strategies to choose from.
     *
     * @var array
     */
    private static $strategies = [
        'Snap Default',
        'Blade',
    ];

    /**
     * Install a theme after create-project used in Composer.
     *
     * @param \Composer\Script\Event $event
     */
    public static function run($event)
    {
        static::$io = $event->getIO();
        static::$composer = $event->getComposer();

        // Set the root theme folder dir.
        static::setRoot();

        // Display snap logo!
        static::writeIntro();

        $strategy = static::$io->select(
            "\n<comment>Please choose a templating system for your theme:</comment>",
            static::$strategies,
            0
        );

        switch ($strategy) {
            case '0':
                static::$io->write("<info>Setup finished.\nEnjoy SnapWP!</info>");
                exit;
                break;

            case '1':
                static::installBlade();
                break;
        }
    }

    /**
     * Install the Blade package and publish.
     */
    private static function installBlade()
    {
        $install = new Process('composer require snapwp/snap-blade -n');

        static::$io->write("\n<comment>Downloading latest snapwp/snap-blade package.\nPlease wait...</comment>");

        try {
            $install->mustRun(
                function ($type, $buffer) {
                    echo ">>> $buffer";
                }
            );

            static::$io->write('<info>Downloaded successfully!</info>');
        } catch (Exception $exception) {
            static::$io->write($exception->getMessage());
            static::$io->write("<error>Could not download.</error>");
            exit;
        }

        static::addBladeToConfig();

        static::clearTemplates();

        // Publish the snap package.
        /** @noinspection PhpParamsInspection */
        $publish = new Process(
            \sprintf(
                'cd %s/vendor/bin && snap publish --package=\Snap\Blade\BladeServiceProvider --root=%s',
                static::$root,
                static::$root
            )
        );

        try {
            $publish->mustRun();

            static::$io->write('<info>Blade package successfully published.</info>');
        } catch (Exception $exception) {
            static::$io->write($exception->getMessage());
            static::$io->write("<error>Could not publish the blade package.</error>");
            exit;
        }
    }

    /**
     * Output welcome message.
     */
    private static function writeIntro()
    {
        static::$io->write(
            '
 _______                     ________ ______ 
|     __|.-----.---.-.-----.|  |  |  |   __ \
|__     ||     |  _  |  _  ||  |  |  |    __/
|_______||__|__|___._|   __||________|___|   
                     |__|'
        );

        static::$io->write("\nVersion " . Snap::VERSION);
    }

    /**
     * Clears all default templates from the theme.
     */
    private static function clearTemplates()
    {
        $dir_iterator = new RecursiveDirectoryIterator(static::$root . '/resources/templates');
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if (!\is_dir($file) && \strpos($file, '_example.php') === false) {
                @\unlink($file);
            }
        }
    }

    /**
     * Add the provider to the services config.
     *
     * Crude but gets the job done.
     */
    private static function addBladeToConfig()
    {
        $config = \file_get_contents(static::$root . '/config/services.php');

        // Bail if already present.
        if (\strpos($config, 'BladeServiceProvider') !== false) {
            return;
        }

        $providers = \preg_replace(
            '/(\'providers\'\s*\=>\s*\[)([^]]*)(\])/m',
            "$1$2\tSnap\Blade\BladeServiceProvider::class,\n\t$3",
            $config
        );

        \file_put_contents(static::$root . '/config/services.php', $providers);
    }

    /**
     * Sets the root of the theme.
     */
    private static function setRoot()
    {
        static::$root = \dirname(static::$composer->getConfig()->get('vendor-dir'));
    }
}
