<?php
require_once "GregorianFormView.class.php";

class GregorianTagFormView extends GregorianFormView {

    /**
     * @var Array Definition of relation between form fields and object/db values.
     * Each key in the array is the name of a db-field, the value is the relation to form
     * fields. If the relation is an array, the form fields are registered as key/value pairs.
     * The key is the field name, the value is the interpretation method.
     * If the relation is a string, it is the field name and there's an 1-to-1 relation.
     */
    protected $_formFieldDefinition = array(
        'tag' => 'tag'
    );

    protected $_objClass = 'GregorianTag';

    public function __construct(&$modx, &$xpdo) {
        parent::__construct(&$modx, &$xpdo);
        $this->set('template','tagform');
    }

    public function render() {
        $this->_preRender();
        $this->modx->setPlaceholder('action','savetag');
        $this->modx->setPlaceholder('formAction',$this->get('baseUrl'));
        $this->set('mainTemplate','tagform');
        $this->_setLangPlaceholders();
        return parent::render();
    }

    protected function _setLangPlaceholders() {
        foreach ($this->_template['lang_placeholders'] as $name => $langKey) {
            $this->modx->setPlaceholder($name,$this->lang($langKey));
        }
    }
}