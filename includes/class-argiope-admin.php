<?php
if ( !class_exists('Argiope_Validator') )
	require(dirname(__FILE__).'/class-argiope-validator.php');

class Argiope_Admin {
	private static $instance;
	private static $options;

	const OPTION_KEY  = 'argiope_amoena';
	const OPTION_PAGE = 'argiope-amoena';

	private $plugin_basename;
	private $admin_hook, $admin_action;
    // 2019-11-01 fukushima update =====>
	private $regions = array(
		'us-east-1',
		'us-east-2',
		'us-west-1',
		'us-west-2',
		'ca-central-1',
		'eu-central-1',
		'eu-west-1',
		'eu-west-2',
		'eu-west-3',
		'eu-north-1',
		'ap-east-1',
		'ap-northeast-1',
		'ap-northeast-2',
		'ap-northeast-3',
		'ap-southeast-1',
		'ap-southeast-2',
		'ap-south-1',
		'me-south-1',
		'sa-east-1'
	);
    private $storage_classes = array(
        'STANDARD',
        'STANDARD_IA',
        'INTELLIGENT_TIERING',
        'ONEZONE_IA',
        'GLACIER',
        'DEEP_ARCHIVE',
        'RRS'
    );
    // <===== 2019-11-01 fukushima update    

	private function __construct() {}

	public static function get_instance() {
		if( !isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}

		return self::$instance;
	}

	public function init(){
		self::$options = $this->get_option();
		$this->plugin_basename = Argiope_Amoena::plugin_basename();
	}

	public function add_hook(){
		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('plugin_action_links', array($this, 'plugin_setting_links'), 10, 2 );
	}

	static public function option_keys(){
		return array(
			'access_key' => __('AWS Access Key',  Argiope_Amoena::TEXT_DOMAIN),
			'secret_key' => __('AWS Secret Key',  Argiope_Amoena::TEXT_DOMAIN),
			'region'     => __('AWS Region',  Argiope_Amoena::TEXT_DOMAIN),
			'bucket'     => __('S3 Bucket',  Argiope_Amoena::TEXT_DOMAIN),
			's3_url'     => __('S3 URL', Argiope_Amoena::TEXT_DOMAIN),
			'storage_class' => __('Storage Class', Argiope_Amoena::TEXT_DOMAIN),
			);
	}

	static public function get_option(){
		$options = get_option(self::OPTION_KEY);
		foreach (array_keys(self::option_keys()) as $key) {
			if (!isset($options[$key]) || is_wp_error($options[$key]))
				$options[$key] = '';
		}
		return $options;
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function admin_menu() {
		global $wp_version;

		$title = __('Argiope amoena', Argiope_Amoena::TEXT_DOMAIN);
		$this->admin_hook = add_options_page($title, $title, 'manage_options', self::OPTION_PAGE, array($this, 'options_page'));
		$this->admin_action = admin_url( apply_filters( 'argiope_amoena_admin_url', '/options-general.php') ) . '?page=' . self::OPTION_PAGE;
	}

	public function options_page(){
		$nonce_action  = 'update_options';
		$nonce_name    = '_wpnonce_update_options';

		$option_keys   = $this->option_keys();
		$option_keys   = apply_filters( 'argiope_amoena_option_keys', $option_keys );
		self::$options = $this->get_option();
		$title = __('Argiope amoena', Argiope_Amoena::TEXT_DOMAIN);

		$av = new Argiope_Validator('POST');
		$av->set_rules($nonce_name, 'required');

		// Update options
		if (!is_wp_error($av->input($nonce_name)) && check_admin_referer($nonce_action, $nonce_name)) {
			// Get posted options
			$fields = array_keys($option_keys);
			foreach ($fields as $field) {
				switch ($field) {
				case 'access_key':
				case 'secret_key':
					$av->set_rules($field, array('trim','esc_html','required'));
					break;
				default:
					$av->set_rules($field, array('trim','esc_html'));
					break;
				}
			}
			$options = $av->input($fields);
			$err_message = '';
			foreach ($option_keys as $key => $field) {
				if (is_wp_error($options[$key])) {
					$error_data = $options[$key];
					$err = '';
					foreach ($error_data->errors as $errors) {
						foreach ($errors as $error) {
							$err .= (!empty($err) ? '<br />' : '') . __('Error! : ', Argiope_Amoena::TEXT_DOMAIN);
							$err .= sprintf(
								__(str_replace($key, '%s', $error), Argiope_Amoena::TEXT_DOMAIN),
								$field
								);
						}
					}
					$err_message .= (!empty($err_message) ? '<br />' : '') . $err;
				}
				if (!isset($options[$key]) || is_wp_error($options[$key]))
					$options[$key] = '';
			}
			if (empty($options['s3_url']) && !empty($options['bucket'])) {
				$options['s3_url'] = sprintf(
					'http://%1$s.s3-website-%2$s.amazonaws.com',
					strtolower($options['bucket']),
					strtolower(str_replace('_','-',$options['region']))
					);
			}
			if ( !empty($options['s3_url']) && !preg_match('#^https?://#i', $options['s3_url']) ) {
				$options['s3_url'] = 'http://' . preg_replace('#^//?#', '', $options['s3_url']);
			}
			$options['s3_url'] = untrailingslashit($options['s3_url']);
			if (Argiope_Amoena::DEBUG_MODE && function_exists('dbgx_trace_var')) {
				dbgx_trace_var($options);
			}

			// Update options
			if (self::$options !== $options) {
				update_option(self::OPTION_KEY, $options);
				if (self::$options['s3_url'] !== $options['s3_url']) {
					global $wpdb;
					$sql = $wpdb->prepare(
						"delete from {$wpdb->postmeta} where meta_key in (%s, %s)",
						Argiope_Amoena::META_KEY,
						Argiope_Amoena::META_KEY.'-replace'
						);
					$wpdb->query($sql);
				}
				printf(
					'<div id="message" class="updated fade"><p><strong>%s</strong></p></div>'."\n",
					empty($err_message) ? __('Done!', Argiope_Amoena::TEXT_DOMAIN) : $err_message
					);
				self::$options = $options;
			}
			unset($options);
		}

		// Get S3 Object
		$s3 = Argiope_S3_Helper::get_instance();
		$s3->init(
			isset(self::$options['access_key']) ? self::$options['access_key'] : null,
			isset(self::$options['secret_key']) ? self::$options['secret_key'] : null,
			isset(self::$options['region']) ? self::$options['region'] : null
			);
		$regions = $this->regions;
		$buckets = false;
		if ($s3) {
			$buckets = $s3->list_buckets();
		}
		if (!$buckets) {
			unset($option_keys['bucket']);
			unset($option_keys['s3_url']);
		}
		$storage_classes = $this->storage_classes;

?>
		<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo esc_html( $title ); ?></h2>
		<form method="post" action="<?php echo $this->admin_action;?>">
		<?php echo wp_nonce_field($nonce_action, $nonce_name, true, false) . "\n"; ?>
		<table class="wp-list-table fixed"><tbody>
		<?php
			foreach ($option_keys as $field => $label) { 
				$this->input_field($field, $label, array('regions' => $regions, 'buckets' => $buckets, 'storage_classes' => $storage_classes)); 
			} 
		?>
		</tbody></table>
		<?php submit_button(); ?>
		</form>
		</div>
<?php
	}

	private function input_field($field, $label, $args = array()){
		extract($args);

		$label = sprintf('<th><label for="%1$s">%2$s</label></th>'."\n", $field, $label);

		$input_field = sprintf('<td><input type="text" name="%1$s" value="%2$s" id="%1$s" size=100 /></td>'."\n", $field, esc_attr(self::$options[$field]));
		switch ($field) {
		case 'region':
			if ($regions && count($regions) > 0) {
				$input_field  = sprintf('<td><select name="%1$s">', $field);
				$input_field .= '<option value=""></option>';
				foreach ($regions as $region) {
					$input_field .= sprintf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr($region),
						$region == self::$options[$field] ? ' selected' : '',
						__($region, Argiope_Amoena::TEXT_DOMAIN));
				}
				$input_field .= '</select></td>';
			}
			break;
		case 'bucket':
			if ($buckets && count($buckets) > 0) {
				$input_field  = sprintf('<td><select name="%1$s">', $field);
				$input_field .= '<option value=""></option>';
				foreach ($buckets as $bucket) {
					$input_field .= sprintf(
						'<option value="%1$s"%2$s>%1$s</option>',
						esc_attr($bucket['Name']),
						$bucket['Name'] == self::$options[$field] ? ' selected' : '');
				}
				$input_field .= '</select></td>';
			}
			break;
		case 'storage_class':
			if ($storage_classes && count($storage_classes) > 0) {
				$input_field  = sprintf('<td><select name="%1$s">', $field);
				$input_field .= '<option value=""></option>';
				foreach ($storage_classes as $storage_class) {
					$input_field .= sprintf(
						'<option value="%1$s"%2$s>%1$s</option>',
						esc_attr($storage_class),
						$storage_class == self::$options[$field] ? ' selected' : '');
				}
				$input_field .= '</select></td>';
			}
			break;
		}

		echo "<tr>\n{$label}{$input_field}</tr>\n";
	}

	//**************************************************************************************
	// Add setting link
	//**************************************************************************************
	public function plugin_setting_links($links, $file) {
		if ($file === $this->plugin_basename) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}
}
