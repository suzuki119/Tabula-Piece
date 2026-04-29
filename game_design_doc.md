# チェス×ソシャゲ ゲーム設計ドキュメント

> v1.1 — Phase 1〜10 実装完了（認証・ランキング・キャラ詳細追加）

---

## 1. ゲーム概要

### コンセプト

チェスのボードゲームとしての戦略性をベースに、キャラクター収集・デッキ編成のソシャゲ要素を組み合わせたPvP非同期対戦ゲーム。

### ターゲット

- チェスを知っているが「重い」と感じているユーザー
- ソシャゲのコレクション・育成要素が好きなユーザー

### 参考タイトル

- Clash Royale（非同期PvP × カード収集）
- 逆転オセロニア（ボードゲーム × キャラクター能力）

---

## 2. ゲームルール

### 基本ルール

- チェスのルールをベースとする
- 駒の種類・動き方は標準チェスに準拠

### 変更点（スピード化）

- **盤面サイズ**：6×6（標準8×8から縮小）
- **使用する駒**：ルーク×1・ナイト×1・ビショップ×1・クイーン×1・キング×1・ポーン×6
- **1手あたりの制限時間**：60秒
- **1試合の目安**：10〜20分
- **ターン上限**：20〜30ターン

### 勝利条件

1. **即勝利**：相手のキングを取る
2. **ターン切れ判定**：上限ターン到達時、残っている駒の価値合計が高い方が勝利
3. **降参**：プレイヤーが降参した場合、相手の勝利

### 駒の価値（ポイント計算用）

| 駒         | 基本価値                   |
| ---------- | -------------------------- |
| ポーン     | 1                          |
| ナイト     | 3                          |
| ビショップ | 3                          |
| ルーク     | 5                          |
| クイーン   | 9                          |
| キング     | 対象外（取られたら即敗北） |

> スキル効果（価値上昇・駒強化・死に際の呪い）により価値が変動する場合がある。最低値は0。

### 戦略の幅

- **攻め**：キングを狙って即勝利を目指す
- **耐久**：高価値の駒を守り切ってポイント勝ちを狙う
- **一発逆転**：ターン切れ間際にクイーンなどを奪ってポイント逆転

---

## 3. ソシャゲ要素仕様

### キャラクター

- 各キャラクターは「駒の種類」に対応したロール（クラス）を持つ
  - クラス：ポーン・ナイト・ビショップ・ルーク・クイーン・キング
- キャラクターはガチャで入手
- レアリティ：N / R / SR / SSR

### デッキ編成

- 試合前に駒の種類（6種）ごとに装備するキャラクターを1体選択
- 例：ナイトに「〇〇騎士」を装備 → その駒がスキルを持つ
- ポーンは全6枚が同じキャラクターを共有する（1枠）

### 初期配置

```
後列（黒）：♜ ♞ ♝ ♛ ♚ —
前列（黒）：♟ ♟ ♟ ♟ ♟ ♟
　　　　　　（空き4列）
前列（白）：♙ ♙ ♙ ♙ ♙ ♙
後列（白）：♖ ♘ ♗ ♕ ♔ —
※ f列後段は空きマス
```

### スキル構成

- 1キャラクターにアクティブスキル×1＋パッシブスキル×1の両方を持たせる
- **アクティブ**：駒を動かしたターンにセットで手動発動。移動後にスキル発動機会が与えられ、発動またはスキップを選択する。使用回数はスキルの強さによって設定（1試合1回〜複数回）
- **パッシブ**：条件を満たすと自動発動（回数制限はキャラごとに設定）

### スキルカテゴリと実装済みスキル一覧

| ID  | 名前         | 種別       | カテゴリ | 効果                                               | 最大使用回数 |
| --- | ------------ | ---------- | -------- | -------------------------------------------------- | ------------ |
| 1   | 再移動       | アクティブ | 移動     | 駒を移動した後、もう1マス追加で移動できる          | 3            |
| 2   | テレポート   | アクティブ | 移動     | 盤面上の任意の空きマスに瞬間移動する               | 1            |
| 3   | 先手         | パッシブ   | 移動     | ターン開始時に1マス先に移動してからターンを行える  | —            |
| 4   | シールド     | アクティブ | 戦闘     | 次に受ける捕獲を1回無効化する                      | 2            |
| 5   | 駒強化       | アクティブ | 戦闘     | 隣接する味方駒1体の価値をそのターン中+2する        | 2            |
| 6   | 反撃         | パッシブ   | 戦闘     | 捕獲される直前に、相手の駒を1体除去する            | 1            |
| 7   | 価値上昇     | アクティブ | ポイント | この駒の価値をターン切れまで+3する                 | 1            |
| 8   | 死に際の呪い | パッシブ   | ポイント | 取られたとき、相手の任意の駒の価値を-2する         | —            |
| 9   | トラップ設置 | アクティブ | 盤面     | 隣接マスにトラップを置く。踏んだ相手の駒を除去する | 1            |
| 10  | 聖域         | パッシブ   | 盤面     | この駒が乗っているマスに相手は進入できない         | —            |

### スキルバランスの指針

- アクティブの使用回数は強さに反比例して設定する（例：テレポート=1回、再移動=3回）
- パッシブは「弱めだが条件次第で複数回」を基本とする
- ポーンのスキルは小さめに、クイーン・キングのスキルは大きめに設計する

---

## 4. ガチャ仕様

### 排出レート

| レアリティ | 確率 |
| ---------- | ---- |
| SSR        | 3%   |
| SR         | 12%  |
| R          | 35%  |
| N          | 50%  |

### ガチャ石（通貨）

| 操作    | 消費石 |
| ------- | ------ |
| 1回引く | 100石  |
| 10連    | 1000石 |

### 保証・重複処理

- **10連SR保証**：10連で1枚もSR以上が出なかった場合、最後の1枚を強制的にSRにする
- **重複排出**：すでに所持しているキャラが出た場合、50石を返還する

---

## 5. ゲームスピード設計

### 非同期対戦

- リアルタイム通信は行わない
- 相手のターン中はアプリを閉じてよい
- 複数試合を並行して進行可能

### 待ち時間対策

- 1手60秒の制限時間（超過でパス扱い or 自動着手）

---

## 6. 技術スタック

| レイヤー       | 技術                        | 備考                                      |
| -------------- | --------------------------- | ----------------------------------------- |
| フロントエンド | HTML / CSS / JavaScript     | フレームワークなし、ページ遷移方式        |
| バックエンド   | PHP 7.4以上                 | アロー関数 `fn()` を使用                  |
| データベース   | MySQL（InnoDB）             | PDO接続、トランザクション使用             |
| 開発環境       | MAMP（Mac）                 | ポート8889、root/root                     |
| 本番環境       | ロリポップ レンタルサーバー | DB設定は `api/config/db_local.php` で管理 |
| 開発ツール     | Claude Code                 | —                                         |

---

## 7. データ設計

### マスターデータ

**skills**（スキル）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | スキルID |
| name | VARCHAR | スキル名 |
| type | ENUM | active / passive |
| category | ENUM | move / combat / point / board |
| description | TEXT | 効果説明 |
| max_uses | INT | 1試合の最大使用回数（NULLは無制限） |

**characters**（キャラクター）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | キャラクターID |
| name | VARCHAR | キャラクター名 |
| piece_class | ENUM | pawn / knight / bishop / rook / queen / king |
| rarity | ENUM | N / R / SR / SSR |
| active_skill_id | INT FK | スキルID（NULL可） |
| passive_skill_id | INT FK | スキルID（NULL可） |

---

### ユーザーデータ

**users**（ユーザー）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | ユーザーID |
| name | VARCHAR | 表示名 |
| stones | INT | ガチャ石残高（DEFAULT 0） |
| created_at | DATETIME | 登録日時 |

**user_characters**（所持キャラクター）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | — |
| user_id | INT FK | ユーザーID |
| character_id | INT FK | キャラクターID |
| obtained_at | DATETIME | 入手日時 |

**decks**（デッキ編成）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | デッキID |
| user_id | INT FK | ユーザーID |
| name | VARCHAR | デッキ名 |
| pawn_char_id | INT FK | ポーンに装備するキャラ（NULL可） |
| knight_char_id | INT FK | ナイトに装備するキャラ（NULL可） |
| bishop_char_id | INT FK | ビショップに装備するキャラ（NULL可） |
| rook_char_id | INT FK | ルークに装備するキャラ（NULL可） |
| queen_char_id | INT FK | クイーンに装備するキャラ（NULL可） |
| king_char_id | INT FK | キングに装備するキャラ（NULL可） |

**gacha_logs**（ガチャ履歴）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | — |
| user_id | INT FK | ユーザーID |
| character_id | INT FK | 排出されたキャラクターID |
| rarity | ENUM | N / R / SR / SSR |
| is_new | TINYINT(1) | 新規入手かどうか |
| stones_spent | INT | 消費した石数 |
| pulled_at | DATETIME | 排出日時 |

---

### 対戦データ

**matches**（試合）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | 試合ID |
| player1_id | INT FK | ユーザーID（白） |
| player2_id | INT FK | ユーザーID（黒）、マッチング待機中はNULL |
| player1_deck_id | INT FK | デッキID |
| player2_deck_id | INT FK | デッキID |
| status | ENUM | waiting / in_progress / finished |
| current_turn | INT | 現在のターン数 |
| current_player | ENUM | player1 / player2 |
| winner_id | INT FK | 勝者のユーザーID（NULLは未決） |
| end_reason | ENUM | checkmate / points / timeout |
| room_code | VARCHAR(20) | ルームマッチ用コード（NULL可） |
| created_at | DATETIME | 試合開始日時 |
| updated_at | DATETIME | 最終更新日時 |

**board_states**（盤面状態）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | — |
| match_id | INT FK | 試合ID |
| turn | INT | ターン数 |
| board_json | JSON | ゲーム状態全体をJSON形式で保存 |
| created_at | DATETIME | 記録日時 |

> board_json の構造（Phase 4以降）：
>
> ```json
> {
>   "board": {
>     "a1": {
>       "piece": "rook",
>       "color": "white",
>       "character_id": 12,
>       "active_skill_id": 5,
>       "passive_skill_id": 6,
>       "active_used": 0,
>       "passive_used": 0,
>       "shield": false,
>       "value_bonus": 0
>     },
>     "a6": null
>   },
>   "traps": { "c4": { "color": "white" } },
>   "rematchPending": null,
>   "skillOpportunity": { "player": "player1", "sq": "b2", "skill_id": 1 }
> }
> ```

**skill_logs**（スキル使用履歴）
| カラム | 型 | 説明 |
|-------|----|------|
| id | INT PK | — |
| match_id | INT FK | 試合ID |
| turn | INT | 使用ターン |
| user_id | INT FK | 使用したユーザー |
| skill_id | INT FK | スキルID |
| target_square | VARCHAR | 対象マス（例：c3） |

---

## 8. 画面設計

### 実装済み画面一覧

| ファイル                 | 説明                                                   |
| ------------------------ | ------------------------------------------------------ |
| `public/login.html`      | ログイン・新規登録                                     |
| `public/home.html`       | ホーム（進行中試合一覧・ナビ）                         |
| `public/characters.html` | 所持キャラクター一覧・クラスフィルタ・詳細モーダル     |
| `public/decks.html`      | デッキ編成（スロット選択・保存）                       |
| `public/play.html`       | マッチング選択（オンライン / ルーム作成 / ルーム参加） |
| `public/match.html`      | 対戦盤面メイン画面                                     |
| `public/gacha.html`      | ガチャ演出・結果表示                                   |
| `public/ranking.html`    | ランキング・個人戦績表示                               |

### 対戦盤面の表示要素

- 6×6盤面
- 現在のターン数・残りターン
- 両者の残駒ポイント合計
- 手番表示（自分 / 相手の番）
- スキル発動機会パネル（発動 / スキップ）
- 選択中の駒のスキル情報・発動ボタン
- 残り思考時間（60秒カウントダウン）
- 降参ボタン

---

## 9. ディレクトリ構成

```
Tabula-Piece/
├── api/
│   ├── characters/
│   │   └── list.php          # 所持キャラ一覧
│   ├── config/
│   │   ├── db.php            # DB接続（ローカル設定を自動読み込み）
│   │   ├── db_local.php      # 本番DB設定（.gitignore対象）
│   │   └── db_local.sample.php
│   ├── decks/
│   │   ├── list.php          # デッキ一覧取得
│   │   └── save.php          # デッキ保存
│   ├── gacha/
│   │   ├── info.php          # 石残高・所持数取得
│   │   └── pull.php          # ガチャ実行
│   ├── game/
│   │   ├── Chess.php         # チェスロジック・スキル処理
│   │   ├── match_starter.php # 試合開始処理（両デッキ適用）
│   │   ├── move.php          # 駒移動API
│   │   ├── skill.php         # スキル発動API
│   │   └── state.php         # 盤面状態取得API
│   ├── matching/
│   │   ├── online.php        # ランダムマッチング
│   │   ├── room_create.php   # ルーム作成
│   │   ├── room_join.php     # ルーム参加
│   │   └── status.php        # マッチング状態ポーリング
│   └── matches/
│       ├── create.php        # 試合作成（直接指定）
│       ├── list.php          # 試合一覧
│       └── surrender.php     # 降参
├── db/
│   ├── 001_create_tables.sql
│   ├── 002_seed_master_data.sql
│   ├── 003_test_skill_match.sql
│   ├── 004_give_all_characters.sql
│   ├── 005_matching.sql      # player2_id NULL対応
│   ├── 006_gacha.sql         # stones・gacha_logsテーブル
│   └── .htaccess             # 直接アクセス禁止
├── public/
│   ├── css/
│   │   ├── board.css         # 対戦盤面スタイル
│   │   ├── deck.css          # キャラ・デッキ画面スタイル
│   │   ├── gacha.css         # ガチャ画面スタイル
│   │   ├── home.css
│   │   └── play.css          # マッチング・ホーム共通スタイル
│   ├── js/
│   │   ├── board.js          # 対戦盤面コントローラー
│   │   ├── characters.js
│   │   ├── chess.js          # クライアント側チェスロジック
│   │   ├── decks.js
│   │   ├── gacha.js
│   │   ├── home.js
│   │   └── play.js
│   ├── characters.html
│   ├── decks.html
│   ├── gacha.html
│   ├── home.html
│   ├── match.html
│   └── play.html
├── .claude/
│   └── settings.json         # Claude Code設定
├── .gitignore
├── CLAUDE.md                 # 開発チームルール
└── game_design_doc.md        # 本ドキュメント
```

---

## 10. 本番環境デプロイ手順（ロリポップ）

### 1. ファイルアップロード

FTP/SFTPでプロジェクトを丸ごとアップロード（例: `public_html/tabula-piece/`）

### 2. DBセットアップ

ロリポップ コントロールパネル → データベース → MySQL追加

### 3. DB設定ファイルの作成

サーバー上で `api/config/db_local.sample.php` を `api/config/db_local.php` にコピーし編集：

```php
define('DB_HOST', 'mysql○○.lolipop.jp'); // コントロールパネルで確認
define('DB_PORT', 0);                      // 0 = ポート省略（標準3306）
define('DB_NAME', '実際のDB名');
define('DB_USER', '実際のDBユーザー');
define('DB_PASS', '実際のパスワード');
```

### 4. マイグレーション実行

phpMyAdmin で以下を順番に実行：

1. `db/001_create_tables.sql`
2. `db/002_seed_master_data.sql`
3. `db/005_matching.sql`
4. `db/006_gacha.sql`

> テストデータ（003・004）は本番環境では不要

### 5. 動作確認URL

```
https://example.com/tabula-piece/public/home.html?user_id=1
```

---

## 11. 実装ロードマップ

| フェーズ | 内容                 | 状態    |
| -------- | -------------------- | ------- |
| Phase 1  | DB構築               | ✅ 完了 |
| Phase 2  | チェスロジック       | ✅ 完了 |
| Phase 3  | 対戦盤面UI           | ✅ 完了 |
| Phase 4  | スキルシステム       | ✅ 完了 |
| Phase 5  | キャラクター・デッキ | ✅ 完了 |
| Phase 6  | マッチング           | ✅ 完了 |
| Phase 7  | ガチャ               | ✅ 完了 |
| Phase 8  | ユーザー認証         | ✅ 完了 |
| Phase 9  | ランキング・戦績      | ✅ 完了 |
| Phase 10 | キャラクター詳細     | ✅ 完了 |

### 今後の候補機能

- プッシュ通知（手番が来たとき）
- キャラクター追加
- キャラクターイラスト
- クエストモード

_最終更新：2026-04-30_
