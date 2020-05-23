<?php

namespace Snap\Services;

/**
 * Allow static access to the Config service.
 *
 * @method static \Snap\Email\Email to(string|string[] $to)
 * @method static \Snap\Email\Email from(string $email, string $name = null)
 * @method static \Snap\Email\Email replyTo(string $email, string $name = null)
 * @method static \Snap\Email\Email cc(string|string[] $cc)
 * @method static \Snap\Email\Email bcc(string|string[] $bcc)
 * @method static \Snap\Email\Email subject(string $subject)
 * @method static \Snap\Email\Email body(string $message)
 * @method static \Snap\Email\Email headers(string|string[] $headers)
 * @method static \Snap\Email\Email isHtml(bool $isHtml)
 * @method static \Snap\Email\Email template(string $template, $data = [])
 * @method static \Snap\Email\Email with(string|array $key, mixed $value = null)
 * @method static \Snap\Email\Email attach(string|string[] $files)
 * @method static bool send()
 * @method static \Snap\Email\Email reset()
 * @method static string[] getTo()
 * @method static null|string getFrom()
 * @method static null|string getReplyTo()
 * @method static string[] getCc()
 * @method static string[] getBcc()
 * @method static null|string[] getSubject()
 * @method static null|string[] getMessage()
 * @method static array getAttachments()
 * @method static array getHeaders()
 * @method static \Snap\Email\Email getRootInstance()
 *
 * @see \Snap\Routing\Router
 */
class Email
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Email\Email::class;
    }
}
