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

$panel1->onOpen(function ($p) {
    $panel = \atk4\ui\View::addTo($p, ['ui' => 'basic segment']);
    $buttonNumber = $panel->stickyGet('btn');

    $panelText = 'You loaded panel content using button #' . $buttonNumber;
    \atk4\ui\Message::addTo($panel, ['Panel 1', 'text' => $panelText]);

    $reloadPanelButton = \atk4\ui\Button::addTo($panel, ['Reload Myself']);
    $reloadPanelButton->on('click', new \atk4\ui\JsReload($panel));
});
