<?php
/**
 * Email class file.
 *
 * You can configure the extension as follows:
 *
 * 'components'=>array(
 *     'email'=>array(
 *         'class'=>'application.extensions.email.Email',
 *         'delivery'=>'php', //Will use the php mailing function.
 *         // May also be set to 'debug' to instead dump the contents of the email into the view
 *     ),
 *
 * You need to put the debug widget somewhere in the view or layout, if you wish to use debug mode
 *
 * <?php $this->widget('ext.components.email.Email'); ?>
 *
 * Example code:
 *
 * $email = Yii::app()->email;
 * $email->to = 'admin@example.com';
 * $email->subject = 'Hello';
 * $email->message = 'Hello brother';
 * $email->send();
 *
 * Using views when send mail:
 *
 * First of all, we call the mail method from your controller or model:
 *
 * $email = Yii::app()->email;
 * $email->to = 'to@mail.com';
 * $email->subject = 'Subject text';
 * $email->view = 'myview';
 * $email->viewVars = array('var1'=>$var1,'var2'=>$var2);
 * $email->send();
 *
 * Next, you need to create the view in /protected/views/email/myview.php
 *
 * Some email vars:<br>
 * Email:<?php echo $email->subject ?>
 * <br>
 * From:<?php echo $email->from ?>
 * <br>
 * Now, my own vars:<br>
 * Var1:<?php echo $var1 ?>
 * <br>
 * Var2:<?php echo $var2 ?>
 *
 * @author Jonah Turnquist <poppitypop@gmail.com>
 * @link http://php-thoughts.cubedwater.com/
 * @version 1.0
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.1
 */
class Email extends CApplicationComponent {
	/**
	 * @var string Type of email.  Options include "text/html" and "text/plain".
	 */
	public $type = 'text/html';
	/**
	 * @var string the receiver or receivers of the mail
	 */
	public $to;
	/**
	 * @var string the email subject
	 */
	public $subject;
	/**
	 * @var string the sender address
	 */
	public $from;
	/**
	 * @var string the reply-to address
	 */
	public $replyTo;
	/**
	 * @var string the return-path address
	 */
	public $returnPath;
	/**
	 * @var string contact email address to display in message body for user inquiries.
	 * This is NOT added to email headers, only appended to the message body.
	 */
	public $contactEmail;
	/**
	 * @var string MTA command line parameter. It is useful when setting the correct Return-Path header when using sendmail.
	 */
	public $additionalParams='-fnoreply.vau@ra.ee';
	/**
	 * @var string the Carbon Copy - list of email's that should receive a copy of the email
	 * The Recipient WILL be able to see this list.
	 */
	public $cc;
	/**
	 * @var string the Blind Carbon Copy - list of email's that should receive a copy of the email
	 * The Recipient WILL NOT be able to see this list.
	 */
	public $bcc;
	/**
	 * @var string the mail body content
	 */
	public $message;
	/**
	 * @var string the delivery type
	 * If set to 'php' it will use php's mail() function.
	 * If set to 'debug' it will not actually send it but output it to the screen.
	 * If set to 'dummy' it will do nothing.
	 */
	public $delivery = 'php';
	/**
	 * @var string the language to encode the message in (eg "Japanese", "ja", "English", "en" and "uni" (UTF-8))
	 */
	public $language = 'uni';
	/**
	 * @var string the content-type of the email
	 */
	public $contentType = 'utf-8';
	/**
	 * @var string the path to the email view directory. Defaults to 'application.views.email'.
	 */
	public $viewPath = 'application.views.email';
	/**
	 * @var string the view to use as the content of the email, as an alternative to setting $this->message.
	 * This email object is availiable within the view through $email, thus letting you define things such
	 * as the subject within the view (helps maintain seperation of logic and output).
	 */
	public $view;
	/**
	 * @var array the variables to be sent to the view.
	 */
	public $viewVars = array();
	/**
	 * @var string path to the email layout directory. Defaults to 'application.views.email.layouts'.
	 */
	public $layoutPath = 'application.views.email.layouts';
	/**
	 * @var string The layout for the view to be imbedded in. Must be located in
	 * application.views.email.layouts directory.  Not required even if you are using a view
	 */
	public $layout;

	/**
	 * @var integer line length of email as per RFC2822 Section 2.1.1
	 */
	public $lineLength = 70;

	public function __construct()
	{
		Yii::setPathOfAlias('email', dirname(__FILE__).'/views');
	}

	/**
	 * Sends email.
	 * @param mixed the content of the email, or variables to be sent to the view.
	 * If not set, it will use $this->message instead for the content of the email
	 */
	public function send($arg1=null)
	{
		if ($this->view !== null)
		{
			if ($arg1 == null)
				$vars = $this->viewVars;
			else
				$vars = $arg1;

			$view = Yii::app()->controller->renderPartial($this->viewPath.'.'.$this->view, array_merge($vars, array('email'=>$this)), true);

			if ($this->layout === null)
				$message = $view;
			else
				$message = Yii::app()->controller->renderPartial($this->layoutPath.'.'.$this->layout, array('content'=>$view), true);
		}
		else
		{
			if ($arg1 === null)
				$message = $this->message;
			else
				$message = $arg1;
		}

		// Append contact email footer if set
		if($this->contactEmail !== null)
			$message = $this->appendContactFooter($message);

		// Default Reply-To to contactEmail if not set
		if($this->replyTo === null && $this->contactEmail !== null)
			$this->replyTo = $this->contactEmail;

		//process 'to' attribute
		$to = $this->processAddresses($this->to);
		return $to ? $this->mail($to, $this->subject, $message) : false;
	}

	/**
	 * Appends contact email footer to message body
	 * @param string $message the original message
	 * @return string message with contact footer
	 */
	private function appendContactFooter($message)
	{
		$contactText = Yii::t('Email.email', 'If you have any questions, please contact us at');
		
		if($this->type === 'text/html')
		{
			$footer = '<br><br>---<br><br><p>' . $contactText . ': <a href="mailto:' . 
					  CHtml::encode($this->contactEmail) . '">' . 
					  CHtml::encode($this->contactEmail) . '</a></p>';
		}
		else
			$footer = "\n\n---\n\n" . $contactText . ': ' . $this->contactEmail;
		
		return $message . $footer;
	}

	private function mail($to, $subject, $message)
	{
		switch ($this->delivery)
		{
			case 'php':
				$message = wordwrap($message, $this->lineLength);
				mb_language($this->language);
				return mb_send_mail($to, $subject, $message, implode("\r\n", $this->createHeaders()), $this->additionalParams);
			case 'debug':
				$debug = Yii::app()->controller->renderPartial('email.debug', array_merge(
					compact('to', 'subject', 'message'),
					array('headers'=>$this->createHeaders(),'type'=>$this->type)
				), true);
				$uniqid = md5(uniqid(rand(), true));
				Yii::app()->user->setFlash('debug.email'.$uniqid, $debug);
				return true;
				break;
			case 'dummy':
				return true;
				break;
		}
	}

	private function createHeaders()
	{
		$headers = array();

		//maps class variable names to header names
		$map = array(
			'from' => 'From',
			'cc' => 'Cc',
			'bcc' => 'Bcc',
			'replyTo' => 'Reply-To',
			'returnPath' => 'Return-Path',
		);
		foreach ($map as $key => $value) {
			if (isset($this->$key))
				$headers[] = "$value: {$this->processAddresses($this->$key)}";
		}
		$headers[] = "Content-Type: {$this->type}; charset=".$this->contentType;
		// $headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/" . phpversion();

		return $headers;
	}

	private function processAddresses($addresses) {
		return (is_array($addresses)) ? implode(', ', $addresses) : $addresses;
	}
}