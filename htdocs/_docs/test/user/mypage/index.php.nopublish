
<table class="def" style="width:100%;">
	<tr>
		<th>アカウント名</th>
		<td><?php print t::h( $px->user()->get_login_user_account() ); ?></td>
	</tr>
	<tr>
		<th>お名前</th>
		<td><?php print t::h( $px->user()->get_login_user_name() ); ?></td>
	</tr>
	<tr>
		<th>メールアドレス</th>
		<td><?php print t::h( $px->user()->get_login_user_email() ); ?></td>
	</tr>
	<tr>
		<th>最後にパスワードを変更した日時</th>
		<td><?php print t::h( date( 'Y年m月d日 H時i分', $this->px->dbh()->datetime2int($px->user()->get_login_user_set_pw_date()) ) ); ?></td>
	</tr>
</table>

