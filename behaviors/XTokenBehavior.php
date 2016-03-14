<?php
/**
 * XTokenBehavior
 *
 * This behavior adds token methods to a ActiveRecord model.
 *
 * It can be  be attached to a model on its behaviors() method:
 * <pre>
 * public function behaviors()
 * {
 *     return array(
 *         'TokenBehavior' => array(
 *             'class'=>'ext.behaviors.XTokenBehavior',
 *         ),
 *     );
 * }
 * </pre>
 *
 * @author Erik Uus <erik.uus@gmail.com>
 * @version 1.0.0
 */
class XTokenBehavior extends CBehavior
{
	/**
	 * @var string the model attribute name that stores token
	 * Defaults to 'token'
	 */
	public $attributeName='token';
	/**
	 * @var integer $length the length of the generated token in characters.
	 * Defaults to 12
	 */
	public $length=12;
	/**
	 * @var array $scenarios the list of scenarions under which new token is saved
	 * Defaults to array('insert')
	 */
	public $scenarios = array('insert');

	public function events()
	{
		return array(
			'onBeforeValidate' => 'addTokenValidators',
		);
	}

	/**
	 * Add token validators to owner model
	 */
	public function addTokenValidators()
	{
		$owner = $this->getOwner();
		if (in_array($owner->getScenario(), $this->scenarios)) {
			$list = $owner->getValidatorList();
			$list->add(CValidator::createValidator(
					'setTokenAttribute',
					$this,
					$this->attributeName
				));
			$list->add(CValidator::createValidator(
					'validateUniqueToken',
					$this,
					$this->attributeName
				));
		}
	}

	/**
	 * Set token attribute to random ASCII string
	 */
	public function setTokenAttribute()
	{
		$owner = $this->getOwner();
		$owner->setAttribute($this->attributeName, $this->generateToken());
	}

	/**
	 * Validate token uniqueness
	 */
	public function validateUniqueToken()
	{
		$owner = $this->getOwner();
		CValidator::createValidator('unique', $owner, $this->attributeName)
			->validate($owner, $this->attributeName);
	}

	/**
	 * Finds a single active record with the specified token.
	 * @param string $token.
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @return static the record found. Null if none is found.
	 */
	public function findByToken($token, $condition = '', array $params = array())
	{
		return $this->filterByToken($token)->find($condition, $params);
	}

	/**
	 * Filter owner model with the specified token.
	 * @param string $token.
	 * @param string $operator.
	 * @return CModel.
	 */
	public function filterByToken($token, $operator = 'AND')
	{
		$owner = $this->getOwner();
		$column = $owner->getDbConnection()
			->quoteColumnName($owner->getTableAlias() . '.' . $this->attributeName);
		$owner->getDbCriteria()
			->addCondition($column . ' = :token', $operator)
			->params[':token'] = $token;

		return $this->getOwner();
	}

	/**
	 * Generate a random unique string token.
	 */
	protected function generateToken()
	{
		do {
			$token=$this->generateRandomString();
		} while($this->findByToken($token)!==null);
		return $token;
	}

	/**
	 * Generate a random unique ASCII string token.
	 * Generates only [0-9a-zA-z_-] characters which are all transparent in raw URL encoding.
	 */
	protected function generateRandomString()
	{
		if(method_exists(Yii::app()->securityManager,'generateRandomString'))
			return strtr(Yii::app()->securityManager->generateRandomString($this->length), array('~'=>'-'));
		elseif(($randomBytes=openssl_random_pseudo_bytes($this->length+2))!==false)
			return strtr($this->substr(base64_encode($randomBytes),0,$this->length), array('+'=>'_','/'=>'-'));
		else
			return false;
	}

	/**
	 * Returns the portion of string specified by the start and length parameters.
	 * If available uses the multibyte string function mb_substr
	 * @param string $string the input string. Must be one character or longer.
	 * @param integer $start the starting position
	 * @param integer $length the desired portion length
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 */
	private function substr($string,$start,$length)
	{
		return extension_loaded('mbstring') ? mb_substr($string,$start,$length,'8bit') : substr($string,$start,$length);
	}
}