<?php
/**
 * <p>
 * This validator should be used to validate the 'status' attribute for an active record
 * object, before it is saved. It tests if the transition that may be about to occur is valid. 
 * Moreover, if <strong>$enableSwValidation</strong> is set to true, this validator applies all
 * validators that may have been defined by the model, for the scenario associated to the transition
 * being done.<br/>
 * Scenario name associated with a transition, has following format : <br/>
 * <strong> sw:[currentStatus]_[nextStatus]</strong><br/>
 * For instance, if the model being validated is currently in status 'A' and it is sent in status 'B', the
 * corresponding scenario name is 'sw:A_B'. Note that if the destination status doesn't belong to he same
 * workflow as the current status, [nextStatus] is in the form 'workflowId/statusId' (e.g 'sw:A_workflow/B').
 * Eventually, when the model enters in a workflow, the scenario name is '_[nextStatus]' where 'nextStatus'
 * includes the workflow Id (e.g 'sw:_workflowIs/statusId').
 * </p>
 * <p>
 * 	If this validator is initialized with parameter <b>match</b> set to TRUE, then transitions scenario defined
 * for validators are assumes to be regular expressions. If the current transition matches, then the associated 
 * validator is executed.<br/>
 * For instance, if validator 'required' for attribute A applies to scenarion 'sw:/S1_.?/' then each time the
 * model will pass a transition that leaves status S1 then the \'required\' validator will be executed.  
 * </p>
 */
class SWValidator extends CValidator
{
	/**
	 * @var boolean (default FALSE) Enables simpleWorkflow Validation. When TRUE, the SWValidator not only
	 * validates status change for the model, but also applies all validators that may have been created and
	 * which are associated with the scenario for the transition being done. Such scenario names are based on 
	 * both the current and the next status name.
	 */
	public $enableSwValidation=false;
	/**
	 * 
	 * @var boolean (default FALSE)
	 */
	public $match=false;
	
    const SW_SCENARIO_STATUS_SEPARATOR='_';
    const SW_SCENARIO_PREFIX='sw:';
    private $_lenPrefix=null;
    /**
     * Validate status change and applies all validators defined by the model for the current transition scenario if
     * enableSwValidation is TRUE. If validator parameter 'match' is true, the transition scenario is matched
     * against validator scenario (which are assumed to be regular expressions).
     * 
     * @see validators/CValidator::validateAttribute()
     * @param CModel $model the model to validate
     * @param string $attribute the model attribute to validate
     */
	protected function validateAttribute($model,$attribute)
    {
    	Yii::trace(__CLASS__.'.'.__FUNCTION__,SWActiveRecordBehavior::SW_LOG_CATEGORY);
    	$value=$model->$attribute;
    	
    	if($model->swValidate($attribute,$value)==true and $this->enableSwValidation ===true){

	    	$swScenario=$this->_getSWScenarioName($model, $value);
	    	
	    	Yii::trace('swScenario : '.$swScenario,SWActiveRecordBehavior::SW_LOG_CATEGORY);
			if(!empty($swScenario)){
				if($this->match === true){
					
					// validator scenario are Regular Expression that must match the transition scenarion
					// for the validator to be executed.
					
					$validators=$model->getValidatorList();
					foreach($validators as $validator){						
						//Yii::trace(CVarDumper::dumpAsString($validator));
						if($this->_validatorMatches($validator,$swScenario)){
							Yii::trace('applying validator : '.CVarDumper::dumpAsString($validator),SWActiveRecordBehavior::SW_LOG_CATEGORY);
							$validator->validate($model);
						}
					}									
				}else {
					$swScenario=SWValidator::SW_SCENARIO_PREFIX.$swScenario;
					// execute only validator defined for the current transition scenario ($swScenario)
					
					// getValidators returns validators with no scenario, and the ones
					// that apply to the current scenario (swScenario). 
					$saveScenario=$model->getScenario();
					$model->setScenario($swScenario);
					
					$validators=$model->getValidators();
					
					foreach($model->getValidators() as $validator){
						
						// only run validators that applies to the current (swScenario) scenario
						
						//Yii::trace(CVarDumper::dumpAsString($validator));
						if(isset($validator->on[$swScenario])){
							Yii::trace('applying validator : '.CVarDumper::dumpAsString($validator),SWActiveRecordBehavior::SW_LOG_CATEGORY);
							$validator->validate($model);
						}
					}				
					// restore original scenario so validation can continue.
					$model->setScenario($saveScenario);					
				}
			}
    	}
    } 	
    /**
     * Create the scenario name for the current transition. Scenario name has following format : <br/>
     * <pre> [currentStatus]_[nextStatus]</pre>
     * 
     * @param CModel $model  the model being validated
     * @param string $nxtStatus  the next status name (destination status for the model)
     * @return string SW scenario name for this transition  
     * 
     */
    private function _getSWScenarioName($model,$nxtStatus){
    	$swScenario=null;
        $nextNode=$model->swCreateNode($nxtStatus);
    	$curNode=$model->swGetStatus();
    	if(!is_null($curNode)){
	    	$swScenario=$curNode->getId().SWValidator::SW_SCENARIO_STATUS_SEPARATOR;
    		if($curNode->getWorkflowId()!=$nextNode->getWorkflowId()){
	    		$swScenario.=$nextNode->toString();
	    	}else {
	    		$swScenario.=$nextNode->getId();	
	    	}       		
    	}else {
    		$swScenario='_'.$nextNode->toString();
    	}   
    	return $swScenario; 	
    }
    /**
     * Check that a CValidator based object is defined for a scenario that matches
     * the simple workflow scenario passed as argument.
     * 
     * @param $validator CValidator validator to test
     * @param $swScenario string simple workflow scenario defined as a regular expression
     */
    private function _validatorMatches($validator,$swScenario){
    	$bResult=false;
    	if(isset($validator->on)){
    		$validatorScenarios=(is_array($validator->on)?$validator->on:array($validator->on));
			foreach ($validatorScenarios as $valScenario) {

				// SW Scenario validator must begin with a non-empty prefix (default 'sw:')
				// and then define a valide regular expression 
				$re=$this->_extractSwScenarioPattern($valScenario);
				
				if( ! is_null($re)){
					//echo "($re)\n";
					Yii::trace('testing scenario : '.$valScenario.'('.$re.') againt : '.$swScenario,SWActiveRecordBehavior::SW_LOG_CATEGORY);
					if(preg_match($re, $swScenario)){
						$bResult=true;
						break;
					}					
				}
			}    		
    	}
    	return $bResult;
    }
    /**
     * Extract a regular expression pattern out of a simepleWorkflow scenario name
     * 
     * @param $valScenario String validator scenario name (example : 'sw:/^status1_.*$/')
     * @return String regular expression (example : '/^status1_.*$/')
     */
    private function _extractSwScenarioPattern($valScenario){
    	$pattern=null;
    	if($this->_lenPrefix==null)
    		$this->_lenPrefix=strlen(SWValidator::SW_SCENARIO_PREFIX);
    	if( $this->_lenPrefix != 0 and 
			strpos($valScenario, SWValidator::SW_SCENARIO_PREFIX) === 0) {
			$pattern=substr($valScenario, $this->_lenPrefix);
		}  	
		return $pattern;
    }
}
?>
