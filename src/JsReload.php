<?php

declare(strict_types=1);

namespace atk4\ui;

/**
 * This class generates action, that will be able to loop-back to the callback method.
 */
class JsReload implements JsExpressionable
{
    /**
     * Specifies which view to reload. Use constructor to set.
     *
     * @var View
     */
    public $view;

    /**
     * A Js function to execute after reload is complete and onSuccess is execute.
     *
     * @var JsExpression
     */
    public $afterSuccess;

    /**
     * If defined, they will be added at the end of your URL.
     * Value in ARG can be either string or JsExpressionable.
     *
     * @var array
     */
    public $args = [];

    /**
     * Semantic-ui api settings.
     * ex: ['loadingDuration' => 1000].
     *
     * @var array
     */
    public $apiConfig = [];

    public $includeStorage = false;

    public function __construct($view, $args = [], $afterSuccess = null, $apiConfig = [], $includeStorage = false)
    {
        $this->view = $view;
        $this->args = $args;
        $this->afterSuccess = $afterSuccess;
        $this->apiConfig = $apiConfig;
        $this->includeStorage = $includeStorage;
    }

    public function jsRender()
    {
        $final = (new Jquery($this->view))
            ->atkReloadView(
                [
                    'uri' => $this->jsUrl(['__atk_reload' => $this->view->name]),
                    'uri_options' => !empty($this->args) ? $this->args : null,
                    'afterSuccess' => $this->afterSuccess ? $this->afterSuccess->jsRender() : null,
                    'apiConfig' => !empty($this->apiConfig) ? $this->apiConfig : null,
                    'storeName' => $this->includeStorage ? $this->view->name : null,
                ]
            );

        return $final->jsRender();
    }

    /** @var View|Callback */
    public $viewForUrl;

    public function jsUrl($page = [])
    {
        return ($this->viewForUrl ?? $this->view)->jsUrl($page);
    }
}
