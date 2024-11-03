<?php

namespace Engelsystem\Mail;

use GuzzleHttp\Client as GuzzleClient;
use Engelsystem\Application;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class Mailer
{
    /** @var MailerInterface */
    protected $mailer;

    /** @var string */
    protected $fromAddress = "";

    /** @var string */
    protected $fromName = null;

    /** @var GuzzleClient */
    protected $guzzleClient;

    /** @var Application */
    protected $app;

    protected $telegram_base;
    protected $telegram_api_key;

    public function __construct(MailerInterface $mailer, GuzzleClient $g, Application $app)
    {
        $this->mailer = $mailer;
        $this->guzzleClient = $g;
        $this->app = $app;

        /** @var Config $config */
        $config = $app->get("config");
        $emailConfig = $config->get("email");

        $this->telegram_base = $emailConfig["telegram_base_url"];
        $this->telegram_api_key = $emailConfig["telegram_api_key"];
    }

    public function sendTelegram(int $to, string $body): void
    {
        $msg = [
            "to" => "$to",
            "message" => $body,
        ];

        $uri = "{$this->telegram_base}/bot/message";
        $this->guzzleClient->post($uri, ["json" => $msg, "headers" => ["x-api-key" => $this->telegram_api_key]]);
    }

    /**
     * Send the mail
     *
     * @param string|string[] $to
     * @param string          $subject
     * @param string          $body
     */
    public function send($to, string $subject, string $body): void
    {
        $message = (new Email())
            ->to(...(array) $to)
            ->from(sprintf("%s <%s>", $this->fromName, $this->fromAddress))
            ->subject($subject)
            ->text($body);

        $this->mailer->send($message);
    }

    /**
     * @return string
     */
    public function getFromAddress(): string
    {
        return $this->fromAddress;
    }

    /**
     * @param string $fromAddress
     */
    public function setFromAddress(string $fromAddress)
    {
        $this->fromAddress = $fromAddress;
    }

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     */
    public function setFromName(string $fromName)
    {
        $this->fromName = $fromName;
    }
}
