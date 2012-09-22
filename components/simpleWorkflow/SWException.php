<?php
/**
 * Exception thrown by the simpleWorkflow behavior
 */			
class SWException extends CException {
	const SW_ERR_ATTR_NOT_FOUND=01;
	const SW_ERR_REETRANCE=02;
	const SW_ERR_WRONG_TYPE=03;
	const SW_ERR_IN_WORKFLOW=04;
	const SW_ERR_CREATE_FAILS=05;
	const SW_ERR_WRONG_STATUS=06;
	const SW_ERR_WORKFLOW_NOT_FOUND=07;
	const SW_ERR_WORKFLOW_ID=08;
	const SW_ERR_NODE_NOT_FOUND=09;
	const SW_ERR_STATUS_UNREACHABLE=10;
}
?>