<?php

/**
 * ShareBox
 * Create a list of social networks that a user may share the page with.
 *
 * CSS base and 48px icons from Beautiful Social Bookmarking Widget by Harish.
 * http://www.way2blogging.org/2011/03/add-beautiful-social-bookmarking-widget.html
 *
 * 16, 24 and 32 px icons from IconDock
 * http://icondock.com/free/vector-social-media-icons
 *
 * @copyright © Digitick <www.digitick.net> 2011-2012
 * @license Public Domain
 * @author Ianaré Sévi
 * @author Vincent Castelain
 *
 * Note: the company logos in the icons are copyright of their respective owners.
 */

/**
 * Main widget class.
 */
class EShareBox extends CWidget
{
	/**
	 * @var mixed the CSS file used for the widget. Defaults to null, meaning
	 * using the default CSS file included together with the widget.
	 * If false, no CSS file will be used. Otherwise, the specified CSS file
	 * will be included when using this widget.
	 */
	public $cssFile;
	/**
	 * @var string URL to share.
	 */
	public $url;
	/**
	 * @var string Title for the page to share.
	 */
	public $title;
	/**
	 * @var boolean whether to animate the links.
	 */
	public $animate = true;
	/**
	 * @var integer Size for share icons.
	 */
	public $iconSize = 24;
	/**
	 * @var string custom icon path.
	 */
	public $iconPath;
	/**
	 * @var array Services to include.
	 * Note that the exclude filter is still applied.
	 */
	public $include = array();
	/**
	 * @var array Services to exclude.
	 */
	public $exclude = array();
	/**
	 * @var array Html options for UL element.
	 */
	public $ulHtmlOptions = array();
	/**
	 * @var array Html options for LI elements.
	 */
	public $liHtmlOptions = array();
	/**
	 * @var array Definitions for sharing services.
	 */
	protected $shareDefinitions = array(
		'facebook' => array(
			'url' => 'https://www.facebook.com/sharer.php?u={url}&t={title}',
			'title' => 'Share this on Facebook',
			'name' => 'Facebook'
		),
		'twitter' => array(
			'url' => 'http://twitter.com/intent/tweet?url={url}&text={title}',
			'title' => 'Tweet This!',
			'name' => 'Twitter',
		),
		'google-plus' => array(
			'url' => 'https://plus.google.com/share?url={url}',
			'title' => 'Share this on Google+',
			'name' => 'Google+'
		),
		'stumbleupon' => array(
			'url' => 'http://www.stumbleupon.com/submit?url={url}&title={title}',
			'title' => 'Stumble upon something good? Share it on StumbleUpon',
			'name' => 'StumbleUpon'
		),
		'digg' => array(
			'url' => 'http://digg.com/submit?phase=2&url={url}&title={title}',
			'title' => 'Digg this!',
			'name' => 'Digg',
		),
		'delicious' => array(
			'url' => 'http://delicious.com/post?url={url}&title={title}',
			'title' => 'Share this on del.icio.us',
			'name' => 'Delicious',
		),
		'linkedin' => array(
			'url' => 'http://www.linkedin.com/shareArticle?mini=true&url={url}&title={title}',
			'title' => 'Share this on LinkedIn',
			'name' => 'LinkedIn',
		),
		'reddit' => array(
			'url' => 'http://reddit.com/submit?url={url}&title={title}',
			'title' => 'Share this on Reddit',
			'name' => 'Reddit',
		),
		'technorati' => array(
			'url' => 'http://technorati.com/faves?add={url}',
			'title' => 'Share this on Technorati',
			'name' => 'Technorati',
		),
		'newsvine' => array(
			'url' => 'http://www.newsvine.com/_tools/seed&save?u={url}',
			'title' => 'Bookmark on Newsvine',
			'name' => 'Newsvine',
		),
			/*
			  'email' => array(
			  'url' => 'http://mysite.com/sendEmail?url={url}&title={title}',
			  'title' => 'Email this',
			  'name' => 'E-Mail',
			  ),
			 */
	);
	/**
	 * @var array Default html options. Will be merged with $htmlOptions provided by user.
	 */
	protected $defaultUlHtmlOptions = array(
		'class' => 'way2blogging-social',
	);

	public function init()
	{
		if (!$this->url || !$this->title)
			throw new CException('Could not initialize ShareBox : "title" and "url" parameters are required.');

		$assets = Yii::app()->getAssetManager()->publish(dirname(__FILE__) . '/assets');

		// register css file
		if($this->cssFile===null)
			Yii::app()->clientScript->registerCssFile($assets . '/style.css');
		else if($this->cssFile!==false)
			Yii::app()->clientScript->registerCssFile($this->cssFile);

		if (!$this->iconPath)
			$this->iconPath = $assets . '/images';

		if (!empty($this->include))
		{
			foreach ($this->shareDefinitions as $share => $info)
			{
				if (!in_array($share, $this->include))
					unset($this->shareDefinitions[$share]);
			}
		}
		foreach ((array) $this->exclude as $share)
			unset($this->shareDefinitions[$share]);

		if($this->animate)
			$this->defaultUlHtmlOptions['class'] = $this->defaultUlHtmlOptions['class'] . ' way2blogging-cssanime';

		$this->defaultUlHtmlOptions['class'] = $this->defaultUlHtmlOptions['class'] . " way2blogging-size{$this->iconSize}";

		$this->ulHtmlOptions = array_merge($this->defaultUlHtmlOptions, $this->ulHtmlOptions);
	}

	public function run()
	{
		echo CHtml::openTag('ul', $this->ulHtmlOptions);
		foreach ($this->shareDefinitions as $name => $def) {
			$linkText = CHtml::tag('strong', array(), $def['name']);
			$url = strtr($def['url'], array('{url}' => urlencode($this->url), '{title}' => urlencode($this->title)));
			$link = CHtml::link($linkText, $url, array('rel' => 'nofollow', 'target' => '_blank', 'title' => $def['title']));

			$bgImage = "{$this->iconPath}/{$this->iconSize}px/{$name}.png";
			$this->liHtmlOptions['style'] = "background-image:url({$bgImage});";
			echo "\n" . CHtml::tag('li', $this->liHtmlOptions, $link);
		}
		echo CHtml::closeTag('ul');
	}
}
