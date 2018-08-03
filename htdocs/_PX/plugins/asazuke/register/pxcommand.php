<?php
$this->load_px_class('/bases/pxcommand.php');

/**
 * PX Plugin "asazuke"
 */
class pxplugin_asazuke_register_pxcommand extends px_bases_pxcommand{

	/**
	 * コンストラクタ
	 * @param $command = PXコマンド配列
	 * @param $px = PxFWコアオブジェクト
	 */
	public function __construct( $command , $px ){
		parent::__construct( $command , $px );
		$this->homepage();
	}

	/**
	 * ホームページを表示する。
	 */
	private function homepage(){
		$command = $this->get_command();


		#--------------------------------------
		#    PicklesCrawler コンフィグを生成
		$className = $this->px->load_px_plugin_class( '/asazuke/config.php' );
		$pcconf = new $className( $this->px );

		#--------------------------------------
		#    設定

		# クローラのページIDを設定。
		# ここでは、ページID crawlctrl を指定する。
		// $pcconf->pid = array(
		//     'crawlctrl'=>'crawlctrl',
		// );

		# ASAZUKEに付与するホームディレクトリを設定。
		# RAMデータディレクトリ内に専用の領域を付与している。
		if( !$pcconf->set_home_dir( $this->px->get_conf('paths.px_dir').'_sys/ramdata/plugins/asazuke' ) ){
			$src = '';
			$src .= '<p class="error">ホームディレクトリ 「'.t::h($this->px->get_conf('paths.px_dir').'_sys/ramdata/plugins/asazuke').'」 を設定できませんでした。</p>'."\n";
			print $this->html_template($src);
			exit;
		}

		#    / 設定
		#--------------------------------------

		$cmd = $this->pxcommand_name;
		array_shift($cmd);// "plugins" をトル
		array_shift($cmd);// "asazuke" をトル

		if( @$cmd[0] == 'run' ){
			// クロールを実行する
			$obj = &$pcconf->factory_crawlctrl($cmd);
			print $obj->start();
			exit;
		}

		// 管理画面を表示
		$obj = &$pcconf->factory_admin($cmd);

		$src = '';
		$src .= $obj->start();

		$this->set_title( $obj->get_page_title() );//タイトルをセットする

		print $this->html_template($src);
		exit;
	}

}

?>
