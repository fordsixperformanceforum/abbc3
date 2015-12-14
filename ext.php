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
	const BBVIDEO_WIDTH = 560;
	const BBVIDEO_HEIGHT = 315;

	/**
	 * Require 3.2.0 due to new FAQ controller helper.
	 *
	 * {@inheritdoc}
	 */
	public function is_enableable()
	{
		return phpbb_version_compare(PHPBB_VERSION, '3.2.0-dev', '>=');
	}
}
