<?php
/**
 *
 * Author Column extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\authorcolumn\tests\event;

use phpbbmodders\authorcolumn\event\listener;

/**
 * Tests \phpbbmodders\authorcolumn\event\listener
 */
class listener_test extends \phpbb_test_case
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Temporary phpBB root holding test styles */
	protected $root_path;

	protected function setUp(): void
	{
		parent::setUp();

		$this->template = $this->getMockBuilder('\phpbb\template\template')->getMock();
		$this->user = $this->getMockBuilder('\phpbb\user')->disableOriginalConstructor()->getMock();

		$this->root_path = sys_get_temp_dir() . '/authorcolumn_test_' . uniqid() . '/';
		mkdir($this->root_path . 'styles/prosilver/template', 0777, true);
	}

	protected function tearDown(): void
	{
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->root_path, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($files as $file)
		{
			$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
		}
		rmdir($this->root_path);

		parent::tearDown();
	}

	/**
	 * Give a test style its own copy of a template.
	 *
	 * @param string $style_path	Style folder name
	 * @param string $filename		Template file name
	 */
	protected function add_style_template($style_path, $filename)
	{
		$directory = $this->root_path . 'styles/' . $style_path . '/template/';
		if (!is_dir($directory))
		{
			mkdir($directory, 0777, true);
		}
		touch($directory . $filename);
	}

	/**
	 * @param string $style_path		Style folder name
	 * @param string $parent_tree	Parent styles, separated by /
	 * @return listener
	 */
	protected function get_listener($style_path = 'prosilver', $parent_tree = '')
	{
		$this->user->style = [
			'style_path'		=> $style_path,
			'style_parent_tree'	=> $parent_tree,
		];

		return new listener($this->template, $this->user, $this->root_path);
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	protected function expected_body($filename)
	{
		$directory = phpbb_version_compare(PHPBB_VERSION, '4.0.0-dev', '>=') ? 'phpbb40' : 'phpbb33';

		return ['body' => '@phpbbmodders_authorcolumn/' . $directory . '/' . $filename];
	}

	public function test_construct()
	{
		$this->assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->get_listener());
	}

	public function test_subscribed_events()
	{
		$this->assertEquals([
			'core.viewforum_generate_page_after',
			'core.search_results_modify_search_title',
			'core.page_footer',
		], array_keys(listener::getSubscribedEvents()));
	}

	public static function prosilver_style_data()
	{
		return [
			'prosilver'			=> ['prosilver', ''],
			'child style'		=> ['prosilver_se', 'prosilver'],
			'grandchild style'	=> ['my_style', 'prosilver/prosilver_se'],
		];
	}

	/**
	 * @dataProvider prosilver_style_data
	 */
	public function test_viewforum_uses_copy_for_prosilver_styles($style_path, $parent_tree)
	{
		$this->template->expects($this->once())
			->method('set_filenames')
			->with($this->expected_body('viewforum_body.html'));

		$this->get_listener($style_path, $parent_tree)->use_viewforum_template(new \phpbb\event\data([]));
	}

	public static function other_style_data()
	{
		return [
			'unrelated style'			=> ['other_style', ''],
			'child of unrelated style'	=> ['other_child', 'other_style'],
			'name only contains prosilver'	=> ['prosilver_dark', 'not_prosilver'],
		];
	}

	/**
	 * @dataProvider other_style_data
	 */
	public function test_other_styles_keep_their_pages($style_path, $parent_tree)
	{
		$this->template->expects($this->never())->method('set_filenames');

		$listener = $this->get_listener($style_path, $parent_tree);
		$listener->use_viewforum_template(new \phpbb\event\data([]));
		$listener->mark_search_results(new \phpbb\event\data([]));
		$listener->use_search_results_template(new \phpbb\event\data([]));
	}

	public function test_child_style_with_own_template_keeps_it()
	{
		$this->add_style_template('my_style', 'viewforum_body.html');

		$this->template->expects($this->once())
			->method('set_filenames')
			->with($this->expected_body('search_results.html'));

		// my_style's own topic list stays; its search results still get the column
		$listener = $this->get_listener('my_style', 'prosilver');
		$listener->use_viewforum_template(new \phpbb\event\data([]));
		$listener->mark_search_results(new \phpbb\event\data([]));
		$listener->use_search_results_template(new \phpbb\event\data([]));
	}

	public function test_template_in_a_middle_style_is_kept()
	{
		$this->add_style_template('prosilver_se', 'viewforum_body.html');

		$this->template->expects($this->never())->method('set_filenames');

		$this->get_listener('my_style', 'prosilver/prosilver_se')->use_viewforum_template(new \phpbb\event\data([]));
	}

	public function test_search_results_use_copy()
	{
		$this->template->expects($this->once())
			->method('set_filenames')
			->with($this->expected_body('search_results.html'));

		$listener = $this->get_listener();
		$listener->mark_search_results(new \phpbb\event\data([]));
		$listener->use_search_results_template(new \phpbb\event\data([]));
	}

	public function test_other_pages_are_left_alone()
	{
		$this->template->expects($this->never())->method('set_filenames');

		$this->get_listener()->use_search_results_template(new \phpbb\event\data([]));
	}
}
