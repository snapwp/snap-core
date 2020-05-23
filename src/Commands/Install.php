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
     * Install a theme after create-project used in Composer.
     *
     * @param \Composer\Script\Event $event
     */
    public static function run($event): void
    {
        static::$io = $event->getIO();

        // Display snap logo!
        static::writeIntro();
    }

    /**
     * Output welcome message.
     */
    private static function writeIntro(): void
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
}
