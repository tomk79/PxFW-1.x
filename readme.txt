【Pickles Framework 1.x】

@author Tomoya Koyanagi (@tomk79)

静的な構成のウェブサイトを効率よく制作するためのフレームワークです。
Pickles Framework 0.x系( http://pickles.pxt.jp/ )の後継です。

■セットアップ手順

1. 最新のソース一式をダウンロードする。
2. 解凍する。
3. htdocsに格納されたファイル一式を、
   お使いのウェブサーバーのドキュメントルート配下の任意のディレクトリに設置する。
4. ウェブブラウザからアクセスする。


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


■Windowsサーバでの制約

Windows系OSでは、次のような制約を受ける。

・ボリューム名からバックスラッシュ区切りのパス名は使用できない。
　絶対パスは、スラッシュから始まり、
　ディレクトリをスラッシュで区切ったUNIXの規則に変換して取り扱われる。
・セットアップされたディスク以外のボリュームにアクセスすることはできない。
　また、\\から始まるネットワークディレクトリにもアクセスできない。
・コマンドラインから実行することはできない。
・この他、PHPやApacheが持つ制約の影響を受ける。


■作者

Tomoya Koyanagi
http://www.pxt.jp/
Twitter: @tomk79 http://twitter.com/tomk79/


