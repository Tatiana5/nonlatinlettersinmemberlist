<?php
/**
 *
 * @package       Non-Latin Letters in Memberlist
 * @copyright (c) Татьяна5
 * @license       http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'NONLATIN_ALPHABET' => 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя',
]);
