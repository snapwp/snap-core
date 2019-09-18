<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static \Snap\Http\Validation\Validator make($data = null, array $rules = [], array $messages = [])
 * @method static \Snap\Http\Validation\Validator setMessages(array $messages = [])
 * @method static \Snap\Http\Validation\Validator setRules(array $rule_set = [])
 * @method static \Snap\Http\Validation\Validator setAliases(array $aliases = [])
 * @method static \Snap\Http\Validation\Validator setTranslations(array $translations)
 * @method static bool isValid()
 * @method static array getErrors($key = null, string $format = ':message')
 * @method static int getErrorCount()
 * @method static array getAllErrors(string $format = ':message')
 * @method static bool hasErrors(string $key)
 * @method static \Rakit\Validation\ErrorBag errors()
 * @method static array getValidatedData()
 * @method static array getValidData()
 * @method static array getInvalidData()
 * @method static \Snap\Http\Validation\Validator getRootInstance()
 *
 * @see \Snap\Http\Validation\Validator
 */
class Validator
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Http\Validation\Validator::class;
    }
}
