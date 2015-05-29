<?php
/**
*
* Advanced BBCode Box
*
* @copyright (c) 2013 Matt Friedman
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace vse\abbc3\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \vse\abbc3\core\bbcodes_parser */
	protected $bbcodes_parser;

	/** @var \vse\abbc3\core\bbcodes_display */
	protected $bbcodes_display;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string Extension root path */
	protected $ext_root_path;

	/** @var string default width of bbvideo */
	protected $bbvideo_width;

	/** @var string default height of bbvideo */
	protected $bbvideo_height;

	/**
	 * Constructor
	 *
	 * @param \vse\abbc3\core\bbcodes_parser  $bbcodes_parser
	 * @param \vse\abbc3\core\bbcodes_display $bbcodes_display
	 * @param \phpbb\controller\helper        $helper
	 * @param \phpbb\template\template        $template
	 * @param \phpbb\user                     $user
	 * @param string                          $root_path
	 * @param string                          $ext_root_path
	 * @param string                          $bbvideo_width
	 * @param string                          $bbvideo_height
	 * @access public
	 */
	public function __construct(\vse\abbc3\core\bbcodes_parser $bbcodes_parser, \vse\abbc3\core\bbcodes_display $bbcodes_display, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, $root_path, $ext_root_path, $bbvideo_width, $bbvideo_height)
	{
		$this->bbcodes_parser = $bbcodes_parser;
		$this->bbcodes_display = $bbcodes_display;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->ext_root_path = $ext_root_path;
		$this->bbvideo_width = $bbvideo_width;
		$this->bbvideo_height = $bbvideo_height;
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
		return array(
			'core.user_setup'							=> 'load_language_on_setup',

			// functions_content events
			'core.modify_text_for_display_before'		=> 'parse_bbcodes_before',
			'core.modify_text_for_display_after'		=> 'parse_bbcodes_after',

			// functions_display events
			'core.display_custom_bbcodes'				=> 'setup_custom_bbcodes',
			'core.display_custom_bbcodes_modify_sql'	=> 'custom_bbcode_modify_sql',
			'core.display_custom_bbcodes_modify_row'	=> 'display_custom_bbcodes',

			// message_parser events
			'core.modify_format_display_text_after'		=> 'parse_bbcodes_after',
			'core.modify_bbcode_init'					=> 'allow_custom_bbcodes', // Deprecated 3.2.x. Provides bc for 3.1.x.

			// text_formatter events
			'core.text_formatter_s9e_parser_setup'		=> 's9e_allow_custom_bbcodes',

			// BBCode FAQ
			'core.help_manager_add_block_after'			=> 'add_bbcode_faq',
		);
	}

	/**
	 * Load common files during user setup
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'vse/abbc3',
			'lang_set' => 'abbc3',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Alter BBCodes before they are processed by phpBB
	 *
	 * This is used to change old/malformed ABBC3 BBCodes to a newer structure
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function parse_bbcodes_before($event)
	{
		$event['text'] = $this->bbcodes_parser->pre_parse_bbcodes($event['text'], $event['uid']);
	}

	/**
	 * Alter BBCodes after they are processed by phpBB
	 *
	 * This is used on ABBC3 BBCodes that require additional post-processing
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function parse_bbcodes_after($event)
	{
		$event['text'] = $this->bbcodes_parser->post_parse_bbcodes($event['text']);
	}

	/**
	 * Modify the SQL array to gather custom BBCode data
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function custom_bbcode_modify_sql($event)
	{
		$sql_ary = $event['sql_ary'];
		$sql_ary['SELECT'] .= ', b.bbcode_group';
		$sql_ary['ORDER_BY'] = 'b.bbcode_order, b.bbcode_id';
		$event['sql_ary'] = $sql_ary;
	}

	/**
	 * Setup custom BBCode variables
	 *
	 * @return null
	 * @access public
	 */
	public function setup_custom_bbcodes()
	{
		$this->template->assign_vars(array(
			'ABBC3_USERNAME'			=> $this->user->data['username'],
			'ABBC3_BBCODE_ICONS'		=> $this->ext_root_path . 'images/icons',
			'ABBC3_BBVIDEO_HEIGHT'		=> $this->bbvideo_height,
			'ABBC3_BBVIDEO_WIDTH'		=> $this->bbvideo_width,

			'UA_ABBC3_BBVIDEO_WIZARD'	=> $this->helper->route('vse_abbc3_bbcode_wizard', array('mode' => 'bbvideo')),
			'UA_ABBC3_URL_WIZARD'		=> $this->helper->route('vse_abbc3_bbcode_wizard', array('mode' => 'url')),
		));
	}

	/**
	 * Alter custom BBCodes display
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function display_custom_bbcodes($event)
	{
		$event['custom_tags'] = $this->bbcodes_display->display_custom_bbcodes($event['custom_tags'], $event['row']);
	}

	/**
	 * Set custom BBCodes permissions
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 *
	 * @deprecated 3.2.0. Provides bc for phpBB 3.1.x.
	 */
	public function allow_custom_bbcodes($event)
	{
		$event['bbcodes'] = $this->bbcodes_display->allow_custom_bbcodes($event['bbcodes'], $event['rowset']);
	}

	/**
	 * Toggle custom BBCodes in the s9e\TextFormatter parser based on user's group memberships
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function s9e_allow_custom_bbcodes($event)
	{
		/** @var $service \phpbb\textformatter\s9e\parser object from the text_formatter.parser service */
		$service = $event['parser'];
		$parser = $service->get_parser();
		foreach ($parser->registeredVars['abbc3.bbcode_groups'] as $bbcode_name => $groups)
		{
			if (!$this->bbcodes_display->user_in_bbcode_group($groups))
			{
				$bbcode_name = rtrim($bbcode_name, '=');
				$service->disable_bbcode($bbcode_name);
			}
		}
	}

	/**
	 * Add ABBC3 BBCodes to the BBCode FAQ
	 *
	 * @param object $event The event object
	 * @return null
	 * @access public
	 */
	public function add_bbcode_faq($event)
	{
		// Add after the HELP_BBCODE_BLOCK_OTHERS block
		if ($event['block_name'] === 'HELP_BBCODE_BLOCK_OTHERS')
		{
			// Set the block template data
			$this->template->assign_block_vars('faq_block', array(
				'BLOCK_TITLE'	=> $this->user->lang('ABBC3_FAQ_TITLE'),
				'SWITCH_COLUMN'	=> false,
			));

			$abbc3_questions = array(
				'ABBC3_FONT_HELPLINE'		=> "[font=Comic Sans MS]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/font]",
				'ABBC3_HIGHLIGHT_HELPLINE'	=> "[highlight=yellow]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/highlight]",
				'ABBC3_ALIGN_HELPLINE'		=> "[align=center]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/align]",
				'ABBC3_FLOAT_HELPLINE'		=> "[float=right]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/float]",
				'ABBC3_STRIKE_HELPLINE'		=> "[s]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/s]",
				'ABBC3_SUB_HELPLINE'		=> "[sub]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/sub] {$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}",
				'ABBC3_SUP_HELPLINE'		=> "[sup]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/sup] {$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}",
				'ABBC3_GLOW_HELPLINE'		=> "[glow=red]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/glow]",
				'ABBC3_SHADOW_HELPLINE'		=> "[shadow=blue]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/shadow]",
				'ABBC3_DROPSHADOW_HELPLINE'	=> "[dropshadow=blue]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/dropshadow]",
				'ABBC3_BLUR_HELPLINE'		=> "[blur=blue]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/blur]",
				'ABBC3_FADE_HELPLINE'		=> "[fade]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/fade]",
				'ABBC3_PREFORMAT_HELPLINE'	=> "[pre]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}\n\t{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/pre]",
				'ABBC3_DIR_HELPLINE'		=> "[dir=rtl]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/dir]",
				'ABBC3_MARQUEE_HELPLINE'	=> "[marq=left]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/marq]",
				'ABBC3_SPOILER_HELPLINE'	=> "[spoil]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/spoil]",
				'ABBC3_HIDDEN_HELPLINE'		=> "[hidden]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/hidden]",
				'ABBC3_MOD_HELPLINE'		=> "[mod=\"Moderator_name\"]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/mod]",
				'ABBC3_OFFTOPIC_HELPLINE'	=> "[offtopic]{$this->user->lang('ABBC3_FAQ_SAMPLE_TEXT')}[/offtopic]",
				'ABBC3_NFO_HELPLINE'		=> '[nfo]༼ つ ◕_◕ ༽つ    ʕ•ᴥ•ʔ   ¯\_(ツ)_/¯[/nfo]',
				'ABBC3_BBVIDEO_HELPLINE'	=> '[BBvideo=560,340]http://www.youtube.com/watch?v=sP4NMoJcFd4[/BBvideo]',
			);

			// Process questions data for display as parsed and unparsed bbcodes
			foreach ($abbc3_questions as $key => $question)
			{
				$uid = $bitfield = $flags = '';
				generate_text_for_storage($question, $uid, $bitfield, $flags, true);
				$example = generate_text_for_edit($question, $uid, $flags);
				$result = generate_text_for_display($question, $uid, $bitfield, $flags);
				$title = explode(':', $this->user->lang($key), 2);

				$this->template->assign_block_vars('faq_block.faq_row', array(
					'FAQ_QUESTION'	=> $title[0],
					'FAQ_ANSWER'	=> $this->user->lang('ABBC3_FAQ_ANSWER', $title[1], $example['text'], $result),
				));
			}
		}
	}
}
