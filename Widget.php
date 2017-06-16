<?php

namespace maksyutin\duallistbox;

use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * This is just an example.
 */
class Widget extends InputWidget
{

	public $nametitle;
	public $title;
	public $lngOptions;
	public $attributes;
	public $data;
	public $data_id;
	public $data_value;

	public function init()
	{
		parent::init();

		$this->data_id = isset($this->data_id) ? $this->data_id : 'id';
		$this->data_value = isset($this->data_value) ? $this->data_value : 'name';

		$this->nametitle = isset($this->nametitle) ? $this->nametitle : '';
		$this->registerAssets();

		echo Html::activeTextInput($this->model, $this->attribute, ['class' => 'hidden', 'value' => $this->value]);
	}

	public function run()
	{
		$view = $this->getView();

		$inputId = $this->attribute;

		$selected = Json::decode($this->model->$inputId, JSON_UNESCAPED_UNICODE);
		$selected = ($selected == null) ? [] : $selected;
		$json_sel = Json::encode($selected);

		$idModel = strtolower($this->model->formName());

		$this->attributes = $this->model->attributes();

		if ( $this->data instanceof ActiveQuery ) {
			// Transform ActiveQuery data to key-value array
			$this->data = ($this->data) ? ArrayHelper::map(
				$this->data->all(),
				$this->data_id,
				function($element) {
					return $element->{$this->data_value};
				}
			):
				[];
		}

		$ret_sel = '';
		$ret = '<select style="display: none;" multiple = "multiple">';
		$cnt = 0;
		$flipped_selected = array_flip($selected);
		foreach ($this->data as $id => $value) {
			// Check if data item is selected
			if ( isset($flipped_selected[$id]) ) {
				// Add to selected array
				$ret_sel .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($value) . '</option>';
				$cnt++;
			} else {
				// Add to options array
				$ret .= '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($value) . '</option>';
			}
		}
		$ret .= '</select>';

		// If items were pre-selected add them to the selected select list
		if ($ret_sel) {
			$ret_sel = '$("#dlb-'.strtolower($this->attribute).' .selected").append(\''.$ret_sel.'\');';
		}

		$lng_opt = new Json();
		$lng_opt->warning_info = 'Are you sure you want to move this many items?
        Doing so can cause your browser to become unresponsive.';
		$lng_opt->search_placeholder = 'Filter';
		$lng_opt->showing = '- showing';
		$lng_opt->available = 'Available';
		$lng_opt->selected = 'Selected';

		foreach($lng_opt as $id=>$value) {
			$lng_opt->$id = isset($this->lngOptions[$id]) ? $this->lngOptions[$id] : $value;
		}

		$options = 'lngOptions: '. json_encode($lng_opt);

		// Setup

		// Create the JS code to transform
		$formInputID = Html::getInputId($this->model, $this->attribute);

		$inputIdLower = strtolower($inputId);

		$js = <<<SCRIPT

            $('#$inputId').DualListBox({
                json: false,
                name: '$idModel',
                id: '$inputIdLower',
                title: '$this->nametitle',
                $options
            });

            $ret_sel

            $("#$formInputID").val('$json_sel');

            $('#dlb-$inputId .selected-count').text('$cnt');

SCRIPT;

		$view->registerJs($js);

		return '<div id="'.$inputId.'" >'.$ret.'</div>';
	}

	/**
	 * Registers the needed assets
	 */
	public function registerAssets()
	{
		$view = $this->getView();
		Asset::register($view);
	}
}