<?php

// @todo: remove if composer autoloading used
require_once('cleantalk.class.php');

/**
 * CleanTalk API application component.
 * Required set apiKey property.
 *
 * @version 1.1.0
 * @author CleanTalk (welcome@cleantalk.ru)
 * @copyright (C) 2013 Сleantalk team (http://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
class CleanTalkApi extends CApplicationComponent
{
    const AGENT_VERSION = 'yii-1.1.0';
    const FORM_SUBMIT_START_TIME = 'cleantalk_form_submit_start_time';

    /**
     * API key
     * @var string
     */
    public $apiKey;

    /**
     * API URL
     * @var string
     */
    public $apiUrl = 'http://moderate.cleantalk.ru';

    /**
     * API response lang en|ru
     * @var string
     */
    public $responseLang = 'en';

    /**
     * Check js hidden field ID
     * @var string
     */
    public $checkJsHtmlId;

    /**
     * API last result comment (deny message)
     * @var string
     */
    protected $lastResultComment;

    /**
     * @inheritdoc
     * @throws CException
     */
    public function init()
    {
        parent::init();
        if (is_null($this->apiKey)) {
            throw new CException(Yii::t('cleantalk', 'CleanTalkApi configuration must have "apiKey" value'));
        }
        if (is_null($this->checkJsHtmlId)) {
            $this->checkJsHtmlId = md5(rand(0, 1000));
        }
    }

    /**
     * Check if user registration allow
     * @param string $email user email
     * @param string $nickName user nickName
     * @return bool true, if user registration allow
     */
    public function isAllowUser($email = '', $nickName = '')
    {

        $ctRequest = $this->createRequest();
        $ctRequest->sender_nickname = $nickName;
        $ctRequest->sender_email = $email;

        /**
         * @var CleantalkResponse $ctResult CleanTalk API call result
         */
        $ctResult = $this->sendRequest($ctRequest, 'isAllowUser');

        $this->lastResultComment = $ctResult->comment;

        if ($ctResult->inactive == 1) {
            Yii::log(
                'Need admin approval for "isAllowUser": ' . $ctResult->comment,
                CLogger::LEVEL_INFO,
                'ext.cleantalk'
            );
        }

        return $ctResult->allow == 1;
    }

    /**
     * Check if user text message allow
     * @param string $email user email
     * @param string $nickName user nickName
     * @param string $message message
     * @return bool
     */
    public function isAllowMessage($message, $email = '', $nickName = '')
    {

        $ctRequest = $this->createRequest();
        $ctRequest->message = $message;
        $ctRequest->sender_email = $email;
        $ctRequest->sender_nickname = $nickName;

        /**
         * @var CleantalkResponse $ctResult CleanTalk API call result
         */
        $ctResult = $this->sendRequest($ctRequest, 'isAllowMessage');
        $this->lastResultComment = $ctResult->comment;

        if ($ctResult->inactive == 1) {
            Yii::log(
                'Need admin approval for "isAllowMessage": ' . $ctResult->comment,
                CLogger::LEVEL_INFO,
                'ext.cleantalk'
            );
        }

        return $ctResult->allow == 1;
    }

    /**
     * Get last API call result
     * @return string
     */
    public function getValidationError()
    {
        return $this->lastResultComment;
    }

    /**
     * Generate form Javascript check code
     * @return string
     */
    public function getCheckJsCode()
    {
        return md5($this->apiKey . Yii::app()->getId());
    }

    /**
     * Set begin time of submitting form
     */
    public function startFormSubmitTime()
    {
        Yii::app()->user->setState(self::FORM_SUBMIT_START_TIME, time());
    }

    /**
     * Generate CleanTalk check js hidden form element.
     * Set form submit time.
     * @return string
     */
    public function checkJsHiddenField()
    {
        $this->startFormSubmitTime();
        Yii::app()->clientScript
            ->registerScript(
                'cleantalk_javascript_' . $this->checkJsHtmlId,
                'document.getElementById("' . $this->checkJsHtmlId . '").value=document.getElementById("' . $this->checkJsHtmlId . '").value.replace(-1,"' . $this->getCheckJsCode() . '");',
                CClientScript::POS_END
            );
        return CHtml::hiddenField('ct_checkjs', '-1', array('id' => $this->checkJsHtmlId));
    }

    /**
     * Get form submit time in seconds.
     * @return int|null
     */
    public function getFormSubmitTime()
    {
        $startTime = Yii::app()->user->getState(self::FORM_SUBMIT_START_TIME);
        return $startTime > 0 ? time() - $startTime : null;
    }

    /**
     * Is javascript enabled
     * @return int
     */
    protected function getIsJavascriptEnable()
    {
        $formJsCode = Yii::app()->request->getParam('ct_checkjs');
        return $formJsCode == $this->getCheckJsCode() ? 1 : 0;
    }

    /**
     * Create request for CleanTalk API.
     * @return CleantalkRequest
     */
    protected function createRequest()
    {
        $ctRequest = new CleantalkRequest();
        $ctRequest->auth_key = $this->apiKey;
        $ctRequest->response_lang = $this->responseLang;
        $ctRequest->agent = self::AGENT_VERSION;
        $ctRequest->sender_ip = Yii::app()->request->getUserHostAddress();
        $ctRequest->submit_time = $this->getFormSubmitTime();
        $ctRequest->js_on = $this->getIsJavascriptEnable();

        $ctRequest->sender_info = CJSON::encode(
            array(
                'REFFERRER' => Yii::app()->request->getUrlReferrer(),
                'USER_AGENT' => Yii::app()->request->getUserAgent(),
                'cms_lang' => Yii::app()->getLanguage(),
            )
        );
        return $ctRequest;
    }

    /**
     * @param $request
     * @param $method
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function sendRequest($request, $method)
    {
        $ct = new Cleantalk();
        $ct->server_url = $this->apiUrl;
        if ($method != 'isAllowMessage' && $method != 'isAllowUser') {
            throw new InvalidArgumentException('Method unknown');
        }

        return $ct->$method($request);
    }
}