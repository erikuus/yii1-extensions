<?php

/**
 * XVauUserIdentity class file.
 *
 * XVauUserIdentity class identifies user based on data posted by VAU after successful login in VAU.
 * If authenticate($syncData) is set to true, VAU user data will be synced into local user table
 * through User model.
 *
 * For usage first set up 'vauLogin' action in SiteController as follows:
 * <pre>
 * public function actionVauLogin()
 * {
 *     if(isset($_POST['postedData']))
 *     {
 *         $identity=new XVauUserIdentity($_POST['postedData']);
 *         $identity->authenticate();
 *         if($identity->errorCode==XVauUserIdentity::ERROR_NONE)
 *         {
 *             Yii::app()->user->login($identity);
 *             $this->redirect(Yii::app()->user->returnUrl));
 *         }
 *         else
 *             throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
 *     }
 *     else
 *         throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
 * }
 * </pre>
 *
 * Then place VAU login and logout links into your application. For example in MainMenu portlet:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/login?remoteUrl='.$this->controller->createAbsoluteUrl('/site/vauLogin'),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>'http://www.ra.ee/vau/index.php/site/logout?remoteUrl='.$this->controller->createAbsoluteUrl('/site/logout'),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
 *
 * Then point your 'login' action to VAU login page:
 * <pre>
 * public function actionLogin()
 * {
 *     $this->redirect('http://www.ra.ee/vau/index.php/site/login?remoteUrl='.$this->createAbsoluteUrl('/site/vauLogin'));
 * }
 * </pre>
 *
 * IMPORTANT!!!
 *
 * You are strongly encouraged to configure alternative setup using
 * - XVauLoginAction for setting up vau login action
 * - XVauRedirectLoginAction for setting up login action
 * - XVauHelper for VAU login and logout links
 *
 * So, first import XVauHelper in main/config:
 * <pre>
 * 'import'=>array(
 *     'ext.components.vau.XVauHelper',
 * ),
 * </pre>
 *
 * Then set up SiteController actions() as follows:
 * <pre>
 * public function actions()
 * {
 *     return array(
 *         'vauLogin'=>array(
 *             'class'=>'ext.components.vau.XVauLoginAction',
 *             'syncData'=>true,
 *         ),
 *         'login'=>array(
 *             'class'=>'ext.components.vau.XVauRedirectLoginAction',
 *         ),
 *     );
 * }
 * </pre>
 *
 * And finally build VAU login and logout links:
 * <pre>
 * $this->widget('zii.widgets.CMenu',array(
 *     'items'=>array(
 *         array(
 *             'label'=>Yii::t('ui', 'Login'),
 *             'url'=>XVauHelper::loginUrl(),
 *             'visible'=>Yii::app()->user->isGuest,
 *         )
 *         array(
 *             'label'=>Yii::t('ui', 'Logout'),
 *             'url'=>XVauHelper::logoutUrl(),
 *             'visible'=>!Yii::app()->user->isGuest,
 *         ),
 * ));
 * </pre>
 *
 * @link http://www.ra.ee/apps/remotelogin/
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0
 */
class XVauUserIdentity extends CBaseUserIdentity
{
	const ERROR_INVALID_DATA=1;
	const ERROR_EXPIRED_DATA=2;
	const ERROR_SYNC_DATA=3;

	/**
	 * @var string encrypted data posted back by VAU after successful login.
	 * When decrypted this string is for example:
	 * 'DATA|3|Erik Uus|erik.uus@mail.ee|Erik|Uus|2016-12-02T21:27:32+02:00'
	 */
	protected $postedData;
	/**
	 * @var string the unique identifier for the identity.
	 */
	protected $id;
	/**
	 * @var string the display name for the identity.
	 */
	protected $name;
	/**
	 * @var string the encryption key for VAU remote login
	 */
	private $_key="*(&!^@AAA\0\0\0\0\0\0\0";

	/**
	 * Constructor.
	 * @param string encrypted data posted back by VAU after successful login.
	 */
	public function __construct($postedData=null)
	{
		$this->postedData=$postedData;
	}

	/**
	 * Returns the unique identifier for the identity.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the unique identifier for the identity.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Returns the display name for the identity.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the display name for the identity.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Authenticates VAU user.
	 * @param boolean $syncData whether to sync VAU user data.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate($syncData=false)
	{
		// decrypt posted data and explode to array
		$decryptedData=$this->lindecrypt(@$this->hex2bin($this->postedData));
		$data=explode('|', $decryptedData);

		// validate data
		if($data[0]=='DATA' && count($data)==7)
		{
			// assign keys for clearity
			$keys=array('data','id','fullname','email','firstname','lastname','timestamp');
			$data=array_combine($keys, $data);

			// validate that data was posted within one minute
			if((time() - strtotime($data['timestamp'])) < 60)
			{
				// save VAU user data if syncing is required
				if($syncData===true)
				{
					// check if user with given vau id exists
					$user=User::model()->find('vau_id='.(int)$data['id']);

					// add new user if there is no user with given id
					if($user===null)
					{
						$user=new User('vauLogin');
						$user->vau_id=$data['id'];
					}
					else
						$user->scenario='vauLogin';

					// sync data
					$user->email=$data['email'];
					$user->firstname=$data['firstname'];
					$user->lastname=$data['lastname'];

					// save user data
					if($user->save())
					{
						// assign identity attributes
						$this->id=$user->id;
						$this->name=$data['fullname'];
						$this->errorCode=self::ERROR_NONE;
					}
					else
						$this->errorCode=self::ERROR_SYNC_DATA;
				}
				else
				{
					// assign identity attributes
					$this->id=$data['id'];
					$this->name=$data['fullname'];
					$this->errorCode=self::ERROR_NONE;
				}
			}
			else
				$this->errorCode=self::ERROR_EXPIRED_DATA;
		}
		else
			$this->errorCode=self::ERROR_INVALID_DATA;

		return !$this->errorCode;
	}

	/**
	 * Decryptes data posted back by VAU after successful login.
	 * @param string encrypted data
	 * @return string decrypted data
	 */
	protected function lindecrypt($encrypted)
	{
		$iv_size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv=mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted=mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->_key, $encrypted, MCRYPT_MODE_ECB, $iv);
		return rtrim($decrypted);
	}

	/**
	 * Decodes a hexadecimally encoded binary string.
	 * Note that generic hex2bin function is available since PHP 5.4.0
	 * @param hexadecimal representation of data.
	 * @return the binary representation of the given data.
	 */
	protected function hex2bin($h)
	{
		if(!is_string($h))
			return null;
		$r='';
		for($a=0;$a<strlen($h);$a+=2)
			$r.=chr(hexdec($h{$a}.$h{($a+1)}));
		return $r;
	}
}