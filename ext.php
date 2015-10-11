<?php
/**
*
* Advanced BBCode Box
*
* @copyright (c) 2015 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\abbc3;

class ext extends \phpbb\extension\base
{
	/**
	 * @var string Require 3.2.0 due to new FAQ controller helper.
	 */
	const PHPBB_VERSION = '3.2.0-dev';

	/**
	 * {@inheritdoc}
	 */
	public function is_enableable()
	{
		return phpbb_version_compare(PHPBB_VERSION, self::PHPBB_VERSION, '>=');
	}
}
