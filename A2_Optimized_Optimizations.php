<?php

/*
	Author: Benjamin Cool
	Author URI: https://www.a2hosting.com/
	License: GPLv2 or Later
*/

// Prevent direct access to this file
if (! defined('WPINC')) {
	die;
}

require_once 'A2_Optimized_Server_Info.php';

class A2_Optimized_Optimizations {
	private $thisclass;
	public $server_info;

	public function __construct($thisclass) {
		$this->thisclass = $thisclass;
		$w3tc = $thisclass->get_w3tc_config();
		$this->check_server_gzip();
		$this->server_info = new A2_Optimized_Server_Info($w3tc);
	}

	/**
	 * Checks if gzip test has been run to see if server is serving gzip, if not we run it.
	 * Expires after one week to reduce number of curl calls to server
	 */
	public function check_server_gzip() {
		$checked_gzip = get_transient('a2_checked_gzip');
		if (false === $checked_gzip) {
			$w3tc = $this->thisclass->get_w3tc_config();
			$previous_setting = $w3tc['browsercache.html.compression'];
			$this->thisclass->disable_w3tc_gzip();
			if ($previous_setting && is_object($this->server_info)) {
				if (!$this->server_info->gzip || !$this->server_info->cf || !$this->server_info->br) {
					$this->thisclass->enable_w3tc_gzip();
				}
			}
			set_transient('a2_checked_gzip', true, WEEK_IN_SECONDS);
		}
	}

	public function get_optimizations() {
		$public_opts = $this->get_public_optimizations();
		$private_opts = $this->get_private_optimizations();

		if (!is_plugin_active('a2-w3-total-cache/a2-w3-total-cache.php')) {
			unset($private_opts['memcached']);
		}

		return array_merge($public_opts, $private_opts);
	}

	protected function get_public_optimizations() {
		$thisclass = $this->thisclass;
		$thisclass->server_info = $this->server_info;

		$a2_object_cache_additional_info = '';

		if (get_option('a2_optimized_memcached_invalid')) {
			$a2_object_cache_additional_info = '<p><strong>Please confirm your memcached server settings before enabling Object Caching.</strong><br />' . get_option('a2_optimized_memcached_invalid') . '</p>';
		}

		if (is_plugin_active('a2-w3-total-cache/a2-w3-total-cache.php')) {
			/* W3 Total Cache Caching */
			$optimizations = [
				'page_cache' => [
					'slug' => 'page_cache',
					'name' => 'Page Caching with W3 Total Cache',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'description' => 'Utilize W3 Total Cache to make the site faster by caching pages as static content.  Cache: a copy of rendered dynamic pages will be saved by the server so that the next user does not need to wait for the server to generate another copy.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['pgcache.enabled']) {
							$item['configured'] = true;
							$permalink_structure = get_option('permalink_structure');
							$vars = [];
							if ($w3tc['pgcache.engine'] == 'apc') {
								if ($permalink_structure == '') {
									$vars['pgcache.engine'] = 'file';
								} else {
									$vars['pgcache.engine'] = 'file_generic';
								}
							} else {
								if ($permalink_structure == '' && $w3tc['pgcache.engine'] != 'file') {
									$vars['pgcache.engine'] = 'file';
								} elseif ($permalink_structure != '' && $w3tc['pgcache.engine'] == 'file') {
									$vars['pgcache.engine'] = 'file_generic';
								}
							}

							if (count($vars) != 0) {
								$thisclass->update_w3tc($vars);
							}

							$thisclass->set_install_status('page_cache', true);
						} else {
							$thisclass->set_install_status('page_cache', false);
						}
					},
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_w3tc_page_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_w3tc_page_cache();
					}
				],
				'db_cache' => [
					'slug' => 'db_cache',
					'name' => 'DB Caching with W3 Total Cache',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'description' => 'Speed up the site by storing the responses of common database queries in a cache.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['dbcache.enabled']) {
							$vars = [];
							$item['configured'] = true;
							if (class_exists('W3_Config')) {
								if (class_exists('WooCommerce')) {
									if (array_search('_wc_session_', $w3tc['dbcache.reject.sql']) === false) {
										$vars['dbcache.reject.sql'] = $w3tc['dbcache.reject.sql'];
										$vars['dbcache.reject.sql'][] = '_wc_session_';
									}
								}
							}
							if (count($vars) != 0) {
								$thisclass->update_w3tc($vars);
							}

							$thisclass->set_install_status('db_cache', true);
						} else {
							$thisclass->set_install_status('db_cache', false);
						}
					},
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_w3tc_db_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_w3tc_db_cache();
					}
				],
				'object_cache' => [
					'slug' => 'object_cache',
					'name' => 'Object Caching with W3 Total Cache',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'description' => 'Store a copy of widgets and menu bars in cache to reduce the time it takes to render pages.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['objectcache.enabled']) {
							$item['configured'] = true;
							$thisclass->set_install_status('object_cache', true);
						} else {
							$thisclass->set_install_status('object_cache', false);
						}
					},
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_w3tc_object_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_w3tc_object_cache();
					}
				],
				'browser_cache' => [
					'slug' => 'browser_cache',
					'name' => 'Browser Caching with W3 Total Cache',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'description' => 'Add Rules to the web server to tell the visitor&apos;s browser to store a copy of static files to reduce the load time pages requested after the first page is loaded.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['browsercache.enabled']) {
							$item['configured'] = true;
							$thisclass->set_install_status('browser_cache', true);
						} else {
							$thisclass->set_install_status('browser_cache', false);
						}
					},
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_w3tc_browser_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_w3tc_browser_cache();
					}
				],
				'minify' => [
					'name' => 'Minify HTML Pages',
					'slug' => 'minify',
					'plugin' => 'W3 Total Cache',
					'optional' => false,
					'configured' => false,
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'description' => 'Removes extra spaces,tabs and line breaks in the HTML to reduce the size of the files sent to the user.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['minify.enabled'] && $w3tc['minify.html.enable']) {
							$item['configured'] = true;
							$thisclass->set_install_status('minify-html', true);
						} else {
							$thisclass->set_install_status('minify-html', false);
						}
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_html_minify();
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_html_minify();
					}
				],
				'css_minify' => [
					'name' => 'Minify CSS Files',
					'slug' => 'css_minify',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'description' => 'Makes your site faster by condensing css files into a single downloadable file and by removing extra space in CSS files to make them smaller.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['minify.css.enable']) {
							$item['configured'] = true;
							$thisclass->set_install_status('minify-css', true);
						} else {
							$thisclass->set_install_status('minify-css', false);
						}
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->update_w3tc([
							'minify.css.enable' => true,
							'minify.enabled' => true,
							'minify.auto' => 0,
							'minify.engine' => 'file'
						]);
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->update_w3tc([
							'minify.css.enable' => false,
							'minify.auto' => 0
						]);
					}
				],
				'js_minify' => [
					'name' => 'Minify JS Files',
					'slug' => 'js_minify',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
					'description' => 'Makes your site faster by condensing JavaScript files into a single downloadable file and by removing extra space in JavaScript files to make them smaller.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['minify.js.enable']) {
							$item['configured'] = true;
							$thisclass->set_install_status('minify-js', true);
						} else {
							$thisclass->set_install_status('minify-js', false);
						}
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->update_w3tc([
							'minify.js.enable' => true,
							'minify.enabled' => true,
							'minify.auto' => 0,
							'minify.engine' => 'file'
						]);
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->update_w3tc([
							'minify.js.enable' => false,
							'minify.auto' => 0
						]);
					}
				],
				'gzip' => [
					'name' => 'Gzip Compression Enabled',
					'slug' => 'gzip',
					'plugin' => 'W3 Total Cache',
					'configured' => false,
					'description' => 'Makes your site significantly faster by compressing all text files to make them smaller.',
					'is_configured' => function (&$item) use (&$thisclass) {
						$w3tc = $thisclass->get_w3tc_config();
						if ($w3tc['browsercache.html.compression'] || $thisclass->server_info->cf || $thisclass->server_info->gzip || $thisclass->server_info->br) {
							$item['configured'] = true;
							$thisclass->set_install_status('gzip', true);
						} else {
							$thisclass->set_install_status('gzip', false);
						}
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_w3tc_gzip();
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_w3tc_gzip();
					},
					'remove_link' => true
				]
			];
		} else {
			/* Internal Caching */
			$optimizations = [
				'a2_page_cache' => [
					'slug' => 'a2_page_cache',
					'name' => 'Page Caching',
					'plugin' => 'A2 Optimized',
					'configured' => false,
					'description' => 'Enable Disk Cache to make the site faster by caching pages as static content.  Cache: a copy of rendered dynamic pages will be saved by the server so that the next user does not need to wait for the server to generate another copy.<br /><a href="admin.php?a2-page=cache_settings&page=A2_Optimized_Plugin_admin">Advanced Settings</a>',
					'is_configured' => function (&$item) use (&$thisclass) {
						if (get_option('a2_cache_enabled') == 1 && file_exists( WP_CONTENT_DIR . '/advanced-cache.php')) {
							$item['configured'] = true;

							$thisclass->set_install_status('a2_page_cache', true);
						} else {
							$thisclass->set_install_status('a2_page_cache', false);
						}
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_a2_page_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_a2_page_cache();
					}
				],
				'a2_page_cache_gzip' => [
					'slug' => 'a2_page_cache_gzip',
					'name' => 'Gzip Compression Enabled',
					'plugin' => 'A2 Optimized',
					'configured' => false,
					'description' => 'Makes your site significantly faster by compressing all text files to make them smaller.',
					'is_configured' => function (&$item) use (&$thisclass) {
						if (A2_Optimized_Cache_Engine::$settings['compress_cache']) {
							$item['configured'] = true;

							$thisclass->set_install_status('a2_page_cache_gzip', true);
						} else {
							$thisclass->set_install_status('a2_page_cache_gzip', false);
						}
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_a2_page_cache_gzip();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_a2_page_cache_gzip();
					},
					'remove_link' => true
				],
				'a2_object_cache' => [
					'slug' => 'a2_object_cache',
					'name' => 'Memcache Object Caching',
					'plugin' => 'A2 Optimized',
					'configured' => false,
					'description' => '
						<ul>
							<li>Extremely fast and powerful caching system.</li>
							<li>Store frequently used database queries and WordPress objects in an in-memory object cache.</li>
							<li>Object caching is a key-value store for small chunks of arbitrary data (strings, objects) from results of database calls, API calls, or page rendering.</li>
							<li>Take advantage of A2 Hosting&apos;s one-click memcached configuration for WordPress.</li>
						</ul>
						<strong>A supported object cache server and the corresponding PHP extension are required.</strong><br /><a href="admin.php?a2-page=cache_settings&page=A2_Optimized_Plugin_admin">Configure Object Cache Settings</a>
					' . $a2_object_cache_additional_info,
					'is_configured' => function (&$item) use (&$thisclass) {
						if (get_option('a2_object_cache_enabled') == 1 && file_exists( WP_CONTENT_DIR . '/object-cache.php')) {
							$item['configured'] = true;

							$thisclass->set_install_status('a2_object_cache', true);
						} else {
							$thisclass->set_install_status('a2_object_cache', false);
						}
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_a2_object_cache();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_a2_object_cache();
					}
				],
				'a2_page_cache_minify_html' => [
					'slug' => 'a2_page_cache_minify_html',
					'name' => 'Minify HTML Pages',
					'plugin' => 'A2 Optimized',
					'configured' => false,
					'description' => 'Removes extra spaces, tabs and line breaks in the HTML to reduce the size of the files sent to the user.',
					'is_configured' => function (&$item) use (&$thisclass) {
						if (A2_Optimized_Cache_Engine::$settings['minify_html']) {
							$item['configured'] = true;

							$thisclass->set_install_status('a2_page_cache_minify_html', true);
						} else {
							$thisclass->set_install_status('a2_page_cache_minify_html', false);
						}
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_a2_page_cache_minify_html();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_a2_page_cache_minify_html();
					},
					'remove_link' => true
				],
				'a2_page_cache_minify_jscss' => [
					'slug' => 'a2_page_cache_minify_jscss',
					'name' => 'Minify Inline CSS and Javascript',
					'plugin' => 'A2 Optimized',
					'configured' => false,
					'optional' => true,
					'description' => 'Removes extra spaces, tabs and line breaks in inline CSS and Javascript to reduce the size of the files sent to the user. <strong>Note:</strong> This may cause issues with some page builders or other Javascript heavy front end plugins/themes.',
					'is_configured' => function (&$item) use (&$thisclass) {
						if (A2_Optimized_Cache_Engine::$settings['minify_inline_css_js']) {
							$item['configured'] = true;

							$thisclass->set_install_status('a2_page_cache_minify_jscss', true);
						} else {
							$thisclass->set_install_status('a2_page_cache_minify_jscss', false);
						}
					},
					'disable' => function () use (&$thisclass) {
						$thisclass->disable_a2_page_cache_minify_jscss();
					},
					'enable' => function () use (&$thisclass) {
						$thisclass->enable_a2_page_cache_minify_jscss();
					},
					'remove_link' => true
				]
			];
		}

		/* Common optimizations */
		$common_optimizations = [
			'a2-db-optimizations' => [
				'name' => 'Schedule Automatic Database Optimizations',
				'slug' => 'a2-db-optimizations',
				'plugin' => 'A2 Optimized',
				'configured' => false,
				'description' => 'If enabled, will periodically clean the MySQL database of expired transients, trashed comments, spam comments, and optimize all tables. You may also select to remove post revisions and trashed posts from the Database Optimization Settings.<br />
				<a href="admin.php?a2-page=cache_settings&page=A2_Optimized_Plugin_admin">Configure Database Optimization Settings</a>',
				'is_configured' => function (&$item) use (&$thisclass) {
					$toggles = get_option(A2_Optimized_DBOptimizations::WP_SETTING);
					if (isset($toggles[A2_Optimized_DBOptimizations::CRON_ACTIVE]) && $toggles[A2_Optimized_DBOptimizations::CRON_ACTIVE]) {
						$item['configured'] = true;
						$thisclass->set_install_status('a2-db-optimizations', true);
					} else {
						$thisclass->set_install_status('a2-db-optimizations', false);
					}
				},
				'enable' => function () use (&$thisclass) {
					A2_Optimized_DBOptimizations::set(A2_Optimized_DBOptimizations::CRON_ACTIVE, true);
				},
				'disable' => function () use (&$thisclass) {
					A2_Optimized_DBOptimizations::set(A2_Optimized_DBOptimizations::CRON_ACTIVE, false);
				},
			],
			'woo-cart-fragments' => [
				'name' => 'Dequeue WooCommerce Cart Fragments AJAX calls',
				'slug' => 'woo-cart-fragments',
				'plugin' => 'A2 Optimized',
				'optional' => true,
				'configured' => false,
				'description' => 'Disable WooCommerce Cart Fragments on your homepage. Also enables "redirect to cart page" option in WooCommerce',
				'is_configured' => function (&$item) use (&$thisclass) {
					if (get_option('a2_wc_cart_fragments')) {
						$item['configured'] = true;
						$thisclass->set_install_status('woo-cart-fragments', true);
					} else {
						$thisclass->set_install_status('woo-cart-fragments', false);
					}
				},
				'enable' => function () use (&$thisclass) {
					$thisclass->enable_woo_cart_fragments();
				},
				'disable' => function () use (&$thisclass) {
					$thisclass->disable_woo_cart_fragments();
				},
			],
			'xmlrpc-requests' => [
				'name' => 'Block Unauthorized XML-RPC Requests',
				'slug' => 'xmlrpc-requests',
				'plugin' => 'A2 Optimized',
				'optional' => true,
				'configured' => false,
				'description' => '
					<p>Completely Disable XML-RPC services</p>
				',
				'is_configured' => function (&$item) use (&$thisclass) {
					if (get_option('a2_block_xmlrpc')) {
						$item['configured'] = true;
						$thisclass->set_install_status('xmlrpc-requests', true);
					} else {
						$thisclass->set_install_status('xmlrpc-requests', false);
					}
				},
				'enable' => function () use (&$thisclass) {
					$thisclass->enable_xmlrpc_requests();
				},
				'disable' => function () use (&$thisclass) {
					$thisclass->disable_xmlrpc_requests();
				},
			],
			'regenerate-salts' => [
				'name' => 'Regenerate wp-config salts',
				'slug' => 'regenerate-salts',
				'plugin' => 'A2 Optimized',
				'optional' => true,
				'configured' => false,
				'is_configured' => function (&$item) use (&$thisclass) {
					if (get_option('a2_updated_regenerate-salts')) {
						$last_updated = strtotime(get_option('a2_updated_regenerate-salts'));
						if ($last_updated > strtotime('-3 Months')) {
							$item['configured'] = true;
						}
					}
				},
				'description' => "<p>Generate new salt values for wp-config.php</p><p>WordPress salts and security keys help secure your site's login process and the cookies that WordPress uses to authenticate users. There are security benefits to periodically changing your salts to make it even harder for malicious actors to access them. You may need to clear your browser cookies after activating this option.</p><p><strong>This will log out all users including yourself</strong></p>",
				'last_updated' => true,
				'update' => true,
				'enable' => function () use (&$thisclass) {
					$thisclass->regenerate_wpconfig_salts();
				},
			],
			'htaccess' => [
				'name' => 'Deny Direct Access to Configuration Files and Comment Form',
				'slug' => 'htaccess',
				'plugin' => 'A2 Optimized',
				'optional' => true,
				'configured' => false,
				'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-the-a2-optimized-plugin',
				'description' => 'Protects your configuration files by generating a Forbidden error to web users and bots when trying to access WordPress configuration files. <br> Also prevents POST requests to the site not originating from a user on the site. <br> <span class="danger" >note</span>: if you are using a plugin to allow remote posts and comments, disable this option.',
				'is_configured' => function (&$item) use (&$thisclass) {
					$htaccess = file_get_contents(ABSPATH . '.htaccess');
					if (strpos($htaccess, '# BEGIN WordPress Hardening') === false) {
						if ($thisclass->get_deny_direct() == true) {
							$thisclass->set_deny_direct(false);
						}
						//make sure the basic a2-optimized rules are present
						$thisclass->set_install_status('htaccess-deny-direct-access', false);
					} else {
						if ($thisclass->get_deny_direct() == true) {
							$item['configured'] = true;
						}
					}
				},
				'enable' => function () use (&$thisclass) {
					$thisclass->set_deny_direct(true);
					$thisclass->write_htaccess();
				},
				'disable' => function () use (&$thisclass) {
					$thisclass->set_deny_direct(false);
					$thisclass->write_htaccess();
				}
			],
			'lock' => [
				'name' => 'Lock Editing of Plugins and Themes from the WP Admin',
				'slug' => 'lock',
				'plugin' => 'A2 Optimized',
				'configured' => false,
				'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-the-a2-optimized-plugin',
				'description' => 'Prevents exploits that use the built in editing capabilities of the WP Admin',
				'is_configured' => function (&$item) use (&$thisclass) {
					$wpconfig = file_get_contents(ABSPATH . 'wp-config.php');
					if (strpos($wpconfig, '// BEGIN A2 CONFIG') === false) {
						if ($thisclass->get_lockdown() == true) {
							$thisclass->get_lockdown(false);
						}
						$thisclass->set_install_status('lock-editing', false);
					} else {
						if ($thisclass->get_lockdown() == true) {
							$item['configured'] = true;
						}
					}
				},
				'enable' => function () use (&$thisclass) {
					$thisclass->set_lockdown(true);
					$thisclass->write_wp_config();
				},
				'disable' => function () use (&$thisclass) {
					$thisclass->set_lockdown(false);
					$thisclass->write_wp_config();
				}
			],
			'wp-login' => [
				'name' => 'Login URL Change',
				'slug' => 'wp-login',
				'premium' => true,
				'plugin' => 'Rename wp-login.php',
				'configured' => false,
				'kb' => 'http://www.a2hosting.com/kb/security/application-security/wordpress-security#a-namemethodRenameLoginPageaMethod-3.3A-Change-the-WordPress-login-URL',
				'description' => '
					<p>Change the URL of your login page to make it harder for bots to find it to brute force attack.</p>
				',
				'is_configured' => function () {
					return false;
				}
			],
			'captcha' => [
				'name' => 'reCAPTCHA on comments and login',
				'plugin' => 'reCAPTCHA',
				'slug' => 'captcha',
				'premium' => true,
				'configured' => false,
				'description' => 'Decreases spam and increases site security by adding a CAPTCHA to comment forms and the login screen.  Without a CAPTCHA, bots will easily be able to post comments to you blog or brute force login to your admin panel. You may override the default settings and use your own Site Key and select a theme.',
				'is_configured' => function () {
					return false;
				}
			],
			'images' => [
				'name' => 'Compress Images on Upload',
				'plugin' => 'Image Optimizer',
				'slug' => 'images',
				'premium' => true,
				'configured' => false,
				'description' => 'Makes your site faster by compressing images to make them smaller.',
				'is_configured' => function () {
					return false;
				}
			],
			'turbo' => [
				'name' => 'Turbo Web Hosting',
				'slug' => 'turbo',
				'configured' => false,
				'premium' => true,
				'description' => '
					<ul>
						<li>Turbo Web Hosting servers compile .htaccess files to make speed improvements. Any changes to .htaccess files are immediately re-compiled.</li>
						<li>Turbo Web Hosting servers have their own PHP API that provides speed improvements over FastCGI and PHP-FPM (FastCGI Process Manager). </li>
						<li>To serve static files, Turbo Web Hosting servers do not need to create a worker process as the user. Servers only create a worker process for PHP scripts, which results in faster performance.</li>
						<li>PHP OpCode Caching is enabled by default. Accounts are allocated 256 MB of memory toward OpCode caching.</li>
						<li>Turbo Web Hosting servers have a built-in caching engine for Full Page Cache and Edge Side Includes.</li>
					</ul>
				',
				'is_configured' => function () {
					return false;
				}
			]
		];

		$optimizations = array_merge($optimizations, $common_optimizations);

		$optimizations = $this->apply_optimization_filter($optimizations);

		return $optimizations;
	}

	/*
	 * Changes to optimizations based on various factors
	 */
	public function apply_optimization_filter($optimizations) {
		if (get_template() == 'Divi') {
			$optimizations['minify']['optional'] = true;
			$optimizations['css_minify']['optional'] = true;
			$optimizations['js_minify']['optional'] = true;
		}

		if (is_plugin_active('litespeed-cache/litespeed-cache.php')) {
			$optimizations['a2_object_cache']['name'] = 'Object Caching with Memcached or Redis';
			if (get_option('litespeed.conf.object') == 1) {
				$optimizations['a2_object_cache']['configured'] = true;
				$optimizations['a2_object_cache']['description'] .= '<br /><strong>This feature is provided by the LiteSpeed Cache plugin.</strong></p>';
				unset($optimizations['a2_object_cache']['disable']);
			}
			if (class_exists('A2_Optimized_Private_Optimizations')) {
				$a2opt_priv = new A2_Optimized_Private_Optimizations();
				$file_path = $a2opt_priv->get_redis_socket();
				$optimizations['a2_object_cache']['description'] .= "<br />$file_path";
			}
		}
		if (get_option('a2_optimized_memcached_invalid')) {
			unset($optimizations['a2_object_cache']['enable']);
		}

		return $optimizations;
	}

	protected function get_private_optimizations() {
		if (class_exists('A2_Optimized_Private_Optimizations')) {
			$a2opt_priv = new A2_Optimized_Private_Optimizations();

			return $a2opt_priv->get_optimizations($this->thisclass);
		} else {
			return [];
		}
	}

	public function get_advanced() {
		$public_opts = $this->get_public_advanced();
		$private_opts = $this->get_private_advanced();

		return array_merge($public_opts, $private_opts);
	}

	protected function get_public_advanced() {
		$thisclass = $this->thisclass;

		return [
			'gtmetrix' => [
				'slug' => 'gtmetrix',
				'name' => 'GTmetrix',
				'plugin' => 'GTmetrix',
				'plugin_slug' => 'gtmetrix-for-wordpress',
				'file' => 'gtmetrix-for-wordpress/gtmetrix-for-wordpress.php',
				'configured' => false,
				'partially_configured' => false,
				'required_options' => ['gfw_options' => ['authorized']],
				'description' => '
      			<p>
					Plugin that actively keeps track of your WP install and sends you alerts if your site falls below certain criteria.
					The GTMetrix plugin requires an account with <a href="http://gtmetrix.com/" >gtmetrix.com</a>
      			</p>
				<p>
      				<b>Use this plugin only if your site is experiencing issues with slow load times.</b><br><b style="color:red">The GTMetrix plugin will slow down your site.</b>
      			</p>
      			',
				'not_configured_links' => [],
				'configured_links' => [
					'Configure GTmetrix' => 'admin.php?page=gfw_settings',
					'GTmetrix Tests' => 'admin.php?page=gfw_tests',
				],
				'partially_configured_links' => [
					'Configure GTmetrix' => 'admin.php?page=gfw_settings',
					'GTmetrix Tests' => 'admin.php?page=gfw_tests',
				],
				'partially_configured_message' => 'Click &quot;Configure GTmetrix&quot; to enter your GTmetrix Account Email and GTmetrix API Key.',
				'kb' => 'http://www.a2hosting.com/kb/installable-applications/optimization-and-configuration/wordpress2/optimizing-wordpress-with-w3-total-cache-and-gtmetrix',
				'is_configured' => function (&$item) use (&$thisclass) {
					$gfw_options = get_option('gfw_options');
					if (is_plugin_active($item['file']) && isset($gfw_options['authorized']) && $gfw_options['authorized'] == 1) {
						$item['configured'] = true;
						$thisclass->set_install_status('gtmetrix', true);
					} elseif (is_plugin_active($item['file'])) {
						$item['partially_configured'] = true;
					} else {
						$thisclass->set_install_status('gtmetrix', false);
					}
				},
				'enable' => function ($slug) use (&$thisclass) {
					$item = $thisclass->get_advanced_optimizations();
					$item = $item[$slug];
					if (!isset($thisclass->plugin_list[$item['file']])) {
						$thisclass->install_plugin($item['plugin_slug']);
					}
					if (!is_plugin_active($item['file'])) {
						$thisclass->activate_plugin($item['file']);
					}
				},
				'disable' => function ($slug) use (&$thisclass) {
					$item = $thisclass->get_advanced_optimizations();
					$item = $item[$slug];
					$thisclass->deactivate_plugin($item['file']);
				}
			],
			'cloudflare' => [
				'slug' => 'cloudflare',
				'name' => 'CloudFlare',
				'premium' => true,
				'description' => '
                        <p>
                                CloudFlare is a free global CDN and DNS provider that can speed up and protect any site online.
                        </p>

                        <dl style="padding-left:20px">
                                        <dt>CloudFlare CDN</dt>
                                        <dd>Distribute your content around the world so it&apos;s closer to your visitors (speeding up your site).</dd>
                                        <dt>CloudFlare optimizer</dt>
                                        <dd>Web pages with ad servers and third party widgets load snappy on both mobile and computers.</dd>
                                        <dt>CloudFlare security</dt>
                                        <dd>Protect your website from a range of online threats from spammers to SQL injection to DDOS.</dd>
                                        <dt>CloudFlare analytics</dt>
                                        <dd>Get insight into all of your website&apos;s traffic including threats and search engine crawlers.</dd>
                        </dl>
                        <div class="alert alert-info">
                                Host with A2 Hosting to take advantage of one click CloudFlare configuration.
                        </div>
                ',
				'configured' => $this->server_info->cf,
				'is_configured' => function () {
					return false;
				},
				'not_configured_links' => ['Host with A2' => 'https://www.a2hosting.com/wordpress-hosting?utm_source=A2%20Optimized&utm_medium=Referral&utm_campaign=A2%20Optimized']
			]
		];
	}

	protected function get_private_advanced() {
		if (class_exists('A2_Optimized_Private_Optimizations')) {
			$a2opt_priv = new A2_Optimized_Private_Optimizations();

			return $a2opt_priv->get_advanced($this->thisclass);
		} else {
			return [];
		}
	}

	public function get_warnings() {
		$public_opts = $this->get_public_warnings();
		$private_opts = $this->get_private_warnings();

		return array_merge($public_opts, $private_opts);
	}

	protected function get_public_warnings() {
		return [
			'Bad WP Options' => [
				'posts_per_page' => [
					'title' => 'Recent Post Limit',
					'description' => 'The number of recent posts per page is set greater than five. This could be slowing down page loads.',
					'type' => 'numeric',
					'threshold_type' => '>',
					'threshold' => 5,
					'config_url' => admin_url() . 'options-reading.php'
				],
				'posts_per_rss' => [
					'title' => 'RSS Post Limit',
					'description' => 'The number of posts from external feeds is set greater than 5. This could be slowing down page loads.',
					'type' => 'numeric',
					'threshold_type' => '>',
					'threshold' => 5,
					'config_url' => admin_url() . 'options-reading.php'
				],
				'show_on_front' => [
					'title' => 'Recent Posts showing on home page',
					'description' => 'Speed up your home page by selecting a static page to display.',
					'type' => 'text',
					'threshold_type' => '=',
					'threshold' => 'posts',
					'config_url' => admin_url() . 'options-reading.php'
				],
				'permalink_structure' => [
					'title' => 'Permalink Structure',
					'description' => 'To fully optimize page caching with "Disk Enhanced" mode:<br>you must set a permalink structure other than "Default".',
					'type' => 'text',
					'threshold_type' => '=',
					'threshold' => '',
					'config_url' => admin_url() . 'options-permalink.php'
				]
			],
			'Advanced Warnings' => [
				'themes' => [
					'is_warning' => function () {
						$theme_count = 0;
						$themes = wp_get_themes();
						foreach ($themes as $theme_name => $theme) {
							if (substr($theme_name, 0, 6) != 'twenty') {
								// We don't want default themes to count towards our warning total
								$theme_count++;
							}
						}
						switch ($theme_count) {
							case 1:
								return false;
							case 2:
								$theme = wp_get_theme();
								if ($theme->get('Template') != '') {
									return false;
								}
						}

						return true;
					},
					'title' => 'Unused Themes',
					'description' => 'One or more unused non-default themes are installed. Unused non-default themes should be deleted.  For more information read the Wordpress.org Codex on <a target="_blank" href="http://codex.wordpress.org/WordPress_Housekeeping#Theme_Housekeeping">WordPress Housekeeping</a>',
					'config_url' => admin_url() . 'themes.php'
				],
				'a2_hosting' => [
					'title' => 'Not Hosted with A2 Hosting',
					'description' => 'Get faster page load times and more optimizations when you <a href="https://www.a2hosting.com/wordpress-hosting?utm_source=A2%20Optimized&utm_medium=Referral&utm_campaign=A2%20Optimized" target="_blank">host with A2 Hosting</a>.',
					'is_warning' => function () {
						if (is_dir('/opt/a2-optimized')) {
							return false;
						}

						return true;
					},
					'config_url' => 'https://www.a2hosting.com/wordpress-hosting?utm_source=A2%20Optimized&utm_medium=Referral&utm_campaign=A2%20Optimized'
				]
			],
			'Bad Plugins' => [
				'wp-super-cache',
				'wp-file-cache',
				'wp-db-backup',
			]
		];
	}

	protected function get_private_warnings() {
		if (class_exists('A2_Optimized_Private_Optimizations')) {
			$a2opt_priv = new A2_Optimized_Private_Optimizations();

			return $a2opt_priv->get_warnings($this->thisclass);
		} else {
			return [];
		}
	}
}
