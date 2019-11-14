<?php
/*
Plugin Name: Plain Custom Login
Version: 0.23.2
Plugin URI: https://github.com/kifd/plain-custom-login
Description: Lightweight plugin to let you customise the login page and popup to better reflect your site's appearance.
Author: Keith Drakard
Author URI: https://drakard.com/
*/

class PlainCustomLogin {

	public function __construct() {
		load_theme_textdomain('PlainCustomLogin', plugin_dir_path(__FILE__).'/languages');

		// plugin settings
		add_action('admin_init', array($this, 'settings_init'));
		add_action('admin_menu', array($this, 'add_settings_page'));

		// remove the default WP styling to avoid unnecessary conflicts
		add_action('login_init', array($this, 'remove_head_links_start'));
		add_action('login_head', array($this, 'remove_head_links_end'));
		// and adjust the default HTML so we can pretty up the checkbox
		add_action('login_form', array($this, 'alter_forget_me_html_start'), 10);
		add_action('login_footer', array($this, 'alter_forget_me_html_end'), 10);

		// queue up our css on the relevant pages
		add_action('login_init', array($this, 'register_styles_and_scripts'));
		add_action('admin_init', array($this, 'register_styles_and_scripts'));
		add_action('login_footer', array($this, 'enqueue_styles_and_scripts'));
		add_action('admin_footer', array($this, 'enqueue_styles_and_scripts'));

		// replace the default WP titles with the site specific ones
		add_filter('login_headerurl', array($this, 'custom_login_header_url'));
		add_filter('login_headertext', array($this, 'custom_login_header_text'));
	}



	/******************************************************************************************************************************************************************/

	// can't use deregister_style as WP echos the <link>, so have to do it this way instead
	public function remove_head_links_start() {
		ob_start(array($this, 'remove_head_links'));
	}
	public function remove_head_links($head) {
		//return preg_replace("/<link rel='stylesheet' id='.+\-css'  .+>/", '', $head);
		$head = preg_replace("/<link rel='stylesheet' id='forms\-css'  .+>/", '', $head);
		$head = preg_replace("/<link rel='stylesheet' id='login\-css'  .+>/", '', $head);

		return $head;
	}
	public function remove_head_links_end() {
		ob_get_flush();
	}

	public function alter_forget_me_html_start() {
		ob_start(array($this, 'alter_forget_me_html'));
	}
	public function alter_forget_me_html($body) {
		return preg_replace(
			'|<p class="forgetmenot"><label for="rememberme">(<input.+>)\s*(.+)</label></p>|',
			'<p class="forgetmenot">\1 <label for="rememberme">\2?</label></p>',
		$body);
	}
	public function alter_forget_me_html_end() {
		ob_get_flush();
	}



	public function register_styles_and_scripts() {
		wp_register_style('plain-custom-login-style', plugins_url('style.css', __FILE__), false, '0.1');
		wp_register_script('plain-custom-login-script', plugins_url('admin.js', __FILE__), array('wp-color-picker'));
	}

	public function enqueue_styles_and_scripts() {
		// we're only called from the admin side or login page
		if (is_admin()) {
			// don't need the colour picker outside the admin, and we've got the options already
			wp_enqueue_style('wp-color-picker'); 
			wp_enqueue_script('plain-custom-login-script', plugins_url('admin.js', __FILE__), array('wp-color-picker'), false, true);
		} else {
			// if we're on the login page, we still need to get the options
			if (! ($this->options = get_option('PlainCustomLoginPluginOptions'))) {
				$this->options = $this->make_default_selections();
			}
		}

		$custom_css = '
			body.login {
				background-color:'.$this->options['colours']['base']['value'].';
			}
			#wp-auth-check-wrap #wp-auth-check {
				background-color:'.change_hsl($this->options['colours']['base']['value'], 1,1, 0.8).';
			}
			#wp-auth-check-wrap .wp-auth-check-close:before {
				color:'.get_contrast($this->options['colours']['base']['value']).';
			}

			#login h1 a {
				color:'.$this->options['colours']['title']['value'].';
				text-shadow:0 0 0.1rem '.get_contrast($this->options['colours']['base']['value']).',
							0.1rem 0.1rem 0.2rem '.get_contrast($this->options['colours']['title']['value']).';
			}

			#login_error {
				background-color:'.$this->options['colours']['errback']['value'].';
				border-color:'.change_hsl($this->options['colours']['errback']['value'], 1, 3, 1).';
				color:'.$this->options['colours']['errtext']['value'].';
				text-shadow:0 0 0.1rem '.get_contrast($this->options['colours']['errtext']['value']).';

				-webkit-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['errback']['value'], 1, 1, 0.7).' inset;
				-moz-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['errback']['value'], 1, 1, 0.7).' inset;
				box-shadow:0 0 1rem '.change_hsl($this->options['colours']['errback']['value'], 1, 1, 0.7).' inset;
			}
			.message {
				background-color:'.$this->options['colours']['msgback']['value'].';
				border-color:'.change_hsl($this->options['colours']['msgback']['value'], 1, 1.8, 1.1).';
				color:'.$this->options['colours']['msgtext']['value'].';
				text-shadow:0 0 0.1rem '.get_contrast($this->options['colours']['msgtext']['value']).';

				-webkit-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['msgback']['value'], 1, 1, 0.7).' inset;
				-moz-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['msgback']['value'], 1, 1, 0.7).' inset;
				box-shadow:0 0 1rem '.change_hsl($this->options['colours']['msgback']['value'], 1, 1, 0.7).' inset;
			}

			#login form {
				background-color:'.$this->options['colours']['formback']['value'].';
				border-color:'.$this->options['colours']['base']['value'].';
				color:'.$this->options['colours']['formtext']['value'].';

				-webkit-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).';
				-moz-box-shadow:0 0 1rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).';
				box-shadow:0 0 1rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).';
			}

			#login form label {
				text-shadow:0 0 0.1rem '.get_contrast($this->options['colours']['formtext']['value']).';
			}

			#login form .input,
			#login form .forgetmenot label:before {
				border-color:#ffffff;
				background-color:#f8f8f8;
			}

			#login form .forgetmenot input[type="checkbox"]:checked + label:before {
				color:#111111;
				text-shadow:0.1rem 0.1rem 0.1rem #999999;
			}

			#login form .input:focus,
			#login form input[type="checkbox"]:focus {
				background-color:#fcfcfc;
				border-color:'.$this->options['colours']['base']['value'].';

				-webkit-box-shadow: 0;
				-moz-box-shadow: 0;
				box-shadow:	0 0.1rem 0.4rem '.change_hsl($this->options['colours']['base']['value'], 1, 1.4, 1).' inset,
							0.1rem 0.4rem 0.4rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 1.2).';
			}

			#login form .submit input[type="submit"] {
				background-color:#f8f8f8;
				text-shadow:0.1rem 0.1rem 0.1rem #ffffff;
				color:'.change_hsl($this->options['colours']['formtext']['value'], 1, 1, 0.8).';
				border-color:'.$this->options['colours']['formback']['value'].' '.$this->options['colours']['base']['value'].' '.$this->options['colours']['base']['value'].' '.$this->options['colours']['formback']['value'].'; 
			}

			#login form .submit input[type="submit"]:focus,
			#login form .submit input[type="submit"]:hover {
				background-color:'.change_hsl($this->options['colours']['base']['value'], 1, 1, 1.5).';
				color:'.get_contrast($this->options['colours']['base']['value']).';
				border-color:'.$this->options['colours']['base']['value'].' '.$this->options['colours']['formback']['value'].' '.$this->options['colours']['formback']['value'].' '.$this->options['colours']['base']['value'].'; 
				text-shadow:0.1rem 0.1rem 0.1rem '.$this->options['colours']['base']['value'].';

				-webkit-box-shadow:0 0.1rem 0.4rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).' inset;
				-moz-box-shadow:0 0.1rem 0.4rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).' inset;
				box-shadow:0 0.1rem 0.4rem '.change_hsl($this->options['colours']['base']['value'], 1, 1, 0.5).' inset;
			}

			#nav {
				color:'.$this->options['colours']['links']['value'].';
			}
			#nav a {
				color:'.$this->options['colours']['links']['value'].';
				text-shadow:2px 2px 0 '.$this->options['colours']['base']['value'].',
							2px 0 0 '.$this->options['colours']['base']['value'].',
							2px -2px 0 '.$this->options['colours']['base']['value'].',
							0 2px 0 '.$this->options['colours']['base']['value'].',
							0 -2px 0 '.$this->options['colours']['base']['value'].',
							-2px 2px 0 '.$this->options['colours']['base']['value'].',
							-2px 0 0 '.$this->options['colours']['base']['value'].',
							-2px -2px 0 '.$this->options['colours']['base']['value'].'
							;
			}
			#nav a:hover:after {
				border-color:'.$this->options['colours']['links']['value'].';
			}

		';
		
		wp_add_inline_style('plain-custom-login-style', $custom_css);
		wp_enqueue_style('plain-custom-login-style');


		/*
		$test = $this->options['colours']['msgback']['value'];
		echo '<div style="background-color:'.$test.'">'.$test.'</div>';
		for ($i = 0.1; $i < 3; $i+= 0.05) {
			$new = change_hsl($test, $i,1,1);
			echo '<div style="display:inline-block;width:33%;background-color:'.$new.'">'.$new.'</div>';
			$new = change_hsl($test, 1,$i,1);
			echo '<div style="display:inline-block;width:33%;background-color:'.$new.'">'.$new.'</div>';
			$new = change_hsl($test, 1,1,$i);
			echo '<div style="display:inline-block;width:33%;background-color:'.$new.'">'.$new.'</div>';
		}
		*/

	}



	public function custom_login_header_url($url) {
		return get_bloginfo('url'); // rather than http://wordpress.org
	}
	public function custom_login_header_text($message) {
		return get_bloginfo('title'); // rather than Powered by WP
	}



	/******************************************************************************************************************************************************************/

	public function settings_init() {
		if (! ($this->options = get_option('PlainCustomLoginPluginOptions'))) {
			add_option('PlainCustomLoginPluginOptions', $this->make_default_selections(), null, false);
		}

		register_setting('PlainCustomLoginPluginOptions', 'PlainCustomLoginPluginOptions', array($this, 'validate_settings'));
		add_settings_section('PlainCustomLoginSettings', __('Colours', 'PlainCustomLogin'), array($this, 'colour_settings_form'), 'PlainCustomLoginPlugin');
	}

	public function add_settings_page() {
		add_options_page(__('Plain Custom Login Page Options', 'PlainCustomLogin'), __('Plain Custom Login', 'PlainCustomLogin'), 'manage_options', __CLASS__.'/settings.php', array($this, 'display_settings_page'));
	}

	public function display_settings_page() {
		echo '<div class="wrap"><h2>'.__('Plain Custom Login Page Options', 'PlainCustomLogin').'</h2>'
			.'<form action="options.php" method="post">';
				settings_fields('PlainCustomLoginPluginOptions');
		echo '<table class="form-table"><tbody>';
				do_settings_sections('PlainCustomLoginPlugin');
		echo '</tbody></table>';
				submit_button();
		echo '</form></div>';
	}

	public function colour_settings_form() {
		$output = '';

		foreach ($this->options['colours'] as $colour => $data) {
			$output.= '<tr><th scope="row">'.$data['name'].'</th><td><fieldset>'
					. '<input type="text" class="color-field" size="5" id="PlainCustomLoginPluginOptions[colours]['.$colour.']"'
					. ' name="PlainCustomLoginPluginOptions[colours]['.$colour.']" value="'.$data['value'].'">'
					. '</fieldset></td></tr>';
		}

		echo $output;
	}


	public function validate_settings($input) {
		$this->options = $this->make_default_selections();

		if (isset($input['colours']) AND is_array($input['colours'])) {
			foreach ($input['colours'] as $colour => $value) {
				if ($this->is_valid_colour($value)) $this->options['colours'][$colour]['value'] = $value;
			}
		}

		return $this->options;
	}

	private function is_valid_colour($value) {
		return preg_match('/^#(?:[0-9a-f]{3}){1,2}$/i', $value);
	}
	
	private function make_default_selections() {
		return array(
			'colours' => array(
				'base'		=> array('name' => __('Background', 'PlainCustomLogin'), 'value' => '#b43c38'),
				'title'		=> array('name' => __('Main Title', 'PlainCustomLogin'), 'value' => '#ffffff'),
				'msgback'	=> array('name' => __('Message Background', 'PlainCustomLogin'), 'value' => '#c29ec4'),
				'msgtext'	=> array('name' => __('Message Text', 'PlainCustomLogin'), 'value' => '#111111'),
				'errback'	=> array('name' => __('Error Background', 'PlainCustomLogin'), 'value' => '#e99e9d'),
				'errtext'	=> array('name' => __('Error Text', 'PlainCustomLogin'), 'value' => '#111111'),
				'formback'	=> array('name' => __('Form Background', 'PlainCustomLogin'), 'value' => '#ffffff'),
				'formtext'	=> array('name' => __('Form Text', 'PlainCustomLogin'), 'value' => '#555555'),
				'links'		=> array('name' => __('Links', 'PlainCustomLogin'), 'value' => '#ffffff'),
			),
		);
	}

}

$PlainCustomLogin = new PlainCustomLogin();





// what foreground colour should be used on any given background choice - http://24ways.org/2010/calculating-color-contrast/
if (! function_exists('get_contrast')) {
	function get_contrast($hexcolor) {
		$hexcolor = str_replace('#', '', $hexcolor);
		$r = hexdec(substr($hexcolor,0,2));
		$g = hexdec(substr($hexcolor,2,2));
		$b = hexdec(substr($hexcolor,4,2));
		$yiq = (($r*299)+($g*587)+($b*114))/1000;
		return ($yiq >= 128) ? 'black' : 'white';
	}
}

if (! function_exists('transparent_color')) {
	function transparent_color($hexcolor, $opacity = 0.5) {
		$hexcolor = str_replace('#', '', $hexcolor);
		$r = hexdec(substr($hexcolor,0,2));
		$g = hexdec(substr($hexcolor,2,2));
		$b = hexdec(substr($hexcolor,4,2));
		return "rgba({$r},{$g},{$b},{$opacity})";
	}
}

if (! function_exists('change_hsl')) {
	function change_hsl($hexcolor, $hmod = 1, $smod = 1, $lmod = 1) {
		$hexcolor = hexdec(str_replace('#', '', $hexcolor));
		$r = 0xFF & ($hexcolor >> 0x10); $g = 0xFF & ($hexcolor >> 0x8); $b = 0xFF & $hexcolor;

		list($h, $s, $l) = RGBtoHSL($r,$g,$b);
		$h = $h * $hmod; $s = $s * $smod; $l = $l * $lmod;
		list($r, $g, $b) = HSLtoRGB($h,$s,$l);
		
		$r = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
		$g = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
		$b = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
		return "#{$r}{$g}{$b}";
	}



	// converted http://stackoverflow.com/questions/2353211/hsl-to-rgb-color-conversion top answer to PHP
	function RGBtoHSL($r,$g,$b) {
		$r = $r / 255; $g = $g / 255; $b = $b / 255;
		$max = max($r,$g,$b); $min = min($r,$g,$b);
		$h = $s = $l = ($max + $min) / 2;
		if ($max == $min) {
			$h = $s = 0; // achromatic
		} else {
			$d = $max - $min;
			$s = ($l > 0.5) ? $d / (2 - $max - $min) : $d / ($max + $min);
			switch ($max) {
				case $r: $h = ($g - $b) / $d + (($g < $b) ? 6 : 0); break;
				case $g: $h = ($b - $r) / $d + 2; break;
				case $b: $h = ($r - $g) / $d + 4; break;
			}
			$h = $h / 6;
		}
		return array($h, $s, $l);
	}

	function hue2rgb($p, $q, $t) {
		if ($t < 0) $t += 1; elseif ($t > 1) $t -= 1;

		if ($t < 1/6) {
			$value = $p + ($q - $p) * 6 * $t;
		} elseif ($t < 1/2) {
			$value = $q;
		} elseif ($t < 2/3) {
			$value = $p + ($q - $p) * (2/3 - $t) * 6;
		} else {
			$value = $p;
		}

		return max(0, min(1, $value));
	}

	function HSLtoRGB($h, $s, $l) {
		if ($s == 0) {
			$r = $g = $b = $l; // achromatic
		} else {
			$q = ($l < 0.5) ? $l * (1 + $s) : $l + $s - $l * $s;
			$p = 2 * $l - $q;
			$r = hue2rgb($p, $q, $h + 1/3);
			$g = hue2rgb($p, $q, $h);
			$b = hue2rgb($p, $q, $h - 1/3);
		}
		return array(round($r * 255), round($g * 255), round($b * 255));
	}

}