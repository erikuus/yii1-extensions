<?php
/**
 * Cookie class
 *
 * This class adds helper methods to get and set cookies.
 *
 * Examples of usage:
 * <pre>
 *     Cookie::set('name', $value);
 *     $value=Cookie::get('name');
 *     Cookie::delete('name');
 * </pre>
 */
class Cookie
{
	/**
	 * Get cookie value
	 * @param string $name the name of cookie
	 * @return string cookie value, null if cookie is missing
	 */
	public static function get($name)
	{
		$cookie=Yii::app()->request->cookies[$name];
		if(!$cookie)
			return null;

		return $cookie->value;
	}

	/**
	 * Create new cookie
	 * @param string $name the name of cookie
	 * @param string $value the value of cookie
	 * @param integer $expiration the timestamp at which the cookie expires.
	 * This is the server timestamp. Defaults to 0, meaning "until the browser is closed".
	 */
	public static function set($name, $value, $expiration=0)
	{
		$cookie=new CHttpCookie($name,$value);
		$cookie->expire = $expiration;
		Yii::app()->request->cookies[$name]=$cookie;
	}

	/**
	 * Delete
	 * Set the expiration date to one hour ago
	 * @param string $name the name of cookie
	 */
	public static function delete($name)
	{
		self::set($name, '', time() - 3600);
	}
}