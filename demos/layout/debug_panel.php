<?php

declare(strict_types=1);

namespace atk4\ui\demo;

/** @var \atk4\ui\App $app */
require_once __DIR__ . '/../init-app.php';

$country = new CountryLock($app->db);
$country->tryLoadAny();
DemoActionsUtil::setupDemoActions($country);

$panel1 = new \atk4\ui\Panel\Right();
$app->layout->addRightPanel($panel1);
$btn = \atk4\ui\Button::addTo($app, ['Button 1']);
$btn->js(true)->data('btn', '1');
$btn->on('click', $panel1->jsOpen(['btn'], 'orange'));

$panel1->onOpen(function ($p) use ($panel1) {
    /** @var \atk4\ui\Callback $cb */
    $cb = null;
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT) as $stackFrame) {
        if (($stackFrame['object'] ?? null) instanceof \atk4\ui\Callback && $stackFrame['function'] === 'set') {
            $cb = $stackFrame['object'];
            break;
        }
    }
    if ($cb === null) {
        throw new \Exception('Failed to get cb');
    }

    $panel = \atk4\ui\View::addTo($p, ['ui' => 'basic segment']);
    $buttonNumber = $panel->stickyGet('btn');

    $panelText = 'You loaded panel content using button #' . $buttonNumber;
    \atk4\ui\Message::addTo($panel, ['Panel 1', 'text' => $panelText]);

    $reloadPanelButton = \atk4\ui\Button::addTo($panel, ['Reload Myself']);
    $reload = new \atk4\ui\JsReload($panel);

    // reload must be asociated with originating callback (no longer app sticky args)
    $reload->viewForUrl = $cb;

//    $eToPath = function ($e) use (&$eToPath) {
//        $res = [get_class($e) . '(' . $e->name . ')'];
//
//        $o = $e->owner;
//        if ($o === null) {
//            $res = array_merge(['NULL'], $res);;
//        } elseif (!$o instanceof App) {
//            $res = array_merge($eToPath($o), $res);
//        }
//
//        return $res;
//    };
//
//    $dumpE = function($e) use($eToPath) {echo implode('<br>->', $eToPath($e)) . "<br><br>";};
//    $dumpE($panel);
//    $dumpE($cb);
//
//    var_dump($panel->jsUrl()); echo "<br>";
//    var_dump($cb->jsUrl());
//
//    exit;

    $cb->stickyGet($cb->getUrlTrigger(), $cb->getTriggeredValue());

    $reloadPanelButton->on('click', $reload);
});
