<?php

namespace Engelsystem\Mail;

use Engelsystem\Helpers\Translation\Translator;
use Engelsystem\Models\User\User;
use Engelsystem\Renderer\Renderer;
use Engelsystem\Application;
use Symfony\Component\Mailer\MailerInterface;
use GuzzleHttp\Client as GuzzleClient;

class EngelsystemMailer extends Mailer
{
    /** @var Renderer|null */
    protected $view;

    /** @var Translator|null */
    protected $translation;

    /** @var string */
    protected $subjectPrefix = null;

    /**
     * @param MailerInterface $mailer
     * @param Renderer|null   $view
     * @param Translator|null $translation
     * @param GuzzleClient $guzzle
     * @param Application $app
     */
    public function __construct(
        MailerInterface $mailer,
        GuzzleClient $guzzle,
        Application $app,
        Renderer $view = null,
        Translator $translation = null
    ) {
        parent::__construct($mailer, $guzzle, $app);

        $this->translation = $translation;
        $this->view = $view;
    }

    /**
     * @param string|string[]|User $to
     * @param string               $subject
     * @param string               $template
     * @param array                $data
     * @param string|null          $locale
     */
    public function sendViewTranslated(
        $to,
        string $subject,
        string $template,
        array $data = [],
        ?string $locale = null
    ): void {
        $userId = null;

        if ($to instanceof User) {
            $userId = $to->id;
            $locale = $locale ?: $to->settings->language;
            $to = $to->contact->email ? $to->contact->email : $to->email;
        }

        $activeLocale = null;
        if ($locale && $this->translation && isset($this->translation->getLocales()[$locale])) {
            $activeLocale = $this->translation->getLocale();
            $this->translation->setLocale($locale);
        }

        $subject = $this->translation ? $this->translation->translate($subject, $data) : $subject;
        $this->sendView($to, $subject, $template, $userId, $data);

        if ($activeLocale) {
            $this->translation->setLocale($activeLocale);
        }
    }

    /**
     * Send a template
     *
     * @param string|string[] $to
     * @param string          $subject
     * @param string          $template
     * @param array           $data
     * @param int             $userID
     */
    public function sendView($to, string $subject, string $template, $userID, array $data = []): void
    {
        $body = $this->view->render($template, $data);

        if ($userID != null) {
            $this->sendTelegram($userID, $body);
        }

        $this->send($to, $subject, $body);
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
        if ($this->subjectPrefix) {
            $subject = sprintf("[%s] %s", $this->subjectPrefix, trim($subject));
        }

        parent::send($to, $subject, $body);
    }

    /**
     * @return string
     */
    public function getSubjectPrefix(): string
    {
        return $this->subjectPrefix;
    }

    /**
     * @param string $subjectPrefix
     */
    public function setSubjectPrefix(string $subjectPrefix)
    {
        $this->subjectPrefix = $subjectPrefix;
    }
}
