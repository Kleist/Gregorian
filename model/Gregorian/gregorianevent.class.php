<?php
class GregorianEvent extends xPDOSimpleObject {
	private $_tags = NULL;
	
    function GregorianEvent(& $xpdo) {
        $this->__construct($xpdo);
    }
    function __construct(& $xpdo) {
        parent :: __construct($xpdo);
    }

	public function getTags() {
		if ($this->_tags == NULL) {
            $tags = $this->getMany('Tags');
    		foreach($tags as $tag) {
                $this->_tags[] = $tag->getOne('Tag')->get('tag');
            }
		}
		return ($this->_tags!==NULL) ? $this->_tags : array(); // Return _tags or array() if NULL
	}
	
	/**
	 * Add a tag to the event. Returns false if the tag doesn't exist.
	 * NB: Doesn't save the event.
	 *
	 * @todo Should be possible to create the tag if it doesn't exist already. Should be configurable if this works.
	 * @param string|array $name String or array of strings with name of the tags to add
	 * @return boolean Returns true if all tags are added. (If sizeof($name) = number of tags added)
	 */
	public function addTag($tagnames,$addMissingTags = false) {
		if (!is_array($tagnames)) $tagnames = array(0=>$tagnames);
		$tagsAdded = 0;
		foreach ($tagnames as $name) {
			// If tag exists create the link and add it to the event
			if ($tag = $this->xpdo->getObject('GregorianTag',array('tag' => $name))) {
			}
			elseif ($addMissingTags) {
				$tag = $this->xpdo->newObject('GregorianTag',array('tag' => $name));
				$tag->save();
			}

			if ($tag) {
				$tagLink = $this->xpdo->newObject('GregorianEventTag');
				$tagLink->addOne($tag);
				$this->addMany($tagLink);
				$tagsAdded++;
			}
		}
		return ($tagsAdded == sizeof($tagnames));
	}
	
	/**
	 * Check if event spans multiple days
	 * @return boolean False for single-day event, true otherwise
	 */
	public function isMultiDay() {
		$start = substr($this->get('dtstart'),0,10);
		$end = substr($this->get('dtend'),0,10);
		if ($start == $end || $end == '') return false;
		else return true;
	}
}