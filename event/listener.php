<?php
/**
*
* @package phpBB Extension - Non-Latin Letters in Memberlist
* @copyright (c) 2017 Татьяна5
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace tatiana5\nonlatinlettersinmemberlist\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	//** @var string phpbb_root_path */
	protected $phpbb_root_path;

	//** @var string php_ext */
	protected $php_ext;

	/**
	 * Constructor
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\user                       $user
	 * @param \phpbb\request\request            $request
	 * @param \phpbb\template\template          $template
	 * @param string                            $phpbb_root_path Root path
	 * @param string                            $php_ext
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\request\request $request,
								\phpbb\template\template $template, $phpbb_root_path, $php_ext)
	{
		$this->db = $db;
		$this->user = $user;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return [
			'core.memberlist_modify_sql_query_data'			=> 'memberlist_modify_sql_query_data',
			'core.memberlist_modify_sort_pagination_params'	=> 'memberlist_modify_sort_pagination_params',
		];
	}

	public function memberlist_modify_sql_query_data($event)
	{
		$this->user->add_lang_ext('tatiana5/nonlatinlettersinmemberlist', ['nonlatinletters']);

		$sql_where = $event['sql_where'];

		$first_char = $this->request->variable('first_char', '', true);
		$lcase = (strpos($this->db->get_sql_layer(), 'mssql') !== false) ? 'LOWER' : 'LCASE';

		//Replace old to ''
		if ($first_char == 'other')
		{
			for ($i = 97; $i < 123; $i++)
			{
				$sql_where = str_replace(' AND u.username_clean NOT ' . $this->db->sql_like_expression(chr($i) . $this->db->get_any_char()), '', $sql_where);
			}
		}
		else if ($first_char)
		{
			$sql_where = str_replace(' AND u.username_clean ' . $this->db->sql_like_expression(mb_substr($this->request->variable('first_char', ''), 0, 1) . $this->db->get_any_char()), '', $sql_where);
		}

		//Add new
		$chars = array_unique(array_merge(
			range('a', 'z'),
			is_array($this->user->lang['NONLATIN_ALPHABET']) ? $this->user->lang['NONLATIN_ALPHABET'] : preg_split('//u', $this->user->lang['NONLATIN_ALPHABET'], -1, PREG_SPLIT_NO_EMPTY)
		));

		if ($first_char == 'other')
		{
			foreach ($chars as $char)
			{
				$sql_where .= ($char == '-') ? '' : " AND $lcase(u.username) NOT " . $this->db->sql_like_expression($char . $this->db->get_any_char());
			}
		}
		else if ($first_char !== '')
		{
			$sql_where .= " AND $lcase(u.username) " . $this->db->sql_like_expression(utf8_substr($first_char, 0, 1) . $this->db->get_any_char());
		}

		$event['sql_where'] = $sql_where;
	}

	public function memberlist_modify_sort_pagination_params($event)
	{
		//Template
		$sort_params = $event['sort_params'];
		$params = $event['params'];
		$first_characters = $event['first_characters'];
		$u_first_char_params = $event['u_first_char_params'];
		$first_char_block_vars = $event['first_char_block_vars'];

		$first_char = $this->request->variable('first_char', '', true);

		$nonlatin_characters = array_unique(is_array($this->user->lang['NONLATIN_ALPHABET']) ? $this->user->lang['NONLATIN_ALPHABET'] : preg_split('//u', $this->user->lang['NONLATIN_ALPHABET'], -1, PREG_SPLIT_NO_EMPTY));
		//$nonlatin_characters['other'] = $first_characters['other'];
		array_pop($first_char_block_vars);
		
		$first_char_block_vars[] = [
			'DESC'			=> '&nbsp;',
			'VALUE'			=> '&nbsp;',
			'S_SELECTED'	=> false,
			'U_SORT'		=> '#',
		];

		foreach ($nonlatin_characters as $char => $desc)
		{
			$first_char_block_vars[] = [
				'DESC'			=> utf8_strtoupper($desc),
				'VALUE'			=> $desc,
				'S_SELECTED'	=> ($first_char == $desc) ? true : false,
				'U_SORT'		=> append_sid("{$this->phpbb_root_path}memberlist." . $this->php_ext, $u_first_char_params . 'first_char=' . $desc) . '#memberlist',
			];
		}

		$first_char_block_vars[] = [
			'DESC'			=> $first_characters['other'],
			'VALUE'			=> 'other',
			'S_SELECTED'	=> ($first_char == 'other') ? true : false,
			'U_SORT'		=> append_sid("{$this->phpbb_root_path}memberlist." . $this->php_ext, $u_first_char_params . 'first_char=other') . '#memberlist',
		];

		foreach ($sort_params as $key => $param)
		{
			if (stripos($param, 'first_char=') === 0)
			{
				$sort_params[$key] = 'first_char=' . $first_char;
			}
		}

		foreach ($params as $key => $param)
		{
			if (stripos($param, 'first_char=') === 0)
			{
				$params[$key] = 'first_char=' . $first_char;
			}
		}

		$event['first_char_block_vars'] = $first_char_block_vars;
		$event['sort_params'] = $sort_params;
		$event['params'] = $params;
	}
}
