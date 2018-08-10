<?php

/**
 * CookieMonster is the Yii widget that adds warning about website cookies
 * @see https://github.com/bizley-code/Yii-CookieMonster
 * @see http://www.yiiframework.com/extension/cookiemonster
 * 
 * See README file for configuration and usage examples.
 * 
 * CookieMonster requires Yii version 1.1.
 * http://www.yiiframework.com
 * https://github.com/yiisoft/yii
 * 
 * @author Paweł Bizley Brzozowski
 * @version 1.0.1
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
class CookieMonster extends CWidget
{

    /**
     * @var boolean Whether to copy the asset file even if it has been already 
     * published before, default false.
     * @see CAssetManager::publish()
     * @since 1.0.1
     */
    public $debug = false;
    
    /**
     * @var boolean Whether to use minified version of js asset file, default 
     * true.
     * @since 1.0.1
     */
    public $minified = true;
    
    /**
     * @var array parameters for the CSS class and style and other HTML options 
     * for the view containers.
     * Available options:
     * addButtonStyle: array 
     *      list of button CSS style options to be added or replaced with new 
     *      values i.e. 'padding-right' => '20px', 'font-weight' => 'bold'
     * addInnerStyle: array 
     *      list of inner div CSS style options to be added or replaced with 
     *      new values
     * addOuterStyle: array 
     *      list of outer div CSS style options to be added or replaced with 
     *      new values
     * buttonHtmlOptions: array 
     *      list of button HTML options to be added (except style and class)
     * classButton: string 
     *      button class or classes (separated by spaces), 
     *      default 'CookieMonsterOk'
     * classInner: string 
     *      inner div class or classes (separated by spaces)
     * classOuter: string 
     *      outer div class or classes (separated by spaces), 
     *      default 'CookieMonsterBox'
     * innerHtmlOptions: array 
     *      list of inner div HTML options to be added (except style and class)
     * outerHtmlOptions: array 
     *      list of outer div HTML options to be added (except style and class)
     * replaceButtonStyle: array 
     *      list of button CSS style options to be replaced with new values or 
     *      removed i.e. 'margin-left' => '10px', 'font-size' => false
     * replaceInnerStyle: array 
     *      list of inner div CSS style options to be replaced with new values 
     *      or removed
     * replaceOuterStyle: array 
     *      list of outer div CSS style options to be replaced with new values 
     *      or removed
     * setButtonStyle: array 
     *      list of button CSS style options to be set replacing the default 
     *      ones
     * setInnerStyle: array 
     *      list of inner div CSS style options to be set replacing the default 
     *      ones
     * setOuterStyle: array 
     *      list of outer div CSS style options to be set replacing the default 
     *      ones
     * view: string 
     *      path to the custom view (required if $mode is set to 'custom'), 
     *      for views outside the widget folder use alias path 
     *      i.e. 'application.views.cookie'
     */
    public $box = array();

    /**
     * @var array parameters for the texts.
     * Available options:
     * buttonMessage: string 
     *      button original message as in Yii::t() $message, 
     *      default 'I understand'
     * buttonParams: array 
     *      parameters to be applied to the buttonMessage as in Yii::t() $params, 
     *      default array()
     * category: string 
     *      message category as in Yii::t() $category, default 'app'
     * language: string 
     *      target language as in Yii::t() $language, default null
     * mainMessage: string 
     *      main original message as in Yii::t() $message, 
     *      default 'We use cookies on our websites to help us offer you the 
     *      best online experience. By continuing to use our website, you are 
     *      agreeing to our use of cookies. Alternatively, you can manage them 
     *      in your browser settings.'
     * mainParams: array 
     *      parameters to be applied to the mainMessage as in Yii::t() $params, 
     *      default array()
     * source: string 
     *      which message source application component to use as in Yii::t() 
     *      $source, default null
     */
    public $content = array();

    /**
     * @var array parameters for the cookie
     * https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie
     * Available options:
     * domain: string 
     *      domain name for the cookie, default host portion of the current 
     *      document location
     * expires: integer 
     *      number of days this cookie will be valid for, default 30
     * max-age: integer 
     *      max cookie age in seconds
     * path: string 
     *      path for the cookie, default '/'
     * secure: boolean
     *      whether cookie should be transmitted over secure protocol as https, 
     *      default false
     */
    public $cookie = array();

    /**
     * @var string name of the mode to be used
     * Available options:
     * bottom:  bottom strip
     * box:     bottom right box
     * custom:  custom mode defined by user (requires box[view] to be set)
     * top:     top strip, default
     */
    public $mode = 'top';

    /**
     * @var mixed parameter or parameters passed to the custom user's view 
     * in case this is needed
     */
    public $params;

    /**
     * @var array list of button's HTML options
     */
    protected $buttonHtml = array();
    
    /**
     * @var array list of button's CSS styles
     */
    protected $buttonStyle = array();
    
    /**
     * @var array list of CSS class options
     */
    protected $classOptions = array();
    
    /**
     * @var array list of content's options
     */
    protected $contentOptions = array();
    
    /**
     * @var array list of cookie's options
     */
    protected $cookieOptions = array();
    
    /**
     * @var array list of inner div's HTML options
     */
    protected $innerHtml = array();
    
    /**
     * @var array list of inner div's CSS styles
     */
    protected $innerStyle = array();

    /**
     * @var array list of outer div's HTML options
     */
    protected $outerHtml = array();

    /**
     * @var array list of outer div's CSS styles
     */
    protected $outerStyle = array();

    /**
     * @var string custom view path
     */
    protected $view = '';    

    /**
     * Adds class option, removes dot and hash.
     * @param string $name option name
     * @param string $value option value
     */
    protected function addClassOption($name, $value)
    {
        $value = trim($value);
        if (substr($value, 0, 1) == '.' || substr($value, 0, 1) == '#') {
            $value = substr($value, 1);
        }
        if ($value != '') {
            $this->addOption('class', $name, $value);
        }
    }
    
    /**
     * Adds content option.
     * @param string $name option name
     * @param string $value option value
     */
    protected function addContentOption($name, $value)
    {
        $value = trim($value);
        if (!empty($value) && is_string($value)) {
            $this->addOption('content', $name, $value);
        }
    }
    
    /**
     * Adds boolean cookie option.
     * @param string $name option name
     * @param string $value option value
     */
    protected function addCookieBoolOption($name, $value)
    {
        if ($value === true || $value === false) {
            $this->addOption('cookie', $name, $value);
        }
    }
    
    /**
     * Adds integer cookie option.
     * @param string $name option name
     * @param string|integer|float $value option value
     */
    protected function addCookieIntOption($name, $value)
    {
        $value = trim($value);
        if ($value !== null && $value !== '' && is_numeric($value)) {
            $this->addOption('cookie', $name, (int)$value);
        }
    }
    
    /**
     * Adds cookie option, removes '; name=' part.
     * @param string $name option name
     * @param string $value option value
     */
    protected function addCookieOption($name, $value)
    {
        if (is_string($value)) {
            $value = preg_replace("~^;? ?$name=~", '', trim($value));
            if ($value !== '') {
                $this->addOption('cookie', $name, $value);
            }
        }
    }

    /**
     * Adds option of certain type.
     * @param string $type type name
     * @param string $name option name
     * @param string $value option value
     */
    protected function addOption($type, $name, $value)
    {
        $this->{$type . 'Options'}[$name] = $value;
    }

    /**
     * Adds content parameters option.
     * @param string $name option name
     * @param array $value list of parameters
     */
    protected function addParamsOption($name, $value)
    {
        if (is_array($value) && count($value)) {
            $this->addOption('content', $name, $value);
        }
    }
    
    /**
     * Adds the CSS styles for selected part.
     * @param integer $what number of part
     * @param array $value list of styles
     */
    protected function addStyle($what, $value)
    {
        if (is_array($value) && count($value)) {
            $type = 'inner';
            switch ($what) {
                case 0: 
                    $type = 'outer';
                    break;
                case 2: 
                    $type = 'button';
                    break;
            }
            foreach ($value as $name => $set) {
                if (!empty($set) && is_string($set)) {
                    $this->{$type . 'Style'}[$name] = str_replace(';', '', trim($set));
                }
            }
        }
    }
    
    /*
     * Validates box parameters.
     */
    protected function checkBox()
    {
        if (is_array($this->box) && count($this->box)) {
            foreach ($this->box as $name => $value) {
                switch ($name) {
                    case 'classOuter':
                        $this->addClassOption('classOuter', $value);
                        break;
                    case 'classButton':
                        $this->addClassOption('classButton', $value);
                        break;
                    case 'classInner':
                        $this->addClassOption('classInner', $value);
                        break;
                    case 'replaceOuterStyle':
                        $this->replaceStyle(0, $value);
                        break;
                    case 'replaceInnerStyle':
                        $this->replaceStyle(1, $value);
                        break;
                    case 'replaceButtonStyle':
                        $this->replaceStyle(2, $value);
                        break;
                    case 'addOuterStyle':
                        $this->addStyle(0, $value);
                        break;
                    case 'addInnerStyle':
                        $this->addStyle(1, $value);
                        break;
                    case 'addButtonStyle':
                        $this->addStyle(2, $value);
                        break;
                    case 'setOuterStyle':
                        $this->setStyle(0, $value);
                        break;
                    case 'setInnerStyle':
                        $this->setStyle(1, $value);
                        break;
                    case 'setButtonStyle':
                        $this->setStyle(2, $value);
                        break;
                    case 'view':
                        $this->setView($value);
                        break;
                    case 'outerHtmlOptions':
                        $this->setHtmlOptions(0, $value);
                        break;
                    case 'innerHtmlOptions':
                        $this->setHtmlOptions(1, $value);
                        break;
                    case 'buttonHtmlOptions':
                        $this->setHtmlOptions(2, $value);
                        break;
                }
            }
        }
    }
    
    /**
     * Validates content parameters.
     */
    protected function checkContent()
    {
        if (is_array($this->content) && count($this->content)) {
            foreach ($this->content as $name => $value) {
                switch ($name) {
                    case 'category':
                    case 'mainMessage':
                    case 'buttonMessage':
                    case 'source':
                    case 'language':
                        $this->addContentOption($name, $value);
                        break;
                    case 'mainParams':
                    case 'buttonParams':
                        $this->addParamsOption($name, $value);
                        break;
                }
            }
        }
    }
    
    /**
     * Validates cookie parameters.
     */
    protected function checkCookie()
    {
        if (is_array($this->cookie) && count($this->cookie)) {
            foreach ($this->cookie as $name => $value) {
                switch ($name) {
                    case 'domain':
                    case 'path':
                        $this->addCookieOption($name, $value);
                        break;
                    case 'max-age':
                    case 'expires':
                        $this->addCookieIntOption($name, $value);
                        break;
                    case 'secure':
                        $this->addCookieBoolOption($name, $value);
                        break;
                }
            }
        }
    }

    /**
     * Initialises the widget, registers the js file (minimised for production).
     */
    public function init()
    {
        $assets = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        Yii::app()
            ->getClientScript()
            ->registerScriptFile(
                Yii::app()
                    ->getAssetManager()
                    ->publish($assets, false, 0, $this->debug) 
                        . '/CookieMonster' 
                        . ($this->minified ? '.min' : '') 
                        . '.js', 
                CClientScript::POS_END
            );
        Yii::app()->clientScript->registerCoreScript('jquery');
    }
    
    /**
     * Initialises the js file, prepares the JSON js options.
     */
    protected function initCookie()
    {
        $cookieOptions = json_encode(
                            array_merge(
                                $this->cookieOptions, 
                                array(
                                    'classOuter'  => str_replace(' ', '.', $this->classOptions['classOuter']),
                                    'classInner'  => str_replace(' ', '.', $this->classOptions['classInner']),
                                    'classButton' => str_replace(' ', '.', $this->classOptions['classButton']),
                                )
                            )
                        );
        Yii::app()
            ->getClientScript()
            ->registerScript(
                'CookieMonsterInit', 
                "CookieMonster.init($cookieOptions);", 
                CClientScript::POS_END
            );
    }
    
    /**
     * Prepares the list of parameters passed to the view.
     * @return array
     */
    protected function prepareViewParams()
    {
        $outerStyle  = array();
        $innerStyle  = array();
        $buttonStyle = array();

        foreach ($this->outerStyle as $name => $value) {
            $outerStyle[] = $name . ':' . $value;
        }
        foreach ($this->innerStyle as $name => $value) {
            $innerStyle[] = $name . ':' . $value;
        }
        foreach ($this->buttonStyle as $name => $value) {
            $buttonStyle[] = $name . ':' . $value;
        }

        return array(
            'content'           => $this->contentOptions,
            'outerHtmlOptions'  => array_merge(
                                        $this->outerHtml, 
                                        array(
                                            'style' => implode(';', $outerStyle),
                                            'class' => $this->classOptions['classOuter']
                                        )
                                    ),
            'innerHtmlOptions'  => array_merge(
                                        $this->innerHtml, 
                                        array(
                                            'style' => implode(';', $innerStyle),
                                            'class' => $this->classOptions['classInner']
                                        )
                                    ),
            'buttonHtmlOptions' => array_merge(
                                        $this->buttonHtml, 
                                        array(
                                            'style' => implode(';', $buttonStyle),
                                            'class' => $this->classOptions['classButton']
                                        )
                                    ),
            'params'            => $this->params
        );
    }
    
    /**
     * Replaces the CSS styles for selected part.
     * @param integer $what number of part
     * @param array $value list of styles
     */
    protected function replaceStyle($what, $value)
    {
        if (is_array($value) && count($value)) {
            $type = 'inner';
            switch ($what) {
                case 0: 
                    $type = 'outer';
                    break;
                case 2: 
                    $type = 'button';
                    break;
            }
            foreach ($value as $name => $set) {
                if (isset($this->{$type . 'Style'}[$name])) {
                    if ($set === false || $set === null) {
                        unset($this->{$type . 'Style'}[$name]);
                    } else {
                        $this->{$type . 'Style'}[$name] = str_replace(';', '', trim($set));
                    }
                }
            }
        }
    }
    
    /**
     * Runs the widget.
     * @return string
     */
    public function run()
    {
        $this->setMode();
        $this->checkBox();
        $this->checkCookie();
        $this->checkContent();
        $this->setDefaults();
        $this->initCookie();

        return $this->render(
                $this->mode == 'custom' 
                    ? $this->view 
                    : 'box', 
                $this->prepareViewParams()
            );
    }
    
    /**
     * Sets default values.
     */
    protected function setDefaults()
    {
        if (!isset($this->contentOptions['category']) 
                || (isset($this->contentOptions['category']) 
                        && empty($this->contentOptions['category']))) {
            $this->contentOptions['category'] = 'app';
        }
        if (!isset($this->contentOptions['mainParams'])) {
            $this->contentOptions['mainParams'] = array();
        }
        if (!isset($this->contentOptions['buttonParams'])) {
            $this->contentOptions['buttonParams'] = array();
        }
        if (!isset($this->contentOptions['source'])) {
            $this->contentOptions['source'] = null;
        }
        if (!isset($this->contentOptions['language'])) {
            $this->contentOptions['language'] = null;
        }
        if (!isset($this->contentOptions['mainMessage'])) {
            $this->contentOptions['mainMessage'] = 'We use cookies on our websites to help us offer you the best online experience. By continuing to use our website, you are agreeing to our use of cookies. Alternatively, you can manage them in your browser settings.';
        }
        if (!isset($this->contentOptions['buttonMessage'])) {
            $this->contentOptions['buttonMessage'] = 'I understand';
        }
        if (!isset($this->cookieOptions['path'])) {
            $this->cookieOptions['path'] = '/';
        }
        if (!isset($this->cookieOptions['expires'])) {
            $this->cookieOptions['expires'] = 30;
        }
        if (!isset($this->cookieOptions['secure'])) {
            $this->cookieOptions['secure'] = false;
        }
        if (!isset($this->classOptions['classOuter'])) {
            $this->classOptions['classOuter'] = 'CookieMonsterBox';
        }
        if (!isset($this->classOptions['classInner'])) {
            $this->classOptions['classInner'] = '';
        }
        if (!isset($this->classOptions['classButton'])) {
            $this->classOptions['classButton'] = 'CookieMonsterOk';
        }
    }
    
    /**
     * Sets HTML options for selected part.
     * @param integer $what number of part
     * @param array $value list of options
     */
    protected function setHtmlOptions($what, $value)
    {
        if (is_array($value) && count($value)) {
            $type = 'inner';
            switch ($what) {
                case 0: 
                    $type = 'outer';
                    break;
                case 2: 
                    $type = 'button';
                    break;
            }
            foreach ($value as $name => $set) {
                if ($name == 'class' || $name == 'style') {
                    continue;
                } else {
                    $this->{$type . 'Html'}[$name] = trim($set);
                }
            }
        }
    }
    
    /**
     * Sets the mode with default CSS styles.
     */
    protected function setMode()
    {
        $this->outerStyle = array(
            'display'          => 'none',
            'z-index'          => 10000,
            'position'         => 'fixed',
            'background-color' => '#fff',
            'font-size'        => '12px',
            'color'            => '#000'
        );
        $this->innerStyle = array('margin' => '10px');
        $this->buttonStyle = array('margin-left' => '10px');

        switch ($this->mode) {
            case 'bottom':
                $this->outerStyle = array_merge(
                                        $this->outerStyle, 
                                        array(
                                            'bottom'     => 0,
                                            'left'       => 0,
                                            'width'      => '100%',
                                            'box-shadow' => '0 -2px 2px #000',
                                        )
                                    );
                break;
            case 'box':
                $this->outerStyle = array_merge(
                                        $this->outerStyle, 
                                        array(
                                            'bottom'        => '20px',
                                            'right'         => '20px',
                                            'width'         => '300px',
                                            'box-shadow'    => '-2px 2px 2px #000',
                                            'border-radius' => '10px',
                                        )
                                    );
                break;
            case 'custom':
                $this->outerStyle  = array();
                $this->innerStyle  = array();
                $this->buttonStyle = array();
                break;
            case 'top':
            default:
                $this->outerStyle = array_merge(
                                        $this->outerStyle, 
                                        array(
                                            'top'        => 0,
                                            'left'       => 0,
                                            'width'      => '100%',
                                            'box-shadow' => '0 2px 2px #000',
                                        )
                                    );
        }
    }
    
    /**
     * Sets the CSS styles for selected part.
     * @param integer $what number of part
     * @param array $value list of styles
     */
    protected function setStyle($what, $value)
    {
        if (is_array($value) && count($value)) {
            $type = 'inner';
            switch ($what) {
                case 0: 
                    $type = 'outer';
                    break;
                case 2: 
                    $type = 'button';
                    break;
            }
            $tmp = array();
            foreach ($value as $name => $set) {
                if (!empty($set) && is_string($set)) {
                    $tmp[$name] = str_replace(';', '', trim($set));
                }
            }
            $this->{$type . 'Style'} = $tmp;
        }
    }
    
    /**
     * Sets custom user's view path.
     * @param string $value view path
     */
    protected function setView($value)
    {
        $value = trim($value);
        if (!empty($value)) {
            $this->view = $value;
        }
    }
}
