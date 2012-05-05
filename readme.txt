【Pickles Framework 1.x】
@author Tomoya Koyanagi (@tomk79)
--------

静的な構成のウェブサイトを効率よく制作するためのフレームワークです。
Pickles Framework 0.x系( http://pickles.pxt.jp/ )の後継です。


■セットアップ手順

1. 最新のソース一式をダウンロードする。
2. 解凍する。
3. htdocsに格納されたファイル一式を、
   お使いのウェブサーバーのドキュメントルート配下の任意のディレクトリに設置する。
4. ウェブブラウザからアクセスする。

　※パーミッション設定
　　次のディレクトリとそれ以下の全てのファイルに、
　　Apacheが書き込みできるパーミッションを設定する。

　　　・./_PX/_sys
　　　・./_caches


■使い方

1. ./_PX/sitemaps/sitemap.csv を編集して、ページを登録する。
2. コンテンツのHTMLを編集する。
3. テーマ ./_PX/themes/default/default.html を編集する。


■パブリッシュ手順

1. URLに ?PX=publish を付けてアクセスする。
2. ./_PX/_sys/publish/ に出力される。

出力先のディレクトリは ./_PX/configs/mainconf.ini の
publishディレクティブ path_publish_dir で変更できます。


■システム用件

・Linux系サーバ または Windowsサーバ
・Apache1.3以降
　　⇒mod_rewrite が利用可能であること
　　⇒.htaccess が利用可能であること
・PHP5系 (PHP4系では動作しない)
　　⇒mb_string が有効に設定されていること
　　⇒safe_mode が無効に設定されていること


■対応データベース

・MySQL
・PostgreSQL
・SQLite

※フレームワークの基本的な処理にデータベースは使用しません。


■Windowsサーバでの制約

Windows系OSでは、次のような制約を受ける。

・ボリューム名からバックスラッシュ区切りのパス名は使用できない。
　絶対パスは、スラッシュから始まり、
　ディレクトリをスラッシュで区切ったUNIXの規則に変換して取り扱われる。
・セットアップされたディスク以外のボリュームにアクセスすることはできない。
　また、\\から始まるネットワークディレクトリにもアクセスできない。
・コマンドラインから実行することはできない。
・この他、PHPやApacheが持つ制約の影響を受ける。


■ライセンス

MIT License を適用する。
http://ja.wikipedia.org/wiki/MIT_License


■作者

(C)Tomoya Koyanagi <tomk79@gmail.com>
http://www.pxt.jp/
Twitter: @tomk79 http://twitter.com/tomk79/


