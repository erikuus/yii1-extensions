<?php

/**
 * CookieMonster view file.
 * 
 * @author Paweł Bizley Brzozowski
 * @version 1.0.1
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

echo CHtml::openTag('div', $outerHtmlOptions);
echo CHtml::openTag('div', $innerHtmlOptions);
echo Yii::t(
        $content['category'], 
        $content['mainMessage'], 
        $content['mainParams'], 
        $content['source'], 
        $content['language']
    );
echo CHtml::htmlButton(
        Yii::t(
            $content['category'], 
            $content['buttonMessage'], 
            $content['buttonParams'], 
            $content['source'], 
            $content['language']
        ), 
        $buttonHtmlOptions
    );
echo CHtml::closeTag('div');
echo CHtml::closeTag('div') . "\n";
