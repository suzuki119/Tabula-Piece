<?php
// このファイルを db_local.php にコピーして本番環境の値を設定してください
// ロリポップの場合: MySQLの設定はコントロールパネル → データベース から確認

define('DB_HOST', 'localhost');   // ロリポップのMySQLホスト
define('DB_PORT', 0);             // 0 = ポート指定なし（標準3306）
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
