<?php

namespace Snap\Utils;

use Snap\Core\Concerns\ManagesHooks;
use Snap\Exceptions\EmailException;
use Snap\Services\Blade;

class Email
{
    use ManagesHooks;

    /**
     * @var string[]
     */
    private $to = [];

    /**
     * @var array
     */
    private $cc = [];

    /**
     * @var array
     */
    private $bcc = [];

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $replyTo;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string|callable
     */
    private $message;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var bool
     */
    private $isHtml = false;

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * @param string|string[] $to
     * @return $this
     */
    public function to($to): Email
    {
        if (\is_array($to)) {
            $this->to = $to;
        } else {
            $this->to = [$to];
        }

        return $this;
    }

    /**
     * Set the from email, and optionally the from name.
     *
     * @param string      $email
     * @param string|null $name
     * @return $this
     */
    public function from(string $email, string $name = null): Email
    {
        if ($name === null) {
            $this->from = $email;
        } else {
            $this->from = "$name <$email>";
        }
        return $this;
    }

    /**
     * Set the reply to. Defaults to the from address.
     *
     * @param string      $email
     * @param string|null $name
     * @return $this
     */
    public function replyTo(string $email, string $name = null): Email
    {
        if ($name === null) {
            $this->replyTo = $email;
        } else {
            $this->replyTo = "$name <$email>";
        }
        return $this;
    }

    /**
     * Set cc address(es).
     *
     * @param string|string[] $cc
     * @return $this
     */
    public function cc($cc): Email
    {
        if (\is_array($cc)) {
            $this->cc = $cc;
        } else {
            $this->cc = [$cc];
        }

        return $this;
    }

    /**
     * Set bcc address(es).
     *
     * @param string|string[] $bcc
     * @return $this
     */
    public function bcc($bcc): Email
    {
        if (\is_array($bcc)) {
            $this->bcc = $bcc;
        } else {
            $this->bcc = [$bcc];
        }

        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function subject(string $subject): Email
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set a string as the email body.
     *
     * @param string $message
     * @return $this
     */
    public function body(string $message): Email
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Set the email headers. Note that Cc, Bc, Reply-to, and From are set automatically.
     *
     * @param string|string[] $headers
     * @return $this
     */
    public function headers($headers): Email
    {
        if (\is_array($headers)) {
            $this->headers = $headers;
        } else {
            $this->headers = [$headers];
        }

        return $this;
    }

    /**
     * Set whether to send the email as HTML or not.
     *
     * @param bool $isHtml
     * @return $this
     */
    public function isHtml(bool $isHtml): Email
    {
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * Set the body from a template.
     *
     * @param string $template
     * @param array  $data
     * @return $this
     */
    public function template(string $template, $data = []): Email
    {
        $this->with($data);

        $this->message = static function ($data) use ($template) {
            return Blade::make($template, $data);
        };

        return $this;
    }

    /**
     * Provide data to the email template.
     *
     * @param array|string $key
     * @param mixed        $value
     * @return $this
     */
    public function with($key, $value = null): Email
    {
        if (\is_array($key)) {
            $this->data = \array_merge($this->data, $key);
            return $this;
        }

        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Attach a file or array of files. File paths must be absolute.
     *
     * @param string|array $files
     * @return $this
     *
     * @throws \Snap\Exceptions\EmailException
     */
    public function attach($files): Email
    {
        if (\is_array($files)) {
            foreach ($files as $path) {
                if (!\file_exists($path)) {
                    throw new EmailException("Invalid attachment path: $files");
                }

                $this->attachments[] = $path;
            }
            return $this;
        }

        if (!\file_exists($files)) {
            throw new EmailException("Invalid attachment path: $files");
        }

        $this->attachments = [$files];
        return $this;
    }

    /**
     * Send the email.
     *
     * @return bool
     *
     * @throws \Snap\Exceptions\EmailException
     */
    public function send(): bool
    {
        if (empty($this->getTo())) {
            throw new EmailException('You must set at least 1 recipient');
        }

        if (empty($this->getSubject())) {
            throw new EmailException('You must set a subject');
        }

        if ($this->isHtml) {
            $this->addFilter('wp_mail_content_type', 'setHtmlContentType');
        }

        $result = \wp_mail(
            $this->getTo(),
            $this->getSubject(),
            $this->getMessage(),
            $this->getHeaders(),
            $this->getAttachments()
        );

        if ($this->isHtml) {
            $this->removeFilter('wp_mail_content_type', 'setHtmlContentType');
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function reset(): Email
    {
        $this->to = [];
        $this->from = null;
        $this->subject = null;
        $this->replyTo = null;
        $this->message = null;
        $this->cc = [];
        $this->bcc = [];
        $this->data = [];
        $this->isHtml = false;
        $this->headers = [];
        $this->attachments = [];
        return $this;
    }

    /**
     * @return string[]
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return null|string
     */
    public function getFrom(): ?string
    {
        return apply_filters('wp_mail_from', $this->from);
    }

    /**
     * @return null|string
     */
    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    /**
     * @return string[]
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @return string[]
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return null|string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return null|string
     */
    public function getMessage(): ?string
    {
        if ($this->message !== null && !\is_string($this->message)) {
            return ($this->message)($this->data);
        }

        return $this->message;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = $this->headers;

        if (!empty($this->getCc())) {
            $headers[] = \sprintf("Cc: %s", \implode(',', $this->getCc()));
        }

        if (!empty($this->getBcc())) {
            $headers[] = \sprintf("Bcc: %s", \implode(',', $this->getBcc()));
        }

        if (!empty($this->getFrom())) {
            $headers[] = \sprintf("From: %s", $this->getFrom());
        }

        if (!empty($this->getReplyTo())) {
            $headers[] = \sprintf("Reply-To: %s", $this->getReplyTo());
        }

        return $headers;
    }

    /**
     * @return string
     */
    public function setHtmlContentType(): string
    {
        return 'text/html';
    }
}
