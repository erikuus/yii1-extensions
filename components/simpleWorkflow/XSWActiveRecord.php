<?php
/**
 * XSWActiveRecord class
 *
 * This is the extended base class for all AR models that needs to handle events
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XSWActiveRecord extends SWActiveRecord
{
	/**
	 * @param string sw status id (status)
	 * @return string sw status value (swWorkflowId/status)
	*/
	public function swDbStatus($status)
	{
		return $status && strstr($status,'/')===false ? $this->swBehavior->swGetWorkflowId().'/'.$status : $status;
	}

	/**
	 * @param string sw status value (swWorkflowId/status)
	 * @return string sw status id (status)
	*/
	public function swIdStatus($status)
	{
		return $status && strstr($status,'/')!==false ? substr(strrchr($status, "/"), 1) : $status;
	}

	/**
	 * Checks if given status exists in workflow
	 * @param string status id
	 * @return boolen whether status exists
	 */
	public function checkStatus($status)
	{
		$status=$this->swDbStatus($status);
		$nodes=SWHelper::allStatuslistData($this);
		return $status=='' || isset($nodes[$status]) ? true : false;
	}

	/**
	 * Finds simple workflow status label
	 * @param string status id
	 * @return string label
	 */
	public function getLabelByStatus($status)
	{
		$status=$this->swDbStatus($status);
		$nodes=SWHelper::allStatuslistData($this);
		return isset($nodes[$status]) ? $nodes[$status] : null;
	}

	/**
	 * Returns workflow status label
	 */
	public function getStatusLabel()
	{
		return $this->swBehavior->swGetStatus()->getLabel();
	}

	/**
	 * Prepares attributes before performing validation.
	 */
	protected function beforeValidate()
	{
		parent::beforeValidate();

		if($this->isNewRecord)
			$this->status=$this->swBehavior->swGetStatus()->toString();

		return true;
	}
}
?>