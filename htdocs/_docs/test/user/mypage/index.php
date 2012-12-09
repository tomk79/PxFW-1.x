
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
</table>

