<?php
class cont_exec{
	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( &$px ){
		$this->px = &$px;
	}

	/**
	 * コンテンツを実行
	 */
	public function execute(){
		switch( $this->px->req()->get_param('mode') ){
			case 'add_user':
				return $this->mode_add_user(); break;
			case 'get_user_info':
				return $this->mode_get_user_info(); break;
			case 'login_test':
				return $this->mode_login_test(); break;
			case 'logout_test':
				return $this->mode_logout_test(); break;
			default:
				break;
		}
		return $this->mode_default();
	}

	/**
	 * $dao_userを生成する
	 */
	private function &factory_dao_user(){
		$class_name_dao_user = $this->px->load_pxclass( '/daos/user.php' );
		if( !$class_name_dao_user ){
			return false;
		}
		$dao_user = new $class_name_dao_user( &$this->px );
		return $dao_user;
	}

	/**
	 * モード：デフォルト
	 */
	private function mode_default(){
		$rtn = '';
		$rtn .= '<ol>'."\n";
		$rtn .= '<li>はじめに ?PX=config を実行し、メインコンフィグの dbs の設定内容が正しいか確認します。</li><li>?PX=initialize を実行してユーザーテーブルを作成してください。</li>'."\n";
		$rtn .= '</ol>'."\n";
		$rtn .= '<ul>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?mode=add_user' ).'">ユーザーを追加する</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?mode=get_user_info' ).'">ユーザー情報を閲覧する</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?mode=login_test' ).'">ログインテスト</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?mode=logout_test' ).'">ログアウトテスト</a></li>'."\n";
		$rtn .= '</ul>'."\n";

		return $rtn;
	}

	/**
	 * モード：ユーザー追加テスト
	 */
	private function mode_add_user(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザーを追加します。<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<p>'."\n";
		$rtn .= '	/daos/user.php をロードします。<br />'."\n";
		$dao_user = $this->factory_dao_user();
		if( $dao_user ){
			$rtn .= '	/daos/user.php をロードしました。<br />'."\n";
		}else{
			$rtn .= '	/daos/user.php のロードに失敗しました。<br />'."\n";
		}
		$rtn .= '</p>'."\n";

		$rtn .= '<p>'."\n";
		$rtn .= '	$dao_user->create_user(); を実行し、次のユーザーを追加します。<br />'."\n";
		$rtn .= '</p>'."\n";
		$new_user_info = array(
			'user_account'=>'testuser'.time(),
			'user_pw'=>'testuser',
			'user_name'=>'テストユーザー['.time().']',
			'user_email'=>'testuser'.time().'@example.com',
		);
		ob_start();
		test::var_dump($new_user_info);
		$rtn .= ob_get_clean();

		$result = $dao_user->create_user( $new_user_info );

		$rtn .= '<p>'."\n";
		if( $result ){
			$rtn .= '	成功しました。<br />'."\n";
			$rtn .= '	追加されたユーザーのIDは '.t::h( $dao_user->get_last_insert_user_id() ).' です。<br />'."\n";
		}else{
			$rtn .= '	失敗しました。<br />'."\n";
		}
		$rtn .= '</p>'."\n";
		$rtn .= '<p>'."\n";
		$rtn .= '	現在のユーザー数: '.t::h($dao_user->get_user_count()).'<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<p class="center">[<a href="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}

	/**
	 * モード：ユーザー情報を閲覧する
	 */
	private function mode_get_user_info(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー情報を閲覧します。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<form action="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'" method="post">'."\n";
		$rtn .= '	<p><input type="text" name="id" value="'.t::h($this->px->req()->get_param('id')).'" /><input type="submit" value="送信" /></p>'."\n";
		$rtn .= '	<div><input type="hidden" name="mode" value="'.t::h($this->px->req()->get_param('mode')).'" /></div>'."\n";
		$rtn .= '</form>'."\n";

		if( !strlen($this->px->req()->get_param('id')) ){
			$rtn .= '<p>'."\n";
			$rtn .= '	ユーザーIDを入力して送信してください。<br />'."\n";
			$rtn .= '</p>'."\n";
		}else{
			$rtn .= '<p>'."\n";
			$rtn .= '	/daos/user.php をロードします。<br />'."\n";
			$dao_user = $this->factory_dao_user();
			if( $dao_user ){
				$rtn .= '	/daos/user.php をロードしました。<br />'."\n";
			}else{
				$rtn .= '	/daos/user.php のロードに失敗しました。<br />'."\n";
			}
			$rtn .= '</p>'."\n";
			$rtn .= '<p>'."\n";
			$rtn .= '	$dao_user->get_user_info(&quot;'.t::h($this->px->req()->get_param('id')).'&quot;) からユーザー情報を取得します。<br />'."\n";
			$rtn .= '</p>'."\n";
			$user_info = $dao_user->get_user_info($this->px->req()->get_param('id'));
			ob_start();
			test::var_dump($user_info);
			$rtn .= ob_get_clean();
		}

		$rtn .= '<p class="center">[<a href="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//mode_get_user_info()

	/**
	 * モード：ログインテスト
	 */
	private function mode_login_test(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	現在、ログインして'.($this->px->user()->is_login()?'います':'いません').'。<br />'."\n";
		$rtn .= '	ユーザーアカウント：'.t::h($this->px->user()->get_login_user_account()).'<br />'."\n";
		$rtn .= '	ユーザー名：'.t::h($this->px->user()->get_login_user_name()).'<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<form action="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'" method="post">'."\n";
		$rtn .= '	<p><input type="text" name="ID" value="'.t::h($this->px->req()->get_param('ID')).'" /><br /></p>'."\n";
		$rtn .= '	<p><input type="password" name="PW" value="" /><br /></p>'."\n";
		$rtn .= '	<p><input type="submit" value="送信" /></p>'."\n";
		$rtn .= '	<div><input type="hidden" name="mode" value="'.t::h($this->px->req()->get_param('mode')).'" /></div>'."\n";
		$rtn .= '</form>'."\n";

		$rtn .= '<p class="center">[<a href="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//mode_login_test()


	/**
	 * モード：ログアウトテスト
	 */
	private function mode_logout_test(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	現在、ログインして'.($this->px->user()->is_login()?'います':'いません').'。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<p>'."\n";
		$rtn .= '	ログアウト処理を実施します。<br />'."\n";
		$this->px->user()->logout();
		$rtn .= '</p>'."\n";

		$rtn .= '<p>'."\n";
		$rtn .= '	現在、ログインして'.($this->px->user()->is_login()?'います':'いません').'。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<p class="center">[<a href="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//mode_login_test()


}
?>