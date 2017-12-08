<?php
/**
 * XCaptchaValidator validator
 *
 * XCaptchaValidator validator enables captch for ajax validation
 *
 * The following shows how to use this validator on model actions() method
 * <pre>
 *    return array(
 *        array('verifyCode','XCaptchaValidator'),
 *    );
 * </pre>
 *
 * Next prepare an ajax validator method as below for yourself to reuse it repetitively (it can be a static method in your FormModel base or your AR base:
 * <pre>
 *    if (property_exists($model, 'dontValidateCaptcha'))
 *        $model->dontValidateCaptcha = true;
 *    echo CActiveForm::validate($model);
 *    Yii::app()->end();
 * </pre>
 *
 * @link https://github.com/yiisoft/yii/issues/2815#issuecomment-31175619
 */
class XCaptchaValidator extends CCaptchaValidator
{
    protected function validateAttribute($object, $attribute)
    {
        if (property_exists($object, "dontValidateCaptcha") && $object->dontValidateCaptcha)
            return;
        parent::validateAttribute($object, $attribute);
    }
}