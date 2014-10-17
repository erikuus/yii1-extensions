<?php
/**
 * GUID class
 *
 * This class adds helper method to generate globally unigue identifier
 *
 * Examples of usage:
 * <pre>
 *     $guid=GUID::generate();
 * </pre>
 */
class GUID
{
	/**
	 * @return string globally unigue identifier
	 */
	public static function generate()
	{
		$guid = '';

		$timeLow = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);
		$timeMid = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);

		$timeHighAndVersion = mt_rand(0, 255);
		$timeHighAndVersion = $timeHighAndVersion & hexdec('0f');
		$timeHighAndVersion = $timeHighAndVersion ^ hexdec('40');  // Sets the version number to 4 in the high byte
		$timeHighAndVersion = str_pad(dechex($timeHighAndVersion), 2, '0', STR_PAD_LEFT);

		$clockSeqHiAndReserved = mt_rand(0, 255);
		$clockSeqHiAndReserved = $clockSeqHiAndReserved & hexdec('3f');
		$clockSeqHiAndReserved = $clockSeqHiAndReserved ^ hexdec('80');  // Sets the variant for this GUID type to '10x'
		$clockSeqHiAndReserved = str_pad(dechex($clockSeqHiAndReserved), 2, '0', STR_PAD_LEFT);

		$clockSeqLow = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);

		$node = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);

		$guid = $timeLow . '-' . $timeMid . '-' . $timeHighAndVersion . $clockSeqHiAndReserved . '-' . $clockSeqLow . '-' . $node;

		return $guid;
	}
}