<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Command: pluginsを実行する
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class px_pxcommands_plugins extends px_bases_pxcommand{

	private $path_plugin_dir;

	/**
	 * コンストラクタ
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );

		$command = $this->get_command();

		$this->path_plugin_dir = $this->px->get_conf('paths.px_dir').'plugins/';

		if( strlen($command[1]) ){
			$this->execute();
			return;
		}
		$this->homepage();
		return;
	}//__construct()

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();

		$plugins_list = $this->px->dbh()->ls( $this->path_plugin_dir );
		foreach( $plugins_list as $tmp_key=>$tmp_plugin_name ){
			if( !is_file( $this->path_plugin_dir.$tmp_plugin_name.'/register/pxcommand.php' ) ){
				unset($plugins_list[$tmp_key]);
			}
		}

		$src = '';
		$src .= '<div class="unit">'."\n";
		if(!count($plugins_list)){
			$src .= '	<p>PX Commandを登録しているプラグインはありません。</p>'."\n";
		}else{
			$src .= '	<ul>'."\n";
			foreach( $plugins_list as $plugin_name){
				$src .= '	<li><a href="?PX='.t::h(urlencode(implode('.',$command))).'.'.t::h(urlencode($plugin_name)).'">'.t::h($plugin_name).'</a></li>'."\n";
			}
			$src .= '	</ul>'."\n";
		}
		$src .= '</div><!-- /.unit -->'."\n";

		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "plugins".
	 * @access private
	 * @return null
	 */
	private function execute(){
		$command = $this->get_command();
		$plugin_object = null;
		$tmp_plugin_name = $command[1];

		if( !is_dir($this->path_plugin_dir.$tmp_plugin_name) ){
			$src = '';
			$src .= '<div class="unit">'."\n";
			$src .= '	<p>プラグイン '.t::h($tmp_plugin_name).' はインストールされていません。</p>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
			print $this->html_template($src);
			exit;
		}

		if( !is_file( $this->path_plugin_dir.$tmp_plugin_name.'/register/pxcommand.php' ) ){
			$src = '';
			$src .= '<div class="unit">'."\n";
			$src .= '	<p>プラグイン '.t::h($tmp_plugin_name).' は、PX Command インターフェイスを持っていません。</p>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
			print $this->html_template($src);
			exit;
		}

		$tmp_class_name = $this->px->load_px_plugin_class($tmp_plugin_name.'/register/pxcommand.php');
		if(!$tmp_class_name){
			$src = '';
			$src .= '<div class="unit">'."\n";
			$src .= '	<p>プラグイン '.t::h($tmp_plugin_name).' の PX Command インターフェイスにエラーがあり、実行できませんでした。</p>'."\n";
			$src .= '</div><!-- /.unit -->'."\n";
			print $this->html_template($src);
			exit;
		}


		$plugin_object = new $tmp_class_name($command, $this->px);

		$src = '';
		$src .= '<div class="unit">'."\n";
		$src .= '	<p>プラグイン '.t::h($tmp_plugin_name).' は、実行されました。</p>'."\n";
		$src .= '</div><!-- /.unit -->'."\n";
		print $this->html_template($src);
		exit;
	}

}

?>