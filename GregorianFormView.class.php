<?php
require_once "GregorianView.class.php";

abstract class GregorianFormView extends GregorianView {

    public function __construct(&$modx, &$xpdo) {
        parent::__construct(&$modx, &$xpdo);
    }


    public function _preRender() {
        parent::_preRender();
        $this->_setFormFieldPlaceholders();
    }
    /**
     * Get the fields for the form.
     * If invalid, use POST'ed values those values
     * elseif objId is set, use those values
     * else use none (new object).
     * Sets the placeholders and returns a string describing method used.
     *
     * @return string Method used.
     * 'fromPost', 'fromId' or 'new' on success.
     * 'fromIdFailed' if objId is set but object does not exist.
     * 'fromPostFailed' if REQUEST_METHOD is POST but setting fields failed.
     */
    protected function _setFormFieldPlaceholders() {
        $method = false;

        // Get fields from POST if posted (typically because something is not valid)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->_formFieldsFromPost($_POST)) {
                $method = 'fromPost';
            }
            else {
                $method = 'fromPostFailed';
            }
        }


        if (!$method) {
            // Edit existing object if objId is set.
            $objId = $this->get('objId');
            var_dump($objId);
            if (is_integer($objId)) {
                $obj = $this->xpdo->getObject($this->_objClass);
                if (is_object($obj)) { // Convert from object to form fields
                    if ($this->_formFieldsFromObject($obj)) $method = 'fromId';
                }
                else {
                    $this->_error($this->_template['error_obj_doesnt_exist'],$objId);
                    $method = 'fromIdFailed';
                }
            }
            else {
                $this->_setTaggingPlaceholders();
                $method = 'new';
            }
        }

        return $method;
    }

    private function _formFieldsFromObject($obj) {
        $set = false;
        $formFields = array();
        if (!is_array($this->_formFieldDefinition)) die('The form field definition should be defined in the class extending GregorianFormView');
        foreach ($this->_formFieldDefinition as $objField => $ffdef) {
            $objValue = $obj->get($objField);

            if (is_string($ffdef)) {
                $this->modx->setPlaceholder($ffdef, $objValue);
                $set = true;
            }

            elseif (is_array($ffdef)) {
                $this->modx->toPlaceholders($this->_e2f($ffdef,$objValue));
                $set = true;
            }
        }

        if ($this->_setTaggingPlaceholders($obj->getTags())) {
            $set = true;
        }

        return $set;
    }

    private function _e2f($def,$value) {
        // Example $def's (right side)
        //        'allday'        => array('allday' => 'checkbox'),
        //        'dtstart'       => array(
        //            'startdate' => 'dateFormat',
        //            'starttime' => 'timeFormat',
        //            'startunix' => 'unixtime'),
        $result = array();
        foreach ($def as $name => $method) {
            switch ($method) {
                case 'checkbox':
                    $result[$name] = ($value) ? ' checked="yes" ' : '';
                    break;
                case 'strtotime(:)':
                    $result[$name] = strtotime($value);
                    break;
                case 'substr(:,11,5)':
                    $result[$name] = substr($value,11,5);
                    break;
                case 'substr(:,0,10)':
                    $result[$name] = substr($value,0,10);
                    break;
            }
        }
        return $result;
    }

    /**
     * Sets form fields based on array (which normally is $_POST) and _formFieldDefinition
     * @param $post Array from posted form field.
     * @return boolean True if at least one form field placeholder was set from the $post array.
     */
    private function _formFieldsFromPost($post) {
        $set = false;
        foreach ($this->_formFieldDefinition as $objField => $ffdef) {
            if (is_string($ffdef) && isset($post[$ffdef])) {
                $this->modx->setPlaceholder($ffdef, $post[$ffdef]);
                $set = true;
            }
            elseif (is_array($ffdef)) {
                $ffs = $this->_f2f($ffdef,$post);
                if (!empty($ffs)) {
                    $this->modx->toPlaceholders($ffs);
                    $set = true;
                }
            }
        }
        if ($this->_setTaggingPlaceholders($post)) $set = true;
        return $set;
    }

    private function _f2f($def,$array) {
        // Example $def's (right side)
        //        'allday'        => array('allday' => 'checkbox'),
        //        'dtstart'       => array(
        //            'startdate' => 'dateFormat',
        //            'starttime' => 'timeFormat',
        //            'startunix' => 'unixtime'),
        $result = array();
        foreach ($def as $name => $method) {
            switch ($method) {
                case 'checkbox':
                    $result[$name] = (isset($array[$name])) ? ' checked="yes" ' : '';
                    break;
                default:
                    if (isset($name)) {
                        $result[$name] = $array[$name];
                    }
                    break;
            }
        }
        return $result;
    }

    private function _setTaggingPlaceholders($selectedTags = array()) {
        $formatted = '';
        // Get possible tags
        $tags = $this->xpdo->getCollection('GregorianTag');
        foreach ($tags as $tag) {
            $tagName = $tag->get('tag');
            $cleanTagName = GregorianTag::cleanTagName($tagName);
            if ($selectedTags[$tagName] || $selectedTags[$cleanTagName]) {
                $checked = 'checked="yes"';
            }
            else {
                $checked = '';
            }

            $this->modx->toPlaceholders(array('name'=>$cleanTagName,'label'=>$tag->get('tag'),'checked'=>$checked));
            $formatted .= $this->modx->mergePlaceholderContent($this->_template['tag']);
        }
        if ($formatted != '') {
            $this->modx->setPlaceholder('tags',$formatted);
            return true;
        }
    }
}

