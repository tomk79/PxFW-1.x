<?php
$this->load_pxclass('/bases/pxcommand.php');

/**
 * PX Command: editを表示する
 **/
class px_pxcommands_edit extends px_bases_pxcommand{

	private $command = array();
	private $path_content_src = null;
	private $pageinfo = null;

	public function __construct( $command , &$px ){
		parent::__construct( $command , &$px );

		$this->command = $command;
		$this->pageinfo = $this->px->site()->get_current_page_info();

		$this->path_content_src = $this->px->dbh()->get_realpath( $_SERVER['DOCUMENT_ROOT'].$this->px->get_install_path().$this->pageinfo['content'] );

		switch( $command[1] ){
			case 'update':
				$this->execute_update();
				break;
			case 'result':
				$this->execute_result();
				break;
			default:
				$this->home();
				break;
		}
	}//__construct()

	/**
	 * ホーム画面を表示する。
	 */
	private function home(){

		$src = '';
		$src .= '<p>コンテンツファイル '.$this->px->dbh()->get_realpath( $this->px->get_install_path().$this->pageinfo['content'] ).' を編集します。</p>'."\n";

		$src .= '<form action="?PX='.t::h( implode('.',$this->command) ).'.update" method="post" onsubmit="return confirm(\'編集した内容でファイルを上書き保存します。よろしいですか？\');">'."\n";
		$src .= '<p><textarea name="SRC" style="width:100%; height:260px;">';
		$src .= t::h( $this->px->dbh()->file_get_contents( $this->path_content_src ) );
		$src .= '</textarea></p>'."\n";
		$src .= '<p class="center"><button>上書き保存する</button></p>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}

	/**
	 * Execute PX Command "edit".
	 */
	private function execute_update(){
		$SRC = $this->px->req()->get_param('SRC');

		//↓編集後のファイルを上書き
		if( !$this->px->dbh()->file_overwrite( $this->path_content_src , $SRC ) ){
			$src = '';
			$src .= '<p>コンテンツファイル '.$this->px->dbh()->get_realpath( $this->px->get_install_path().$this->pageinfo['content'] ).' の更新に失敗しました。</p>'."\n";

			$src .= '<form action="?PX='.t::h( $this->command[0] ).'" method="post">'."\n";
			$src .= '<p class="center"><button>戻る</button></p>'."\n";
			$src .= '</form>'."\n";
			print $this->html_template($src);
			exit;
		}

		$this->px->redirect( '?PX='.$this->command[0].'.result' );
		exit;
	}
	/**
	 * 上書き完了画面を表示する。
	 */
	private function execute_result(){

		$src = '';
		$src .= '<p>コンテンツファイル '.$this->px->dbh()->get_realpath( $this->px->get_install_path().$this->pageinfo['content'] ).' を更新しました。</p>'."\n";

		$src .= '<form action="?PX='.t::h( $this->command[0] ).'" method="post">'."\n";
		$src .= '<p class="center"><button>戻る</button></p>'."\n";
		$src .= '</form>'."\n";
		print $this->html_template($src);
		exit;
	}

}

?>