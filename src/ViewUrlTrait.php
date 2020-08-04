<?php

declare(strict_types=1);

namespace atk4\ui;

trait ViewUrlTrait
{
    // {{{ Sticky URLs

    /** @var string[] stickyGet arguments */
    public $stickyArgs = [];

    /** @var string[] Cached stickyGet arguments */
    public $_stickyArgsCached;

    /**
     * Build an URL which this view can use for js call-backs. It should
     * be guaranteed that requesting returned URL would at some point call
     * $this->init().
     *
     * @param array $page
     *
     * @return string
     */
    public function jsUrl($page = [])
    {
        return $this->app->jsUrl($page, false, array_merge($this->_getStickyArgs($this->name), $this->stickyArgs));
    }

    /**
     * Build an URL which this view can use for call-backs. It should
     * be guaranteed that requesting returned URL would at some point call
     * $this->init().
     *
     * @param string|array $page URL as string or array with page name as first element and other GET arguments
     *
     * @return string
     */
    public function url($page = [])
    {
        return $this->app->url($page, false, array_merge($this->_getStickyArgs($this->name), $this->stickyArgs));
    }

    /**
     * Needed for tracking which view in a render tree called url().
     *
     * @internal
     */
    public $_triggerBy;

    /**
     * Get sticky arguments defined by the view and parents (including API).
     *
     * @param string $triggerBy If exception occurs, will know which view called url()
     *
     * @return array
     */
    public function _getStickyArgs($triggerBy)
    {
        $this->_triggerBy = $triggerBy;
        if ($this->owner && $this->owner instanceof self) {
            $this->_stickyArgsCached = array_merge($this->owner->_getStickyArgs($triggerBy), $this->stickyArgs);
        } else {
            $this->_stickyArgsCached = [];
        }

        return $this->_stickyArgsCached;
    }

    /**
     * Mark GET argument as sticky. Calling url() on this view or any
     * sub-views will embedd the value of this GET argument.
     *
     * If GET argument is empty or false, it won't make into URL.
     *
     * If GET argument is not presently set you can specify a 2nd argument
     * to forge-set the GET argument for current view and it's sub-views.
     */
    public function stickyGet(string $name, string $newValue = null): ?string
    {
        $this->stickyArgs[$name] = $newValue ?? $_GET[$name] ?? null;

        return $this->stickyArgs[$name];
    }

    // }}}
}
