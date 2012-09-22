<?php
/**
 * This class gives access to workflow and statuses stored as PHP files.
 */

class SWPhpWorkflowSource extends SWWorkflowSource {
	/**
	 * @var string the base path alias where all workflow are stored.By default, it is set to
	 * application.models.workflows (folder  "protected/models/workflows").
	 */
	public $basePath = 'application.models.workflows';
		
	private $_workflow;	// workflow definition collection
	/**
	 * Initialize the component with configured values. To preload workflows, set configuration
	 * setting 'preload' to an array containing all workflows to preload. If no preload is set
	 * workflows are loaded on demand.
	 * 
	 * @see SWWorkflowSource
	 */
	public function init()
	{
		parent::init();
		if($this->basePath===null)
			$this->basePath=Yii::getPathOfAlias('application.workflows');
		if( is_array($this->preload) and count($this->preload)!=0){
			foreach ( $this->preload as $wfId ) {
				Yii::t('simpleWorkflow','preloading workflow : {name}',array('{name}'=>$wfId));
				$this->_load($wfId);       
			}
		}
		Yii::trace(Yii::t('simpleWorkflow','SWWorkflowSource initialized - basePath : '.$this->basePath),'application.simpleWorkflow');
	}	
	//		
	///////////////////////////////////////////////////////////////////////////////////
	// private methods
		
	/**
	 * Actually loads a workflow from a php source file into the $this->_workflow
	 * associative array. A call to methode reset() will unload all workflows.
	 */
	private function _load($wfId, $forceReload)
	{
		if( !is_string($wfId) or empty($wfId))
			throw new SWException(Yii::t('simpleWorkflow','invalid workflow Id : {workflowId}',
				array('{workflowId}'=>(is_null($wfId)?'null':$wfId))),
				SWException::SW_ERR_WORKFLOW_ID);
		
		if( !isset($this->_workflow[$wfId]) or $forceReload==true){
			$f=Yii::getPathOfAlias($this->basePath).DIRECTORY_SEPARATOR.$wfId.'.php';
			if( file_exists($f)==false){
				throw new SWException(Yii::t('simpleWorkflow','workflow definition file not found : {file}',
					array('{file}'=>$f)),
					SWException::SW_ERR_WORKFLOW_NOT_FOUND
				);
			}
			Yii::trace(
				Yii::t('simpleWorkflow','loading workflow {wfId} from file {file}',
					array('{wfId}'=>$wfId,'{file}'=>$f)
				),
				'application.simpleWorkflow'					
			);
			$this->_workflow[$wfId] = $this->_createWorkflow(require($f),$wfId);	
		}
		return $this->_workflow[$wfId];
	}	
	/**
	 * @param array $wf workflow definition
	 * @param string $wfId workflow Id
	 */	
	private function _createWorkflow($wf,$wfId)
	{
		if(!is_array($wf) or empty($wfId)){
			throw new SWException(Yii::t('simpleWorkflow','invalid argument'));
		}
		$wfDefinition=array();
		if( !isset($wf['initial']))
			throw new SWException(Yii::t('simpleWorkflow','missing initial status for workflow {workflow}',
				array('{workflow}'=>$wfId)),
				SWException::SW_ERR_IN_WORKFLOW
			);
			
		// load node list
		
		foreach($wf['node'] as $rnode)
		{
			$node=new SWNode($rnode,$wfId);
			$wfDefinition[$node->getId()]=$node;
			if($node->getId()==$wf['initial']){
				$wfDefinition['swInitialNode']= $node;
			}		
		}
		// checks that initialnode is set
		 
		if(!isset($wfDefinition['swInitialNode']))
			throw new SWException(Yii::t('simpleWorkflow','missing initial status for workflow {workflow}',
				array('{workflow}'=>$wfId)),
				SWException::SW_ERR_IN_WORKFLOW
			);
		
		return $wfDefinition;
	}
	/**
	 * Returns the SWNode object from the workflow collection.
	 * 
	 * @param SWnode swNode node to search for in the node list
	 * @return SWNode the SWNode object retrieved from the workflow collection, or NULL if this
	 * node could not be found in the workflow collection
	 */
	private function _getNode($swNode){
		$wfId=$swNode->getWorkflowId();
		if($wfId==null){
			throw new SWException(Yii::t('simpleWorkflow','workflow {workflow} not found',
				array('{workflow}'=>$wfId)),
				SWException::SW_ERR_WORKFLOW_NOT_FOUND
			);
		}
		$this->_load($wfId,false);
		$nodeId=$swNode->getId();
		if(isset($this->_workflow[$wfId][$nodeId])){
			return $this->_workflow[$wfId][$nodeId];
		}else {
			return null;
		}				
	}	

	//		
	///////////////////////////////////////////////////////////////////////////////////
	//	public methods
	
	/**
	 * 
	 */
	public function loadWorkflow($workflowId,$forceReload=false){
		$this->_load($workflowId,$forceReload);
	}
	/**
	 * 
	 */
	public function addWorkflow($definition, $id){
		if(!is_array($definition))
			throw new SWException(Yii::t('simpleWorkflow','array expected'));
			
		if(isset($this->_workflow[$id])){
			Yii::trace(Yii::t('simpleWorkflow','workflow {workflow} already loaded',array('{workflow}'=>$id))	,'application.simpleWorkflow');
		}else {
			$this->_workflow[$id] = $this->_createWorkflow($definition,$id);
		}
	}
	/**
	 * 
	 */
	public function getNodeDefinition($node, $defaultWorkflowId=null)
	{	
		return $this->_getNode(
			$this->createSWNode($node,$defaultWorkflowId)
		);
	}
	/**
	 * 
	 */
	public function getNextNodes($sourceNode,$workflowId=null){
		$result=array();
		
		// convert startStatus into SWNode
		$startNode=$this->getNodeDefinition(
			$this->createSWNode($sourceNode,$workflowId)
		);
		
		if($startNode==null){
			throw new SWException(Yii::t('simpleWorkflow','node {node] could not be found',
				array('{node}'=>$sourceNode),
				SWException::SW_ERR_NODE_NOT_FOUND
			));
		}else {
			foreach($startNode->getNext() as $nxtNodeId => $tr){
				//$result[]=$nxtNodeId;
				$result[]=$this->_getNode(new SWNode($nxtNodeId,$workflowId));
			}
		}
		return $result;
	}
	/**
	 * 
	 */
	public function isNextNode($sourceNode,$targetNode,$workflowId=null){
		
		$startNode=$this->createSWNode($sourceNode,$workflowId);
		$nextNode=$this->createSWNode($targetNode,($workflowId!=null?$workflowId:$startNode->getWorkflowId()));
				
		$nxt=$this->getNextNodes($startNode);
		if( $nxt != null){
			return in_array($nextNode->toString(),$nxt);
		}else {
			return false;
		}		
	}
	/**
	 * 
	 */
	public function getInitialNode($workflowId){
		$this->_load($workflowId,false);
		return $this->_workflow[$workflowId]['swInitialNode'];
	}
	/**
	 * 
	 */
	public function getAllNodes($workflowId)
	{
		$result=array();
		$wf=$this->_load($workflowId,false);
		foreach($wf as $key => $value){
			if($key!='swInitialNode'){
				$result[]=$value;
			}
		}
		return $result;		
	}	
}
?>
