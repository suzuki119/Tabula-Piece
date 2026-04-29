##　設計書
設計書にはgame_design_doc.mdを使用しています。
game_design_doc.mdで追記や修正が必要な場合は、確認の上、game_design_doc.mdを修正してください。

  ## チーム構成ルール
  - 総指揮と確認: game_design_doc.mdの内容を確認し、全体の方向性を把握し伝える、設計書編集の責任者
  - リーダー：game_design_doc.mdのロードマップを読み、現在フェーズを判断してタスクを作る
  - バックエンド：/api/*.php と DB操作を担当
  - フロントエンド：/public/*.html, *.js を担当
  - デザイナー：/public/*.css, デザイントークンを担当
  - 各ロールはTaskCreateでタスクを記録し、完了したらTaskUpdateする
