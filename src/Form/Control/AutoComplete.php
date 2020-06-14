<?php

declare(strict_types=1);

namespace atk4\ui\FormField;

use atk4\dsql\Query;
use atk4\ui\Exception;
use atk4\ui\jsExpression;
use atk4\ui\jsFunction;
use atk4\ui\jQuery;

class AutoComplete extends Input
{
    public $defaultTemplate = 'formfield/autocomplete.html';
    public $ui = 'input';

    /**
     * Object used to capture requests from the browser.
     *
     * @var callable
     */
    public $callback;

    /**
     * Set this to true, to permit "empty" selection. If you set it to string, it will be used as a placeholder for empty value.
     *
     * @var string
     */
    public $empty = '...';

    /**
     * Either set this to array of fields which must be searched (e.g. "name", "surname"), or define this
     * as a callback to be executed callback($model, $search_string);.
     *
     * If left null, then search will be performed on a model's title field
     *
     * @var array|Closure
     */
    public $search;

    /**
     * Set this to create right-aligned button for adding a new a new record.
     *
     * true = will use "Add new" label
     * string = will use your string
     *
     * @var null|bool|string
     */
    public $plus = false;

    /**
     * Sets the max. amount of records that are loaded. The default 10
     * displays nicely in UI.
     *
     * @var int
     */
    public $limit = 10;

    /**
     * Set custom model field here to use it's value as ID in dropdown instead of default model ID field.
     *
     * @var string
     */
    public $id_field;

    /**
     * Set custom model field here to display it's value in dropdown instead of default model title field.
     *
     * @var string
     */
    public $title_field;

    /**
     * Semantic UI uses cache to remember choices. For dynamic sites this may be dangerous, so
     * it's disabled by default. To switch cache on, set 'cache'=>'local'.
     *
     * Use this apiConfig variable to pass API settings to Semantic UI in .dropdown()
     *
     * @var array
     */
    public $apiConfig = [];

    /**
     * Semantic UI dropdown module settings.
     * Use this setting to configure various dropdown module settings
     * to use with Autocomplete.
     *
     * For example, using this setting will automatically submit
     * form when field value is changes.
     * $form->addField('field', ['AutoComplete', 'settings'=>['allowReselection' => true,
     *                           'selectOnKeydown' => false,
     *                           'onChange'        => new atk4\ui\jsExpression('function(value,t,c){
     *                                                          if ($(this).data("value") !== value) {
     *                                                            $(this).parents(".form").form("submit");
     *                                                            $(this).data("value", value);
     *                                                          }
     *                                                         }'),
     *                          ]]);
     *
     * @var array
     */
    public $settings = [];


    /**
     * Default options for Autocomplete Semantic UI component.
     */
    public static function getDefaultAutocompleteSettings(): array {
        $options = array_merge(DropDown::getDefaultDropdownSettings(true), [
            'fields' => [
                'name' => 'title',
                'value' => 'value',
                'text' => 'title',
                'disabled' => 'disabled'
            ],
            'minCharacters' => 0,
            'filterRemoteData' => false,
            'saveRemoteData' => false,

            // it seems this does not work for JSON, so escaped on server side 'preserveHTML' => false, // default = true
            // sortSelect => true, // default = false
        ]);

        return $options;
    }
    
    public static function getDefaultAutocompleteApiConfig(): array {
        $apiConfig = [
            'method' => 'POST',
            // 'beforeXHR' => new jsFunction(['xhr'], [new jsExpression('xhr.setRequestHeader(\'Content-Type\', \'application/json; charset=UTF-8\');')]),
            'beforeSend' => new jsFunction(['settings'], [new jsExpression('var refCondValues = { }; var formFields = $(this).closest(\'.form\').serializeArray(); $.each(formFields, function(i, formField) { if ($.inArray(formField.name, [ref_condition_field_names]) !== -1) { refCondValues[formField.name] = formField.value;} }); settings.data = {\'autocomplete_query\': settings.urlData.query, \'autocomplete_ref_condition_values\': JSON.stringify(refCondValues)}; return settings;')]),
            'successTest' => new jsFunction(['response'], [new jsExpression('return response.success || false;')]),
            'onFailure' => new jsFunction(['response', 'elem'], [new jsExpression('elem.dropdown(\'set error\', \'API error\'); elem.dropdown(\'setup menu\', { \'values\': { } }); elem.dropdown(\'add message\', \'API error: Invalid response\'); elem.dropdown(\'show\');')]),
            'onError' => new jsFunction(['errorMessage', 'elem'], [new jsExpression('elem.dropdown(\'set error\', \'API error\'); elem.dropdown(\'setup menu\', { \'values\': { } }); elem.dropdown(\'add message\', \'API error: \' + errorMessage); elem.dropdown(\'show\');')]),
            'cache' => false
        ];
        
        return $apiConfig;
    }

    public function init()
    {
        parent::init();

        $this->template->set('input_id', $this->name.'-ac');

        $this->template->set('place_holder', $this->placeholder);

        if ($this->plus) {
            $this->action = $this->factory(['Button', is_string($this->plus) ? $this->plus : 'Add new', 'disabled' => ($this->disabled || $this->readonly)]);
        }
        //var_Dump($this->model->get());
        if ($this->form) {
            $vp = $this->form->add('VirtualPage');
        } else {
            $vp = $this->owner->add('VirtualPage');
        }

        $vp->set(function ($p) {
            $f = $p->add('Form');
            $f->setModel($this->model);

            $f->onSubmit(function ($f) {
                $id = $f->model->save()->id;

                $modal_chain = new jQuery('.atk-modal');
                $modal_chain->modal('hide');
                $ac_chain = new jQuery('#'.$this->name.'-ac');
                $ac_chain->dropdown('set value', $id)->dropdown('set text', $f->model->getTitle());

                return [
                    $modal_chain,
                    $ac_chain,
                ];
            });
        });
        if ($this->action) {
            $this->action->js('click', new \atk4\ui\jsModal('Adding New Record', $vp));
        }

        $this->apiConfig = static::getDefaultAutocompleteApiConfig();
        $this->settings = static::getDefaultAutocompleteSettings();
    }

    /**
     * Returns URL which would respond with first 50 matching records.
     */
    public function getCallbackURL()
    {
        return $this->callback->getJSURL();
    }

    public function processAutocompleteRequest()
    {
        header('Cache-Control: no-cache'); // make sure the response is not cached

        $postQuery = $_POST['autocomplete_query'] ?? null;
        if ($postQuery === null) {
            throw new Exception(['No autocomplete query']);
        }
        
        $postLimit = $_POST['autocomplete_limit'] ?? null;
        if ($postLimit !== null) {
            if ((string)(int)$postLimit !== (string)$postLimit || $postLimit < 0 || $postLimit > 1000) {
                throw new Exception(['Invalid autocomplete limit']);
            }
            $limit = (int)$postLimit;
        } else {
            $limit = $this->limit;
        }
        unset($postLimit);
        
        $postRefCondValues = $_POST['autocomplete_ref_condition_values'] ?? null;
        if ($postRefCondValues !== null) {
            $refCondValues = \Mvorisek\Utils\Misc::jsonDecode($postRefCondValues);
        } else {
            $refCondValues = [];
        }
        unset($postRefCondValues);
        
        $autocompleteItems = $this->getAutocompleteItems($postQuery, $refCondValues, $limit + 1);
        $isLimited = false;
        if (count($autocompleteItems) > $limit) {
            $isLimited = true;
            $autocompleteItems = array_slice($autocompleteItems, 0, $limit);
        }

        if ((!$this->field || !$this->field->required) && $this->empty) {
            $defaultOptions[] = $this->getAutocompleteEmptyItem();
        }

        if ($isLimited) {
            $autocompleteItems[] = ['value' => '', 'title' => 'Only first ' . $limit . ' results are shown. Please type longer query.', 'disabled' => true];
        }

        $response = [
            'success' => true,
            'results' => array_map(static function($item) {
                $item['title'] = \Mvorisek\Utils\Text::escapeHtml($item['title']);
                return $item;
            }, $autocompleteItems),
            'resultsLimited' => $isLimited
        ];

        header('Content-Type: application/json; charset=UTF-8');
        $this->app->terminate(\Mvorisek\Utils\Misc::jsonEncode($response));
    }

    public function getAutocompleteItems(string $searchQuery, array $refCondValues, int $limit): array {
        if (!$this->model) {
            throw new Exception(['Form field model is not configured']);
        }

        /* @var $q \atk4\dsql\Query */
        $q = $this->model->persistence->dsql();
        $topAndExpr = $q->andExpr();
        $isWhereSetFunc = static function(Query $query) {
            return isset($query->args['where']) && count($query->args['where']) > 0;
        };
        $addWhereCondFunc = function(Query $query, string $fieldName, $cond, $value = null, bool $allowTypecast = true) {
            if (func_num_args() === 3) {
                $value = $cond;
                $cond = '<=>';
            }
            
            // based on \atk4\data\Persistence_SQL::initQueryConditions() method
            $field = $this->model->getElement($fieldName);
            if ($allowTypecast) {
                $value = $this->model->persistence->typecastSaveField($field, $value);
            } else {
                if ($value !== null) {
                    $value = (string)$value;
                }
            }
            $query->where($field, $cond, $value);
        };
        
        // set ref conditions
        if ($this->field && isset($this->field->refConditions)) {
            $orExpr = $q->orExpr();
            foreach ($this->field->refConditions as $refCond) {
                $refCondFieldNames = [];
                foreach ($refCond['fields'] as $fName) {
                    $refCondFieldNames[$fName] = (string)$fName;
                }
                
                $andExpr = $q->andExpr();
                foreach ($refCondFieldNames as $refCondFieldName) {
                    $refCondValue = $refCondValues[$refCondFieldName];
                    $addWhereCondFunc($andExpr, $refCondFieldName, $refCondValue);
                }
                if ($isWhereSetFunc($andExpr)) {
                    $orExpr->where($andExpr);
                }
            }
            if ($isWhereSetFunc($orExpr)) {
                $topAndExpr->where($orExpr);
            }
        }
        
        $title_field = $this->title_field ?: $this->model->title_field;

        // add query conditions
        $searchQuery = trim(\Mvorisek\Utils\Text::cleanString($searchQuery, false));
        if (strlen($searchQuery) > 0) {
            if ($this->search instanceof \Closure) {
                $this->search($topAndExpr, $searchQuery, $title_field);
            } elseif ($this->search && is_array($this->search)) {
                $orExpr = $q->orExpr();
                foreach ($this->search as $field) {
                    $addWhereCondFunc($orExpr, $field, 'LIKE', '%' . $searchQuery . '%', false);
                }
                if ($isWhereSetFunc($orExpr)) {
                    $topAndExpr->where($orExpr);
                }
            } else {
                $addWhereCondFunc($topAndExpr, $title_field, 'LIKE', '%' . $searchQuery . '%', false);
            }
        }
        
        // clone model and set the builded where condition
        $model = clone $this->model;
        if ($isWhereSetFunc($topAndExpr)) {
            $model->addCondition($topAndExpr);
        }
        
        // set limit
        $model->setLimit($limit);
        $model->setOrder($title_field);

        // get items
        $items = [];
        foreach ($model as $row) {
            $items[] = $this->getAutocompleteItemFromModel($row);
        }

        return $items;
    }

    /**
     * returns <input .../> tag.
     */
    public function getInput()
    {
        return $this->app->getTag('input', [
            'name'        => $this->short_name,
            'type'        => 'hidden',
            'id'          => $this->id.'_input',
            'value'       => $this->getValue(),
            'readonly'    => $this->readonly ? 'readonly' : false,
            'disabled'    => $this->disabled ? 'disabled' : false,
        ]);
    }

    /**
     * Set Semantic-ui Api settings to use with dropdown.
     *
     * @param array $config
     *
     * @return $this
     */
    public function setApiConfig($config)
    {
        $this->apiConfig = array_merge($this->apiConfig, $config);

        return $this;
    }

    /**
     * Override this method if you want to add more logic to the initialization of the
     * auto-complete field.
     *
     * @param jQuery
     */
    protected function initDropdown($chain)
    {
        if (!$this->model) {
            throw new Exception(['Form field model is not configured']);
        }

        $defaultOptions = [];
        $value = $this->getValue();
        if (strlen($value) !== 0) {
            $this->model->tryLoad($value);
            $defaultOptions[] = array_merge($this->getAutocompleteItemFromModel($this->model), ['selected' => true]);
        } elseif ($this->empty) {
            $defaultOptions[] = array_merge($this->getAutocompleteEmptyItem(), ['selected' => true]);
        }

        // set ref conditions fields names, values for this fields will be send with the autocomplete query request
        $refCondFieldNames = [];
        if ($this->field && isset($this->field->refConditions)) {
            foreach ($this->field->refConditions as $refCond) {
                foreach ($refCond['fields'] as $fName) {
                    $refCondFieldNames[(string)$fName] = (string)$fName;
                }
            }
        }
        $this->apiConfig['beforeSend']->fx_statements[0]->args = [
            'ref_condition_field_names' => array_values($refCondFieldNames)];

        $settings = array_merge([
            'apiSettings' => array_merge(['url' => $this->getCallbackURL()], $this->apiConfig),
            'values' => $defaultOptions,
        ], $this->settings);

        $chain->dropdown($settings);
    }

    public function renderView()
    {
        if ($this->icon || $this->iconLeft) { // our css fixes are currently not compatible with icons on either side
            throw new Exception([
                'Cannot use icon or iconLeft for dropdown',
                'icon'     => $this->icon,
                'iconLeft' => $this->iconLeft,
            ]);
        }
        
        $this->callback = $this->add('Callback');
        $this->callback->set([$this, 'processAutocompleteRequest']);

        if ($this->disabled) {
            $this->settings['showOnFocus'] = false;
            $this->settings['allowTab'] = false;

            $this->template->set('disabled', 'disabled');
        }

        if ($this->readonly) {
            $this->settings['showOnFocus'] = false;
            $this->settings['allowTab'] = false;
            $this->settings['apiSettings'] = null;
            $this->settings['onShow'] = new jsFunction([new jsExpression('return false')]);
            $this->template->set('readonly', 'readonly');
        }

        $chain = new jQuery('#'.$this->name.'-ac');

        $this->initDropdown($chain);
        
        // fix: remove search term on outfocus event if dropdown is closed (user changed the search term,
        //      but data was not loaded yet - menu not shown yet), needed with forceSelection = false
        $chain->focusout(new jsFunction([new jsExpression('if (!$(this).dropdown(\'is visible\')) { $(this).dropdown(\'remove searchTerm\'); }')]));

        if ($this->field && $this->field->get()) {
            $id_field = $this->id_field ?: $this->model->id_field;
            $title_field = $this->title_field ?: $this->model->title_field;

            $this->model->tryLoadBy($id_field, $this->field->get());

            if (!$this->model->loaded()) {
                $this->field->set(null);
            } else {
                // IMPORTANT: always convert data to string, otherwise numbers can be rounded by JS
                $chain->dropdown('set value', (string) $this->model[$id_field])
                        ->dropdown('set text', (string) $this->model[$title_field]);
                $this->js(true, $chain);
            }
        }

        $this->js(true, $chain);

        parent::renderView();
    }

    public function getAutocompleteItemFromModel(\atk4\data\Model $model) {
        if (!$model->loaded()) {
            throw new Exception(['Form field model is not loaded']);
        }

        $id_field = $this->id_field ?: $this->model->id_field;
        $title_field = $this->title_field ?: $this->model->title_field;

        $value = $model->get($id_field);
        if ($title_field !== $id_field && $model->hasElement($title_field)) {
            $title = $model->get($title_field);
        } else {
            try {
                $title = strtoupper(\Mvorisek\Kelly\Model::encodeID($value));
            } catch (\Exception $e) {
                $title = $value;
            }
        }
        $item = ['value' => (string)$value, 'title' => (string)$title];

        return $item;
    }

    public function getAutocompleteEmptyItem() {
        return ['value' => '', 'title' => (string)$this->empty];
    }
}
