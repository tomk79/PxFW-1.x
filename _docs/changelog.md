

# PxFW(Pickles Framework) 更新履歴


## PxFW 1.0.4 (2014/\*\*/\*\*)

- コンフィグ項目 system.ssi_method に、選択肢 emulate_ssi を追加。
- $site->get_shoulder_menu() を追加。
- $site->get_global_menu() が生成するリストが、list_flgの影響を受けるように修正。
- $theme->mk_link() の label オプションに、コールバック関数を指定できるようになった。
- $theme->mk_link() の label オプションを指定した場合に、no_escape オプションがデフォルトで true になるように変更。
- $px->get_extensions_list() が、プラグインが定義するextensionも検索するようになった。
- composer に対応。./_PX/libs/composer/ に展開したライブラリを自動ロードするようになった。
- system.allow_pxcommands が無効に設定されている場合でも、コマンドラインからは PX Command を実行できるようになった。
- .htaccess に書かれていたPHPのエラー出力に関する設定をコメントアウト。環境に従うようになった。
- プラグインの info クラスの仕様にメソッド config_define() を追加。


## PxFW 1.0.3 (2014/4/29)

- コンフィグ項目 system.ssi_method を追加。$px->ssi() での多重インクルードができない問題を回避する方法として。
- コンフィグ項目 system.output_encoding に指定する Shift_JIS の表記ゆれに対応。SJIS、Shift_JIS に加え、新たに Shift-JIS、x-sjis を指定した時にも SJIS-win で変換するようになった。
- パブリッシュツールのアクセスだった場合に、PHPのエラーを標準出力しないようにした。
- ./_PX/_sys/ 直下に、applock, caches, publish, ramdata がない場合に、ディレクトリを生成するようにした。
- $site->set_current_page_info() を追加。$site->get_current_page_info() に倣って。
- PX=pageinfo の画面で、カレントページのQRコードを表示するようになった。


## PxFW 1.0.2 (2014/3/22)

- ダイナミックパスの適用範囲へのアクセス時、物理ファイルが存在する場合には優先して出力するように修正。
- 外部のコンフィグファイルをインクルードした場合に、インクルード先のファイルに設定のない幾つかの項目がデフォルト値で上書きされる不具合を修正。


## PxFW 1.0.1 (2014/2/26)

- $theme->href() に ダイナミックパスを持つページのページIDを渡したとき、バインドされずに返却される不具合を修正。
- ファイルが存在せず、ページにアサインされていないパスにアクセスした場合に、404 Not found 画面を表示するように修正。
- ファイル名 index.html の単体パブリッシュができない不具合を修正。
- サイトマップCSV更新の際のキャッシュ生成ロジックを高速化。
- その他いくつかの細かい修正。


## PxFW 1.0.0 (2014/1/2)

- $px->realpath_ramdata_dir() を追加。
- $px->realpath_theme_ramdata_dir() を追加。
- $px->realpath_plugin_ramdata_dir() を追加。
- $px->realpath_private_cache_dir() を追加。
- $px->realpath_theme_private_cache_dir() を追加。
- $px->realpath_plugin_private_cache_dir() を追加。
- $site->get_global_menu() を追加。
- コンフィグ項目 system.public_cache_dir を追加。
- デフォルトテーマのデザインを変更。
- 設定ファイル読み込み時に、設定されていないいくつかの項目にデフォルトの値を適用するようにした。
- FESS 1.1.1 に更新。
- パブリッシュログファイルのパーミッションをコンフィグの設定値にセットするようにした。
- システム要件にPHPのバージョンを変更した。PHP 5.3以上とした。


## PxFW 1.0.0b11 (2013/11/30)

- $px->get_plugin_list() を追加。
- コンフィグ項目に、system.file_default_permission, system.dir_default_permission を追加。
- $site->get_page_info() の引数 $path にパラメータやアンカーが付いている場合の処理を修正。
- $theme->href() の引数 $linkto にパラメータやアンカーが付いている場合の処理を修正。
- パブリッシュロックの有効期限を、パブリッシュ開始時点から30分ではなく、最後にロックファイルのタイムスタンプが更新されてから30分に変更。
- $dbh->ls() の返却値がソートされるようになった。
- パブリッシュ中に残件数を表示するようにした。
- その他いくつかの不具合の修正。


## PxFW 1.0.0b10 (2013/10/31)

- コンフィグに拡張子別のパブリッシュ方法を設定する publish_extensions を追加。
- コンフィグに省略するファイル名を設定する project.directory_index を追加。
- コンフィグにインクルード機能 _include を追加。
- プラグインに funcs API を追加。単機能制御のAPIを集約する箱として定義。パブリッシュ後のSSIタグの形式を生成する ssi_static_tag() をここに追加した。
- $px->get_directory_index(), $px->get_directory_index_preg_pattern(), $px->get_directory_index_primary() を追加。
- extension *.md で、PHPを書けるようにした。PxFW のAPIにアクセスできるようになった。
- 動的に生成したページをパスで検索した場合に見つけられないことがある不具合を修正。
- system.output_encoding の設定で文字コード変換するときに、metaタグの体裁を維持するように修正。
- サイトマップで、トップページに当たるページにIDが振られている場合、これを無視して空白文字列に置き換えるようにした。
- プラグインに extensions API を追加。コンテンツの拡張子別の処理をプラグインから制御できるようにした。
- $theme->mk_link() で、リンク先ページの layout が popup だった場合に別窓のリンクを生成するが、 popup で始まるもの全てで別窓にするように挙動を変更した。


## PxFW 1.0.0b9 (2013/9/28)

- PX=themes で、ディレクトリ以外と、ドットで始まる名前をテーマとして認識しないように修正。
- テーマのレイアウトに top(トップページ用レイアウト) を追加。
- $theme->mk_link() のオプションに class を追加。
- ドキュメントモジュールを、FESS という別プロジェクトを立てて分離独立した。
- $site->set_page_info() で、カレントページに layout をセットした場合に、指定したレイアウトが反映されるようになった。
- PxXMLDomParser.php 1.0.4 に更新。
- PXコマンドのUIを改善した。
- その他、いくつかの不具合を修正。


## PxFW 1.0.0b8 (2013/8/29)

- コンフィグ項目に colors.main を追加。
- PXコマンドに themes, api.ls.px, api.dlfile.px, api.delete.px を追加。
- PXコマンドがエラーを返却する場合に、合わせてエラー内容を返すようにした。
- サイトマップCSVのはじめの行が、すべてアスタリスクで始まっている場合、定義行として解釈するようになった。定義行が検出されない場合は、デフォルトとして configs/sitemap_definition.csv に設定された定義に従う。
- ドキュメントモジュールに .notes を追加。インラインで使える。
- $px->path_theme_files() でテーマのリソースファイルにアクセスしている場合に、リソースが正しくパブリッシュされない不具合を修正。$px->path_theme_files() が、$px->add_relatedlink() に対して誤ったパスを渡していた。


## PxFW 1.0.0b7 (2013/7/20)

- プラグインオブジェクトを生成するタイミングを、"最初に一括して" ではなく、"初めて呼び出されたとき" に変更。
- $px->get_theme_resource_dir() を $px->path_theme_files() に変更。
- $px->get_theme_resource_dir_realpath() を $px->realpath_theme_files() に変更。
- $px->realpath_theme_files() の引数に、リソースのパスを指定できるようにした。
- $px->path_files(), $px->realpath_files() の引数を、リソースのパスに変更。
- $px->path_files_cache(), $px->realpath_files_cache() を追加。
- ドキュメントモジュール仕様を更新。
	- 新たに、ボックスモジュールのカテゴリを追加。
	- .must を追加。.form_elements から独立。
	- ul.horizontal を追加。
- テーマリソースキャッシュの仕組みを実装した。
- $user->get_onetime_hash(), $user->use_onetime_hash() を追加。二重送信防止用の機能として。
- $site->is_page_in_breadcrumb() を追加。
- $site->get_page_id_by_path() を追加。
- $site->get_page_path_by_id() を追加。
- コンフィグにコマンドのパスを設定する commands を追加。
- コンフィグの publish.paths_ignore に、物理的に存在しないがサイトマップに登録しているパスを設定している場合に、除外されない不具合を修正。
- コンフィグの project.path_top に "/" 以外のパスを設定している場合に、トップページではない "/" がサイトマップ上に存在すると、ただしい階層解釈ができなくなる不具合を修正。


## PxFW 1.0.0b6 (2013/6/8)

- $req->get_all_params() を追加。パラメータをすべて返す。
- プラグインに info API を追加。バージョン情報を返すインターフェイスを定義した。
- パブリッシュ時に、拡張子 *.php のファイルは、スキップせず copy で出力されるように変更した。
- パブリッシュ時に、拡張子 *.nopublish のファイルをスキップするように変更した。
- サイトマップのツリー構造をキャッシュするようにした。 $site->get_children(), $site->get_bros() などの検索パフォーマンスが向上した。
- サイトマップのpath欄に書いた外部リンク、アンカーリンク、JavaScriptコードで、index.html の省略・自動付加の処理を行わないように変更。
- ユーザーのパスワード保存時のハッシュアルゴリズムを、md5 から sha1 に変更。
- t::data2jssrc() の文字列型のエスケープ処理に存在した不具合を修正。
- 巨大なサイトマップを扱う場合に、キャッシュ生成の処理の途中でタイムアウトが起きうる問題を修正。
- フィルタを無効にした場合のサイトマップのページツリーキャッシュが正しく生成されていない不具合を修正。
- $dbh->rmdir_all() を $dir->rm() に改名。
- $theme->mk_link() のオプションに current を追加。true で強制的にカレント、false で強制的にカレントなし、null で自動判別。


## PxFW 1.0.0b5 (2013/4/30)

- コンフィグ項目 publish.paths_ignore で、ワイルドカードを使用できるようになった。
- コンフィグ項目 publish.paths_ignore で、改行のほか、カンマとセミコロンで区切れるようになった。
- サイトマップのパスに、javascript:, http://, # で始まるパスを書けるようになった。
- プラグインの publish API に、before_execute(), after_execute(), after_copying() を追加。
- $dbh->rmdir() を追加。普通に空っぽディレクトリを削除するメソッド。
- ドキュメント更新。
- その他の細かい修正。


## PxFW 1.0.0b4 (2013/3/31)

- プラグインに publish API を追加。パブリッシュ時にファイルを加工する。
- PX=api.get.version を追加。
- $site->get_next(), $site->get_prev(), $site->get_bros_next(), $site->get_bros_prev() を追加。
- $site->get_bros(), $site->get_children() に、オプション filter(bool,デフォルト true) を追加。ここに false を渡すと、無条件に全部のページを対象として検索する。
- パブリッシュ時に、環境によって /index.html しかパブリッシュされない場合がある不具合を修正。
- ドキュメントモジュール設計を整理。
	- .topic_box, .aside_box を追加。
	- [unit].attention を [parts].attention_box に変更。
	- .anchor_links, .more_links, .page_top を追加。
	- .float_image を .float_media に名称変更。
	- パーセント幅指定の命名規則を、w50per から 1of2 の形式に変更。
	- モジュール命名規則の慣例として、*_box, *-last を追記。


## PxFW 1.0.0b3 (2013/3/2)

- PX=api.hash を追加。
- PX=plugins を追加。プラグインにPX Commandインターフェイスを実装できるようになった。
- プラグインAPIに outputfilter を追加。
- PX=rdb で、テーブル一覧表示、SELECT文発行、行数取得の操作をリンククリックで実行できるようにした。
- $px->theme()->mk_link() のオプションに no_escape を追加。
- 設定項目 system.default_theme_id を追加。
- 設定項目 system.session_expire を追加。
- 設定項目 paths_ignore が効かないパターンがある不具合を修正。
- ドキュメントモジュール設計を整理。.imagereplaceを.imgrepに改名、マージン制御、パディング制御系のモジュールを追加など。
- その他の細かい修正。


## PxFW 1.0.0b2 (2013/1/6)

- プラグインの仕組みを追加。object, initializeプラグインの実装規則を規定。
- サイトマップの auth_level の処理を実装。
- サイトマップの orderby の処理を実装。
- $dbhがPDOに対応。
- PX=api に ls コマンドを追加。
- PX=initialize で、SQLを実行せずにダウンロードする機能を追加。
- CSSモジュール .aural を追加。
- カテゴリトップページを取得する $site->get_category_top() を実装。
- 親ページまでのパンくず配列を取得する $site->get_breadcrumb_array() を実装。
- Not Foundページを出力する $px->page_notfound() を追加実装。
- サイトマップのパンくず欄が ">" から始まっていた場合、これを削除するように変更。
- ダイナミックパスのマッチングの順番をソートするように変更。
- ユーザーDBのカラムに set_pw_date を追加。
- 当面実装予定のない extension *.wiki を仕様から削除。
- ログイン後のリダイレクトパスがずれることがある問題を修正。
- $site->get_bros() が、トップページの兄弟に2階層目のページを返す不具合を修正。
- autoindex機能が作成した目次が、Firefoxで正常に機能しない不具合を修正。


## PxFW 1.0.0b1 (2012/12/6)

- 初版リリース。

