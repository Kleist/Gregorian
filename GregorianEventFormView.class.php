<?php
require_once "GregorianFormView.class.php";

class GregorianEventFormView extends GregorianFormView {

    /**
     * @var Array Definition of relation between form fields and object/db values.
     * Each key in the array is the name of a db-field, the value is the relation to form
     * fields. If the relation is an array, the form fields are registered as key/value pairs.
     * The key is the field name, the value is the interpretation method.
     * If the relation is a string, it is the field name and there's an 1-to-1 relation.
     *
     * TODO This code probably belongs in the model aka Event-object.
     */

    public function __construct(&$modx, &$xpdo) {
		parent::__construct(&$modx, &$xpdo);
		$this->set('template','eventform');
	}

	public function render() {
        $this->_preRender();
        $this->modx->setPlaceholder('action','save');
        $this->modx->setPlaceholder('formAction',$this->get('baseUrl'));
        $this->set('mainTemplate','eventForm');
        $this->_setLangPlaceholders();
        return parent::render();
	}

	protected function _setLangPlaceholders() {
        foreach ($this->_template['lang_placeholders'] as $name => $langKey) {
            $this->modx->setPlaceholder($name,$this->lang($langKey));
        }
	}
}