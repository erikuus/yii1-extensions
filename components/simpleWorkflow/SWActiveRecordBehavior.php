<?php
/**
 * This class implements all the logic for the simpleWorkflow extension. It must be 
 * attached to an object that inherits from the CActiveRecord class. It can be initialized
 * with following parameters :<br/>
 * <ul>
 * <li><b>statusAttribute</b>: (string) name of the active record attribute that is used to stored the
 * value of the current status. In the database, this attribute must be defined as a string. By default
 * the name 'status' is used.</li>
 * <li><b>defaultWorkflow</b>: (string) id for the workflow the active record will be inserted in. Workflow insertion
 * may be automatic (see 'autoInsert') or manual, and in this case, it is possible to sepcify a workflow Id different
 * from the default workflow Id defined here. If this parameter is not set, then it is assumed to be
 * the name of the model, prefixed with 'workflowNamePrefix' defined by the workflow source component.
 * By default this value is set to 'sw' and so, for example Model1 is associated with workflow 'swModel1'.
 * </li>
 * <li><b>autoInsert</b>: (boolean) when true, the active record object is automatically inserted into
 * its default workflow. This occurs at the time this behavior is attached to the active record instance.</li>
 * <li><b>workflowSourceComponent</b> : (string) name of the simple workflow source component to use with this
 * behavior. By ddefault this parameter is set to 'swSource'.</li>
 * <li><b>enableEvent</b> : (boolean) when TRUE, events are fired when the owner model evolves in the workflow. Please
 * note that even if events are enabled by configuration they could be automatically disabled by the
 * behavior if the owner model doesn't support sW events (i.e if it doesn't inherit from SWActiveRecord). By default
 * this parameter is set to true.</li>
 * <li><b>transitionBeforeSave</b>: (boolean) if a workflow transition is associated with a task, this parameter
 * defines whether the task should be executed before or after the owner model is saved. It has no effect
 * if the transition is done programatically by a call to swNextStatus, but only if it is done when the 
 * owner model is saved.</li>
 * </ul>
 * @author Raoul
 *
 */			
class SWActiveRecordBehavior extends CBehavior {
	/**
	 * @var string column name where status is stored. If this attribute doesn't exist for
	 * a model, the Workflow behavior is automatically disabled and a warning is logged.<br/>
	 * default value : 'status'
	 */
	public $statusAttribute = 'status';
	/**
	 * @var string workflow name that should be used by default for the owner model. If no workflow id
	 * is configured, it is automatically created based on the owner model name, prefixed with
	 * the SWWorkflowSource->workflowNamePrefix.
	 * default value : SWWorkflowSource->workflowNamePrefix . ModelName
	 */
	public $defaultWorkflow=null;
	/**
	 * @var boolean if true, the model is automatically inserted in the workflow just after
	 * construction. Otherwise, it is developer responsability to insert the model in the workflow.<br/>
	 * default value : true 
	 */
	public $autoInsert=true;		
	/**
	 * @var string name of the workflow source component to use with this behavior.<br/>
	 * default value : swSource
	 */
	public $workflowSourceComponent='swSource';
	/**
	 * @var boolean when TRUE, this behavior will fire SW events. Note that even if 
	 * is true, this doesn't garantee that SW events will be fired as another condition is that the owner
	 * component provides SWEvent handlers.
	 * default value : true
	 */
	public $enableEvent=true;
	/**
	 * @var boolean (default TRUE) Tells wether transition process and onAfterTransition event should
	 * occur before, or after the owner active Record is saved.<br/>
	 * default value : true
	 */
	public $transitionBeforeSave=true;
	
	///////////////////////////////////////////////////////////////////////////////////////////
	// private members
	
	private $_delayedTransition=null;			// delayed transition  (only when change status occures during save)
	private $_delayedEvent=array();				// delayed event stack (only when change status occures during save)
	private $_beforeSaveInProgress=false;		// prevent delayed event fire when status is changed by a call to swNextStatus
	private $_status=null;						// internal status for the owner model
	private $_wfs;								// workflow source component reference	
	private $_locked=false;						// prevent reentrance	
		
	//
	///////////////////////////////////////////////////////////////////////////////////////////	
	/**
	 * @var string name of the class the owner should inherit from in order for SW events
	 * to be enabled.
	 */
	protected $eventClassName='SWActiveRecord';	

	const SW_LOG_CATEGORY='application.simpleWorkflow';
	const SW_I8N_CATEGORY='simpleworkflow';

	/**
	 * @return reference to the workflow source used by this behavior
	 */
	private function getSWSource(){
		return $this->_wfs;
	}
	/**
	 * Checks that the owner component is able to handle workflow events that could be fired
	 * by this behavior
	 * 
	 * @param CComponent $owner the owner component attaching this behavior
	 * @param string $className 
	 * @return bool TRUE if workflow events are fired, FALSE if not.
	 */
	protected function canFireEvent($owner,$className){
		return is_a($owner, $className);
	}	
	/**
	 * If the owner component is inserted into a workflow, this method returns the SWNode object
	 * that represent this status, otherwise NULL is returned.
	 * 
	 * @return SWNode the current status or NULL if no status is set
	 */
	public function swGetStatus(){
		return $this->_status;
	}
	/**
	 * @return bool TRUE if workflow events are fire by this behavior, FALSE if not.
	 */
	public function swIsEventEnabled(){
		return $this->enableEvent;
	}	
	/**
	 * Use this method to find out if the owner component is currently inserted into a workflow.
	 * This method is equivalent to swGetStatus()!=null.
	 * 
	 * @return boolean true if the owner model is in a workflow, FALSE otherwise
	 */
	public function swHasStatus(){
		return !is_null($this->_status);
	}	
	/**
	 * acquire the lock in order to avoid reentrance
	 * 
	 * @throws SWException
	 */
	private function _lock(){
		if($this->_locked==true){
			throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'re-entrant exception on set status'),
				SWException::SW_ERR_REETRANCE
			);
		}
		$this->_locked=true;		
	}
	/**
	 * Release the lock
	 */
	private function _unlock(){
		$this->_locked=false;
	}	
	/**
	 * Update the owner model attribute configured to store the current status and the internal
	 * value too.
	 * 
	 * @param SWnode $SWNode internal status is set to this node
	 */
	private function _updateStatus($SWNode){
		if(!is_a($SWNode,'SWNode'))
			throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'SWNode object expected'),SWException::SW_ERR_WRONG_TYPE);
		Yii::trace('_updateStatus : '.$SWNode->toString(),self::SW_LOG_CATEGORY);
		$this->_status=$SWNode;
	}		
	/**
	 * Returns the current workflow Id the owner component is inserted in, or NULL if the owner
	 * component is not inserted into a workflow.
	 * 
	 * @param string current workflow Id or NULL
	 */
	public function swGetWorkflowId() {
		return ($this->swHasStatus()?$this->_status->getWorkflowId():null);
	}		
	/**
	 * Overloads parent attach method so at the time the behavior is about to be 
	 * attached to the owner component, the behavior is initialized.<br/>
	 * During the initialisation, following actions are performed:<br/>
	 * <ul>
	 * 	<li>Is there a default workflow associated with the owner component ? : if not, and if the 
	 * behavior is initialized with autoInsert set to TRUE, an exception is thrown as it will not be
	 * possible to insert the component into a workflow.</li>
	 * 	<li>If a default workflow is available for the owner component, and if autoInsert is set to TRUE,
	 * the component is inserted in the initial status of its default workflow.
	 * </li>
	 * <li>Check whether or not, workflow events should be enabled, by testing if the owner component
	 * class inherits from the 'SWComponent' class. </li>
	 * </ul>
	 * 
	 * @see base/CBehavior::attach()
	 */
	public function attach($owner){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if( ! $this->canFireEvent($owner, $this->eventClassName)){
			if( $this->swIsEventEnabled()){
				
				// workflow events are enabled by configuration but the owner component is not
				// able to handle workflow event : warning
				
				Yii::log(Yii::t(self::SW_I8N_CATEGORY,'events disabled : owner component doesn\'t inherit from {className}',
							array('{className}' => $this->eventClassName)),
					CLogger::LEVEL_WARNING,self::SW_LOG_CATEGORY);	
			}
			$this->enableEvent=false;	// force
		}
		parent::attach($owner);
		
		$this->_wfs= Yii::app()->{$this->workflowSourceComponent};
		
		$this->initialize();
				
	}	
	/**
	 * This method is called to initialize the current owner status. If a default workflow can
	 * be found and if 'autoInsert' is set to TRUE, the owner component is inserted in the
	 * worflow now, by calling swInsertToWorkflow().
	 * 
	 * @throws SWException
	 */
	protected function initialize(){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if(is_a($this->getOwner(), 'CActiveRecord')){
			
			$statusAttributeCol = $this->getOwner()->getTableSchema()->getColumn($this->statusAttribute);
			if(!isset($statusAttributeCol) || $statusAttributeCol->type != 'string' )
			{		
		    	throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'attribute {attr} not found',
		    		array('{attr}'=>$this->statusAttribute)),SWException::SW_ERR_ATTR_NOT_FOUND);
		    }			
		}		
		$workflow = $this->swGetDefaultWorkflowId();
		if($this->autoInsert){			
			Yii::trace('owner auto-inserted into workflow ',$workflow,self::SW_LOG_CATEGORY);				
			$this->swInsertToWorkflow($workflow);
		}
	}	
	/**
	 * Finds out what should be the default workflow to use with the owner model.
	 * A default workflow id in several ways which are explored by this method, in the following order:
	 * <ul>
	 * 	<li>behavior initialization parameter <i>defaultWorkflow</i></li>
	 * 	<li>owner component method <i>workflow</i> : if the owner component is able to provide the 
	 * complete workflow, this method will invoke SWWorkflowSource.addWorkflow</li>
	 *  <li>created based on the configured prefix followed by the model class name </li>
	 * </ul>
	 * @return string workflow id to use with the owner component or NULL if now workflow was found
	 */
	public function swGetDefaultWorkflowId(){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		
		if(is_null($this->defaultWorkflow))
		{
			$workflowName=null;
			if(! is_null($this->defaultWorkflow)) {
				
				// the behavior has been initialized with the default workflow name
				// that should be used.
				
				$workflowName=$this->defaultWorkflow;
			}
			elseif(method_exists($this->getOwner(),'workflow'))
			{
				
				$wf=$this->getOwner()->workflow();
				if( is_array($wf)){
					
					// Cool ! the owner is able to provide its own private workflow definition ...and optionally
					// a workflow name too. If no workflow name is provided, the model name is used to
					// identity the workflow
					
					$workflowName=(isset($wf['name'])?$wf['name']:
						$this->getSWSource()->workflowNamePrefix.get_class($this->getOwner()));
					$this->getSWSource()->addWorkflow($wf,$workflowName);
					Yii::trace('workflow provided by owner',self::SW_LOG_CATEGORY);
					
				}elseif(is_string($wf)) {
					
					// the owner returned a string considered as its default workflow Id
	
					$workflowName=$wf;
				}else {
					throw new SWException(Yii::t(self::SW_I8N_CATEGORY, 'incorrect type returned by owner method : string or array expected'),
						SWException::SW_ERR_WRONG_TYPE);
				}
			}else {
	
				// ok then, let's use the owner model name as the workflow name and hope that
				// its definition is available in the workflow basePath.
				
				$workflowName=$this->getSWSource()->workflowNamePrefix.get_class($this->getOwner());
			}
			$this->defaultWorkflow=$workflowName;
			Yii::trace('defaultWorkflow : '.$this->defaultWorkflow,self::SW_LOG_CATEGORY);			
		}
		return $this->defaultWorkflow;
	}	
	/**
	 * Insert the owner component into the workflow whose id is passed as argument.
	 * If NULL is passed as argument, the default workflow is used.
	 * 
	 * @param string workflow Id or NULL.
	 * @throws SWException the owner model is already in a workflow
	 */
	public function swInsertToWorkflow($workflow=null){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
//		if($this->swHasStatus()){
//			throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'object already in a workflow'),
//				SWException::SW_ERR_IN_WORKFLOW);
//		}
		$this->_lock();		
		try{
			$wfName=(is_null($workflow)==true?$this->swGetDefaultWorkflowId():$workflow);
			$initialSt=$this->getSWSource()->getInitialNode($wfName);
			
			$this->onEnterWorkflow(
				new SWEvent($this->getOwner(),null,$initialSt)
			);

			$this->_updateStatus($initialSt);		
		
		}catch(SWException $e){
			$this->_unlock();
			throw $e;	
		}
		$this->_unlock();
	}	
	/**
	 * This method returns a list of nodes that can be actually reached at the time the method is called. To be reachable,
	 * a transition must exist between the current status and the next status, AND if a constraint is defined, it must be 
	 * evaluated to true.
	 * 
	 * @return array SWNode object array for all nodes thats can be reached from the current node. 
	 */
	public function swGetNextStatus(){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		$n=array();
		if($this->swHasStatus()){
			$allNxtSt=$this->getSWSource()->getNextNodes($this->_status);
			if(!is_null($allNxtSt)){
				foreach ( $allNxtSt as $aStatus ) {
       				if($this->swIsNextStatus($aStatus) == true){
       					$n[]=$aStatus;	
       				}
				}
			}
		}else{
			$n[]=$this->getSWSource()->getInitialNode($this->swGetDefaultWorkflowId());
		}
		return $n;
	}
	/**
	 * Returns all statuses belonging to the workflow the owner component is inserted in. If the 
	 * owner component is not inserted in a workflow, an empty array is returned.
	 * 
	 * @return array list of SWNode objects. 
	 */
	public function swGetAllStatus(){
		if(!$this->swHasStatus() or is_null($this->swGetWorkflowId()))
			return array();
		else
			return $this->getSWSource()->getAllNodes($this->swGetWorkflowId());
	}
	/**
	 * Checks if the status passed as argument can be reached from the current status. This occurs when
	 * <br/>
	 * <ul>
	 * 	<li>a transition has be defined in the workflow between those 2 status</li>
	 * <li>the destination status has a constraint that is evaluated to true in the context of the
	 * owner model</li>
	 * </ul>
	 * Note that if the owner component is not in a workflow, this method returns true if argument 
	 * $nextStatus is the initial status for the workflow associated with the owner model. In other words
	 * the initial status for a given workflow is considered as the 'next' status, for all component associated
	 * to this workflow but not inserted in it. Of course, if a constraint is associated with the initial
	 * status, it must be evaluated to true.
	 * 
	 * @param mixed nextStatus String or SWNode object for the next status
	 * @return boolean TRUE if the status passed as argument can be reached from the current status, FALSE
	 * otherwise.
	 */
	public function swIsNextStatus($nextStatus){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		
		$bIsNextStatus=false;
		
		// get (create) a SWNode object
		
		$nxtNode=$this->swCreateNode($nextStatus);
		
		if( (! $this->swHasStatus() and $this->swIsInitialStatus($nextStatus)) or 
		    (  $this->swHasStatus() and $this->getSWSource()->isNextNode($this->_status,$nxtNode)) ){
			
			// a workflow initial status is considered as a valid 'next' status from the NULL
			// status.

		    // there is a transition between current and next status,
		    // now let's see if constraints to actually enter in the next status
		    // are evaluated to true.
		    
		    $swNodeNext=$this->getSWSource()->getNodeDefinition($nxtNode);
		    if($this->_evaluateConstraint($swNodeNext->getConstraint()) == true)
		    {
		    	$bIsNextStatus=true;
		    }
		    else 
		    {
		    	$bIsNextStatus=false;
		    	Yii::trace('constraint evaluation returned FALSE for : '.$swNodeNext->getConstraint(),
		    		self::SW_LOG_CATEGORY
		    	);		    	
		    }			
		}
		Yii::trace('SWItemBehavior->swIsNextStatus returns : {result}'.($bIsNextStatus==true?'true':'false'),
			self::SW_LOG_CATEGORY
		);
		return $bIsNextStatus;
	}
	/**
	 * Creates a node from the string passed as argument. If $str doesn't contain
	 * a workflow Id, this method uses the workflowId associated with the owner
	 * model. The node created here doesn't have to exist within a workflow.
	 * 
	 * @param string $str string status name
	 * @return SWNode the node
	 */
	public function swCreateNode($str){
		return $this->getSWSource()->createSWNode($str,$this->swGetDefaultWorkflowId());
	}
	/**
	 * Evaluate the expression passed as argument in the context of the owner
	 * model and returns the result of evaluation as a boolean value. 
	 */
	private function _evaluateConstraint($constraint){
		return (is_null($constraint) or
			$this->getOwner()->evaluateExpression($constraint) ==true?true:false);
	}	
	/**
	 * If a expression is attached to the transition, then it is evaluated in the context
	 * of the owner model, otherwise, the processTransition event is raised. Note that the value
	 * returned by the expression evaluation is ignored.
	 */
	private function _runTransition($sourceSt,$destSt,$event=null){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if(!is_null($sourceSt) and is_a($sourceSt,'SWNode')){
			$tr=$sourceSt->getTransition($destSt);
			Yii::trace('transition process = '.$tr,self::SW_LOG_CATEGORY);
			if(!is_null($tr)){
				if( $this->transitionBeforeSave){
					$this->getOwner()->evaluateExpression($tr);	
				}else {
					$this->_delayedTransition = $tr;
				}				
			}	
		}
	}		
	/**
	 * Test if the status passed as argument a final status. If null is passed as argument
	 * tests if the current status of the owner component is a final status. By definition a final 
	 * status as no outgoing transition to other status.
	 * 
	 * @param status status to test, or null (will test current status)
	 * @return boolean TRUE when the owner component is in a final status, FALSE otherwise
	 */
	public function swIsFinalStatus($status=null){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		$workflowId=($this->swHasStatus()?$this->swGetWorkflowId():$this->swGetDefaultWorkflowId());
		
		if(is_null($status)==false){
			$swNode=$this->getSWSource()->createSWNode($status,$workflowId);
		}elseif($this->swHasStatus() == true) {
			$swNode=$this->_status;
		}else {
			return false;
		}		
		return count($this->getSWSource()->getNextNodes($swNode,$workflowId))===0;
	}	
	/**
	 * Checks if the status passed as argument, or the current status (if NULL is passed) is the initial status
	 * of the corresponding workflow. An exception is raised if the owner model is not in a workflow
	 * and if $status is null.
	 * 
	 * @param mixed $status
	 * @return boolean TRUE if the owner component is in an initial status or if $status is the initial
	 * status for the owner component default workflow.
	 * @throws SWException
	 */
	public function swIsInitialStatus($status=null){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		
		// search for initial status associated with the workflow for the owner component 
		
		$workflowId=($this->swHasStatus()?$this->_status->getWorkflowId():$this->swGetDefaultWorkflowId());
		$swInit=$this->getSWSource()->getInitialNode($workflowId);
		
		// get the node to compare to the initial node found above
		
		if(is_null($status)==false){
			$swNode=$this->getSWSource()->createSWNode($status,$workflowId);
		}elseif($this->swHasStatus() == true) {
			$swNode=$this->_status;
		}else {
			throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'could not create node'),
				SWException::SW_ERR_CREATE_FAILS);
		}
		return $swInit->equals($swNode); // compare now
	}				
	/**
	 * Validate the status attribute stored in the owner model. This attribute is valid if : <br/>
	 * <ul>
	 * 	<li>it is not empty</li>
	 * 	<li>it contains a valid status name</li>
	 * 	<li>this status can be reached from the current status</li>
	 * 	<li>or it is equal to the current status (no status change)</li>
	 * </ul>
	 * @param string $attribute status attribute name (by default 'status')
	 * @param mixed $value current value of the status attribute provided as a string or a SWNode object
	 * @return boolean TRUE if the status attribute contains a valid value, FALSE otherwise
	 */
	public function swValidate($attribute, $value){
        Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
        $bResult=false;
        try{	        			
        	if(is_a($value, 'SWNode')){
        		$swNode=$value;
        	}else {
        		$swNode=$this->swCreateNode($value);
        	}  
			if($this->swIsNextStatus($value)==false and $swNode->equals($this->swGetStatus()) == false){
				$this->getOwner()->addError($attribute,Yii::t(self::SW_I8N_CATEGORY,'not a valid next status'));
			}else {
				$bResult=true;
			}          	
        }catch(SWException $e){
        	$this->getOwner()->addError($attribute,Yii::t(self::SW_I8N_CATEGORY,'value {node} is not a valid status',array(
        		'{node}'=>$value)
        	));
        }		
        return $bResult;
	}		
	/**
	 * 
	 * @param mixed $nextStatus
	 * @return boolean 
	 */	
	public function swNextStatus($nextStatus=null){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		$bResult=false;
		
		// if no nextStatus is passed, it is assumed that the owner component
		// is not in a workflow and so, try to insert it in its associated default
		// workflow.
		
		if( ! $this->swHasStatus()){
			$this->swInsertToWorkflow();
			$bResult=true;
		}else {

			// $nextStatus may be provided as an array with a 'statusAttribute' key (POST and
			// GET arrays for instance)
			
			if( is_array($nextStatus) and isset($nextStatus[$this->statusAttribute])){
				$nextStatus=$nextStatus[$this->statusAttribute];
			}		
			
			// ok, now nextStatus is known. It is time to validate that it can be reached
			// from current status, and if yes, perform the status change
			
			try {
				$this->_lock();
				$workflowId = $this->swGetWorkflowId();
				if( $this->swIsNextStatus($nextStatus,$workflowId))
				{	
					// the $nextStatus can be reached from the current status, it is time
					// to run the transition. 
	
					$newStObj=$this->getSWSource()->getNodeDefinition($nextStatus,$workflowId);
					$event=new SWEvent($this->getOwner(),$this->_status,$newStObj);
					if( ! $this->swHasStatus()){
						$this->onEnterWorkflow($event);
					}else {
						$this->onBeforeTransition($event);	
					}					
					
					$this->onProcessTransition($event);
					$this->_runTransition($this->_status,$newStObj);
					$this->_updateStatus($newStObj);
					$this->onAfterTransition($event);
					if($this->swIsFinalStatus()){
						$this->onFinalStatus($event);
					}
					$bResult=true;
	
				} else {
					throw new SWException('status can\'t be reached',SWException::SW_ERR_STATUS_UNREACHABLE);
				}	
			} catch (CException $e) {
				$this->_unlock();
				Yii::log($e->getMessage(),CLogger::LEVEL_ERROR,self::SW_LOG_CATEGORY);			
				throw $e;			
			}
			$this->_unlock();
		}
		return $bResult;
	}		
	
	///////////////////////////////////////////////////////////////////////////////////////
	// Events
	//
	
	/**
	 * 
	 * @see base/CBehavior::events()
	 */
	public function events()
	{
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
			
		// this behavior could be attached to a CComponent based class other
		// than CActiveRecord.
		
		if(is_a($this->getOwner(), 'CActiveRecord')){
			$ev=array(
				'onBeforeSave'=> 'beforeSave',
				'onAfterSave' => 'afterSave',
				'onAfterFind' => 'afterFind',
			);
		} else {
			$ev=array();
		}
		
		if($this->swIsEventEnabled())		
		{
			Yii::trace('workflow event enabled',self::SW_LOG_CATEGORY);
			$this->getOwner()->attachEventHandler('onEnterWorkflow',array($this->getOwner(),'enterWorkflow'));
			$this->getOwner()->attachEventHandler('onBeforeTransition',array($this->getOwner(),'beforeTransition'));
			$this->getOwner()->attachEventHandler('onAfterTransition',array($this->getOwner(),'afterTransition'));
			$this->getOwner()->attachEventHandler('onProcessTransition',array($this->getOwner(),'processTransition'));
			$this->getOwner()->attachEventHandler('onFinalStatus',array($this->getOwner(),'finalStatus'));
			$ev=array_merge($ev, array(
				// Custom events
				'onEnterWorkflow'	 => 'enterWorkflow',	
				'onBeforeTransition' => 'beforeTransition',
				'onProcessTransition'=> 'processTransition',
				'onAfterTransition'  => 'afterTransition',
				'onFinalStatus'		 => 'finalStatus',			
			));
		}
		return $ev;
	}	
	/**
	 * Responds to {@link CActiveRecord::onBeforeSave} event.
	 * 
	 * Overrides this method if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * You may set {@link CModelEvent::isValid} to be false to quit the saving process.
	 * @param CModelEvent event parameter
	 */
	public function beforeSave($event)
	{
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		
		$this->_beforeSaveInProgress = true;
		if(!$this->getOwner()->hasErrors()){
			$ownerStatus = $this->getOwner()->{$this->statusAttribute};

			if( $this->swIsNextStatus($ownerStatus))
			{
				
				$this->swNextStatus($this->getOwner()->{$this->statusAttribute});
				$this->getOwner()->{$this->statusAttribute} = $this->swGetStatus()->toString();	
				Yii::trace(__CLASS__.'.'.__FUNCTION__.'. New status is now : '.$this->swGetStatus()->toString());			
			} 
			elseif( ! $this->swGetStatus()->equals($ownerStatus)) 
			{
				throw new SWException(Yii::t(self::SW_I8N_CATEGORY,'incorrect status : {status}',
					array('{status}'=>$ownerStatus)),SWException::SW_ERR_WRONG_STATUS);
			}
		} else {
			Yii::trace(__CLASS__.'.'.__FUNCTION__.': hasErros');
		}
		$this->_beforeSaveInProgress = false;
		return true;
	}	
	/**
	 * When option transitionBeforeSave is false, if a task is associated with
	 * the transition that was performed, it is executed now, that it after the activeRecord
	 * owner component has been saved. The onAfterTransition is also raised.
	 * 
	 * @param SWEvent $event
	 */
	public function afterSave($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if(!is_null($this->_delayedTransition)){
			Yii::trace('running delayed transition process');
			$tr=$this->_delayedTransition;
			$this->_delayedTransition=null;
			$this->getOwner()->evaluateExpression($tr);			
		}
		
		foreach ($this->_delayedEvent as $delayedEvent) {
			$this->_raiseEvent($delayedEvent['name'],$delayedEvent['objEvent']);
		}
		$this->_delayedEvent=array();
	}
	/**
	 * Responds to {@link CActiveRecord::onAfterFind} event.
	 * This method is called when a CActiveRecord instance is created from DB access (model
	 * read from DB). At this time, the worklow behavior must be initialized.
	 * 
	 * @param CEvent event parameter
	 */
	public function afterFind($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		
		if( !$this->getEnabled())
			return;
			
		try{
			// call _init here because 'afterConstruct' is not called when an AR is created
			// as the result of a query, and we need to initialize the behavior.
		
			$status=$this->getOwner()->{$this->statusAttribute};
			if(!is_null($status)){
				
				// the owner model already has a status value (it has been read from db)
				// and so, set the underlying status value without performing any transition
				
				$st=$this->getSWSource()->getNodeDefinition($status,$this->swGetWorkflowId());
				$this->_updateStatus($st);				
				
			}else {
				
				// the owner doesn't have a status : initialize the behavior. This will 
				// auto-insert the owner into its default workflow if autoInsert was set to TRUE
				
				$this->initialize();
			}
		}catch(SWException $e){				
			Yii::log(Yii::t(self::SW_I8N_CATEGORY,'failed to set status : {status}',
				array('{status}'=>$status)),
				CLogger::LEVEL_WARNING,
				self::SW_LOG_CATEGORY
			);
			Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);			
		}		
	}
	/**
	 * Log event fired
	 * 
	 * @param string $ev event name
	 * @param SWNode $source
	 * @param SWNode $dest
	 */
	private function _logEventFire($ev,$source,$dest){
		Yii::log(Yii::t('simpleWorkflow','event fired : \'{event}\' status [{source}] -> [{destination}]',
			array(
				'{event}'		=> $ev,
				'{source}'		=> (is_null($source)?'null':$source->toString()),
				'{destination}'	=> $dest->toString(),
			)),
			CLogger::LEVEL_INFO,
			self::SW_LOG_CATEGORY
		);		
	}	
	private function _raiseEvent($evName,$event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if( $this->swIsEventEnabled() ){
			$this->_logEventFire($evName, $event->source, $event->destination);
			$this->getOwner()->raiseEvent($evName, $event);			
		}
	}
	/**
	 * Default implementation for the onEnterWorkflow event.<br/>
	 * This method is dedicated to be overloaded by custom event handler.
	 * @param SWEvent the event parameter
	 */	
	public function enterWorkflow($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);	
	}	
	/**
	 * This event is raised after the record instance is inserted into a workflow. This may occur
	 * at construction time (new) if the behavior is initialized with autoInsert set to TRUE and in this
	 * case, the 'onEnterWorkflow' event is always fired. Consequently, when a model instance is created
	 * from database (find), the onEnterWorkflow is fired even if the record has already be inserted
	 * in a workflow (e.g contains a valid status).
	 * 
	 * @param SWEvent the event parameter
	 */	
	public function onEnterWorkflow($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		$this->_raiseEvent('onEnterWorkflow',$event);
	}	
	/**
	 * Default implementation for the onBeforeTransition event.<br/>
	 * This method is dedicated to be overloaded by custom event handler.
	 * @param SWEvent the event parameter
	 */	
	public function beforeTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);	
	}
	/**
	 * This event is raised before a workflow transition is applied to the owner instance.
	 * 
	 * @param SWEvent the event parameter
	 */	
	public function onBeforeTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		$this->_raiseEvent('onBeforeTransition',$event);	
	}	
	/**
	 * Default implementation for the onProcessTransition event.<br/>
	 * This method is dedicated to be overloaded by custom event handler.
	 * @param SWEvent the event parameter
	 */	
	public function processTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);	
	}	
	/**
	 * This event is raised when a workflow transition is in progress. In such case, the user may
	 * define a handler for this event in order to run specific process.<br/>
	 * Depending on the <b>'transitionBeforeSave'</b> initialization parameters, this event could be
	 * fired before or after the owner model is actually saved to the database. Of course this only
	 * applies when status change is initiated when saving the record. A call to swNextStatus()
	 * is not affected by the 'transitionBeforeSave' option.
	 * 
	 * @param SWEvent the event parameter
	 */		
	public function onProcessTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if( $this->transitionBeforeSave || $this->_beforeSaveInProgress == false){
			$this->_raiseEvent('onProcessTransition',$event);	
		}else {
			$this->_delayedEvent[]=array('name'=> 'onProcessTransition','objEvent'=>$event);
		}	
	}		
	/**
	 * Default implementation for the onAfterTransition event.<br/>
	 * This method is dedicated to be overloaded by custom event handler.
	 * 
	 * @param SWEvent the event parameter
	 */	
	public function afterTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);	
	}	
	/**
	 * This event is raised after the onProcessTransition is fired. It is the last event fired 
	 * during a non-final transition.<br/>
	 * Again, in the case of an AR being saved, this event may be fired before or after the record
	 * is actually save, depending on the <b>'transitionBeforeSave'</b> initialization parameters.
	 * 
	 * @param SWEvent the event parameter
	 */		
	public function onAfterTransition($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if( $this->transitionBeforeSave || $this->_beforeSaveInProgress == false){
			$this->_raiseEvent('onAfterTransition',$event);	
		}else {
			$this->_delayedEvent[]=array('name'=> 'onAfterTransition','objEvent'=>$event);
		}	
	}		
	/**
	 * Default implementation for the onEnterWorkflow event.<br/>
	 * This method is dedicated to be overloaded by custom event handler.
	 * @param SWEvent the event parameter
	 */		
	public function finalStatus($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);	
	}	
	/**
	 * This event is raised at the end of a transition, when the destination status is a 
	 * final status (i.e the owner model has reached a status from where it will not be able
	 * to move).
	 * 
	 * @param SWEvent the event parameter
	 */	
	public function onFinalStatus($event){
		Yii::trace(__CLASS__.'.'.__FUNCTION__,self::SW_LOG_CATEGORY);
		if( $this->transitionBeforeSave || $this->_beforeSaveInProgress == false){
			$this->_raiseEvent('onFinalStatus',$event);	
		}else {
			$this->_delayedEvent[]=array('name'=> 'onFinalStatus','objEvent'=>$event);
		}	
	}	
}
?>