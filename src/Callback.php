<?php

declare(strict_types=1);

namespace atk4\ui;

/**
 * Add this object to your render tree and it will expose a unique URL which, when
 * executed directly will perform a PHP callback that you set().
 *
 * Callback function run when triggered, i.e. when it's urlTrigger param value is present in the $_GET request.
 * The current callback will be set within the $_GET['__atk_callback'] and will be set to urlTrigger as well.
 *
 * $button = Button::addTo($layout);
 * $button->set('Click to do something')->link(
 *      Callback::addTo($button)
 *          ->set(function(){
 *              do_something();
 *          })
 *          ->getUrl()
 *  );
 */
class Callback extends View
{
    /** @var string Specify a custom GET trigger. */
    protected $urlTrigger;

    /** @var bool Create app sticky trigger. */
    public $isSticky = true;

    /** @var bool Allow this callback to trigger during a reload. */
    public $triggerOnReload = true;

    /**
     * Initialization.
     */
    public function init(): void
    {
        if (!$this->app) {
            throw new Exception('Callback must be part of a render tree');
        }

        parent::init();

        $this->setUrlTrigger($this->urlTrigger);
    }

    public function setUrlTrigger(string $trigger = null)
    {
        $this->urlTrigger = $trigger ?: $this->name;
        if ($this->isSticky) {
            $this->app->stickyGet($this->urlTrigger);
        }
    }

    public function getUrlTrigger(): string
    {
        return $this->urlTrigger;
    }

    /**
     * Executes user-specified action when call-back is triggered.
     *
     * @param \Closure $fx
     * @param array    $args
     *
     * @return mixed|null
     */
    public function set($fx = null, $args = null)
    {
        if ($this->isTriggered() && $this->canTrigger()) {
            $this->app->catch_runaway_callbacks = false;
            $t = $this->app->run_called;
            $this->app->run_called = true;
            $ret = $fx(...($args ?? []));
            $this->app->run_called = $t;

            return $ret;
        }
    }

    /**
     * Terminate this callback
     * by rendering the owner view by default.
     */
    public function terminateJson(View $view = null): void
    {
        if ($this->canTerminate()) {
            $this->app->terminateJson($view ?? $this->owner);
        }
    }

    /**
     * Return true if urlTrigger is part of the request.
     */
    public function isTriggered()
    {
        return isset($_GET[$this->urlTrigger]);
    }

    /**
     * Return callback triggered value.
     */
    public function getTriggeredValue(): string
    {
        return $_GET[$this->urlTrigger] ?? '';
    }

    /**
     * Only current callback can terminate.
     */
    public function canTerminate(): bool
    {
        return isset($_GET['__atk_callback']) && $_GET['__atk_callback'] === $this->urlTrigger;
    }

    /**
     * Allow callback to be triggered or not.
     */
    public function canTrigger(): bool
    {
        return $this->triggerOnReload || empty($_GET['__atk_reload']);
    }

    /**
     * Return URL that will trigger action on this call-back. If you intend to request
     * the URL direcly in your browser (as iframe, new tab, or document location), you
     * should use getUrl instead.
     */
    public function getJsUrl(string $value = 'ajax'): string
    {
        return $this->owner->jsUrl($this->getUrlArguments($value));
    }

    /**
     * Return URL that will trigger action on this call-back. If you intend to request
     * the URL loading from inside JavaScript, it's always advised to use getJsUrl instead.
     */
    public function getUrl(string $value = 'callback'): string
    {
        return $this->owner->url($this->getUrlArguments($value));
    }

    /**
     * Return proper url argument for this callback.
     */
    private function getUrlArguments(string $value): array
    {
        return ['__atk_callback' => $this->urlTrigger, $this->urlTrigger => $value];
    }
}
