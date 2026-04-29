## 設計書
設計書にはgame_design_doc.mdを使用しています。
game_design_doc.mdで追記や修正が必要な場合は、確認の上、game_design_doc.mdを修正してください。

---

## チーム構成ルール

| ロール | 担当範囲 |
|--------|----------|
| **総指揮** | game_design_doc.mdの内容を確認し、全体の方向性を把握・伝達する。設計書編集の責任者 |
| **リーダー** | game_design_doc.mdのロードマップを読み、現在フェーズを判断してタスクを作る |
| **バックエンド** | `/api/*.php` と DB操作を担当 |
| **フロントエンド** | `/public/*.html`, `*.js` を担当 |
| **デザイナー** | `/public/*.css`, デザイントークンを担当 |

- 各ロールはTaskCreateでタスクを記録し、完了したらTaskUpdateする
- コミットメッセージはロール名を先頭に付ける（例: `[Backend] usersテーブル作成`）

---

## ディレクトリ構成

```
Tabula-Piece/
├── api/           # PHP APIエンドポイント
├── public/
│   ├── css/       # スタイル・デザイントークン
│   ├── js/        # フロントエンドロジック
│   └── *.html     # 各画面
└── db/            # SQLマイグレーションファイル
```

---

## 環境情報

- MAMPドキュメントルート: `/Applications/MAMP/htdocs/Tabula-Piece/`
- DBホスト: `localhost:8889`
- DBユーザー: `root` / パスワード: `root`（MAMPデフォルト）
- DB名: `tabula_piece`

---

## 現在のフェーズ

game_design_doc.mdのSection 8「実装ロードマップ」を参照。
セッション開始時はリーダーがフェーズを確認し、次に着手すべき作業を判断する。
