<?php
/**
 * Tabula-Piece チェスロジック
 * Phase 4: スキルシステム追加
 * Phase 11: クラススキル対応
 */

class Chess {

    const COLS = ['a','b','c','d','e','f'];

    const PIECE_VALUES = [
        'pawn'   => 1,
        'knight' => 3,
        'bishop' => 3,
        'rook'   => 5,
        'queen'  => 9,
        'king'   => 0,
    ];

    // ── オリジナルスキルID ────────────────────────────────────
    const SKILL_REMATCH     = 1;
    const SKILL_TELEPORT    = 2;
    const SKILL_FIRST_MOVE  = 3;
    const SKILL_SHIELD      = 4;
    const SKILL_ENHANCE     = 5;
    const SKILL_COUNTER     = 6;
    const SKILL_VALUE_UP    = 7;
    const SKILL_DEATH_CURSE = 8;
    const SKILL_TRAP        = 9;
    const SKILL_SANCTUARY   = 10;

    // ── クラススキルID ────────────────────────────────────────
    const SKILL_HOLY_WALL         = 11;
    const SKILL_HOLY_WALL_2       = 12;
    const SKILL_HOLY_AURA         = 13;
    const SKILL_DIVINE_PROTECTION = 14;
    const SKILL_REVIVE            = 15;
    const SKILL_REVIVE_SHIELD     = 16;
    const SKILL_SACRIFICE         = 17;
    const SKILL_INSPIRE           = 18;
    const SKILL_INSPIRE_STRONG    = 19;
    const SKILL_INSPIRE_ALL       = 20;
    const SKILL_PENETRATE         = 21;
    const SKILL_CLEAVE            = 22;
    const SKILL_DOUBLE_STRIKE     = 23;
    const SKILL_PIERCING_SLASH    = 24;
    const SKILL_SHADOW_CUT        = 25;
    const SKILL_SWAMP_WAVE        = 26;
    const SKILL_AREA_THRUST       = 27;
    const SKILL_EXPLOSION         = 28;
    const SKILL_LIGHTNING_SLASH   = 29;
    const SKILL_VALUE_DOWN_SM     = 30;
    const SKILL_VALUE_DOWN_MD     = 31;
    const SKILL_VALUE_DOWN_LG     = 32;
    const SKILL_DECOY             = 33;
    const SKILL_CURSED_TRADE      = 34;
    const SKILL_VALUE_GAIN_SM     = 35;
    const SKILL_VALUE_GAIN_MD     = 36;
    const SKILL_VALUE_GRANT_MD    = 37;
    const SKILL_VALUE_GRANT_LG    = 38;
    const SKILL_FULL_CURSE        = 39;
    const SKILL_SWAMP_GRACE       = 40;
    const SKILL_STUN_TRAP         = 41;
    const SKILL_STUN_TRAP_STRONG  = 42;
    const SKILL_FORCE_MOVE_TRAP   = 43;
    const SKILL_MAZE_TRAP         = 44;
    const SKILL_PHANTOM_TRAP      = 45;
    const SKILL_WIDE_TRAP         = 46;
    const SKILL_FORTRESS          = 47;
    const SKILL_FORTRESS_LARGE    = 48;
    const SKILL_FORTRESS_SELF     = 49;
    const SKILL_TRAP_SENSE        = 50;
    const SKILL_DOMINATE          = 51;
    const SKILL_CHARM_LONG        = 52;
    const SKILL_CHARM             = 53;
    const SKILL_DISABLE           = 54;
    const SKILL_HINDER            = 55;
    const SKILL_CHAIN_STRONG      = 56;
    const SKILL_CHAIN             = 57;
    const SKILL_FORCE_MOVE        = 58;
    const SKILL_DOMINATION_MEMORY = 59;
    const SKILL_SKILL_STEAL       = 60;
    const SKILL_MIMIC_WEAK        = 61;
    const SKILL_FULL_MIMIC        = 62;
    const SKILL_WAR_TROPHY        = 63;
    const SKILL_DOMINATION_ECHO   = 64;
    const SKILL_ASSAULT_DOMINATE  = 65;
    const SKILL_INFECT            = 66;
    const SKILL_CURSE_REFLECT     = 67;
    const SKILL_STUN              = 68;
    const SKILL_STORM_STRIKE      = 69;
    const SKILL_HINDER_STRONG     = 70;
    const SKILL_TIMED_SANCTUARY   = 71;
    const SKILL_GUARD_REACTION    = 72;
    const SKILL_COUNTER_MOVE      = 73;

    const SKILL_MAX_USES = [
        self::SKILL_REMATCH           => 3,
        self::SKILL_TELEPORT          => 1,
        self::SKILL_SHIELD            => 2,
        self::SKILL_ENHANCE           => 2,
        self::SKILL_VALUE_UP          => 1,
        self::SKILL_TRAP              => 1,
        self::SKILL_HOLY_WALL         => 2,
        self::SKILL_HOLY_WALL_2       => 2,
        self::SKILL_REVIVE            => 1,
        self::SKILL_REVIVE_SHIELD     => 1,
        self::SKILL_SACRIFICE         => 1,
        self::SKILL_INSPIRE           => 2,
        self::SKILL_INSPIRE_STRONG    => 2,
        self::SKILL_INSPIRE_ALL       => 1,
        self::SKILL_CLEAVE            => 1,
        self::SKILL_DOUBLE_STRIKE     => 2,
        self::SKILL_PIERCING_SLASH    => 1,
        self::SKILL_SHADOW_CUT        => 1,
        self::SKILL_SWAMP_WAVE        => 1,
        self::SKILL_AREA_THRUST       => 1,
        self::SKILL_EXPLOSION         => 1,
        self::SKILL_LIGHTNING_SLASH   => 1,
        self::SKILL_VALUE_DOWN_SM     => 3,
        self::SKILL_VALUE_DOWN_MD     => 2,
        self::SKILL_VALUE_DOWN_LG     => 1,
        self::SKILL_DECOY             => 2,
        self::SKILL_CURSED_TRADE      => 2,
        self::SKILL_VALUE_GRANT_MD    => 2,
        self::SKILL_VALUE_GRANT_LG    => 1,
        self::SKILL_STUN_TRAP         => 2,
        self::SKILL_STUN_TRAP_STRONG  => 2,
        self::SKILL_FORCE_MOVE_TRAP   => 1,
        self::SKILL_MAZE_TRAP         => 1,
        self::SKILL_PHANTOM_TRAP      => 1,
        self::SKILL_WIDE_TRAP         => 1,
        self::SKILL_FORTRESS          => 2,
        self::SKILL_FORTRESS_LARGE    => 1,
        self::SKILL_FORTRESS_SELF     => 2,
        self::SKILL_DOMINATE          => 1,
        self::SKILL_CHARM_LONG        => 1,
        self::SKILL_CHARM             => 2,
        self::SKILL_DISABLE           => 2,
        self::SKILL_HINDER            => 2,
        self::SKILL_CHAIN_STRONG      => 1,
        self::SKILL_CHAIN             => 1,
        self::SKILL_FORCE_MOVE        => 2,
        self::SKILL_SKILL_STEAL       => 1,
        self::SKILL_MIMIC_WEAK        => 1,
        self::SKILL_FULL_MIMIC        => 1,
        self::SKILL_ASSAULT_DOMINATE  => 1,
        self::SKILL_INFECT            => 1,
        self::SKILL_STUN              => 1,
        self::SKILL_STORM_STRIKE      => 1,
        self::SKILL_HINDER_STRONG     => 2,
        self::SKILL_TIMED_SANCTUARY   => 2,
    ];

    const SKILL_DATA = [
        // オリジナル
        1  => ['name' => '再移動',           'type' => 'active',  'category' => 'move',   'description' => '駒を移動した後、もう1マス追加で移動できる',              'max_uses' => 3],
        2  => ['name' => 'テレポート',       'type' => 'active',  'category' => 'move',   'description' => '盤面上の任意の空きマスに瞬間移動する',                  'max_uses' => 1],
        3  => ['name' => '先手',             'type' => 'passive', 'category' => 'move',   'description' => 'ターン開始時に1マス先に移動してからターンを行える',      'max_uses' => null],
        4  => ['name' => 'シールド',         'type' => 'active',  'category' => 'combat', 'description' => '次に受ける捕獲を1回無効化する',                          'max_uses' => 2],
        5  => ['name' => '駒強化',           'type' => 'active',  'category' => 'combat', 'description' => '隣接する味方駒1体の価値をそのターン中+2する',            'max_uses' => 2],
        6  => ['name' => '反撃',             'type' => 'passive', 'category' => 'combat', 'description' => '捕獲される直前に、攻撃してきた駒を除去する',             'max_uses' => 1],
        7  => ['name' => '価値上昇',         'type' => 'active',  'category' => 'point',  'description' => 'この駒の価値をターン切れまで+3する',                     'max_uses' => 1],
        8  => ['name' => '死に際の呪い',     'type' => 'passive', 'category' => 'point',  'description' => '取られたとき、攻撃した駒の価値を-2する',                 'max_uses' => null],
        9  => ['name' => 'トラップ設置',     'type' => 'active',  'category' => 'board',  'description' => '指定マスにトラップを置く。踏んだ相手の駒を除去する',    'max_uses' => 1],
        10 => ['name' => '聖域',             'type' => 'passive', 'category' => 'board',  'description' => 'この駒が乗っているマスに相手は進入できない',             'max_uses' => null],
        // 支援・回復系
        11 => ['name' => '聖壁',             'type' => 'active',  'category' => 'combat', 'description' => '隣接する味方駒1体にシールドを付与する',                  'max_uses' => 2],
        12 => ['name' => '聖壁（2体）',      'type' => 'active',  'category' => 'combat', 'description' => '隣接する味方駒1体（最大2体）にシールドを付与する',       'max_uses' => 2],
        13 => ['name' => '聖光のオーラ',     'type' => 'passive', 'category' => 'combat', 'description' => 'ターン開始時、隣接する全味方駒にシールドを付与する',      'max_uses' => null],
        14 => ['name' => '加護',             'type' => 'passive', 'category' => 'combat', 'description' => '味方駒が取られた瞬間、隣接する味方駒1体にシールドを付与', 'max_uses' => null],
        15 => ['name' => '復活',             'type' => 'active',  'category' => 'combat', 'description' => '直近に取られた味方駒1体を隣接マスに復活させる',           'max_uses' => 1],
        16 => ['name' => '復活＋シールド',   'type' => 'active',  'category' => 'combat', 'description' => '直近に取られた味方駒1体を復活させ、シールドを付与する',   'max_uses' => 1],
        17 => ['name' => '自己犠牲',         'type' => 'active',  'category' => 'combat', 'description' => 'この駒を除去し、隣接する全味方駒にシールド＋移動+1付与',  'max_uses' => 1],
        // 移動バフ系
        18 => ['name' => '鼓舞',             'type' => 'active',  'category' => 'move',   'description' => '隣接する味方駒1体の移動量を2ターン+1する',               'max_uses' => 2],
        19 => ['name' => '鼓舞（強）',       'type' => 'active',  'category' => 'move',   'description' => '隣接する味方駒1体の移動量を2ターン+2する',               'max_uses' => 2],
        20 => ['name' => '鼓舞（全体）',     'type' => 'active',  'category' => 'move',   'description' => '隣接する全味方駒の移動量を2ターン+1する',                'max_uses' => 1],
        // 攻撃・貫通系
        21 => ['name' => '貫通',             'type' => 'passive', 'category' => 'combat', 'description' => 'シールドを無視して相手の駒を取ることができる',            'max_uses' => null],
        22 => ['name' => '覇斬',             'type' => 'active',  'category' => 'combat', 'description' => '移動先の周囲1マス全ての駒を除去する（キング除外）',       'max_uses' => 1],
        23 => ['name' => '連撃',             'type' => 'active',  'category' => 'combat', 'description' => '移動後、隣接する相手駒を1体追加で除去する',               'max_uses' => 2],
        24 => ['name' => '斬り込み',         'type' => 'active',  'category' => 'combat', 'description' => 'シールドを無視して相手の駒を取る',                       'max_uses' => 1],
        25 => ['name' => '影斬り',           'type' => 'active',  'category' => 'combat', 'description' => '2マス先の駒を取る（シールド無視、キング除外）',           'max_uses' => 1],
        26 => ['name' => '沼の波',           'type' => 'active',  'category' => 'combat', 'description' => '2マス先のキング以外の駒を取る',                          'max_uses' => 1],
        27 => ['name' => '範囲突き',         'type' => 'active',  'category' => 'combat', 'description' => '直線上の全ての駒を除去する（キング除外）',                'max_uses' => 1],
        28 => ['name' => '爆砕',             'type' => 'active',  'category' => 'combat', 'description' => 'この駒を除去し、周囲1マス全ての相手駒を除去する',         'max_uses' => 1],
        29 => ['name' => '神速斬り',         'type' => 'active',  'category' => 'move',   'description' => '通常移動後、ナイトの動きで1回追加移動して駒を取れる',    'max_uses' => 1],
        // 価値操作系
        30 => ['name' => '価値減少（小）',   'type' => 'active',  'category' => 'point',  'description' => '相手駒1体の価値を-1する',                               'max_uses' => 3],
        31 => ['name' => '価値減少（中）',   'type' => 'active',  'category' => 'point',  'description' => '相手駒1体の価値を-2する',                               'max_uses' => 2],
        32 => ['name' => '価値減少（大）',   'type' => 'active',  'category' => 'point',  'description' => '相手駒1体の価値を-3する',                               'max_uses' => 1],
        33 => ['name' => '価値減少（囮）',   'type' => 'active',  'category' => 'point',  'description' => '自駒の価値を-2する',                                    'max_uses' => 2],
        34 => ['name' => '呪縛の代償',       'type' => 'active',  'category' => 'point',  'description' => '相手駒1体の価値を-2し、自駒の価値を-1する',             'max_uses' => 2],
        35 => ['name' => '取得価値上昇（小）','type' => 'passive', 'category' => 'point',  'description' => 'この駒で相手駒を取ったとき、取得ポイント+1',            'max_uses' => null],
        36 => ['name' => '取得価値上昇（中）','type' => 'passive', 'category' => 'point',  'description' => 'この駒で相手駒を取ったとき、取得ポイント+2',            'max_uses' => null],
        37 => ['name' => '価値付与（中）',   'type' => 'active',  'category' => 'point',  'description' => '隣接する味方駒1体に2ターン「取得価値+2」を付与する',    'max_uses' => 2],
        38 => ['name' => '価値付与（大）',   'type' => 'active',  'category' => 'point',  'description' => '任意の味方駒1体に3ターン「取得価値+3」を付与する',      'max_uses' => 1],
        39 => ['name' => '全呪い',           'type' => 'passive', 'category' => 'point',  'description' => '取られたとき、相手の全駒の価値を-1する',                'max_uses' => null],
        40 => ['name' => '沼の加護',         'type' => 'passive', 'category' => 'combat', 'description' => 'ターン開始時、真上に隣接する駒にシールドを付与する',     'max_uses' => null],
        // 罠・設置系
        41 => ['name' => '行動封じ罠',       'type' => 'active',  'category' => 'board',  'description' => '隣接マスに罠を設置。踏んだ相手駒を1ターン行動封じ',     'max_uses' => 2],
        42 => ['name' => '行動封じ罠（強）', 'type' => 'active',  'category' => 'board',  'description' => '隣接マスに罠を設置。踏んだ相手駒を2ターン行動封じ',     'max_uses' => 2],
        43 => ['name' => '強制移動罠',       'type' => 'active',  'category' => 'board',  'description' => '隣接マスに罠を設置。踏んだ相手駒を強制移動させる',       'max_uses' => 1],
        44 => ['name' => '迷宮罠',           'type' => 'active',  'category' => 'board',  'description' => '隣接マスに罠を設置。踏んだ相手駒を3ターン行動封じ',     'max_uses' => 1],
        45 => ['name' => '幻影罠',           'type' => 'active',  'category' => 'board',  'description' => '隣接マスに罠。踏んだ相手駒を2ターン行動封じ＋強制移動', 'max_uses' => 1],
        46 => ['name' => '広域罠',           'type' => 'active',  'category' => 'board',  'description' => '隣接マスに2つ罠を設置。踏んだ相手駒を2ターン行動封じ',  'max_uses' => 1],
        47 => ['name' => '要塞設置',         'type' => 'active',  'category' => 'board',  'description' => '隣接マスに移動不可の盾駒を1体設置する',                 'max_uses' => 2],
        48 => ['name' => '要塞設置（大）',   'type' => 'active',  'category' => 'board',  'description' => '隣接マスに移動不可の盾駒を2体設置する',                 'max_uses' => 1],
        49 => ['name' => '要塞化',           'type' => 'active',  'category' => 'board',  'description' => 'この駒自身を1ターンの間移動不可の盾に変える',           'max_uses' => 2],
        50 => ['name' => '罠感知',           'type' => 'passive', 'category' => 'board',  'description' => '相手が設置した罠の位置を見ることができる',              'max_uses' => null],
        // 支配・コピー系
        51 => ['name' => '完全支配',         'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体を永続的に自駒として奪取する（キング除外）', 'max_uses' => 1],
        52 => ['name' => '魅了（長）',       'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体を2ターン自駒として操作できる（キング除外）', 'max_uses' => 1],
        53 => ['name' => '魅了',             'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体を1ターン自駒として操作できる（キング除外）', 'max_uses' => 2],
        54 => ['name' => '無能力化',         'type' => 'active',  'category' => 'combat', 'description' => '相手駒1体のスキルを2ターン封じる',                      'max_uses' => 2],
        55 => ['name' => '妨害',             'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体のスキルを1ターン封じる',              'max_uses' => 2],
        56 => ['name' => '鎖縛（強）',       'type' => 'active',  'category' => 'combat', 'description' => '相手駒1体のスキルを封じ、自陣側に3マス強制移動する',    'max_uses' => 1],
        57 => ['name' => '鎖縛',             'type' => 'active',  'category' => 'combat', 'description' => '相手駒1体のスキルを封じ、自陣側に2マス強制移動する',    'max_uses' => 1],
        58 => ['name' => '強制移動',         'type' => 'active',  'category' => 'combat', 'description' => '相手駒1体を隣接マスに強制移動する（キング除外）',        'max_uses' => 2],
        59 => ['name' => '支配の記憶',       'type' => 'passive', 'category' => 'combat', 'description' => '自駒で相手駒を取ったとき、その駒のパッシブをコピーする', 'max_uses' => null],
        60 => ['name' => '技の盗用',         'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体のアクティブスキルを1回コピーして使用', 'max_uses' => 1],
        61 => ['name' => '模倣（弱）',       'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体のパッシブスキルを2ターンコピーする',   'max_uses' => 1],
        62 => ['name' => '完全模倣',         'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒のアクティブ・パッシブ両方をコピーする',   'max_uses' => 1],
        63 => ['name' => '戦利の技',         'type' => 'passive', 'category' => 'combat', 'description' => '自駒で相手駒を取ったとき、その駒のアクティブを1回使用', 'max_uses' => null],
        64 => ['name' => '支配の余韻',       'type' => 'passive', 'category' => 'combat', 'description' => '奪取した駒が取られたとき、その駒のパッシブを1回発動',   'max_uses' => null],
        65 => ['name' => '急襲支配',         'type' => 'active',  'category' => 'combat', 'description' => '移動後、移動先の隣接相手駒1体を1ターン操作できる',      'max_uses' => 1],
        66 => ['name' => '感染',             'type' => 'active',  'category' => 'combat', 'description' => 'この駒を取った相手駒を1ターン行動不能にし強制移動する',  'max_uses' => 1],
        67 => ['name' => '反射の呪い',       'type' => 'passive', 'category' => 'combat', 'description' => '取られる直前に相手駒のパッシブを1回コピーして発動',      'max_uses' => null],
        68 => ['name' => '行動封じ',         'type' => 'active',  'category' => 'combat', 'description' => '相手駒1体を2ターン行動封じにする',                      'max_uses' => 1],
        69 => ['name' => '嵐撃',             'type' => 'active',  'category' => 'board',  'description' => '移動先の周囲1マスにいる相手駒を全て1マス強制移動する',  'max_uses' => 1],
        70 => ['name' => '妨害（強）',       'type' => 'active',  'category' => 'combat', 'description' => '隣接する相手駒1体のスキルを2ターン封じる',              'max_uses' => 2],
        71 => ['name' => '時限聖域',         'type' => 'active',  'category' => 'board',  'description' => '2ターンの間、自駒のいるマスへ相手は進入できなくなる',   'max_uses' => 2],
        72 => ['name' => '護衛反応',         'type' => 'passive', 'category' => 'combat', 'description' => '取られる直前に、隣接する味方駒1体にシールドを付与する', 'max_uses' => null],
        73 => ['name' => '反撃（強制移動）', 'type' => 'passive', 'category' => 'combat', 'description' => '取られる直前に、攻撃してきた相手駒を隣接マスへ強制移動', 'max_uses' => null],
    ];

    // ─── ボードヘルパー ──────────────────────────────────────

    public static function colIdx(string $sq): int {
        return (int)array_search($sq[0], self::COLS, true);
    }

    public static function rowNum(string $sq): int {
        return (int)$sq[1];
    }

    public static function toSq(int $c, int $r): string {
        return self::COLS[$c] . $r;
    }

    public static function inBounds(int $c, int $r): bool {
        return $c >= 0 && $c < 6 && $r >= 1 && $r <= 6;
    }

    public static function opponent(string $color): string {
        return $color === 'white' ? 'black' : 'white';
    }

    public static function isAdjacent(string $sq1, string $sq2): bool {
        $c1 = self::colIdx($sq1); $r1 = self::rowNum($sq1);
        $c2 = self::colIdx($sq2); $r2 = self::rowNum($sq2);
        return $sq1 !== $sq2 && abs($c1 - $c2) <= 1 && abs($r1 - $r2) <= 1;
    }

    public static function getAdjacentSquares(string $sq): array {
        $c = self::colIdx($sq); $r = self::rowNum($sq);
        $result = [];
        for ($dc = -1; $dc <= 1; $dc++) {
            for ($dr = -1; $dr <= 1; $dr++) {
                if ($dc === 0 && $dr === 0) continue;
                if (self::inBounds($c + $dc, $r + $dr)) {
                    $result[] = self::toSq($c + $dc, $r + $dr);
                }
            }
        }
        return $result;
    }

    // ─── 駒データ生成 ────────────────────────────────────────

    public static function makePiece(string $piece, string $color, array $extra = []): array {
        return array_merge([
            'piece'               => $piece,
            'color'               => $color,
            'character_id'        => null,
            'active_skill_id'     => null,
            'passive_skill_id'    => null,
            'active_used'         => 0,
            'passive_used'        => 0,
            'shield'              => false,
            'value_bonus'         => 0,
            'stunned_turns'       => 0,
            'skill_sealed_turns'  => 0,
            'move_bonus'          => 0,
            'move_bonus_turns'    => 0,
            'value_bonus_turns'   => 0,
            'charmed_by'          => null,
            'charmed_turns'       => 0,
            'fortress'            => false,
            'copied_active'       => null,
            'copied_passive'      => null,
            'copied_active_turns' => 0,
            'copied_passive_turns'=> 0,
        ], $extra);
    }

    // ─── 初期ボード ──────────────────────────────────────────

    public static function createInitialBoard(): array {
        $board      = [];
        $backPieces = ['rook','knight','bishop','queen','king'];

        foreach ($backPieces as $i => $piece) {
            $board[self::toSq($i, 1)] = self::makePiece($piece, 'white');
            $board[self::toSq($i, 6)] = self::makePiece($piece, 'black');
        }

        for ($i = 0; $i < 6; $i++) {
            $board[self::toSq($i, 2)] = self::makePiece('pawn', 'white');
            $board[self::toSq($i, 5)] = self::makePiece('pawn', 'black');
        }

        return $board;
    }

    // ─── board_json シリアライズ / デシリアライズ ─────────────

    public static function encodeGameData(array $state): string {
        return json_encode([
            'squares'           => $state['board'],
            'traps'             => $state['traps'] ?? [],
            'timed_traps'       => $state['timedTraps'] ?? [],
            'timed_sanctuaries' => $state['timedSanctuaries'] ?? [],
            'captured'          => $state['captured'] ?? ['white' => [], 'black' => []],
            'rematch_pending'   => $state['rematchPending'] ?? null,
            'skill_opportunity' => $state['skillOpportunity'] ?? null,
        ]);
    }

    public static function decodeGameData(string $json): array {
        $data = json_decode($json, true);
        if (isset($data['squares'])) {
            return [
                'board'            => $data['squares'],
                'traps'            => $data['traps'] ?? [],
                'timedTraps'       => $data['timed_traps'] ?? [],
                'timedSanctuaries' => $data['timed_sanctuaries'] ?? [],
                'captured'         => $data['captured'] ?? ['white' => [], 'black' => []],
                'rematchPending'   => $data['rematch_pending'] ?? null,
                'skillOpportunity' => $data['skill_opportunity'] ?? null,
            ];
        }
        return [
            'board'            => $data,
            'traps'            => [],
            'timedTraps'       => [],
            'timedSanctuaries' => [],
            'captured'         => ['white' => [], 'black' => []],
            'rematchPending'   => null,
            'skillOpportunity' => null,
        ];
    }

    // ─── クラス駒を盤面に適用 ─────────────────────────────────
    // $classPieces = [['board_col'=>0-5, 'board_row'=>0-2, 'character_id'=>int], ...]
    // $charMap = [charId => ['active_skill_id'=>?, 'passive_skill_id'=>?, 'piece_class'=>?]]

    public static function applyClassPiecesToBoard(array $board, array $classPieces, array $charMap, string $color): array {
        foreach ($classPieces as $cp) {
            $charId   = (int)$cp['character_id'];
            $boardCol = (int)$cp['board_col'];
            $boardRow = (int)$cp['board_row'];

            if (!isset($charMap[$charId])) continue;
            $ch = $charMap[$charId];

            // board_row → chess_row 変換
            $chessRow = $color === 'white' ? ($boardRow + 1) : (6 - $boardRow);
            $chessSq  = self::COLS[$boardCol] . $chessRow;

            if (isset($board[$chessSq])) {
                // 既存の駒にキャラクター・スキル情報を付与（標準駒と重なる場合）
                $board[$chessSq]['character_id']     = $charId;
                $board[$chessSq]['active_skill_id']  = $ch['active_skill_id'];
                $board[$chessSq]['passive_skill_id'] = $ch['passive_skill_id'];
            } else {
                // 空きマスに新しい駒を配置
                $pieceClass = $ch['piece_class'] ?? 'pawn';
                $board[$chessSq] = self::makePiece($pieceClass, $color, [
                    'character_id'     => $charId,
                    'active_skill_id'  => $ch['active_skill_id'],
                    'passive_skill_id' => $ch['passive_skill_id'],
                ]);
            }
        }
        return $board;
    }

    // ─── デッキを盤面に適用 ──────────────────────────────────

    public static function applyDeckToBoard(array $board, array $deck, array $chars, string $color): array {
        $backRow = $color === 'white' ? 1 : 6;
        $pawnRow = $color === 'white' ? 2 : 5;
        $colMap  = ['rook' => 'a', 'knight' => 'b', 'bishop' => 'c', 'queen' => 'd', 'king' => 'e'];

        foreach ($colMap as $class => $col) {
            $charId = $deck[$class] ?? null;
            if (!$charId || !isset($chars[$charId])) continue;
            $ch = $chars[$charId];
            $sq = $col . $backRow;
            if (isset($board[$sq])) {
                $board[$sq]['character_id']     = $charId;
                $board[$sq]['active_skill_id']  = $ch['active_skill_id'];
                $board[$sq]['passive_skill_id'] = $ch['passive_skill_id'];
            }
        }

        $pawnCharId = $deck['pawn'] ?? null;
        if ($pawnCharId && isset($chars[$pawnCharId])) {
            $ch = $chars[$pawnCharId];
            foreach (self::COLS as $col) {
                $sq = $col . $pawnRow;
                if (isset($board[$sq])) {
                    $board[$sq]['character_id']     = $pawnCharId;
                    $board[$sq]['active_skill_id']  = $ch['active_skill_id'];
                    $board[$sq]['passive_skill_id'] = $ch['passive_skill_id'];
                }
            }
        }

        return $board;
    }

    // ─── 移動後スキル機会チェック ─────────────────────────────

    public static function checkSkillOpportunity(array $board, string $sq, string $player): ?array {
        $piece = $board[$sq] ?? null;
        if (!$piece) return null;

        // スキル封印中は機会なし
        if ((int)($piece['skill_sealed_turns'] ?? 0) > 0) return null;

        $skillId = $piece['active_skill_id'] ?? null;
        if ($skillId === null) return null;

        $used    = (int)($piece['active_used'] ?? 0);
        $maxUses = self::SKILL_MAX_USES[$skillId] ?? null;
        if ($maxUses !== null && $used >= $maxUses) return null;

        return ['player' => $player, 'sq' => $sq, 'skill_id' => $skillId];
    }

    // ─── 疑似合法手生成 ──────────────────────────────────────

    public static function getPseudoMoves(array $board, string $sq): array {
        $p = $board[$sq] ?? null;
        if (!$p) return [];
        if ($p['fortress'] ?? false) return []; // 要塞は移動不可

        $c     = self::colIdx($sq);
        $r     = self::rowNum($sq);
        $moves = [];

        $addSlide = function(int $dc, int $dr) use ($board, $c, $r, $p, &$moves) {
            $nc = $c + $dc; $nr = $r + $dr;
            while (self::inBounds($nc, $nr)) {
                $t = $board[self::toSq($nc, $nr)] ?? null;
                if ($t) {
                    if ($t['color'] !== $p['color']) $moves[] = self::toSq($nc, $nr);
                    break;
                }
                $moves[] = self::toSq($nc, $nr);
                $nc += $dc; $nr += $dr;
            }
        };

        $addStep = function(int $dc, int $dr) use ($board, $c, $r, $p, &$moves) {
            $nc = $c + $dc; $nr = $r + $dr;
            if (!self::inBounds($nc, $nr)) return;
            $t = $board[self::toSq($nc, $nr)] ?? null;
            if (!$t || $t['color'] !== $p['color']) $moves[] = self::toSq($nc, $nr);
        };

        switch ($p['piece']) {
            case 'pawn':
                $dir = $p['color'] === 'white' ? 1 : -1;
                $nr  = $r + $dir;
                if (self::inBounds($c, $nr) && empty($board[self::toSq($c, $nr)])) {
                    $moves[] = self::toSq($c, $nr);
                }
                foreach ([-1, 1] as $dc) {
                    if (!self::inBounds($c + $dc, $nr)) continue;
                    $t = $board[self::toSq($c + $dc, $nr)] ?? null;
                    if ($t && $t['color'] !== $p['color']) $moves[] = self::toSq($c + $dc, $nr);
                }
                break;
            case 'knight':
                foreach ([[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]] as [$dc,$dr]) {
                    $addStep($dc, $dr);
                }
                break;
            case 'bishop':
                foreach ([[-1,-1],[-1,1],[1,-1],[1,1]] as [$dc,$dr]) $addSlide($dc, $dr);
                break;
            case 'rook':
                foreach ([[-1,0],[1,0],[0,-1],[0,1]] as [$dc,$dr]) $addSlide($dc, $dr);
                break;
            case 'queen':
                foreach ([[-1,-1],[-1,1],[1,-1],[1,1],[-1,0],[1,0],[0,-1],[0,1]] as [$dc,$dr]) {
                    $addSlide($dc, $dr);
                }
                break;
            case 'king':
                foreach ([[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]] as [$dc,$dr]) {
                    $addStep($dc, $dr);
                }
                break;
        }

        return $moves;
    }

    // ─── 合法手（聖域スキル・要塞考慮）──────────────────────

    public static function getLegalMoves(array $board, string $sq, array $traps = [], array $timedSanctuaries = []): array {
        $piece = $board[$sq] ?? null;
        if (!$piece) return [];

        $moves         = self::getPseudoMoves($board, $sq);
        $opponentColor = self::opponent($piece['color']);

        // 聖域: 相手の駒が聖域パッシブを持つマスは進入不可
        $sanctified = [];
        foreach ($board as $oSq => $op) {
            if ($op && $op['color'] === $opponentColor &&
                ($op['passive_skill_id'] ?? null) === self::SKILL_SANCTUARY) {
                $sanctified[$oSq] = true;
            }
        }
        // 時限聖域
        foreach ($timedSanctuaries as $tsSq => $info) {
            if (($info['color'] ?? null) !== $piece['color']) {
                $sanctified[$tsSq] = true;
            }
        }

        // 要塞駒のマスには進入不可（占有もできない）
        $fortressSqs = [];
        foreach ($board as $fSq => $fp) {
            if ($fp && ($fp['fortress'] ?? false) && $fp['color'] !== $piece['color']) {
                $fortressSqs[$fSq] = true;
            }
        }

        if ($sanctified || $fortressSqs) {
            $blocked = array_merge($sanctified, $fortressSqs);
            $moves   = array_values(array_filter($moves, fn($m) => !isset($blocked[$m])));
        }

        return $moves;
    }

    // ─── 移動適用（パッシブスキル発動）──────────────────────

    public static function applyMove(array $board, string $from, string $to, array &$traps = [], array &$timedTraps = [], array &$captured = []): array {
        $piece  = $board[$from];
        $target = $board[$to] ?? null;

        // 貫通: 攻撃側がパッシブ21を持つ場合、シールドを無視
        $hasPenetrate = ($piece['passive_skill_id'] ?? null) === self::SKILL_PENETRATE
                     || ($piece['copied_passive'] ?? null) === self::SKILL_PENETRATE;

        // シールド: 相手の駒がシールド発動中 → 捕獲無効（貫通で無効化）
        if ($target && $target['color'] !== $piece['color'] && ($target['shield'] ?? false) && !$hasPenetrate) {
            $board[$to]['shield'] = false;
            return $board;
        }

        // 反撃: 捕獲される直前に攻撃者を除去
        if ($target && $target['color'] !== $piece['color']) {
            $passiveId   = $target['passive_skill_id'] ?? null;
            $passiveUsed = (int)($target['passive_used'] ?? 0);
            $maxUses     = self::SKILL_MAX_USES[self::SKILL_COUNTER] ?? 1;
            if ($passiveId === self::SKILL_COUNTER && $passiveUsed < $maxUses) {
                unset($board[$from]);
                $board[$to]['passive_used'] = $passiveUsed + 1;
                return $board;
            }

            // 反撃（強制移動）: 取られる直前に攻撃者を隣接マスへ強制移動
            if ($passiveId === self::SKILL_COUNTER_MOVE) {
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    if (!isset($board[$adjSq])) {
                        $movingPiece = $board[$from];
                        unset($board[$from]);
                        $board[$adjSq] = $movingPiece;
                        // 守備側が除去される通常処理へ続く
                        break;
                    }
                }
                // 隣接に空きがなければ通常処理
            }

            // 護衛反応: 取られる直前に隣接する味方駒1体にシールド付与
            if (($target['passive_skill_id'] ?? null) === self::SKILL_GUARD_REACTION) {
                $adjSqs = self::getAdjacentSquares($to);
                foreach ($adjSqs as $adjSq) {
                    $neighbor = $board[$adjSq] ?? null;
                    if ($neighbor && $neighbor['color'] === $target['color']) {
                        $board[$adjSq]['shield'] = true;
                        break;
                    }
                }
            }

            // 反射の呪い: 取られる直前に攻撃者のパッシブを1回コピーして発動
            // （パッシブのコピー発動は複雑なためスキップ）
        }

        // 通常の捕獲・移動
        unset($board[$from]);

        // 取得価値上昇: 攻撃側がパッシブ35/36を持つ場合、取得ポイントを上乗せ
        if ($target && $target['color'] !== $piece['color']) {
            $gainPassive = $piece['passive_skill_id'] ?? $piece['copied_passive'] ?? null;
            if ($gainPassive === self::SKILL_VALUE_GAIN_SM) {
                $piece['value_bonus'] = (int)($piece['value_bonus'] ?? 0) + 1;
            } elseif ($gainPassive === self::SKILL_VALUE_GAIN_MD) {
                $piece['value_bonus'] = (int)($piece['value_bonus'] ?? 0) + 2;
            }

            // 戦利の技: 取った駒のアクティブスキルを1回コピー
            if (($piece['passive_skill_id'] ?? null) === self::SKILL_WAR_TROPHY && $target['active_skill_id']) {
                $piece['copied_active']       = $target['active_skill_id'];
                $piece['copied_active_turns'] = 1;
            }

            // 支配の記憶: 取った駒のパッシブをコピー
            if (($piece['passive_skill_id'] ?? null) === self::SKILL_DOMINATION_MEMORY && $target['passive_skill_id']) {
                $piece['copied_passive']       = $target['passive_skill_id'];
                $piece['copied_passive_turns'] = 0; // 永続
            }

            // 死に際の呪い: 取られる駒がこのパッシブを持つ場合
            if (($target['passive_skill_id'] ?? null) === self::SKILL_DEATH_CURSE) {
                $piece['value_bonus'] = (int)($piece['value_bonus'] ?? 0) - 2;
            }

            // 全呪い: 取られたとき相手の全駒の価値を-1
            if (($target['passive_skill_id'] ?? null) === self::SKILL_FULL_CURSE) {
                foreach ($board as $sq => $p) {
                    if ($p && $p['color'] === $piece['color']) {
                        $board[$sq]['value_bonus'] = (int)($board[$sq]['value_bonus'] ?? 0) - 1;
                    }
                }
            }

            // 捕獲された駒を記録（復活スキル用）
            $capturedColor = $target['color'];
            // charmed_by がある場合は元の色に記録
            $originalColor = $target['charmed_by'] ?? $capturedColor;
            if (!isset($captured[$originalColor])) $captured[$originalColor] = [];
            array_unshift($captured[$originalColor], $target);
            if (count($captured[$originalColor]) > 10) {
                $captured[$originalColor] = array_slice($captured[$originalColor], 0, 10);
            }
        }

        // ポーン成り
        if ($piece['piece'] === 'pawn') {
            if (($piece['color'] === 'white' && self::rowNum($to) === 6) ||
                ($piece['color'] === 'black' && self::rowNum($to) === 1)) {
                $piece['piece'] = 'queen';
            }
        }

        $board[$to] = $piece;

        // 通常トラップ: 着地マスに相手のトラップがある場合、駒を除去
        if (isset($traps[$to])) {
            if ($traps[$to] !== $piece['color']) {
                unset($board[$to]);
            }
            unset($traps[$to]);
        }

        // タイムドトラップ: 着地マスにタイムドトラップがある場合
        if (isset($timedTraps[$to]) && isset($board[$to])) {
            $trap = $timedTraps[$to];
            if (($trap['color'] ?? '') !== $board[$to]['color']) {
                $stunTurns = $trap['stun_turns'] ?? 0;
                if ($stunTurns > 0 && isset($board[$to])) {
                    $board[$to]['stunned_turns'] = max($board[$to]['stunned_turns'] ?? 0, $stunTurns);
                }
                if (($trap['force_move'] ?? false) && isset($board[$to])) {
                    $movedPiece = $board[$to];
                    unset($board[$to]);
                    $adjSqs = self::getAdjacentSquares($to);
                    foreach ($adjSqs as $adjSq) {
                        if (!isset($board[$adjSq])) {
                            $board[$adjSq] = $movedPiece;
                            break;
                        }
                    }
                    if (!isset($board[$adjSq])) {
                        // 空き隣接マスがなければその場に残す
                        $board[$to] = $movedPiece;
                    }
                }
            }
            unset($timedTraps[$to]);
        }

        return $board;
    }

    // ─── スキル発動検証 ──────────────────────────────────────

    public static function validateSkill(array $state, string $player, string $from, int $skillId, ?string $target): array {
        if ($state['status'] !== 'in_progress') {
            return ['valid' => false, 'reason' => '試合は終了しています'];
        }

        $opportunity = $state['skillOpportunity'] ?? null;

        if ($opportunity) {
            if ($opportunity['player'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
            if ($opportunity['sq'] !== $from) {
                return ['valid' => false, 'reason' => 'スキル機会の駒のみ発動できます'];
            }
            if ($opportunity['skill_id'] !== $skillId) {
                return ['valid' => false, 'reason' => 'そのスキルは今発動できません'];
            }
        } else {
            $rematch = $state['rematchPending'] ?? null;
            if ($rematch) {
                return ['valid' => false, 'reason' => '再移動が保留中です。移動を行ってください'];
            }
            if ($state['currentPlayer'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
        }

        $board = $state['board'];
        $piece = $board[$from] ?? null;
        if (!$piece) return ['valid' => false, 'reason' => "{$from} に駒がありません"];

        $color = $player === 'player1' ? 'white' : 'black';
        if ($piece['color'] !== $color) return ['valid' => false, 'reason' => '自分の駒ではありません'];

        // スキル封印チェック
        if ((int)($piece['skill_sealed_turns'] ?? 0) > 0) {
            return ['valid' => false, 'reason' => 'スキルが封印されています'];
        }

        // 駒が持つアクティブスキル or コピースキルか確認
        $pieceSkillId = $piece['active_skill_id'] ?? null;
        $copiedSkillId = $piece['copied_active'] ?? null;
        if ($pieceSkillId !== $skillId && $copiedSkillId !== $skillId) {
            return ['valid' => false, 'reason' => 'その駒はそのスキルを持っていません'];
        }

        $used    = (int)($piece['active_used'] ?? 0);
        $maxUses = self::SKILL_MAX_USES[$skillId] ?? null;
        if ($maxUses !== null && $used >= $maxUses) {
            return ['valid' => false, 'reason' => 'スキルの使用回数が上限に達しています'];
        }

        return ['valid' => true];
    }

    // ─── スキル実行 ──────────────────────────────────────────

    public static function executeSkill(array $state, string $player, string $from, int $skillId, ?string $target): array {
        $check = self::validateSkill($state, $player, $from, $skillId, $target);
        if (!$check['valid']) throw new RuntimeException($check['reason']);

        $board          = $state['board'];
        $traps          = $state['traps'] ?? [];
        $timedTraps     = $state['timedTraps'] ?? [];
        $timedSanct     = $state['timedSanctuaries'] ?? [];
        $captured       = $state['captured'] ?? ['white' => [], 'black' => []];
        $color          = $player === 'player1' ? 'white' : 'black';
        $opponentColor  = self::opponent($color);
        $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
        $advanceTurn    = true;
        $rematchPending = null;

        switch ($skillId) {

            // ─── オリジナルスキル ─────────────────────────────
            case self::SKILL_SHIELD:
                $board[$from]['shield'] = true;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_UP:
                $board[$from]['value_bonus'] = (int)($board[$from]['value_bonus'] ?? 0) + 3;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_ENHANCE:
                if (!$target) throw new RuntimeException('強化対象のマスを指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp) throw new RuntimeException('対象のマスに駒がありません');
                if ($tp['color'] !== $color) throw new RuntimeException('味方の駒のみ強化できます');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) + 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_TELEPORT:
                if (!$target) throw new RuntimeException('移動先のマスを指定してください');
                if (isset($board[$target])) throw new RuntimeException('移動先に駒があります');
                $p = $board[$from];
                $p['active_used']++;
                unset($board[$from]);
                $board[$target] = $p;
                break;

            case self::SKILL_TRAP:
                if (!$target) throw new RuntimeException('トラップを置くマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('トラップは隣接するマスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスにはトラップを置けません');
                $traps[$target] = $color;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_REMATCH:
                $board[$from]['active_used']++;
                $rematchPending = ['player' => $player, 'sq' => $from];
                $advanceTurn    = false;
                break;

            // ─── 支援・回復系 ─────────────────────────────────
            case self::SKILL_HOLY_WALL:
                // 隣接する味方駒1体にシールド付与
                if (!$target) throw new RuntimeException('シールドを付与する味方駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp) throw new RuntimeException('対象のマスに駒がありません');
                if ($tp['color'] !== $color) throw new RuntimeException('味方の駒のみ対象にできます');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['shield'] = true;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_HOLY_WALL_2:
                // 隣接する味方駒2体にシールド付与（target は "sq1,sq2" または省略で全隣接）
                $targets = $target ? explode(',', $target) : [];
                if ($targets) {
                    foreach ($targets as $tSq) {
                        $tSq = trim($tSq);
                        $tp = $board[$tSq] ?? null;
                        if ($tp && $tp['color'] === $color && self::isAdjacent($from, $tSq)) {
                            $board[$tSq]['shield'] = true;
                        }
                    }
                } else {
                    $adjSqs = self::getAdjacentSquares($from);
                    $count  = 0;
                    foreach ($adjSqs as $adjSq) {
                        if ($count >= 2) break;
                        $adj = $board[$adjSq] ?? null;
                        if ($adj && $adj['color'] === $color) {
                            $board[$adjSq]['shield'] = true;
                            $count++;
                        }
                    }
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_REVIVE:
            case self::SKILL_REVIVE_SHIELD:
                // 直近に取られた味方駒を隣接マスに復活
                if (!$target) throw new RuntimeException('復活先のマスを指定してください');
                if (isset($board[$target])) throw new RuntimeException('復活先に駒があります');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('復活先は隣接マスにしてください');
                if (empty($captured[$color])) throw new RuntimeException('復活できる駒がありません');
                $revived = array_shift($captured[$color]);
                // 復活した駒をリセット（スタン等をクリア）
                $revived['stunned_turns']     = 0;
                $revived['skill_sealed_turns']= 0;
                $revived['charmed_by']        = null;
                $revived['charmed_turns']     = 0;
                $revived['color']             = $color;
                $revived['active_used']       = 0;
                if ($skillId === self::SKILL_REVIVE_SHIELD) {
                    $revived['shield'] = true;
                }
                $board[$target] = $revived;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_SACRIFICE:
                // この駒を除去し、隣接する全味方駒にシールド付与＋move_bonus+1（2ターン）
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if ($adj && $adj['color'] === $color) {
                        $board[$adjSq]['shield']          = true;
                        $board[$adjSq]['move_bonus']      = (int)($board[$adjSq]['move_bonus'] ?? 0) + 1;
                        $board[$adjSq]['move_bonus_turns']= max(2, (int)($board[$adjSq]['move_bonus_turns'] ?? 0));
                    }
                }
                unset($board[$from]);
                // 自己犠牲で駒を失うため captured には追加しない
                break;

            // ─── 移動バフ系 ───────────────────────────────────
            case self::SKILL_INSPIRE:
                if (!$target) throw new RuntimeException('鼓舞する味方駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] !== $color) throw new RuntimeException('味方の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['move_bonus']       = (int)($board[$target]['move_bonus'] ?? 0) + 1;
                $board[$target]['move_bonus_turns']  = max(2, (int)($board[$target]['move_bonus_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_INSPIRE_STRONG:
                if (!$target) throw new RuntimeException('鼓舞する味方駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] !== $color) throw new RuntimeException('味方の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['move_bonus']       = (int)($board[$target]['move_bonus'] ?? 0) + 2;
                $board[$target]['move_bonus_turns']  = max(2, (int)($board[$target]['move_bonus_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_INSPIRE_ALL:
                // 隣接する全味方駒に move_bonus +1
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if ($adj && $adj['color'] === $color) {
                        $board[$adjSq]['move_bonus']       = (int)($board[$adjSq]['move_bonus'] ?? 0) + 1;
                        $board[$adjSq]['move_bonus_turns']  = max(2, (int)($board[$adjSq]['move_bonus_turns'] ?? 0));
                    }
                }
                $board[$from]['active_used']++;
                break;

            // ─── 攻撃・貫通系 ────────────────────────────────
            case self::SKILL_CLEAVE:
                // 移動先の周囲1マス全ての駒を除去（キング除外）
                // from = 既に移動後の位置（スキル機会として発動）
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if ($adj && $adj['piece'] !== 'king') {
                        if (!isset($captured[$adj['color']])) $captured[$adj['color']] = [];
                        array_unshift($captured[$adj['color']], $adj);
                        unset($board[$adjSq]);
                    }
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_DOUBLE_STRIKE:
                // 隣接する相手駒を1体追加で除去
                if (!$target) throw new RuntimeException('追加攻撃する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                if (!isset($captured[$tp['color']])) $captured[$tp['color']] = [];
                array_unshift($captured[$tp['color']], $tp);
                unset($board[$target]);
                $board[$from]['active_used']++;
                break;

            case self::SKILL_PIERCING_SLASH:
                // シールドを無視して相手の駒を取る
                if (!$target) throw new RuntimeException('攻撃する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                // シールド無視・反撃無視で捕獲
                if (!isset($captured[$tp['color']])) $captured[$tp['color']] = [];
                array_unshift($captured[$tp['color']], $tp);
                unset($board[$target]);
                $p = $board[$from];
                unset($board[$from]);
                $board[$target] = $p;
                $board[$target]['active_used']++;
                break;

            case self::SKILL_SHADOW_CUT:
            case self::SKILL_SWAMP_WAVE:
                // 2マス先の駒を取る（キング除外）
                if (!$target) throw new RuntimeException('攻撃先のマスを指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒がありません');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは対象にできません');
                $fc = self::colIdx($from); $fr = self::rowNum($from);
                $tc = self::colIdx($target); $tr = self::rowNum($target);
                $dist = max(abs($tc - $fc), abs($tr - $fr));
                if ($dist !== 2) throw new RuntimeException('2マス先の駒のみ対象にできます');
                if (!isset($captured[$tp['color']])) $captured[$tp['color']] = [];
                array_unshift($captured[$tp['color']], $tp);
                unset($board[$target]);
                $board[$from]['active_used']++;
                break;

            case self::SKILL_AREA_THRUST:
                // 直線上の全ての駒を除去（キング除外）
                if (!$target) throw new RuntimeException('方向を指定するマスを指定してください');
                $fc = self::colIdx($from); $fr = self::rowNum($from);
                $tc = self::colIdx($target); $tr = self::rowNum($target);
                $dc = $tc - $fc; $dr = $tr - $fr;
                // 正規化
                $mag = max(abs($dc), abs($dr));
                if ($mag === 0) throw new RuntimeException('対象マスを指定してください');
                $ndc = (int)($dc / $mag); $ndr = (int)($dr / $mag);
                $nc = $fc + $ndc; $nr = $fr + $ndr;
                while (self::inBounds($nc, $nr)) {
                    $sq  = self::toSq($nc, $nr);
                    $adj = $board[$sq] ?? null;
                    if ($adj && $adj['piece'] !== 'king') {
                        if (!isset($captured[$adj['color']])) $captured[$adj['color']] = [];
                        array_unshift($captured[$adj['color']], $adj);
                        unset($board[$sq]);
                    }
                    $nc += $ndc; $nr += $ndr;
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_EXPLOSION:
                // この駒を除去し、周囲1マス全ての相手駒を除去
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if ($adj && $adj['color'] !== $color && $adj['piece'] !== 'king') {
                        if (!isset($captured[$adj['color']])) $captured[$adj['color']] = [];
                        array_unshift($captured[$adj['color']], $adj);
                        unset($board[$adjSq]);
                    }
                }
                unset($board[$from]);
                break;

            case self::SKILL_LIGHTNING_SLASH:
                // 神速斬り: 実装省略（移動後のナイト追加移動は複雑）
                $board[$from]['active_used']++;
                break;

            // ─── 価値操作系 ──────────────────────────────────
            case self::SKILL_VALUE_DOWN_SM:
                if (!$target) throw new RuntimeException('対象の相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) - 1;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_DOWN_MD:
                if (!$target) throw new RuntimeException('対象の相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) - 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_DOWN_LG:
                if (!$target) throw new RuntimeException('対象の相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) - 3;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_DECOY:
                $board[$from]['value_bonus'] = (int)($board[$from]['value_bonus'] ?? 0) - 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_CURSED_TRADE:
                if (!$target) throw new RuntimeException('対象の相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['value_bonus'] = (int)($board[$target]['value_bonus'] ?? 0) - 2;
                $board[$from]['value_bonus']   = (int)($board[$from]['value_bonus'] ?? 0) - 1;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_GRANT_MD:
                if (!$target) throw new RuntimeException('価値を付与する味方駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] !== $color) throw new RuntimeException('味方の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['value_bonus']       = (int)($board[$target]['value_bonus'] ?? 0) + 2;
                $board[$target]['value_bonus_turns']  = 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_VALUE_GRANT_LG:
                if (!$target) throw new RuntimeException('価値を付与する味方駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] !== $color) throw new RuntimeException('味方の駒を指定してください');
                $board[$target]['value_bonus']       = (int)($board[$target]['value_bonus'] ?? 0) + 3;
                $board[$target]['value_bonus_turns']  = 3;
                $board[$from]['active_used']++;
                break;

            // ─── 罠・設置系 ──────────────────────────────────
            case self::SKILL_STUN_TRAP:
            case self::SKILL_STUN_TRAP_STRONG:
            case self::SKILL_MAZE_TRAP:
                $stunTurns = [
                    self::SKILL_STUN_TRAP        => 1,
                    self::SKILL_STUN_TRAP_STRONG => 2,
                    self::SKILL_MAZE_TRAP        => 3,
                ][$skillId];
                if (!$target) throw new RuntimeException('罠を置くマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('罠は隣接マスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスには設置できません');
                $timedTraps[$target] = ['color' => $color, 'stun_turns' => $stunTurns, 'force_move' => false];
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FORCE_MOVE_TRAP:
                if (!$target) throw new RuntimeException('罠を置くマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('罠は隣接マスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスには設置できません');
                $timedTraps[$target] = ['color' => $color, 'stun_turns' => 0, 'force_move' => true];
                $board[$from]['active_used']++;
                break;

            case self::SKILL_PHANTOM_TRAP:
                if (!$target) throw new RuntimeException('罠を置くマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('罠は隣接マスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスには設置できません');
                $timedTraps[$target] = ['color' => $color, 'stun_turns' => 2, 'force_move' => true];
                $board[$from]['active_used']++;
                break;

            case self::SKILL_WIDE_TRAP:
                // 隣接する2マスに同時に罠設置（target = "sq1,sq2"）
                if (!$target) throw new RuntimeException('2マスを "sq1,sq2" の形式で指定してください');
                $wTargets = explode(',', $target);
                if (count($wTargets) < 2) throw new RuntimeException('2マスを指定してください');
                foreach (array_slice($wTargets, 0, 2) as $wSq) {
                    $wSq = trim($wSq);
                    if (!self::isAdjacent($from, $wSq)) throw new RuntimeException('罠は隣接マスにのみ設置できます');
                    if (isset($board[$wSq])) throw new RuntimeException("{$wSq} に駒があります");
                    $timedTraps[$wSq] = ['color' => $color, 'stun_turns' => 2, 'force_move' => false];
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FORTRESS:
                // 隣接マスに移動不可の盾駒を1体設置
                if (!$target) throw new RuntimeException('要塞を設置するマスを指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('要塞は隣接マスにのみ設置できます');
                if (isset($board[$target])) throw new RuntimeException('駒のあるマスには設置できません');
                $board[$target] = self::makePiece('rook', $color, ['fortress' => true, 'character_id' => null]);
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FORTRESS_LARGE:
                // 隣接マスに2体（target = "sq1,sq2"）
                if (!$target) throw new RuntimeException('2マスを "sq1,sq2" の形式で指定してください');
                $fTargets = explode(',', $target);
                if (count($fTargets) < 2) throw new RuntimeException('2マスを指定してください');
                foreach (array_slice($fTargets, 0, 2) as $fSq) {
                    $fSq = trim($fSq);
                    if (!self::isAdjacent($from, $fSq)) throw new RuntimeException('要塞は隣接マスにのみ設置できます');
                    if (isset($board[$fSq])) throw new RuntimeException("{$fSq} に駒があります");
                    $board[$fSq] = self::makePiece('rook', $color, ['fortress' => true, 'character_id' => null]);
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FORTRESS_SELF:
                // この駒自身を1ターンの間移動不可の盾に変える（次の自分のターン終了で解除）
                $board[$from]['fortress']     = true;
                $board[$from]['stunned_turns'] = 1;
                $board[$from]['active_used']++;
                break;

            // ─── 支配・コピー系 ───────────────────────────────
            case self::SKILL_DOMINATE:
                // 隣接する相手駒を永続的に奪取
                if (!$target) throw new RuntimeException('支配する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは支配できません');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['color']        = $color;
                $board[$target]['charmed_by']   = null;
                $board[$target]['charmed_turns'] = 0;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_CHARM_LONG:
            case self::SKILL_CHARM:
                // 隣接する相手駒を一時的に自駒として操作
                $charmTurns = $skillId === self::SKILL_CHARM_LONG ? 2 : 1;
                if (!$target) throw new RuntimeException('魅了する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは魅了できません');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $originalColor = $tp['charmed_by'] ?? $tp['color'];
                $board[$target]['charmed_by']    = $originalColor;
                $board[$target]['charmed_turns']  = $charmTurns;
                $board[$target]['color']          = $color; // 一時的に自駒の色に
                $board[$from]['active_used']++;
                break;

            case self::SKILL_DISABLE:
                // 相手駒1体のスキルを2ターン封じる
                if (!$target) throw new RuntimeException('スキルを封じる相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['skill_sealed_turns'] = max(2, (int)($board[$target]['skill_sealed_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_HINDER:
                // 隣接する相手駒1体のスキルを1ターン封じる
                if (!$target) throw new RuntimeException('スキルを封じる相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['skill_sealed_turns'] = max(1, (int)($board[$target]['skill_sealed_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_HINDER_STRONG:
                // 隣接する相手駒1体のスキルを2ターン封じる
                if (!$target) throw new RuntimeException('スキルを封じる相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $board[$target]['skill_sealed_turns'] = max(2, (int)($board[$target]['skill_sealed_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_CHAIN_STRONG:
            case self::SKILL_CHAIN:
                // スキル封印 + 自陣側へN マス強制移動
                $moveSteps = $skillId === self::SKILL_CHAIN_STRONG ? 3 : 2;
                if (!$target) throw new RuntimeException('鎖縛する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは対象にできません');
                // スキル封印
                $board[$target]['skill_sealed_turns'] = max(2, (int)($board[$target]['skill_sealed_turns'] ?? 0));
                // 自陣側へ強制移動（white=row増, black=row減）
                $dir = $color === 'white' ? 1 : -1;
                $tc  = self::colIdx($target);
                $tr  = self::rowNum($target);
                $newTc = $tc; $newTr = $tr;
                for ($i = 0; $i < $moveSteps; $i++) {
                    $nextTr = $newTr + $dir;
                    if (!self::inBounds($newTc, $nextTr)) break;
                    $nextSq = self::toSq($newTc, $nextTr);
                    if (isset($board[$nextSq])) break;
                    $newTr = $nextTr;
                }
                if ($newTr !== $tr) {
                    $newSq = self::toSq($newTc, $newTr);
                    $movedPiece = $board[$target];
                    unset($board[$target]);
                    $board[$newSq] = $movedPiece;
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FORCE_MOVE:
                // 相手駒1体を隣接マスへ強制移動
                if (!$target) throw new RuntimeException('強制移動する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは対象にできません');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                // 自陣側の隣接空きマスへ移動
                $dir    = $color === 'white' ? 1 : -1;
                $ftc    = self::colIdx($target);
                $ftr    = self::rowNum($target);
                $destSq = null;
                $nextTr = $ftr + $dir;
                if (self::inBounds($ftc, $nextTr) && !isset($board[self::toSq($ftc, $nextTr)])) {
                    $destSq = self::toSq($ftc, $nextTr);
                } else {
                    // 横方向を試す
                    foreach ([-1, 1] as $dc2) {
                        $nc2 = $ftc + $dc2;
                        if (self::inBounds($nc2, $ftr) && !isset($board[self::toSq($nc2, $ftr)])) {
                            $destSq = self::toSq($nc2, $ftr);
                            break;
                        }
                    }
                }
                if ($destSq) {
                    $movedPiece = $board[$target];
                    unset($board[$target]);
                    $board[$destSq] = $movedPiece;
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_SKILL_STEAL:
                // 隣接する相手駒のアクティブスキルを1回コピーして使用可能に
                if (!$target) throw new RuntimeException('スキルを盗む相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                if (!$tp['active_skill_id']) throw new RuntimeException('対象はアクティブスキルを持っていません');
                $board[$from]['copied_active']       = $tp['active_skill_id'];
                $board[$from]['copied_active_turns'] = 1;
                // 相手のスキルを1回消費させる
                $board[$target]['active_used'] = (int)($board[$target]['active_used'] ?? 0) + 1;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_MIMIC_WEAK:
                // 隣接する相手駒のパッシブを2ターンコピー
                if (!$target) throw new RuntimeException('模倣する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                if (!$tp['passive_skill_id']) throw new RuntimeException('対象はパッシブスキルを持っていません');
                $board[$from]['copied_passive']        = $tp['passive_skill_id'];
                $board[$from]['copied_passive_turns']  = 2;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_FULL_MIMIC:
                // 隣接する相手駒のアクティブ・パッシブ両方をコピー
                if (!$target) throw new RuntimeException('模倣する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                if ($tp['active_skill_id']) {
                    $board[$from]['copied_active']       = $tp['active_skill_id'];
                    $board[$from]['copied_active_turns'] = 0; // 永続（このターン使い切り）
                }
                if ($tp['passive_skill_id']) {
                    $board[$from]['copied_passive']       = $tp['passive_skill_id'];
                    $board[$from]['copied_passive_turns'] = 2;
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_ASSAULT_DOMINATE:
                // 急襲支配: 移動後の隣接相手駒を1ターン魅了（スキル機会として発動）
                if (!$target) throw new RuntimeException('魅了する相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                if ($tp['piece'] === 'king') throw new RuntimeException('キングは魅了できません');
                if (!self::isAdjacent($from, $target)) throw new RuntimeException('対象が隣接していません');
                $originalColor = $tp['charmed_by'] ?? $tp['color'];
                $board[$target]['charmed_by']    = $originalColor;
                $board[$target]['charmed_turns']  = 1;
                $board[$target]['color']          = $color;
                $board[$from]['active_used']++;
                break;

            case self::SKILL_INFECT:
                // 感染: アクティブ版はこの駒を除去し、取った相手駒を1ターンスタン+強制移動
                // パッシブ版（66）は applyMove で処理。アクティブ発動は自己犠牲型。
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if ($adj && $adj['color'] !== $color) {
                        $board[$adjSq]['stunned_turns'] = max(1, (int)($board[$adjSq]['stunned_turns'] ?? 0));
                        // 強制移動
                        $dir2 = $color === 'white' ? 1 : -1;
                        $ic   = self::colIdx($adjSq); $ir = self::rowNum($adjSq);
                        $nr2  = $ir + $dir2;
                        if (self::inBounds($ic, $nr2) && !isset($board[self::toSq($ic, $nr2)])) {
                            $movedPiece2 = $board[$adjSq];
                            unset($board[$adjSq]);
                            $board[self::toSq($ic, $nr2)] = $movedPiece2;
                        }
                    }
                }
                unset($board[$from]);
                break;

            case self::SKILL_STUN:
                // 相手駒1体を2ターン行動封じ
                if (!$target) throw new RuntimeException('行動封じする相手駒を指定してください');
                $tp = $board[$target] ?? null;
                if (!$tp || $tp['color'] === $color) throw new RuntimeException('相手の駒を指定してください');
                $board[$target]['stunned_turns'] = max(2, (int)($board[$target]['stunned_turns'] ?? 0));
                $board[$from]['active_used']++;
                break;

            case self::SKILL_STORM_STRIKE:
                // 移動先の周囲1マスにいる相手駒を全て1マス強制移動（外側へ）
                $adjSqs = self::getAdjacentSquares($from);
                foreach ($adjSqs as $adjSq) {
                    $adj = $board[$adjSq] ?? null;
                    if (!$adj || $adj['color'] === $color) continue;
                    $dc2 = self::colIdx($adjSq) - self::colIdx($from);
                    $dr2 = self::rowNum($adjSq) - self::rowNum($from);
                    $nc2 = self::colIdx($adjSq) + (int)($dc2 <=> 0);
                    $nr2 = self::rowNum($adjSq) + (int)($dr2 <=> 0);
                    if (self::inBounds($nc2, $nr2)) {
                        $destSq2 = self::toSq($nc2, $nr2);
                        if (!isset($board[$destSq2])) {
                            $movedPiece3 = $board[$adjSq];
                            unset($board[$adjSq]);
                            $board[$destSq2] = $movedPiece3;
                        }
                    }
                }
                $board[$from]['active_used']++;
                break;

            case self::SKILL_TIMED_SANCTUARY:
                // 2ターンの間、自駒のいるマスへの進入不可
                $timedSanct[$from] = ['color' => $color, 'turns' => 2];
                $board[$from]['active_used']++;
                break;

            default:
                throw new RuntimeException('そのスキルはアクティブ発動できません');
        }

        $nextPlayer = $advanceTurn ? $opponentPlayer : $player;
        $nextTurn   = ($advanceTurn && $player === 'player2')
                        ? $state['turn'] + 1
                        : $state['turn'];

        // ターン開始時パッシブを次のプレイヤーへ適用
        if ($advanceTurn && ($state['status'] ?? 'in_progress') === 'in_progress') {
            $board = self::applyTurnStartPassives($board, $opponentColor);
        }

        return array_merge($state, [
            'board'            => $board,
            'traps'            => $traps,
            'timedTraps'       => $timedTraps,
            'timedSanctuaries' => $timedSanct,
            'captured'         => $captured,
            'currentPlayer'    => $nextPlayer,
            'turn'             => $nextTurn,
            'rematchPending'   => $rematchPending,
            'skillOpportunity' => null,
        ]);
    }

    // ─── 移動検証 ────────────────────────────────────────────

    public static function validateMove(array $state, string $player, string $from, string $to): array {
        if ($state['status'] !== 'in_progress') {
            return ['valid' => false, 'reason' => '試合は終了しています'];
        }

        $opportunity = $state['skillOpportunity'] ?? null;
        if ($opportunity && $opportunity['player'] === $player) {
            return ['valid' => false, 'reason' => 'スキルを発動するかスキップしてください'];
        }

        $rematch = $state['rematchPending'] ?? null;
        if ($rematch) {
            if ($rematch['player'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
            if ($rematch['sq'] !== $from) {
                return ['valid' => false, 'reason' => '再移動は同じ駒で行ってください'];
            }
        } else {
            if ($state['currentPlayer'] !== $player) {
                return ['valid' => false, 'reason' => 'あなたのターンではありません'];
            }
        }

        $piece = $state['board'][$from] ?? null;
        if (!$piece) return ['valid' => false, 'reason' => "{$from} に駒がありません"];

        $color = $player === 'player1' ? 'white' : 'black';
        if ($piece['color'] !== $color) return ['valid' => false, 'reason' => '自分の駒ではありません'];

        // スタン中は移動不可
        if ((int)($piece['stunned_turns'] ?? 0) > 0) {
            return ['valid' => false, 'reason' => 'この駒は行動封じ中です'];
        }

        // 要塞は移動不可
        if ($piece['fortress'] ?? false) {
            return ['valid' => false, 'reason' => 'この駒は移動できません'];
        }

        $legal = self::getLegalMoves($state['board'], $from, $state['traps'] ?? [], $state['timedSanctuaries'] ?? []);
        if (!in_array($to, $legal, true)) {
            return ['valid' => false, 'reason' => "{$from}→{$to} は不正な移動です"];
        }

        return ['valid' => true];
    }

    // ─── 移動実行 ────────────────────────────────────────────

    public static function executeMove(array $state, string $player, string $from, string $to): array {
        $check = self::validateMove($state, $player, $from, $to);
        if (!$check['valid']) throw new RuntimeException($check['reason']);

        $traps       = $state['traps'] ?? [];
        $timedTraps  = $state['timedTraps'] ?? [];
        $timedSanct  = $state['timedSanctuaries'] ?? [];
        $captured    = $state['captured'] ?? ['white' => [], 'black' => []];
        $newBoard    = self::applyMove($state['board'], $from, $to, $traps, $timedTraps, $captured);

        $rematch   = $state['rematchPending'] ?? null;
        $isRematch = $rematch && $rematch['player'] === $player && $rematch['sq'] === $from;

        $opponentPlayer = $player === 'player1' ? 'player2' : 'player1';
        $opponentColor  = $opponentPlayer === 'player1' ? 'white' : 'black';
        $color          = $player === 'player1' ? 'white' : 'black';

        $status    = 'in_progress';
        $winner    = null;
        $endReason = null;

        if (!self::hasKing($newBoard, $opponentColor)) {
            $status    = 'finished';
            $winner    = $player;
            $endReason = 'checkmate';
        }

        $nextTurn = (!$isRematch && $player === 'player2')
                    ? $state['turn'] + 1
                    : $state['turn'];

        if ($status === 'in_progress' && !$isRematch && $nextTurn > $state['maxTurns']) {
            $p1pts     = self::calcPoints($newBoard, 'white');
            $p2pts     = self::calcPoints($newBoard, 'black');
            $status    = 'finished';
            $endReason = 'points';
            if ($p1pts > $p2pts)     $winner = 'player1';
            elseif ($p2pts > $p1pts) $winner = 'player2';
            else                     $winner = 'draw';
        }

        // ターン終了時: 現在プレイヤーの駒のタイムドエフェクトを1減らす
        if (!$isRematch) {
            $newBoard    = self::decrementTimedEffects($newBoard, $color);
            $timedSanct  = self::decrementTimedSanctuaries($timedSanct, $color);
        }

        // 移動後スキル機会チェック
        $skillOpportunity = null;
        if ($status === 'in_progress' && !$isRematch) {
            $movedPiece = $newBoard[$to] ?? null;
            if ($movedPiece && $movedPiece['color'] === $color) {
                $skillOpportunity = self::checkSkillOpportunity($newBoard, $to, $player);
            }
        }

        if ($skillOpportunity) {
            return array_merge($state, [
                'board'            => $newBoard,
                'traps'            => $traps,
                'timedTraps'       => $timedTraps,
                'timedSanctuaries' => $timedSanct,
                'captured'         => $captured,
                'currentPlayer'    => $player,
                'turn'             => $state['turn'],
                'status'           => $status,
                'winner'           => $winner,
                'endReason'        => $endReason,
                'rematchPending'   => null,
                'skillOpportunity' => $skillOpportunity,
            ]);
        }

        // ターン開始時パッシブを次のプレイヤーへ適用
        if ($status === 'in_progress') {
            $newBoard = self::applyTurnStartPassives($newBoard, $opponentColor);
        }

        return array_merge($state, [
            'board'            => $newBoard,
            'traps'            => $traps,
            'timedTraps'       => $timedTraps,
            'timedSanctuaries' => $timedSanct,
            'captured'         => $captured,
            'currentPlayer'    => $status === 'finished' ? null : $opponentPlayer,
            'turn'             => $nextTurn,
            'status'           => $status,
            'winner'           => $winner,
            'endReason'        => $endReason,
            'rematchPending'   => null,
            'skillOpportunity' => null,
        ]);
    }

    // ─── タイムドエフェクトのデクリメント ────────────────────

    private static function decrementTimedEffects(array $board, string $color): array {
        foreach ($board as $sq => $piece) {
            if (!$piece || $piece['color'] !== $color) continue;

            // スタン
            if ((int)($piece['stunned_turns'] ?? 0) > 0) {
                $board[$sq]['stunned_turns']--;
                // スタンが0になったとき要塞を解除
                if ($board[$sq]['stunned_turns'] <= 0 && ($board[$sq]['fortress'] ?? false)) {
                    $board[$sq]['fortress'] = false;
                }
            }

            // スキル封印
            if ((int)($piece['skill_sealed_turns'] ?? 0) > 0) {
                $board[$sq]['skill_sealed_turns']--;
            }

            // 移動バフ
            if ((int)($piece['move_bonus_turns'] ?? 0) > 0) {
                $board[$sq]['move_bonus_turns']--;
                if ($board[$sq]['move_bonus_turns'] <= 0) {
                    $board[$sq]['move_bonus'] = 0;
                }
            }

            // 価値ボーナス（一時的）
            if ((int)($piece['value_bonus_turns'] ?? 0) > 0) {
                $board[$sq]['value_bonus_turns']--;
                if ($board[$sq]['value_bonus_turns'] <= 0 && (int)($board[$sq]['value_bonus'] ?? 0) > 0) {
                    $board[$sq]['value_bonus'] = 0;
                }
            }

            // コピースキル（ターン制）
            if ((int)($piece['copied_passive_turns'] ?? 0) > 0) {
                $board[$sq]['copied_passive_turns']--;
                if ($board[$sq]['copied_passive_turns'] <= 0) {
                    $board[$sq]['copied_passive'] = null;
                }
            }

            // 魅了: charmed_by があるなら魅了者の色（opponent）のターンにデクリメント
            if (($piece['charmed_by'] ?? null) !== null) {
                // 魅了中の駒は、魅了者（= piece の現在の color）のターン終了時にデクリメント
                if ((int)($piece['charmed_turns'] ?? 0) > 0) {
                    $board[$sq]['charmed_turns']--;
                    if ($board[$sq]['charmed_turns'] <= 0) {
                        $board[$sq]['color']      = $board[$sq]['charmed_by'];
                        $board[$sq]['charmed_by'] = null;
                    }
                }
            }
        }
        return $board;
    }

    private static function decrementTimedSanctuaries(array $timedSanct, string $color): array {
        foreach ($timedSanct as $sq => $info) {
            if (($info['color'] ?? null) === $color) {
                $timedSanct[$sq]['turns']--;
                if ($timedSanct[$sq]['turns'] <= 0) {
                    unset($timedSanct[$sq]);
                }
            }
        }
        return $timedSanct;
    }

    // ─── ターン開始時パッシブ適用 ──────────────────────────

    private static function applyTurnStartPassives(array $board, string $color): array {
        foreach ($board as $sq => $piece) {
            if (!$piece || $piece['color'] !== $color) continue;

            // passive_skill_id または copied_passive を参照
            $passiveId = $piece['passive_skill_id'] ?? null;
            if (!$passiveId && ($piece['copied_passive'] ?? null)) {
                $passiveId = $piece['copied_passive'];
            }
            if (!$passiveId) continue;

            if ($passiveId === self::SKILL_HOLY_AURA) {
                // 隣接する全味方駒にシールド付与
                foreach (self::getAdjacentSquares($sq) as $adjSq) {
                    if (isset($board[$adjSq]) && $board[$adjSq]['color'] === $color) {
                        $board[$adjSq]['shield'] = true;
                    }
                }
            } elseif ($passiveId === self::SKILL_SWAMP_GRACE) {
                // 前方マス（白:row+1 / 黒:row-1）の味方駒にシールド付与
                $c  = self::colIdx($sq);
                $r  = self::rowNum($sq);
                $nr = $color === 'white' ? $r + 1 : $r - 1;
                if (self::inBounds($c, $nr)) {
                    $frontSq = self::toSq($c, $nr);
                    if (isset($board[$frontSq]) && $board[$frontSq]['color'] === $color) {
                        $board[$frontSq]['shield'] = true;
                    }
                }
            }
        }
        return $board;
    }

    // ─── ポイント計算（value_bonus 考慮）────────────────────

    public static function calcPoints(array $board, string $color): int {
        $sum = 0;
        foreach ($board as $p) {
            if (!$p || $p['color'] !== $color || $p['piece'] === 'king') continue;
            $base  = self::PIECE_VALUES[$p['piece']] ?? 0;
            $bonus = (int)($p['value_bonus'] ?? 0);
            $sum  += max(0, $base + $bonus);
        }
        return $sum;
    }

    public static function hasKing(array $board, string $color): bool {
        foreach ($board as $p) {
            if ($p && $p['piece'] === 'king' && $p['color'] === $color) return true;
        }
        return false;
    }
}
