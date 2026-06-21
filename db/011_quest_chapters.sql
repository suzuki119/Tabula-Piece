-- 011_quest_chapters.sql: クエストモード 章追加（Chapter 2〜5）
-- - 既存クラスを章ボスとして登場させる PvE 拡充
-- - キャラID対応（002 が id 1〜16、008 が id 17〜 を class 単位で 20 体ずつ
--   witch→blade→architect→paladin→dominant→neutral の順、
--   各 class 内は queen/rook/bishop/knight/pawn × SSR/SR/R/N の順で連番）
--
--   ブロック先頭ID:  witch=17  blade=37  architect=57  paladin=77  dominant=97  neutral=117
--   ブロック内オフセット:
--     SSR: queen+0  rook+4  bishop+8  knight+12 pawn+16
--     SR : queen+1  rook+5  bishop+9  knight+13 pawn+17
--     R  : queen+2  rook+6  bishop+10 knight+14 pawn+18
--   キングは 008 に存在しないため neutral(002) を使用： 老王=15(R) / 不死の王=16(SSR)

USE tabula_piece;

INSERT INTO quests
  (chapter, stage, name,                     description,                                                  difficulty, cpu_deck_class, cpu_deck_json,                                                                  reward_gem, reward_character_id, sort_order)
VALUES
  -- ================= Chapter 2: 剣士の道場（ブレード） =================
  (2, 1, '見習い剣士の挑戦',   'ブレードの基礎を学んだ剣士見習いと対戦する。',
     'normal', 'blade',
     '{"pawn":55,"knight":51,"bishop":47,"rook":43,"queen":39,"king":15}',
     80,  NULL, 10),
  (2, 2, '熟練の剣士団',       'SR級の剣技を操る剣士団。攻めの圧力に注意。',
     'normal', 'blade',
     '{"pawn":54,"knight":50,"bishop":46,"rook":42,"queen":38,"king":15}',
     120, NULL, 20),
  (2, 3, '章ボス: 覇刃の剣士', 'ブレードクラスの頂点に立つ剣豪。初回クリアで「覇刃の剣士・ガルディオス」を解放。',
     'hard',   'blade',
     '{"pawn":53,"knight":49,"bishop":45,"rook":41,"queen":37,"king":16}',
     250, 37,   30),

  -- ================= Chapter 3: 罠の迷宮（アーキテクト） =================
  (3, 1, '罠師の試し',         'アーキテクトの罠と要塞に挑む。盤面支配に慣れよう。',
     'normal', 'architect',
     '{"pawn":75,"knight":71,"bishop":67,"rook":63,"queen":59,"king":15}',
     100, NULL, 10),
  (3, 2, '築城師の防衛線',     'SR級の罠と要塞で固めた防衛線を突破せよ。',
     'normal', 'architect',
     '{"pawn":74,"knight":70,"bishop":66,"rook":62,"queen":58,"king":15}',
     150, NULL, 20),
  (3, 3, '章ボス: 大設計師',   'アーキテクトの大成者。迷宮罠と要塞が立ちはだかる。初回クリアで「大設計師・ラビリンス」を解放。',
     'hard',   'architect',
     '{"pawn":73,"knight":69,"bishop":65,"rook":61,"queen":57,"king":16}',
     300, 57,   30),

  -- ================= Chapter 4: 聖騎士の聖堂（パラディン） =================
  (4, 1, '見習い聖騎士',       'パラディンの回復とシールドで粘る相手。継戦に備えよ。',
     'normal', 'paladin',
     '{"pawn":95,"knight":91,"bishop":87,"rook":83,"queen":79,"king":15}',
     120, NULL, 10),
  (4, 2, '守護騎士団',         'SR級の守護と鼓舞で味方を固める騎士団。',
     'normal', 'paladin',
     '{"pawn":94,"knight":90,"bishop":86,"rook":82,"queen":78,"king":15}',
     180, NULL, 20),
  (4, 3, '章ボス: 聖騎士長',   'パラディンを統べる聖騎士長。復活と聖光で長期戦を強いる。初回クリアで「聖騎士長・セラフィム」を解放。',
     'hard',   'paladin',
     '{"pawn":93,"knight":89,"bishop":85,"rook":81,"queen":77,"king":16}',
     350, 77,   30),

  -- ================= Chapter 5: 支配者の玉座（ドミナント） =================
  (5, 1, '工作員の襲撃',       'ドミナントの妨害と奪取に挑む上級章の入り口。',
     'normal', 'dominant',
     '{"pawn":115,"knight":111,"bishop":107,"rook":103,"queen":99,"king":15}',
     150, NULL, 10),
  (5, 2, '鎖の拘束陣',         'SR級の拘束とコピーで盤面を乱す陣形。',
     'hard',   'dominant',
     '{"pawn":114,"knight":110,"bishop":106,"rook":102,"queen":98,"king":15}',
     220, NULL, 20),
  (5, 3, '最終章ボス: 支配の女王', '全クラスの頂点に立つ支配の女王。奪取と支配で盤面を制圧する最強CPU。初回クリアで「支配の女王・ドミナ」を解放。',
     'hard',   'dominant',
     '{"pawn":113,"knight":109,"bishop":105,"rook":101,"queen":97,"king":16}',
     500, 97,   30);
