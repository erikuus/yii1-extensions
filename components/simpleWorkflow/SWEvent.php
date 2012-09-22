<?php
/**
 * this class implements events fired by the simpleWorkflow behavior. This event is fired
 * at different time during a transition.
 */
class SWEvent extends CEvent
{
	/**
	 * @var SWNode source status the owner model is in
	 */
	public $source;
	/**
	 * @var SWNode destination status the owner model is sent to 
	 */
	public $destination;
	/**
	 * Constructor.
	 * @param mixed sender of this event
	 */
	public function __construct($sender,$source,$destination)
	{
		parent::__construct($sender);
		$this->source=$source;
		$this->destination=$destination;
	}
}
?>
