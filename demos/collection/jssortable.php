<?php

declare(strict_types=1);

namespace atk4\ui\demo;

/** @var \atk4\ui\App $app */
require_once __DIR__ . '/../init-app.php';

$view = \atk4\ui\View::addTo($app, ['template' => new \atk4\ui\Template(
    '<div class="ui header">Click and drag country to reorder</div>
    <div id="{$_id}" style="cursor: pointer">
        <ul>
            {List}<li class="ui icon label" data-name="{$name}"><i class="{iso}ae{/} flag"></i> {name}andorra{/}</li>{/}
        </ul>
    </div>'
)]);

$lister = \atk4\ui\Lister::addTo($view, [], ['List']);
$lister->onHook(\atk4\ui\Lister::HOOK_BEFORE_ROW, function (\atk4\ui\Lister $lister) {
    $lister->current_row->set('iso', mb_strtolower($lister->current_row->get('iso')));
});
$lister->setModel(new Country($app->db))
    ->setLimit(20);

$sortable = \atk4\ui\JsSortable::addTo($view, ['container' => 'ul', 'draggable' => 'li', 'dataLabel' => 'name']);

$sortable->onReorder(function ($order, $src, $pos, $oldPos) {
    if (@$_GET['btn']) {
        return new \atk4\ui\JsToast(implode(' - ', $order));
    }

    return new \atk4\ui\JsToast($src . ' moved from position ' . $oldPos . ' to ' . $pos);
});

$button = \atk4\ui\Button::addTo($app)->set('Get countries order');
$button->js('click', $sortable->jsGetOrders(['btn' => '1']));

//////////////////////////////////////////////////////////////////////////////////////////
\atk4\ui\View::addTo($app, ['ui' => 'divider']);
\atk4\ui\Header::addTo($app, ['Add Drag n drop to Grid']);

$grid = \atk4\ui\Grid::addTo($app, ['paginator' => false]);
$grid->setModel((new Country($app->db))->setLimit(6));

$dragHandler = $grid->addDragHandler();
$dragHandler->onReorder(function ($order) {
    return new \atk4\ui\JsToast('New order: ' . implode(' - ', $order));
});
