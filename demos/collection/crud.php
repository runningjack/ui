<?php

declare(strict_types=1);

namespace atk4\ui\demo;

/** @var \atk4\ui\App $app */
require_once __DIR__ . '/../init-app.php';

class MyUpload extends \atk4\ui\Form\Control\Input {
    /** @var \atk4\ui\View */
    protected $wrapper;
    /** @var \atk4\ui\Form\Control\Upload */
    protected $uploadControl;
    /** @var \atk4\ui\View */
    protected $preview;

    public function init(): void {
        parent::init();

        $this->inputType = 'hidden';

        $this->wrapper = \atk4\ui\View::addTo($this, [
            'region' => 'Input',
            'style' => ['width' => '100%']
        ]);

        $this->initUploadControl();
        $this->initPreview();

        // if triggered, there action are processed immediatelly, thus nothing after will be reached
        $this->uploadControl->onUpload(function($postFile) {
            // "error "may be passed on upload error
            // see https://github.com/atk4/ui/blob/26bc53f40d884b3fc06fada54f44a40c47a75f05/src/Form/Control/Upload.php#L221
            if (!is_array($postFile) || $postFile['error'] !== 0) {
                throw new \atk4\ui\Exception('Upload to server failed');
            }

            $this->onItemUpload($postFile['name']);

            return $this->returnReload();
        });
        $this->uploadControl->onDelete(function() {
            return $this->returnReload();
        });
    }

    private function returnReload() {
        return new \atk4\ui\JsReload($this); // does not work, reload GET loops when in Crud Modal
        return $this->js(true)->html($this->render()); // working - but let's fix also the reload above
    }

    protected function initUploadControl(): void {
        $form = \atk4\ui\Form::addTo($this->wrapper, [
            'buttonSave' => false
        ]);

        $form->addControl('upload', [\atk4\ui\Form\Control\Upload::class]);
        $this->uploadControl = $form->getControl('upload');
        $this->uploadControl->caption = 'select file to upload';
        $this->uploadControl->owner->label = true; // force caption to be displayed as placeholder
        $this->uploadControl->hasUploadCb = true;
        $this->uploadControl->hasDeleteCb = true;
    }

    protected function initPreview(): void
    {
        $this->preview = \atk4\ui\View::addTo($this->wrapper, [
            'Test ' . microtime(true)
        ]);

        addCrudDemo($this->wrapper, $this->app->db);
    }

    protected function onItemUpload(string $filename = null): void
    {
        // do nothing now...
    }
}

function addCrudDemo(object $parent, \atk4\data\Persistence $db): \atk4\ui\Crud
{
    $model = new CountryLock($db);
    $model->getField('name')->ui = ['form' => [MyUpload::class]];

    $crud = \atk4\ui\Crud::addTo($parent, ['ipp' => 10]);
    $crud->onFormAdd(function ($form, $t) {
        $form->js(true, $form->getControl('name')->jsInput()->val('val from JS'));
    });
    $crud->setModel($model);

    return $crud;
}

$crud = addCrudDemo($app, $app->db);
\Closure::bind(function () use ($crud) { return $crud->menuItems; }, $crud, \atk4\ui\Crud::class)()['add']['item']->js(true)->click();
