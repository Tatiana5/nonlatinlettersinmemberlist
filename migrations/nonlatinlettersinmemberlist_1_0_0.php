<?php
/**
*
* @package phpBB Extension - Non-Latin Letters in Memberlist
* @copyright (c) 2017 Татьяна5
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace tatiana5\nonlatinlettersinmemberlist\migrations;

class nonlatinlettersinmemberlist_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['nonlatinletters_version']) && version_compare($this->config['nonlatinletters_version'], '1.0.0', '>=');
	}

	static public function depends_on()
	{
		return ['\tatiana5\nonlatinlettersinmemberlist\migrations\nonlatinlettersinmemberlist_0_0_1'];
	}

	public function update_data()
	{
		return [
			// Current version
			['config.update', ['nonlatinletters_version', '1.0.0']],
		];
	}
}
