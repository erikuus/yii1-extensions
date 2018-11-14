<?php

/**
 * CleanTalk API CModel validator.
 *
 * Required set check property.
 *
 * @version 1.0.1
 * @author CleanTalk (welcome@cleantalk.ru)
 * @copyright (C) 2013 Сleantalk team (http://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
class CleanTalkValidator extends CValidator
{
    const CHECK_MESSAGE = 'message';
    const CHECK_USER = 'user';

    /**
     * message|user
     * @var string
     */
    public $check;

    /**
     * Email attribute name in model
     * @var string
     */
    public $emailAttribute;

    /**
     * Nickname attribute name in model
     * @var string
     */
    public $nickNameAttribute;

    /**
     * CleanTalk application component ID
     * @var string
     */
    public $apiComponentId = 'cleanTalk';

    /**
     * @inheritdoc
     * @var bool
     */
    public $skipOnError = true;

    /**
     * @inheritdoc
     */
    protected function validateAttribute($object, $attribute)
    {
        $this->checkValidateConfig($object);

        /**
         * @var CleanTalkApi $api
         */
        $api = Yii::app()->getComponent($this->apiComponentId);

        $email = $nick = '';
        if ($this->emailAttribute) {
            $email = $object->{$this->emailAttribute};
        }
        if ($this->nickNameAttribute) {
            $nick = $object->{$this->nickNameAttribute};
        }

        if (self::CHECK_MESSAGE == $this->check) {
            if (!$api->isAllowMessage($object->$attribute, $email, $nick)) {
                $this->addError($object, $attribute, $this->getErrorMessage());
            }
        } elseif (self::CHECK_USER == $this->check) {
            if (!$api->isAllowUser($email, $nick)) {
                $this->addError($object, $attribute, $this->getErrorMessage());
            }
        }
    }

    /**
     * Check validator configuration
     * @param CModel $object
     * @throws CException
     * @throws DomainException
     */
    protected function checkValidateConfig(CModel $object)
    {
        if (!Yii::app()->hasComponent($this->apiComponentId)) {
            throw new CException(Yii::t(
                'cleantalk',
                'Application component "' . $this->apiComponentId . '" is not defined'
            ));
        } elseif (!in_array($this->check, array(self::CHECK_MESSAGE, self::CHECK_USER))) {
            throw new DomainException(Yii::t(
                'cleantalk',
                'Validation check property is not defined or invalid'
            ));
        }
    }

    /**
     * Get CleanTalk API deny message
     * @return string
     */
    protected function getErrorMessage()
    {
        return Yii::app()->getComponent($this->apiComponentId)->getValidationError();
    }
}