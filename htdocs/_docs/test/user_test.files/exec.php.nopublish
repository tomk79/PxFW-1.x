<?php
class cont_exec{
	private $px;

	/**
	 * コンストラクタ
	 */
	public function __construct( $px ){
		$this->px = $px;
	}

	/**
	 * コンテンツを実行
	 */
	public function execute(){
		switch( $this->px->req()->get_param('page') ){
			case 'add_user':
				return $this->page_add_user(); break;
			case 'get_user_info':
				return $this->page_get_user_info(); break;
			case 'login_test':
				return $this->page_login_test(); break;
			case 'logout_test':
				return $this->page_logout_test(); break;
			default:
				break;
		}
		return $this->page_default();
	}

	/**
	 * $dao_userを生成する
	 */
	private function &factory_dao_user(){
		$class_name_dao_user = $this->px->load_px_class( '/daos/user.php' );
		if( !$class_name_dao_user ){
			return false;
		}
		$dao_user = new $class_name_dao_user( $this->px );
		return $dao_user;
	}

	/**
	 * モード：デフォルト
	 */
	private function page_default(){
		$rtn = '';
		$rtn .= '<ol>'."\n";
		$rtn .= '	<li>はじめに ?PX=config を実行し、メインコンフィグの dbms の設定内容が正しいか確認します。</li>'."\n";
		$rtn .= '	<li>?PX=initialize.run を実行してユーザーテーブルを作成してください。</li>'."\n";
		$rtn .= '</ol>'."\n";
		$rtn .= '<ul>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?page=add_user' ).'">ユーザーを追加する</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?page=get_user_info' ).'">ユーザー情報を閲覧する</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?page=login_test' ).'">ログインテスト</a></li>'."\n";
		$rtn .= '	<li><a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ).'?page=logout_test' ).'">ログアウトテスト</a></li>'."\n";
		$rtn .= '</ul>'."\n";

		return $rtn;
	}

	/**
	 * モード：ユーザー追加テスト
	 */
	private function page_add_user(){
		switch( $this->px->req()->get_param('mode') ){
			case 'execute':
				return $this->page_add_user_execute();
				break;
			case 'complete':
				return $this->page_add_user_complete();
				break;
		}
		return $this->page_add_user_input();
	}
	private function page_add_user_input(){
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

		$rtn .= '<p>'."\n";
		$rtn .= '	現在のユーザー数: '.t::h($dao_user->get_user_count()).'<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<form action="?" method="post">'."\n";
		$rtn .= '<dl>';
		$rtn .= '	<dt>user_account</dt>';
		$rtn .= '		<dd><input type="text" name="user_account" value="'.t::h( 'testuser'.time() ).'" /></dd>';
		$rtn .= '	<dt>user_pw</dt>';
		$rtn .= '		<dd><input type="text" name="user_pw" value="'.t::h( 'testuser' ).'" /></dd>';
		$rtn .= '	<dt>user_name</dt>';
		$rtn .= '		<dd><input type="text" name="user_name" value="'.t::h( 'テストユーザー['.time().']' ).'" /></dd>';
		$rtn .= '	<dt>user_email</dt>';
		$rtn .= '		<dd><input type="text" name="user_email" value="'.t::h( 'testuser'.time().'@example.com' ).'" /></dd>';
		$rtn .= '</dl>';
		$rtn .= '<div>';
		$rtn .= '<input type="hidden" name="page" value="'.t::h($this->px->req()->get_param('page')).'" />';
		$rtn .= '<input type="hidden" name="mode" value="execute" />';
		$rtn .= '</div>'."\n";
		$rtn .= '<p class="center"><input type="submit" value="ユーザーを追加する" /></p>';
		$rtn .= '</form>'."\n";

		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ) ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}
	private function page_add_user_execute(){
		$new_user_info = array(
			'user_account'=>$this->px->req()->get_param('user_account'),
			'user_pw'=>$this->px->req()->get_param('user_pw'),
			'user_name'=>$this->px->req()->get_param('user_name'),
			'user_email'=>$this->px->req()->get_param('user_email'),
		);
		$dao_user = $this->factory_dao_user();
		if( !$dao_user ){
			return '<p>DAOの生成に失敗しました。</p>';
		}

		$result = $dao_user->create_user( $new_user_info );
		if( !$result ){
			return '<p>ユーザーの追加に失敗しました。</p>';
		}

		return $this->px->redirect( '?page='.urlencode($this->px->req()->get_param('page')).'&mode=complete&insert_user_id='.urlencode($dao_user->get_last_insert_user_id()).'' );
	}
	private function page_add_user_complete(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー['.t::h($this->px->req()->get_param('insert_user_id')).']を追加しました。<br />'."\n";
		$rtn .= '</p>'."\n";
		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ) ).'">戻る</a>]</p>'."\n";
		return $rtn;
	}

	/**
	 * モード：ユーザー情報を閲覧する
	 */
	private function page_get_user_info(){
		$rtn = '';
		$rtn .= '<p>'."\n";
		$rtn .= '	ユーザー情報を閲覧します。<br />'."\n";
		$rtn .= '</p>'."\n";

		$rtn .= '<form action="'.$this->px->theme()->href( $this->px->req()->get_request_file_path() ).'" method="post">'."\n";
		$rtn .= '	<p><input type="text" name="id" value="'.t::h($this->px->req()->get_param('id')).'" /><input type="submit" value="送信" /></p>'."\n";
		$rtn .= '	<div><input type="hidden" name="page" value="'.t::h($this->px->req()->get_param('page')).'" /></div>'."\n";
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

		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ) ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//page_get_user_info()

	/**
	 * モード：ログインテスト
	 */
	private function page_login_test(){
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
		$rtn .= '	<div><input type="hidden" name="page" value="'.t::h($this->px->req()->get_param('page')).'" /></div>'."\n";
		$rtn .= '</form>'."\n";

		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ) ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//page_login_test()


	/**
	 * モード：ログアウトテスト
	 */
	private function page_logout_test(){
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

		$rtn .= '<p class="center">[<a href="'.t::h( $this->px->theme()->href( $this->px->req()->get_request_file_path() ) ).'">戻る</a>]</p>'."\n";

		return $rtn;
	}//page_login_test()

}

?>