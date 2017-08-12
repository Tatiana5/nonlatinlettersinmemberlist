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
/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.memberlist_modify_sql_query_data' => 'nonlatinletters',
		);
	}

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

	public function nonlatinletters($event)
	{
		$this->user->add_lang_ext('tatiana5/nonlatinlettersinmemberlist', array('nonlatinletters'));

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
			$sql_where = str_replace(' AND u.username_clean ' . $this->db->sql_like_expression(substr($this->request->variable('first_char', ''), 0, 1) . $this->db->get_any_char()), '', $sql_where);
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

		//Template
		$check_params = array(
			'g'				=> array('g', 0),
			'sk'			=> array('sk', 'c'),
			'sd'			=> array('sd', 'a'),
			'form'			=> array('form', ''),
			'field'			=> array('field', ''),
			'select_single'	=> array('select_single', $this->request->variable('select_single', false)),
			'username'		=> array('username', '', true),
			'email'			=> array('email', ''),
			'jabber'		=> array('jabber', ''),
			'search_group_id'	=> array('search_group_id', 0),
			'joined_select'	=> array('joined_select', 'lt'),
			'active_select'	=> array('active_select', 'lt'),
			'count_select'	=> array('count_select', 'eq'),
			'joined'		=> array('joined', ''),
			'active'		=> array('active', ''),
			'count'			=> ($this->request->variable('count', '') !== '') ? array('count', 0) : array('count', ''),
			'ip'			=> array('ip', ''),
			'first_char'	=> array('first_char', ''),
		);

		$u_first_char_params = array();

		foreach ($check_params as $key => $call)
		{
			if (!$this->request->is_set($key))
			{
				continue;
			}

			$param = $this->request->variable($key, $call);
			// Encode strings, convert everything else to int in order to prevent empty parameters.
			$param = urlencode($key) . '=' . ((is_string($param)) ? urlencode($param) : (int) $param);
			$params[] = $param;

			if ($key != 'first_char')
			{
				$u_first_char_params[] = $param;
			}
		}

		$u_first_char_params = implode('&amp;', $u_first_char_params);
		$u_first_char_params .= ($u_first_char_params) ? '&amp;' : '';

		$chars = array_unique(is_array($this->user->lang['NONLATIN_ALPHABET']) ? $this->user->lang['NONLATIN_ALPHABET'] : preg_split('//u', $this->user->lang['NONLATIN_ALPHABET'], -1, PREG_SPLIT_NO_EMPTY));

		foreach ($chars as $char)
		{
			$this->template->assign_block_vars('first_char', array(
				'DESC'			=> ($char == 'other') ? $this->user->lang['OTHER'] : utf8_strtoupper($char),
				'VALUE'			=> $char,
				'S_SELECTED'	=> ($first_char == $char) ? true : false,
				'U_SORT'		=> append_sid("{$this->phpbb_root_path}memberlist.$this->php_ext", $u_first_char_params . 'first_char=' . $char) . '#memberlist',
			));
		}
	}
}
