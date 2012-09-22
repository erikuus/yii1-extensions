<?php
/**
 * 
 * this class provides helper methods for the simpleWorkflow behavior
 *
 */
class SWHelper {
	
	/**
	 * Returns the list of all statuses that can be reached from current status of the model
	 * passed as argument. The returned array is in the form suitable for dropDownList and listBox 
	 * (eg. arra[statusId]='status Label').<br/>
	 *  If  $includeCurrentStatus is true, then the current status of the $model is included in 
	 *  the returned list.
	 * 
	 * @param CModel the data model attaching a simpleWorkflow behavior
	 * @param boolean $includeCurrentStatus when TRUE the current status in added to the list returned
	 * @return array the list data that can be used in dropDownList and listBox 
	 */
	public static function nextStatuslistData($model, $includeCurrentStatus=true){
		$result=array();
						
		if( $model->swHasStatus() and $includeCurrentStatus){
			$result[$model->swGetStatus()->toString()]=$model->swGetStatus()->getLabel().'*';
		}
		$ar=$model->swGetNextStatus();
		if(count($ar)!=0){
			foreach ( $ar as $nodeObj ) {				
				$result[$nodeObj->toString()]=$nodeObj->getLabel();
			}
		}
		return $result;			
	}
	/**
	 * Returns the list of all statuses belonging to the workflow the model passed as argument
	 * is in. The returned array is in the form* suitable for dropDownList and listBox 
	 * (eg. arra[statusId]='status Label').<br/>
	 * If $model is not in a workflow, an empty array is returned.
	 * 
	 * @param CModel the data model attaching a simpleWorkflow behavior
	 * @return array the list data that can be used in dropDownList and listBox
	 */
	public static function allStatuslistData($model){
		$result=array();
		$ar=$model->swGetAllStatus();
		if(count($ar)!=0){
			foreach ( $ar as $nodeObj ) {				
				$result[$nodeObj->toString()]=$nodeObj->getLabel();
			}
		}
		return $result;			
	}	
}
?>