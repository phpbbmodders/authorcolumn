<?php
/**
 *
 * Author Column extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\authorcolumn\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Swaps the forum topic list and search results pages for the extension's
 * copies, which add an Author column.
 *
 * phpBB has no template event inside the topic list's header row or between
 * the topic and Replies cells, so the column can't be added with event
 * templates. The extension ships full copies of prosilver's
 * viewforum_body.html and search_results.html instead, one set per phpBB
 * branch, and only uses them when the board style is prosilver or one of its
 * child styles. Other styles keep their own pages, and so does a child style
 * that ships its own copy of the template.
 */
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $root_path;

	/** @var bool Whether search.php is about to show its results page */
	protected $search_results = false;

	/**
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user				$user		User object
	 * @param string					$root_path	phpBB root path
	 */
	public function __construct(\phpbb\template\template $template, \phpbb\user $user, $root_path)
	{
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.viewforum_generate_page_after'		=> 'use_viewforum_template',
			'core.search_results_modify_search_title'	=> 'mark_search_results',
			'core.page_footer'							=> 'use_search_results_template',
		];
	}

	/**
	 * Use the extension's topic list page. viewforum.php has already set
	 * viewforum_body.html as the page body; this event is the last one before
	 * the page is shown.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function use_viewforum_template($event)
	{
		$this->use_template('viewforum_body.html');
	}

	/**
	 * Note that search.php is about to show its results page. It sets
	 * search_results.html after this event, so the swap waits for page_footer().
	 *
	 * @param \phpbb\event\data $event
	 */
	public function mark_search_results($event)
	{
		$this->search_results = true;
	}

	/**
	 * Use the extension's search results page, if search.php is showing one.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function use_search_results_template($event)
	{
		if ($this->search_results)
		{
			$this->use_template('search_results.html');
		}
	}

	/**
	 * Set the page body to the extension's copy of a prosilver template, for
	 * the running phpBB branch.
	 *
	 * @param string $filename prosilver template file name
	 */
	protected function use_template($filename)
	{
		if (!$this->uses_prosilver_template($filename))
		{
			return;
		}

		$this->template->set_filenames([
			'body' => '@phpbbmodders_authorcolumn/' . $this->get_branch_directory() . '/' . $filename,
		]);
	}

	/**
	 * Whether the user's style would show prosilver's own copy of a template:
	 * the style is prosilver or inherits from it, and no style between it and
	 * prosilver ships its own copy of the file.
	 *
	 * @param string $filename prosilver template file name
	 * @return bool
	 */
	protected function uses_prosilver_template($filename)
	{
		$style = $this->user->style ?? [];
		$style_path = (string) ($style['style_path'] ?? '');
		$parent_tree = (string) ($style['style_parent_tree'] ?? '');

		// The parent tree lists the oldest ancestor first; walk from the user's style up
		$styles = array_merge([$style_path], array_reverse($parent_tree !== '' ? explode('/', $parent_tree) : []));

		foreach ($styles as $name)
		{
			if ($name === 'prosilver')
			{
				return true;
			}

			if ($name === '' || file_exists($this->root_path . 'styles/' . $name . '/template/' . $filename))
			{
				return false;
			}
		}

		return false;
	}

	/**
	 * The template folder for the running phpBB branch.
	 *
	 * @return string
	 */
	protected function get_branch_directory()
	{
		return phpbb_version_compare(PHPBB_VERSION, '4.0.0-dev', '>=') ? 'phpbb40' : 'phpbb33';
	}
}
