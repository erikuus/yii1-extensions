<?php
/**
 * Transliteration class file.
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @link https://github.com/yiiext
 */
/**
 * Transliteration transliterate into Latin characters of Cyrillic characters.
 * Use the {@link http://en.wikipedia.org/wiki/ISO_9 international standard ISO 9}.
 *
 * Transliteration can be used as either a widget or a controller filter.
 *
 * @property string $standard
 * @property string $transliterationTable
 *
 * @author Veaceslav Medvedev <slavcopost@gmail.com>
 * @version 0.1
 * @package yiiext
 * @link https://github.com/yiiext
 * @see http://en.wikipedia.org/wiki/ISO_9
 */
class Transliteration extends CApplicationComponent
{
	/**
	 * System A (with diacritics).
	 */
	const GOST_779A = 'GOST 7.79.A';
	/**
	 * System B (using combinations of letters).
	 */
	const GOST_779B = 'GOST 7.79.B';

	/**
	 * @var array Transliteration tables of Slavic alphabets.
	 */
	public static $tables = array(
		Transliteration::GOST_779A => array(
			'А' => 'A', 'а' => 'a',
			'Б' => 'B', 'б' => 'b',
			'В' => 'V', 'в' => 'v',
			'Г' => 'G', 'г' => 'g',
			'Д' => 'D', 'д' => 'd',
			'Е' => 'E', 'е' => 'e',
			'Ё' => 'Ë', 'ё' => 'ë',
			'Ж' => 'Ž', 'ж' => 'ž',
			'З' => 'Z', 'з' => 'z',
			'И' => 'I', 'и' => 'i',
			'Й' => 'J', 'й' => 'j',
			'К' => 'K', 'к' => 'k',
			'Л' => 'L', 'л' => 'l',
			'М' => 'M', 'м' => 'm',
			'Н' => "N", 'н' => 'n',
			'О' => 'O', 'о' => 'o',
			'П' => 'P', 'п' => 'p',
			'Р' => 'R', 'р' => 'r',
			'С' => 'S', 'с' => 's',
			'Т' => 'T', 'т' => 't',
			'У' => 'U', 'у' => 'u',
			'Ф' => 'F', 'ф' => 'f',
			'Х' => 'H', 'х' => 'h',
			'Ц' => 'C', 'ц' => 'c',
			'Ч' => 'Č', 'ч' => 'č',
			'Ш' => 'Š', 'ш' => 'š',
			'Щ' => 'Ŝ', 'щ' => 'ŝ',
			'Ъ' => 'ʺ', 'ъ' => 'ʺ',
			'Ы' => 'Y', 'ы' => 'y',
			'Ь' => 'ʹ', 'ь' => 'ʹ',
			'Э' => 'È', 'э' => 'è',
			'Ю' => 'Û', 'ю' => 'û',
			'Я' => 'Â', 'я' => 'â',
			'№' => '#', 'Ӏ' => '‡',
			'’' => '`', 'ˮ' => '¨',
		),
		Transliteration::GOST_779B => array(
			'А' => 'A', 'а' => 'a',
			'Б' => 'B', 'б' => 'b',
			'В' => 'V', 'в' => 'v',
			'Г' => 'G', 'г' => 'g',
			'Д' => 'D', 'д' => 'd',
			'Е' => 'E', 'е' => 'e',
			'Ё' => 'Yo', 'ё' => 'yo',
			'Ж' => 'Zh', 'ж' => 'zh',
			'З' => 'Z', 'з' => 'z',
			'И' => 'I', 'и' => 'i',
			'Й' => 'J', 'й' => 'j',
			'К' => 'K', 'к' => 'k',
			'Л' => 'L', 'л' => 'l',
			'М' => 'M', 'м' => 'm',
			'Н' => "N", 'н' => 'n',
			'О' => 'O', 'о' => 'o',
			'П' => 'P', 'п' => 'p',
			'Р' => 'R', 'р' => 'r',
			'С' => 'S', 'с' => 's',
			'Т' => 'T', 'т' => 't',
			'У' => 'U', 'у' => 'u',
			'Ф' => 'F', 'ф' => 'f',
			'Х' => 'H', 'х' => 'h',
			'Ц' => 'Cz', 'ц' => 'cz',
			'Ч' => 'Ch', 'ч' => 'ch',
			'Ш' => 'Sh', 'ш' => 'sh',
			'Щ' => 'Shh', 'щ' => 'shh',
			'Ъ' => 'ʺ', 'ъ' => 'ʺ',
			'Ы' => 'Y`', 'ы' => 'y`',
			'Ь' => '', 'ь' => '',
			'Э' => 'E`', 'э' => 'e`',
			'Ю' => 'Yu', 'ю' => 'yu',
			'Я' => 'Ya', 'я' => 'ya',
			'№' => '#', 'Ӏ' => '‡',
			'’' => '`', 'ˮ' => '¨',
		),
	);

	/**
	 * @var string
	 */
	private $_standard = Transliteration::GOST_779B;

	/**
	 * @param $value
	 * @throws CException
	 */
	public function setStandard($value)
	{
		if(!isset(Transliteration::$tables[$value])) {
			throw new CException(Yii::t('yiiext', 'Invalid Transliteration standard {standard}', array(
					'{standard}' => $value,
				)));
		}
		$this->_standard = $value;
	}

	/**
	 * @return string
	 */
	public function getStandard()
	{
		return $this->_standard;
	}

	/**
	 * @return array
	 */
	public function getTransliterationTable()
	{
		return Transliteration::$tables[$this->standard];
	}

	/**
	 * Transliterate into Latin characters of Cyrillic characters.
	 * @param string $content the content to be transliterate.
	 * @return string the transliterated content
	 */
	public function transliterate($content)
	{
		return str_replace(array_keys($this->transliterationTable), array_values($this->transliterationTable), $content);
	}
}
