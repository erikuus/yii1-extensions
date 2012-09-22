<?php
/**
 * This class represent a graph node
 */			
class SWNode {
	/**
	 * @var string workflow identifier
	 */
	private $_ownerWf;
	/**
	 * @var string node identifier which must be unique within the workflow
	 */
	private $_id;
	/**
	 * @var string user friendly node name. If not provided at construction, the string
	 * 'workflowId/nodeId' will be used.
	 */
	private $_label;
	/**
	 * @var string expression evaluated in the context of an CActiveRecord object. It must returns
	 * a boolean value that is used to allow access to this node.
	 */
	private $_constraint;
	/**
	 * @var array array of transitions that exist between this node and other nodes
	 */
	private $_tr=array();
	
	/**
	 * Creates a workflow node object from a string.
	 * If no workflowId is specified in the nodeId, then the $defaultWorkflowId
	 * is used.
	 * @param mixed $node If a string is passed as argument, it can be both in format workflowId/NodeId
	 * or simply 'nodeId'. In this last case, argument $defaultWorkflowIs must be provided, otherwise it is
	 * ignored. The $node argument may also be an associative array, with the following structure :<br/>
	 * <pre>
	 * 	{
	 * 		'id' => string,			// mandatory, may include the workflowId
	 * 		'label' => string ,		// optional
	 * 		'constraint' => string,	// optional
	 * 		'transition' => array,	// optional
	 * 	}
	 * </pre>
	 * At last, $node may also be an array with key $key contains a string. 
	 * @param string defaultWorkflowId workflow Id that is used each time a workflow is needed to complete
	 * a status name.
	 */
	public function __construct($node, $defaultWorkflowId=null, $key=null){
		$st=array();
		if(is_a($node,'SWNode')){
			// copy constructor
			$st['workflow']=$node->getWorkflowId();
			$st['node']=$node->getId();
			$this->_label = $node->getLabel();
			
		}
		elseif( is_array($node) and $key == null){
			if(array_key_exists('id',$node)==false)
				throw new SWException(Yii::t('simpleWorkflow','missing node id'));
			// set node id -----------------------	
			$nodeId=$node['id'];
			if(strstr($nodeId,'/') != false){
				$st=SWNode::parseNodeId($nodeId);
			}else {
				$st=SWNode::parseNodeId($defaultWorkflowId.'/'.$nodeId);
			}
			// set node label ------------------------
			if(isset($node['label'])){
				$this->_label=$node['label'];
			}
			// set node constraint --------------
			if(isset($node['constraint'])){
				$this->_constraint=$node['constraint'];
			}	
			// load node transitions --------------
			if( isset($node['transition'])){
				$this->_loadTransition($node['transition'],$st['workflow']);				
			}	
		}
		else{
			$str=null;
			if(is_array($node) and isset($node[$key]) ){
				$str=$node[$key];
			}elseif(is_string($node)){
				$str=$node;
			}
			if( $str==null){
				//Yii::trace('$node='.CVarDumper::dumpAsString($node),'simpleWorkflow');
				throw new SWException(Yii::t('simpleWorkflow','failed to create node'));
			}
				
				
			if(strstr($str,'/') == true){
				$st=SWNode::parseNodeId($str);
			}else {
				$st=SWNode::parseNodeId($defaultWorkflowId.'/'.$str);
			}				
		}
		if( array_key_exists('workflow',$st)==false or array_key_exists('node',$st)==false)
			throw new SWException(Yii::t('simpleWorkflow','failed to create node'));
			
		$this->_ownerWf=$st['workflow'];
		$this->_id=$st['node'];	
		if(!isset($this->_label)){
			$this->_label=$this->_id;
		}	
	}
	/**
	 * 
	 */
	private function _loadTransition($tr, $defWfId){
		
		if( is_string($tr))
		{
			$trAr=explode(',',$tr);
			foreach($trAr as $aTr)
			{
				$objNode=new SWNode(trim($aTr),$defWfId);
				$this->_tr[$objNode->toString()]=null;
			}
		}
		elseif( is_array($tr))
		{
			foreach($tr as $key => $value){
				if( is_string($key)){
					$objNode=new SWNode(trim($key),$defWfId);
					if($value!=null)
						$this->_tr[$objNode->toString()]=$value;
					else
						$this->_tr[$objNode->toString()]=null;
				}else {
					$objNode=new SWNode(trim($value),$defWfId);
					$this->_tr[$objNode->toString()]=null;
				}
			}
		}
	}	
	/**
	 * Parse a status name and return it as an array. The string passed as argument
	 * may be a complete status name (e.g workflowId/nodeId) and if no workflowId is
	 * specified, then null is returned as workflowId.
	 * 
	 * @param string status status name (wfId/nodeId or nodeId)
	 * @return array the complete status (e.g array ( [workflow] => 'a' [node] => 'b' ))
	 */
	public static function parseNodeId($status,$defaultWorkflow=null){
		$nodeId=$wfId=null;
		if( preg_match('/^[a-zA-Z0-9_-]+$/',$status)==1 and 
			preg_match('/^[a-zA-Z0-9_-]+$/',$defaultWorkflow)==1)
		{
			$nodeId=$status;
			$wfId=$defaultWorkflow;
		} 
		elseif(preg_match('/^([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)$/',$status,$matches) )
		{
			$wfId=$matches[1];
			$nodeId=$matches[2];
		}
		else {
			throw new SWException('invalid status name : '.$status);
		}
		return array('workflow'=>$wfId,'node'=>$nodeId);		
	}
	public static function parseNodeId_orig($status,$defaultWorkflow=null){
		$nodeId=$wfId=null;
		if( preg_match('/^[[:alpha:]][a-zA-Z0-9_-]*$/',$status)==1 and 
			preg_match('/^[[:alpha:]][a-zA-Z0-9_-]*$/',$defaultWorkflow)==1)
		{
			$nodeId=$status;
			$wfId=$defaultWorkflow;
		} 
		elseif(preg_match('/^([[:alpha:]][a-zA-Z0-9_-]*)\/([[:alpha:]][a-zA-Z0-9_-]*)$/',$status,$matches) )
		{
			$wfId=$matches[1];
			$nodeId=$matches[2];
		}
		else {
			throw new SWException('invalid status name : '.$status);
		}
		return array('workflow'=>$wfId,'node'=>$nodeId);		
	}
	public function __toString(){return $this->getWorkflowId().'/'.$this->getId();}
	public function toString(){return $this->__toString();}
	public function getWorkflowId(){return $this->_ownerWf;}
	public function getId(){return $this->_id;}
	public function getLabel(){return $this->_label;}
	public function getNext(){return $this->_tr;}
	public function getConstraint(){return $this->_constraint;}
	public function getTransition($swNodeEnd){
		if( !is_a($swNodeEnd,'SWnode'))
			throw new SWException(Yii::t('simpleWorkflow','SWNode object expected'));
		if( $this->_tr == null or 
			count($this->_tr)==0 or
			!isset($this->_tr[$swNodeEnd->toString()]))
		{
			return null;
		}else {
			return $this->_tr[$swNodeEnd->toString()];
		}
	}
	/**
	 * 
	 */
	public function equals($status){
		if( is_a($status,'SWnode') and 
			$status->getWorkflowId() == $this->getWorkflowId() and
			$status->getId() == $this->getId())
		{
			return true;	
		} elseif( is_string($status) and !empty($status)){
			$other=new SWNode($status,$this->getWorkflowId());
			return $other->equals($this);
		}else
			return false;
	}
}
?>