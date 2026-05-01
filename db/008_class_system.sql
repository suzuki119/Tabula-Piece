-- 008_class_system.sql: クラスシステム DB構築
-- Phase 11: テーブル拡張 / 新規スキル / 全クラスキャラクター投入

USE tabula_piece;

-- ================================================
-- 1. テーブル変更
-- ================================================

ALTER TABLE characters
  ADD COLUMN class ENUM('neutral','witch','blade','architect','paladin','dominant')
  NULL DEFAULT NULL AFTER piece_class;

ALTER TABLE decks
  ADD COLUMN class ENUM('neutral','witch','blade','architect','paladin','dominant')
  NOT NULL DEFAULT 'neutral' AFTER name;

CREATE TABLE IF NOT EXISTS deck_class_pieces (
  id           INT     NOT NULL AUTO_INCREMENT PRIMARY KEY,
  deck_id      INT     NOT NULL,
  character_id INT     NOT NULL,
  board_col    TINYINT NOT NULL COMMENT '列 0-5',
  board_row    TINYINT NOT NULL COMMENT '行 0-2（自陣3行）',
  FOREIGN KEY (deck_id)      REFERENCES decks(id)      ON DELETE CASCADE,
  FOREIGN KEY (character_id) REFERENCES characters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- 2. 新規スキル（ID 11〜73）
-- ================================================

INSERT INTO skills (id, name, type, category, description, max_uses) VALUES
-- 支援・回復系
(11, '聖壁',             'active',  'combat', '隣接する味方駒1体にシールドを付与する',                                           2),
(12, '聖壁（2体）',      'active',  'combat', '隣接する味方駒2体にシールドを付与する',                                           2),
(13, '聖光のオーラ',     'passive', 'combat', 'ターン開始時、隣接する全味方駒にシールドを付与する',                               NULL),
(14, '加護',             'passive', 'combat', '味方駒が取られた瞬間、隣接する味方駒1体に自動でシールドを付与する',               NULL),
(15, '復活',             'active',  'combat', '直近に取られた味方駒1体を隣接マスに復活させる',                                   1),
(16, '復活＋シールド',   'active',  'combat', '直近に取られた味方駒1体を隣接マスに復活させ、シールドを付与する',                 1),
(17, '自己犠牲',         'active',  'combat', 'この駒を除去し、隣接する全味方駒にシールド付与＋移動量+1（2ターン）',             1),
-- 移動バフ系
(18, '鼓舞',             'active',  'move',   '隣接する味方駒1体の移動量を2ターン+1する',                                        2),
(19, '鼓舞（強）',       'active',  'move',   '隣接する味方駒1体の移動量を2ターン+2する',                                        2),
(20, '鼓舞（全体）',     'active',  'move',   '隣接する全味方駒の移動量を2ターン+1する',                                         1),
-- 攻撃・貫通系
(21, '貫通',             'passive', 'combat', 'シールドを無視して相手の駒を取ることができる',                                    NULL),
(22, '覇斬',             'active',  'combat', '移動先の周囲1マス全ての駒を除去する（キング除外）',                               1),
(23, '連撃',             'active',  'combat', '移動後、隣接する相手駒を1体追加で除去する',                                       2),
(24, '斬り込み',         'active',  'combat', 'シールドを無視して相手の駒を取る',                                                1),
(25, '影斬り',           'active',  'combat', '2マス先の駒を取る（シールド無視、キング除外）',                                   1),
(26, '沼の波',           'active',  'combat', '2マス先のキング以外の駒を取る',                                                   1),
(27, '範囲突き',         'active',  'combat', '直線上の全ての駒を除去する（キング除外）',                                        1),
(28, '爆砕',             'active',  'combat', 'この駒を除去し、周囲1マス全ての相手駒を除去する',                                 1),
(29, '神速斬り',         'active',  'move',   '通常移動後、さらにナイトの動きで1回追加移動して駒を取れる',                       1),
-- 価値操作系
(30, '価値減少（小）',   'active',  'point',  '相手駒1体の価値を-1する',                                                         3),
(31, '価値減少（中）',   'active',  'point',  '相手駒1体の価値を-2する',                                                         2),
(32, '価値減少（大）',   'active',  'point',  '相手駒1体の価値を-3する',                                                         1),
(33, '価値減少（囮）',   'active',  'point',  '自駒の価値を-2する',                                                              2),
(34, '呪縛の代償',       'active',  'point',  '相手駒1体の価値を-2し、自駒の価値を-1する',                                      2),
(35, '取得価値上昇（小）','passive','point',  'この駒で相手駒を取ったとき、取得ポイント+1',                                     NULL),
(36, '取得価値上昇（中）','passive','point',  'この駒で相手駒を取ったとき、取得ポイント+2',                                     NULL),
(37, '価値付与（中）',   'active',  'point',  '隣接する味方駒1体に2ターン「取得価値+2」を付与する',                             2),
(38, '価値付与（大）',   'active',  'point',  '任意の味方駒1体に3ターン「取得価値+3」を付与する',                               1),
(39, '全呪い',           'passive', 'point',  '取られたとき、相手の全駒の価値を-1する',                                         NULL),
(40, '沼の加護',         'passive', 'combat', 'ターン開始時、真上に隣接する駒にシールドを付与する',                              NULL),
-- 罠・設置系
(41, '行動封じ罠',       'active',  'board',  '隣接マスに罠を設置する。踏んだ相手駒を1ターン行動封じにする',                    2),
(42, '行動封じ罠（強）', 'active',  'board',  '隣接マスに罠を設置する。踏んだ相手駒を2ターン行動封じにする',                    2),
(43, '強制移動罠',       'active',  'board',  '隣接マスに罠を設置する。踏んだ相手駒を任意の空きマスへ強制移動する',             1),
(44, '迷宮罠',           'active',  'board',  '隣接マスに罠を設置する。踏んだ相手駒を3ターン行動封じにする',                    1),
(45, '幻影罠',           'active',  'board',  '隣接マスに罠を設置する。踏んだ相手駒を2ターン行動封じかつ強制移動する',          1),
(46, '広域罠',           'active',  'board',  '隣接マスに2つ同時に罠を設置する。踏んだ相手駒を2ターン行動封じにする',          1),
(47, '要塞設置',         'active',  'board',  '隣接マスに移動不可の盾駒を1体設置する',                                          2),
(48, '要塞設置（大）',   'active',  'board',  '隣接マスに移動不可の盾駒を2体設置する',                                          1),
(49, '要塞化',           'active',  'board',  'この駒自身を1ターンの間移動不可の盾に変える',                                    2),
(50, '罠感知',           'passive', 'board',  '相手が設置した罠の位置を見ることができる',                                       NULL),
-- 支配・コピー系
(51, '完全支配',         'active',  'combat', '隣接する相手駒1体を永続的に自駒として奪取する（キング除外）',                    1),
(52, '魅了（長）',       'active',  'combat', '隣接する相手駒1体を2ターン自駒として操作できる（キング除外）',                   1),
(53, '魅了',             'active',  'combat', '隣接する相手駒1体を1ターン自駒として操作できる（キング除外）',                   2),
(54, '無能力化',         'active',  'combat', '相手駒1体のスキルを2ターン封じる',                                                2),
(55, '妨害',             'active',  'combat', '隣接する相手駒1体のスキルを1ターン封じる',                                        2),
(56, '鎖縛（強）',       'active',  'combat', '相手駒1体のスキルを封じ、自陣側に3マス強制移動する（キング除外）',                1),
(57, '鎖縛',             'active',  'combat', '相手駒1体のスキルを封じ、自陣側に2マス強制移動する（キング除外）',                1),
(58, '強制移動',         'active',  'combat', '相手駒1体を隣接マスに強制移動する（キング除外）',                                 2),
(59, '支配の記憶',       'passive', 'combat', '自駒で相手駒を取ったとき、その駒のパッシブをコピーして永続取得する',              NULL),
(60, '技の盗用',         'active',  'combat', '隣接する相手駒1体のアクティブスキルを1回コピーして使用する',                     1),
(61, '模倣（弱）',       'active',  'combat', '隣接する相手駒1体のパッシブスキルを2ターンコピーする',                           1),
(62, '完全模倣',         'active',  'combat', '隣接する相手駒1体のアクティブ・パッシブ両方をコピーする',                        1),
(63, '戦利の技',         'passive', 'combat', '自駒で相手駒を取ったとき、その駒のアクティブスキルを1回使用できる',              NULL),
(64, '支配の余韻',       'passive', 'combat', '奪取した駒が取られたとき、その駒のパッシブを1回だけ発動する',                    NULL),
(65, '急襲支配',         'active',  'combat', '通常移動後、移動先の隣接相手駒1体を1ターン自駒として操作できる（キング除外）',  1),
(66, '感染',             'active',  'combat', 'この駒を取った相手駒を1ターン行動不能にし自陣側に強制移動する',                  1),
(67, '反射の呪い',       'passive', 'combat', '取られる直前に相手駒のパッシブを1回コピーして発動する',                          NULL),
(68, '行動封じ',         'active',  'combat', '相手駒1体を2ターン行動封じにする',                                                1),
(69, '嵐撃',             'active',  'board',  '移動先の周囲1マスにいる相手駒を全て1マス強制移動する',                           1),
(70, '妨害（強）',       'active',  'combat', '隣接する相手駒1体のスキルを2ターン封じる',                                        2),
(71, '時限聖域',         'active',  'board',  '2ターンの間、自駒のいるマスに相手は進入できなくなる',                             2),
(72, '護衛反応',         'passive', 'combat', '取られる直前に、隣接する味方駒1体にシールドを付与する',                          NULL),
(73, '反撃（強制移動）', 'passive', 'combat', '取られる直前に、攻撃してきた相手駒を隣接マスへ強制移動する',                     NULL);

-- ================================================
-- 3. キャラクター投入
-- ================================================

-- ウィッチ
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('沼地の魔女・ヴォルテール', 'queen',  'witch', 'SSR', 26, 40),
('霧の魔女・シルヴィア',     'queen',  'witch', 'SR',  37, 36),
('呪いの魔女・モルダ',       'queen',  'witch', 'R',   31, 35),
('見習い魔女・ルナ',         'queen',  'witch', 'N',   30, NULL),
('氷壁の魔女・グラシア',     'rook',   'witch', 'SSR', 71, 4),
('結界の魔女・フィーナ',     'rook',   'witch', 'SR',  9,  10),
('石壁の魔女・ドルバ',       'rook',   'witch', 'R',   4,  NULL),
('守りの魔女・セラ',         'rook',   'witch', 'N',   5,  NULL),
('腐敗の魔女・ネクリア',     'bishop', 'witch', 'SSR', 32, 39),
('毒霧の魔女・ヴェイラ',     'bishop', 'witch', 'SR',  31, 8),
('幻惑の魔女・ミラ',         'bishop', 'witch', 'R',   30, NULL),
('小呪いの魔女・チカ',       'bishop', 'witch', 'N',   NULL, 8),
('嵐の魔女・テンペスト',     'knight', 'witch', 'SSR', 38, 36),
('疾風の魔女・ライラ',       'knight', 'witch', 'SR',  37, 35),
('突風の魔女・フウカ',       'knight', 'witch', 'R',   1,  35),
('風追いの魔女・シオン',     'knight', 'witch', 'N',   1,  NULL),
('呪縛の魔女・ドルシア',     'pawn',   'witch', 'SSR', 34, 8),
('囮の魔女・デコイア',       'pawn',   'witch', 'SR',  33, 8),
('小鬼の魔女・ゴブリナ',     'pawn',   'witch', 'R',   NULL, 8),
('粗削りの魔女・ミニア',     'pawn',   'witch', 'N',   NULL, NULL);

-- ブレード
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('覇刃の剣士・ガルディオス', 'queen',  'blade', 'SSR', 22, 21),
('烈風の剣士・シュラ',       'queen',  'blade', 'SR',  23, NULL),
('疾刃の剣士・レイア',       'queen',  'blade', 'R',   24, NULL),
('若き剣士・カイン',         'queen',  'blade', 'N',   NULL, NULL),
('鉄壁破りの槍兵・ドラガス', 'rook',   'blade', 'SSR', 27, 21),
('強行の槍兵・バルド',       'rook',   'blade', 'SR',  24, NULL),
('直進の槍兵・ゴルダ',       'rook',   'blade', 'R',   1,  NULL),
('新兵・クロン',             'rook',   'blade', 'N',   NULL, NULL),
('暗殺者・ゼファー',         'bishop', 'blade', 'SSR', 25, 3),
('斥候・ミルザ',             'bishop', 'blade', 'SR',  2,  NULL),
('忍び・クロウ',             'bishop', 'blade', 'R',   1,  NULL),
('見習い斥候・リン',         'bishop', 'blade', 'N',   NULL, NULL),
('神速の騎士・ライトニング', 'knight', 'blade', 'SSR', 29, 21),
('疾駆の騎士・ガレオン',     'knight', 'blade', 'SR',  23, NULL),
('突進の騎士・ダグ',         'knight', 'blade', 'R',   1,  NULL),
('新米騎士・ルカ',           'knight', 'blade', 'N',   NULL, NULL),
('特攻兵・バーサーカー',     'pawn',   'blade', 'SSR', 28, NULL),
('突貫兵・ラッシュ',         'pawn',   'blade', 'SR',  24, 6),
('強行兵・グラン',           'pawn',   'blade', 'R',   NULL, 6),
('歩兵・マルク',             'pawn',   'blade', 'N',   NULL, NULL);

-- アーキテクト
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('大設計師・ラビリンス',     'queen',  'architect', 'SSR', 44, 47),
('策謀師・ダルマ',           'queen',  'architect', 'SR',  43, NULL),
('罠師・トリック',           'queen',  'architect', 'R',   41, NULL),
('見習い設計師・ブロック',   'queen',  'architect', 'N',   NULL, 47),
('鉄壁の築城師・フォルテ',   'rook',   'architect', 'SSR', 48, 10),
('城壁師・バリカード',       'rook',   'architect', 'SR',  47, 10),
('石積み師・ゴーレム',       'rook',   'architect', 'R',   47, NULL),
('普請兵・クラフト',         'rook',   'architect', 'N',   NULL, NULL),
('幻惑の罠師・ミラージュ',   'bishop', 'architect', 'SSR', 45, 50),
('迂回の罠師・デトア',       'bishop', 'architect', 'SR',  43, NULL),
('仕掛け師・スナップ',       'bishop', 'architect', 'R',   41, NULL),
('新米罠師・ピット',         'bishop', 'architect', 'N',   NULL, NULL),
('奇襲の陣師・アンブッシュ', 'knight', 'architect', 'SSR', 46, 3),
('遊撃の罠師・フランカー',   'knight', 'architect', 'SR',  42, NULL),
('奇兵・スカウト',           'knight', 'architect', 'R',   1,  NULL),
('斥候兵・ウォッチ',         'knight', 'architect', 'N',   NULL, NULL),
('地雷兵・マインド',         'pawn',   'architect', 'SSR', 44, 73),
('壁兵・ウォール',           'pawn',   'architect', 'SR',  49, NULL),
('伏兵・アンブシュ',         'pawn',   'architect', 'R',   41, NULL),
('歩哨・センチ',             'pawn',   'architect', 'N',   NULL, NULL);

-- パラディン
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('聖騎士長・セラフィム',       'queen',  'paladin', 'SSR', 15, 13),
('守護騎士・イージス',         'queen',  'paladin', 'SR',  11, 18),
('癒しの騎士・グレイス',       'queen',  'paladin', 'R',   11, NULL),
('見習い聖騎士・ラクス',       'queen',  'paladin', 'N',   NULL, NULL),
('不滅の守護者・イモータル',   'rook',   'paladin', 'SSR', 16, 4),
('堅守の騎士・バスティオン',   'rook',   'paladin', 'SR',  12, 4),
('盾の騎士・エギス',           'rook',   'paladin', 'R',   11, NULL),
('衛兵・ガード',               'rook',   'paladin', 'N',   NULL, NULL),
('復活の司祭・リザレクト',     'bishop', 'paladin', 'SSR', 15, 14),
('癒しの司祭・ヒール',         'bishop', 'paladin', 'SR',  15, NULL),
('祈りの修道士・プレイ',       'bishop', 'paladin', 'R',   11, NULL),
('見習い修道士・フェイス',     'bishop', 'paladin', 'N',   NULL, NULL),
('疾風の聖騎士・ゼファリオン', 'knight', 'paladin', 'SSR', 20, 3),
('援護の騎士・サポーター',     'knight', 'paladin', 'SR',  19, NULL),
('伝令騎士・メッセンジャー',   'knight', 'paladin', 'R',   18, NULL),
('新米騎士・スクワイア',       'knight', 'paladin', 'N',   NULL, NULL),
('殉教兵・マーティル',         'pawn',   'paladin', 'SSR', 17, NULL),
('守護兵・プロテクト',         'pawn',   'paladin', 'SR',  11, 72),
('盾兵・シールダー',           'pawn',   'paladin', 'R',   NULL, 72),
('信徒兵・フェイス',           'pawn',   'paladin', 'N',   NULL, NULL);

-- ドミナント
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('支配の女王・ドミナ',       'queen',  'dominant', 'SSR', 51, 59),
('魅了の魔術師・チャーム',   'queen',  'dominant', 'SR',  52, NULL),
('洗脳師・ブレイン',         'queen',  'dominant', 'R',   53, NULL),
('操り師・マリオネット',     'queen',  'dominant', 'N',   NULL, NULL),
('鎖の支配者・シャックル',   'rook',   'dominant', 'SSR', 56, 64),
('拘束師・バインド',         'rook',   'dominant', 'SR',  57, NULL),
('捕縛兵・キャプチャー',     'rook',   'dominant', 'R',   58, NULL),
('見張り兵・ウォーデン',     'rook',   'dominant', 'N',   NULL, NULL),
('模倣の魔女・ミミック',     'bishop', 'dominant', 'SSR', 62, 63),
('盗技師・スティール',       'bishop', 'dominant', 'SR',  60, NULL),
('見習い模倣師・エコー',     'bishop', 'dominant', 'R',   61, NULL),
('観察者・ウォッチャー',     'bishop', 'dominant', 'N',   NULL, NULL),
('奇襲の支配者・アンブロス', 'knight', 'dominant', 'SSR', 65, 3),
('侵食の騎士・コラプト',     'knight', 'dominant', 'SR',  53, NULL),
('撹乱の騎士・ディスラプト', 'knight', 'dominant', 'R',   54, NULL),
('新米支配者・ノービス',     'knight', 'dominant', 'N',   NULL, NULL),
('感染兵・インフェクト',     'pawn',   'dominant', 'SSR', 66, 67),
('潜伏兵・インフィルト',     'pawn',   'dominant', 'SR',  70, NULL),
('妨害兵・サボタージュ',     'pawn',   'dominant', 'R',   55, NULL),
('見習い工作員・スパイ',     'pawn',   'dominant', 'N',   NULL, NULL);

-- ニュートラル
INSERT INTO characters (name, piece_class, class, rarity, active_skill_id, passive_skill_id) VALUES
('嵐の女王・テンペスタ',       'queen',  'neutral', 'SSR', 69, 3),
('呪縛の女王・メドゥーサ',     'queen',  'neutral', 'SR',  68, 35),
('守護の女王・エギーナ',       'queen',  'neutral', 'R',   11, NULL),
('平原の女王・プレーナ',       'queen',  'neutral', 'N',   NULL, NULL),
('要塞の守護者・バスティオン', 'rook',   'neutral', 'SSR', 48, 10),
('反撃の槍兵・カウンター',     'rook',   'neutral', 'SR',  4,  6),
('鉄壁の番兵・アイアン',       'rook',   'neutral', 'R',   4,  NULL),
('石の番兵・ロック',           'rook',   'neutral', 'N',   NULL, NULL),
('大賢者・アルカナム',         'bishop', 'neutral', 'SSR', 60, 36),
('封印の司教・シーラー',       'bishop', 'neutral', 'SR',  54, 8),
('祈りの司教・ブレッサー',     'bishop', 'neutral', 'R',   11, NULL),
('若き司教・ノービス',         'bishop', 'neutral', 'N',   NULL, NULL),
('覇道の騎士・パラゴン',       'knight', 'neutral', 'SSR', 29, 3),
('重装の騎士・アーマード',     'knight', 'neutral', 'SR',  4,  6),
('鼓舞の騎士・モラール',       'knight', 'neutral', 'R',   18, NULL),
('平原の騎士・プレーン',       'knight', 'neutral', 'N',   NULL, NULL),
('英雄の歩兵・ヒーロー',       'pawn',   'neutral', 'SSR', 23, 6),
('呪いの歩兵・カース',         'pawn',   'neutral', 'SR',  NULL, 8),
('反撃の歩兵・カウンター',     'pawn',   'neutral', 'R',   NULL, 6),
('平原の歩兵・プレーン',       'pawn',   'neutral', 'N',   NULL, NULL);
