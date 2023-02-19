<?php
/*
Plugin Name: BlackCrystal Product Feed
Plugin URI: http://www.blackcrystal.net/project/woocommerce-product-xml/
Description: Plugin adds XML and CSV product feeds to your webshop (Woocommerce)
Version: 1.0
Author: Sergei Miami <miami@blackcrystal.net>
Author URI: http://www.blackcrystal.net
*/

if ( !defined( 'ABSPATH' ) ) exit;

class blackcrystal_product_feed
{

	private static $instance = null;

	public static function activate()
	{
		add_rewrite_rule('^product_feed/([\.a-zA-Z0-9-]+)$', 'index.php?blackcrystal_product_feed=$matches[1]', 'top');
		global $wp_rewrite; $wp_rewrite->flush_rules( false );
	}

	public static function init()
	{
		if( self::$instance == null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct()
	{
		load_plugin_textdomain('blackcrystal_product_feed');

		if ( is_admin() ) {
			add_action( 'admin_menu',   array( $this, 'admin_menu' ) );
		}
		else
		{
			add_filter( 'query_vars',   array( $this, 'query_vars' ));
			add_filter( 'request',      array( $this, 'request' ));
		}
	}

	public function query_vars( $vars )
	{
		$vars[] = 'blackcrystal_product_feed';
		return $vars;
	}


	public function request( $vars )
	{
		if ( ! isset ( $vars['blackcrystal_product_feed'] ) ) {
			return $vars;
		}

		$options = get_option( 'blackcrystal_product_feed' );
		if ( ! $options ) { $options = array(); }

		foreach ( $options as $feed ) {
			if (isset($feed['url'] ) && $feed['url'] == $vars['blackcrystal_product_feed']){
				$this->render_feed( $feed );
			}
		}

		// error 404
		include( get_query_template( '404' ) );
		exit;
	}

	public function render_feed($feed)
	{
    include_once dirname(__FILE__) . '/lib/Feed/Base.php';
    include_once dirname(__FILE__) . '/lib/Feed/' . $feed['type'] . '.php';
    $query = new WC_Product_Query([
      //'status' => 'public',
      //'type' => ['simple', 'variable'],
      'limit'   => -1,
      'return'  => 'objects',
      'visibility' => 'catalog',
      //'stock_status' => 'instock',
      //'orderby' => 'modified',
      //'order'   => 'ASC',
    ]);

    $class = $feed['type'];
    $object = new $class($feed);
    $object->render($query);
		exit;
	}

	public function admin_menu()
	{
		add_options_page(
			'BlackCrystal Product Feed', 
			'BlackCrystal Product Feed',
			'manage_options',
			'blackcrystal_product_feed', 
			array($this, 'options_page')
		);

		add_action( 'admin_init', array($this,'admin_init') );
	}

	public function options_page()
	{
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$options = get_option('blackcrystal_product_feed');
		if (!$options) $options = array();
		$options['new'] = array('name'=>'', 'type' => '');

		?>

		<div class="wrap">
		<h2>BlackCrystal Product Feed</h2>

		<p>Here you can create feeds with products for your customers.</p>

		<form method="post" action="options.php">

			<?php settings_fields( 'blackcrystal_product_feed' ); ?>

			<table class="form-table">
				<tr>
					<th>#</th>
					<th>Secret feed name</th>
					<th>Feed type</th>
					<th>Link to feed</th>
				</tr>

				<?php foreach ( $options as $k=>$v): ?>
					<tr valign="top">
						<th scope="row"><?php echo is_numeric($k) ? 'Feed&nbsp;#'.($k+1) : 'New feed'; ?></th>
						<td style="padding-left: 0;"><input type="text" name="blackcrystal_product_feed[<?= $k?>][name]" value="<?=$v['name']; ?>" placeholder="Feed name"/></td>
            <td style="padding-left: 0;">
              <select name="blackcrystal_product_feed[<?= $k?>][type]">
                <option value="">-- select --</option>
                <option value="Kaup24XML" <?= $v['type'] === 'Kaup24XML' ? 'selected':''?>>Kaup24 XML</option>
                <option value="FacebookCSV" <?= $v['type'] === 'FacebookCSV' ? 'selected':''?>>Facebook CSV</option>
              </select></td>
						<td style="padding-left: 0;"><?php if (isset($v['url']) && $v['url'] ) {
								$url = get_site_url().'/product_feed/'. $v['url'];
								echo "<a href='$url' target='preview'>$url</a>";
						} elseif (is_numeric($k)) { echo "<p style='line-height: 1'>Not active<br><small>Fill name and select feed type</small></p>"; }?></td>
					</tr>
				<?php endforeach; ?>

			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>

		</form>
		</div><?php
	}

	public function admin_init()
	{
		register_setting(  
			'blackcrystal_product_feed' , 
			'blackcrystal_product_feed', 
			array($this, 'pre_save')
		);
	}

	public function pre_save($options)
	{

		foreach ($options as $k=>$v)
		{

			// clean empty values
			if ( !strlen( $v['name'] . $v['type'])) {
				unset( $options[ $k ] );
				continue;
			}

			// activate good feeds and deactivate bad
			$url = preg_replace('/[^\.a-z0-9]+/','-', mb_strtolower($v['name']));
			if (  $v['type'] && $url )
				$options[$k]['url'] = $url;
			else
				unset($options[$k]['url']);
		}

		return array_values($options);
	}

}

// clean rewrite cache is expensive thing, let's do it once on activation of plugin
register_activation_hook( __FILE__, array( 'blackcrystal_product_feed', 'activate' ) );

// let's rock!
add_action( 'plugins_loaded', array('blackcrystal_product_feed', 'init') );