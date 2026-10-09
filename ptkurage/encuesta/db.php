<?php
/**
 * PT:Kurage - Encuesta Discografía Plastic Tree
 * Gestor de Base de Datos SQLite y Seeder de Discografía
 */

namespace KurageEncuesta;

use PDO;
use PDOException;

class DB {
    private static ?PDO $pdo = null;
    private static string $dbFile = __DIR__ . '/data/encuesta.sqlite';

    public static function get(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbDir = dirname(self::$dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $isNew = !file_exists(self::$dbFile);

        try {
            self::$pdo = new PDO('sqlite:' . self::$dbFile);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Optimización SQLite
            self::$pdo->exec('PRAGMA journal_mode = WAL;');
            self::$pdo->exec('PRAGMA foreign_keys = ON;');

            if ($isNew || filesize(self::$dbFile) === 0) {
                self::initSchema();
                self::seedDiscography();
            } else {
                // Verificar si existe la tabla albums
                $check = self::$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='albums'")->fetch();
                if (!$check) {
                    self::initSchema();
                    self::seedDiscography();
                }
            }

            return self::$pdo;
        } catch (PDOException $e) {
            error_log('Error conectando a SQLite encuesta: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function initSchema(): void {
        $db = self::$pdo;

        $db->exec("
            CREATE TABLE IF NOT EXISTS albums (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                title_jp TEXT,
                release_date TEXT,
                album_type TEXT NOT NULL, -- 'mini', 'studio', 'single', 'best'
                cover_url TEXT DEFAULT '',
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS songs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                album_id INTEGER NOT NULL,
                track_num INTEGER DEFAULT 1,
                title TEXT NOT NULL,
                title_jp TEXT,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                voter_email TEXT NOT NULL,
                voter_name TEXT DEFAULT '',
                voter_ip TEXT,
                user_agent TEXT,
                vote_token TEXT UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS vote_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                vote_id INTEGER NOT NULL,
                song_id INTEGER NOT NULL,
                album_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
                FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
                FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            );

            CREATE TABLE IF NOT EXISTS audio_tracks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                artist TEXT DEFAULT 'Plastic Tree',
                file_url TEXT NOT NULL,
                song_id INTEGER DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE SET NULL
            );

            CREATE INDEX IF NOT EXISTS idx_songs_album ON songs(album_id);
            CREATE INDEX IF NOT EXISTS idx_votes_email ON votes(voter_email);
            CREATE INDEX IF NOT EXISTS idx_votes_ip ON votes(voter_ip);
            CREATE INDEX IF NOT EXISTS idx_vote_items_song ON vote_items(song_id);
            CREATE INDEX IF NOT EXISTS idx_audio_song ON audio_tracks(song_id);
        ");

        // Configuraciones por defecto
        $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
        $stmt->execute(['survey_active', '1']);
        $stmt->execute(['max_votes_per_user', '10']);
        $stmt->execute(['allow_ip_limit', '1']);
        $stmt->execute(['max_votes_per_ip', '3']);
        $stmt->execute(['google_client_id', '']);
        $stmt->execute(['require_google_auth', '0']);

        try {
            $db->exec("ALTER TABLE songs ADD COLUMN preview_url TEXT DEFAULT ''");
        } catch (\Exception $e) {
            // Ya existe
        }

        try {
            $db->exec("ALTER TABLE votes ADD COLUMN concert_location TEXT DEFAULT 'Chile'");
        } catch (\Exception $e) {
            // Ya existe
        }

        try {
            $db->exec("ALTER TABLE votes ADD COLUMN voter_nickname TEXT DEFAULT ''");
        } catch (\Exception $e) {
            // Ya existe
        }
    }

    public static function seedDiscography(): void {
        $db = self::$pdo;
        $count = (int)$db->query("SELECT COUNT(*) FROM albums")->fetchColumn();
        if ($count > 0) {
            return;
        }

                $discography = [
            [
                'title' => 'Strange Fruits -Kimyou na Kajitsu-',
                'title_jp' => '奇妙な果実',
                'release_date' => '1995-12-11',
                'type' => 'mini',
                'tracks' => [
                    ['title' => 'Psycho Garden', 'title_jp' => 'サイコガーデン'],
                    ['title' => 'Twice', 'title_jp' => 'Twice'],
                    ['title' => 'CREAM', 'title_jp' => 'クリーム'],
                    ['title' => 'Rusty', 'title_jp' => 'Rusty'],
                    ['title' => 'Gin NO Hari', 'title_jp' => '銀ノ針'],
                    ['title' => 'Henge', 'title_jp' => '変化'],
                    ['title' => 'MAKE', 'title_jp' => 'メイク'],
                ]
            ],
            [
                'title' => 'echo',
                'title_jp' => 'echo',
                'release_date' => '2014-03-05',
                'type' => 'mini',
                'tracks' => [
                    ['title' => 'Kodama', 'title_jp' => '木霊'],
                    ['title' => 'Kyokuron', 'title_jp' => '曲論'],
                    ['title' => 'Kiki', 'title_jp' => '嬉々'],
                    ['title' => 'Rondo', 'title_jp' => '輪舞'],
                    ['title' => 'Doko', 'title_jp' => '瞳孔'],
                    ['title' => 'Amaoto', 'title_jp' => '雨音'],
                    ['title' => 'Kagee', 'title_jp' => '影絵'],
                ]
            ],
            [
                'title' => 'Hide and Seek',
                'title_jp' => 'Hide and Seek',
                'release_date' => '1997-10-09',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Itai Ao', 'title_jp' => '痛い青'],
                    ['title' => 'Ether Note', 'title_jp' => 'エーテルノート'],
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'Closet Child', 'title_jp' => 'クローゼットチャイルド'],
                    ['title' => 'Snow Flower', 'title_jp' => 'スノーフラワー'],
                    ['title' => 'Hide and Seek #1', 'title_jp' => 'Hide and Seek #1'],
                    ['title' => 'Trance Orange', 'title_jp' => 'トランスオレンジ'],
                    ['title' => 'Mahiru No Tsuki', 'title_jp' => 'まひるの月'],
                    ['title' => 'Suisou', 'title_jp' => '水葬'],
                    ['title' => 'Nejimaki Neurose', 'title_jp' => 'ねじまきノイローゼ'],
                    ['title' => 'Hide and Seek #2', 'title_jp' => 'Hide and Seek #2'],
                ]
            ],
            [
                'title' => 'Puppet Show',
                'title_jp' => 'Puppet Show',
                'release_date' => '1998-08-26',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Intro', 'title_jp' => 'Intro'],
                    ['title' => 'May Day', 'title_jp' => 'May Day'],
                    ['title' => 'RESET', 'title_jp' => 'リセット'],
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Gentou Kikai', 'title_jp' => '幻燈機械'],
                    ['title' => 'Nukegara', 'title_jp' => '「ぬけがら」'],
                    ['title' => 'Hontou no Uso', 'title_jp' => '本当の嘘'],
                    ['title' => 'Monophobia', 'title_jp' => 'Monophobia'],
                    ['title' => 'CREAM', 'title_jp' => 'クリーム'],
                    ['title' => 'Sangetsu itsuka', 'title_jp' => '3月5日。'],
                    ['title' => 'Circus', 'title_jp' => 'サーカス'],
                ]
            ],
            [
                'title' => 'Parade',
                'title_jp' => 'Parade',
                'release_date' => '2000-08-23',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Ether', 'title_jp' => 'エーテル'],
                    ['title' => 'ROCKET', 'title_jp' => 'ロケット'],
                    ['title' => 'SLIDE (Ver.2.0)', 'title_jp' => 'スライド'],
                    ['title' => 'Shoujokyou Sou', 'title_jp' => '少女狂想'],
                    ['title' => 'VERANDA (Ver.1.0)', 'title_jp' => 'ベランダ'],
                    ['title' => 'Kuuhaku no hi', 'title_jp' => '空白の日'],
                    ['title' => 'Juujiro', 'title_jp' => '十字路'],
                    ['title' => 'Tremolo (Ver.2.0)', 'title_jp' => 'トレモロ'],
                    ['title' => 'Suimin yaku', 'title_jp' => '睡眠薬'],
                    ['title' => 'bloom', 'title_jp' => 'bloom'],
                    ['title' => 'Sink (Ver.2.0)', 'title_jp' => 'Sink'],
                    ['title' => 'Soshite Parade wa Tsuzuku', 'title_jp' => 'そしてパレードは続く'],
                ]
            ],
            [
                'title' => 'Träumerei',
                'title_jp' => 'トロイメライ',
                'release_date' => '2002-09-21',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Rikashitsu', 'title_jp' => '理科室'],
                    ['title' => 'Glider', 'title_jp' => 'グライダー'],
                    ['title' => 'Aoi Tori', 'title_jp' => '蒼い鳥'],
                    ['title' => 'Chiriyuku Bokura', 'title_jp' => '散リユク僕ラ'],
                    ['title' => 'Petshop', 'title_jp' => 'ペットショップ'],
                    ['title' => 'Zange wa Yokushitsu de', 'title_jp' => '懺悔は浴室で'],
                    ['title' => 'Akai Kutsu', 'title_jp' => '赤い靴'],
                    ['title' => 'GERBERA', 'title_jp' => 'ガーベラ'],
                    ['title' => 'Chibashi, Wakabaku, Roku jihan', 'title_jp' => '千葉市、若葉区、6時30分'],
                    ['title' => 'Platform', 'title_jp' => 'プラットホーム'],
                    ['title' => 'Hello', 'title_jp' => 'Hello'],
                    ['title' => 'Ame ni Uteba', 'title_jp' => '雨ニ唄エバ'],
                ]
            ],
            [
                'title' => 'Shiro Chronicle',
                'title_jp' => 'シロクロニクル',
                'release_date' => '2003-10-27',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Irogoto', 'title_jp' => 'イロゴト'],
                    ['title' => 'National Kid', 'title_jp' => 'ナショナルキッド'],
                    ['title' => 'Mizuiro Girlfriend -No Edit Version-', 'title_jp' => '水色ガールフレンド -No Edit Version-'],
                    ['title' => 'Moshimo Piano ga Hiketa Nara', 'title_jp' => 'もしもピアノが弾けたなら'],
                    ['title' => 'Sunday', 'title_jp' => 'サンデー'],
                    ['title' => 'Seiza Zukuri', 'title_jp' => '星座づくり'],
                    ['title' => 'Baka ni Natta no ni', 'title_jp' => 'バカになったのに'],
                    ['title' => 'Himitsu no Carnival', 'title_jp' => '秘密のカーニバル'],
                    ['title' => 'Picasso Gokko', 'title_jp' => 'ピカソごっこ'],
                    ['title' => 'Baria', 'title_jp' => 'バリア'],
                    ['title' => 'Saishuu Densha', 'title_jp' => '最終電車'],
                ]
            ],
            [
                'title' => 'cell.',
                'title_jp' => 'cell.',
                'release_date' => '2004-08-25',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'cell.', 'title_jp' => 'cell.'],
                    ['title' => 'MELANCHOLIC', 'title_jp' => 'メランコリック'],
                    ['title' => 'Harusaki SENTIMENTAL', 'title_jp' => '春咲センチメンタル'],
                    ['title' => 'Dance Macabre', 'title_jp' => 'ダンスマカブラ'],
                    ['title' => 'Kaibutsu-kun', 'title_jp' => '怪物くん'],
                    ['title' => 'Crackpot', 'title_jp' => 'Crackpot'],
                    ['title' => '「Yuki Hotaru」', 'title_jp' => '「雪蛍」'],
                    ['title' => 'comic youth', 'title_jp' => 'comic youth'],
                    ['title' => 'Harienju', 'title_jp' => '針槐'],
                    ['title' => 'Uwa no Sora', 'title_jp' => 'うわのそら'],
                    ['title' => 'Yume no Shima', 'title_jp' => '夢の島'],
                ]
            ],
            [
                'title' => 'Chandelier',
                'title_jp' => 'シャンデリア',
                'release_date' => '2006-06-28',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'HATE・RED, DIP・IT', 'title_jp' => 'ヘイト・レッド、ディップ・イット'],
                    ['title' => 'Naizou Mike', 'title_jp' => '内臓マイク'],
                    ['title' => 'NAMIDA DROP', 'title_jp' => 'ナミダドロップ'],
                    ['title' => 'Ghost', 'title_jp' => 'Ghost'],
                    ['title' => 'Puppet Talk', 'title_jp' => 'Puppet Talk'],
                    ['title' => 'Namae no nai Hana', 'title_jp' => '名前のない花'],
                    ['title' => '37℃', 'title_jp' => '37℃'],
                    ['title' => 'Cage for Rent', 'title_jp' => 'Cage for Rent'],
                    ['title' => 'Sanbika', 'title_jp' => '讃美歌'],
                    ['title' => 'Sentimental Machine', 'title_jp' => 'センチメントマシーン'],
                    ['title' => 'Kuuchuu BURANKO', 'title_jp' => '空中ブランコ'],
                    ['title' => 'Last Waltz', 'title_jp' => 'ラストワルツ'],
                    ['title' => 'Rokugetsu no Ame (Amefure mix)', 'title_jp' => '六月の雨'],
                ]
            ],
            [
                'title' => 'Nega to Poji',
                'title_jp' => 'ネガとポジ',
                'release_date' => '2007-06-27',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Nemureru Mori', 'title_jp' => '眠れる森'],
                    ['title' => 'Fujunbutsu', 'title_jp' => '不純物'],
                    ['title' => 'Elegy', 'title_jp' => 'エレジー'],
                    ['title' => 'SPICA (Nega to Posi Ban)', 'title_jp' => 'スピカ'],
                    ['title' => 'Zaza Furi, Zaza Nari', 'title_jp' => 'ザザ降り、ザザ鳴り'],
                    ['title' => 'Mujin Eki', 'title_jp' => '無人駅'],
                    ['title' => 'Orange', 'title_jp' => 'オレンジ'],
                    ['title' => 'sabbath', 'title_jp' => 'sabbath'],
                    ['title' => 'egg', 'title_jp' => 'egg'],
                    ['title' => 'Ruisen Kairo', 'title_jp' => '涙腺回路'],
                    ['title' => 'Kuroi Kasa', 'title_jp' => '黒い傘'],
                    ['title' => 'ANDROMETAMORPHOSE', 'title_jp' => 'アンドロメタモルフォーゼ'],
                    ['title' => 'Makka na Ito (Nega to Posi Ban)', 'title_jp' => '真っ赤な糸'],
                    ['title' => 'hate red, dip it (loudest sound edition)', 'title_jp' => 'hate red, dip it'],
                ]
            ],
            [
                'title' => 'Utsusemi',
                'title_jp' => 'ウツセミ',
                'release_date' => '2008-09-24',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Kisokuteki na Yonbyoshi', 'title_jp' => '規則的な四拍子'],
                    ['title' => 'Utsusemi', 'title_jp' => 'うつせみ'],
                    ['title' => 'Tetris', 'title_jp' => 'テトリス'],
                    ['title' => 'Replay (Utsusemi ban)', 'title_jp' => 'リプレイ'],
                    ['title' => 'Melt', 'title_jp' => 'メルト'],
                    ['title' => 'Dummy Box', 'title_jp' => 'Dummy Box'],
                    ['title' => 'Q', 'title_jp' => 'Q'],
                    ['title' => 'Fiction', 'title_jp' => 'フィクション'],
                    ['title' => 'Shayou', 'title_jp' => '斜陽'],
                    ['title' => 'GEKKO OVERHEAD', 'title_jp' => 'GEKKO OVERHEAD'],
                    ['title' => 'Balloon', 'title_jp' => 'バルーン'],
                    ['title' => 'Seiten, Kansei, Kaiten Mokuba no Mae', 'title_jp' => '晴天、喚声、回転木馬の前'],
                    ['title' => 'Alone Again Wonderful World (Utsusemi ban)', 'title_jp' => 'アローンアゲイン、ワンダフルワールド'],
                    ['title' => 'Kiokuyuki', 'title_jp' => '記憶行き'],
                    ['title' => 'Sankakukei no Kozu', 'title_jp' => '三角形の構図'],
                    ['title' => 'Closer', 'title_jp' => 'Closer'],
                ]
            ],
            [
                'title' => 'Dona Dona',
                'title_jp' => 'ドナドナ',
                'release_date' => '2009-12-23',
                'type' => 'studio',
                'tracks' => [
                    ['title' => '1999', 'title_jp' => '1999'],
                    ['title' => 'Fukuro (DONADONAban)', 'title_jp' => '梟 (ドナドナ版)'],
                    ['title' => 'EtCEteRA', 'title_jp' => 'エとセとラ'],
                    ['title' => 'sunset bloody sunset', 'title_jp' => 'sunset bloody sunset'],
                    ['title' => 'CONSENT。', 'title_jp' => 'コンセント。'],
                    ['title' => 'GAGAJI', 'title_jp' => 'ガガジ'],
                    ['title' => 'SANATORIUM (DONADONAban)', 'title_jp' => 'サナトリウム (ドナドナ版)'],
                    ['title' => 'DONADONA', 'title_jp' => 'ドナドナ'],
                    ['title' => '---Anten。', 'title_jp' => '---暗転。'],
                    ['title' => '(untitled silent track)', 'title_jp' => ''],
                    ['title' => 'Yasashisa Kurabu (bonus track)', 'title_jp' => 'やさしさ倶楽部'],
                ]
            ],
            [
                'title' => 'Ammonite',
                'title_jp' => 'アンモナイト',
                'release_date' => '2011-04-06',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Thirteenth Friday', 'title_jp' => 'Thirteenth Friday'],
                    ['title' => 'Moonlight —————。 (Ammonite ban)', 'title_jp' => 'ムーンライト────。'],
                    ['title' => 'Taikutsu Machine', 'title_jp' => '退屈マシン'],
                    ['title' => 'Mirai iro (Ammonite ban)', 'title_jp' => 'みらいいろ'],
                    ['title' => 'Setsugenka', 'title_jp' => '雪月花'],
                    ['title' => 'I love you so', 'title_jp' => 'アイラヴュー・ソー'],
                    ['title' => 'Aria', 'title_jp' => 'アリア'],
                    ['title' => 'DUET', 'title_jp' => 'デュエット'],
                    ['title' => 'Bambi (Ammonite ban)', 'title_jp' => 'バンビ'],
                    ['title' => 'Sabishinbou', 'title_jp' => 'さびしんぼう'],
                    ['title' => '~ Sakuhin「ammonite」~', 'title_jp' => '~ 作品「ammonite」~'],
                    ['title' => 'Blue Black', 'title_jp' => 'ブルーバック'],
                    ['title' => 'spooky', 'title_jp' => 'spooky'],
                ]
            ],
            [
                'title' => 'Ink',
                'title_jp' => 'インク',
                'release_date' => '2012-12-12',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'RORSCHACH (Hidari)', 'title_jp' => 'ロールシャッハ(左)'],
                    ['title' => 'INK', 'title_jp' => 'インク'],
                    ['title' => 'Kuchizuke', 'title_jp' => 'くちづけ'],
                    ['title' => 'PIANO BLACK', 'title_jp' => 'ピアノブラック'],
                    ['title' => 'ABANGYArde', 'title_jp' => 'あバンギャルど'],
                    ['title' => 'LIFE・IS・BEAUTIFUL', 'title_jp' => 'ライフ・イズ・ビューティフル'],
                    ['title' => 'Kimi wa CANARIA', 'title_jp' => '君はカナリヤ'],
                    ['title' => 'Jomyaku', 'title_jp' => '静脈'],
                    ['title' => 'Tefu Tefu', 'title_jp' => 'てふてふ'],
                    ['title' => 'SHION', 'title_jp' => 'シオン'],
                    ['title' => 'Nihyaku Juu Hachi Shousetsu Kakumo Nagaki Fuzai', 'title_jp' => '218小節、かくも長き不在。'],
                    ['title' => 'RORSCHACH (Migi)', 'title_jp' => 'ロールシャッハ(右)'],
                ]
            ],
            [
                'title' => 'Hakusei',
                'title_jp' => '剥製',
                'release_date' => '2015-12-23',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Seibutsu', 'title_jp' => '◯生物'],
                    ['title' => 'Frasco', 'title_jp' => 'フラスコ'],
                    ['title' => 'Mime', 'title_jp' => 'マイム'],
                    ['title' => 'Hacienda', 'title_jp' => 'ハシエンダ'],
                    ['title' => 'Kokuhaku', 'title_jp' => '告白'],
                    ['title' => 'Insomnia Blues', 'title_jp' => 'インソムニアブルース'],
                    ['title' => 'Float', 'title_jp' => 'float'],
                    ['title' => 'Rakka', 'title_jp' => '落花'],
                    ['title' => 'Thrashing Pumpkin Death March', 'title_jp' => 'スラッシングパンプキン・デスマーチ'],
                    ['title' => 'Slow', 'title_jp' => 'スロウ'],
                    ['title' => 'Hakusei', 'title_jp' => '剥製'],
                    ['title' => 'Seibutsu', 'title_jp' => '●静物'],
                ]
            ],
            [
                'title' => 'doorAdore',
                'title_jp' => 'doorAdore',
                'release_date' => '2018-03-07',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Engoku', 'title_jp' => '遠国'],
                    ['title' => 'Koi wa Haiiro', 'title_jp' => '恋は灰色'],
                    ['title' => 'Existentialisme', 'title_jp' => 'エクジスタンシアリスム'],
                    ['title' => 'Uchuu yuuei', 'title_jp' => '雨中遊泳'],
                    ['title' => 'Silent Noise', 'title_jp' => 'サイレントノイズ'],
                    ['title' => 'SEARCH AND DESTROY', 'title_jp' => 'サーチ アンド デストロイ'],
                    ['title' => 'Zanei', 'title_jp' => '残映'],
                    ['title' => 'Irotsuki', 'title_jp' => 'いろつき'],
                    ['title' => 'Nenriki', 'title_jp' => '念力'],
                    ['title' => 'scenario', 'title_jp' => 'scenario'],
                    ['title' => 'Nocturne', 'title_jp' => 'ノクターン'],
                    ['title' => 'Shizuka no Umi', 'title_jp' => '静かの海'],
                ]
            ],
            [
                'title' => 'Jusshokuteiri',
                'title_jp' => '十色定理',
                'release_date' => '2020-03-25',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'Ama no Jaku', 'title_jp' => 'あまのじゃく'],
                    ['title' => 'Medusa', 'title_jp' => 'メデューサ'],
                    ['title' => 'Senzo', 'title_jp' => '潜像'],
                    ['title' => 'C.C.C.', 'title_jp' => 'C.C.C.'],
                    ['title' => 'remain', 'title_jp' => 'remain'],
                    ['title' => 'Swing・Noir', 'title_jp' => 'スウィング・ノワール'],
                    ['title' => 'Inside Out', 'title_jp' => 'インサイドアウト'],
                    ['title' => 'Light.Gentle.and Soul.', 'title_jp' => 'Light.Gentle.and Soul.'],
                    ['title' => 'Tsuki ni Negai wo', 'title_jp' => '月に願いを'],
                    ['title' => 'ENDROLL。', 'title_jp' => 'エンドロール。'],
                ]
            ],
            [
                'title' => 'Plastic Tree',
                'title_jp' => 'Plastic Tree',
                'release_date' => '2024-05-29',
                'type' => 'studio',
                'tracks' => [
                    ['title' => 'LIME LIGHT', 'title_jp' => 'ライムライト'],
                    ['title' => 'Zawameki', 'title_jp' => 'ざわめき'],
                    ['title' => 'no rest for the wicked', 'title_jp' => 'no rest for the wicked'],
                    ['title' => 'Yuuen', 'title_jp' => 'ゆうえん'],
                    ['title' => 'Shikabaneeze', 'title_jp' => 'シカバネーゼ'],
                    ['title' => 'Yoiyami', 'title_jp' => '宵闇'],
                    ['title' => 'Invisible letter', 'title_jp' => 'Invisible letter'],
                    ['title' => 'Azabana', 'title_jp' => '痣花'],
                    ['title' => 'Meruhen', 'title_jp' => 'メルヘン'],
                    ['title' => 'Yume ochi', 'title_jp' => '夢落ち'],
                ]
            ],
            [
                'title' => 'Rira no Ki',
                'title_jp' => 'リラの樹',
                'release_date' => '1996-09-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'LILA no ki', 'title_jp' => 'リラの樹'],
                    ['title' => 'AngelDust', 'title_jp' => 'エンジェルダスト'],
                    ['title' => 'Sick', 'title_jp' => 'Sick'],
                ]
            ],
            [
                'title' => 'Wareta Mado',
                'title_jp' => '割れた窓',
                'release_date' => '1997-06-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'Narihibiku, Kane', 'title_jp' => '鳴り響く、鐘'],
                ]
            ],
            [
                'title' => 'Hontou no Uso',
                'title_jp' => '本当の嘘',
                'release_date' => '1998-02-15',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Hontou no Uso', 'title_jp' => '本当の嘘'],
                    ['title' => 'Abstruct my life', 'title_jp' => 'アブストラクトマイライフ'],
                ]
            ],
            [
                'title' => 'Zetsubou no Oka',
                'title_jp' => '絶望の丘',
                'release_date' => '1998-07-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Nukegara', 'title_jp' => '「ぬけがら」'],
                ]
            ],
            [
                'title' => 'Tremolo',
                'title_jp' => 'トレモロ',
                'release_date' => '1999-03-10',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Tremolo', 'title_jp' => 'トレモロ'],
                    ['title' => 'Panorama', 'title_jp' => 'パノラマ'],
                    ['title' => 'Inori', 'title_jp' => '祈り'],
                    ['title' => 'Gessekai', 'title_jp' => '月世界'],
                ]
            ],
            [
                'title' => 'Sink',
                'title_jp' => 'Sink',
                'release_date' => '1999-08-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Sink', 'title_jp' => 'Sink'],
                    ['title' => 'Angelfish', 'title_jp' => 'エンゼルフィッシュ'],
                ]
            ],
            [
                'title' => 'Tsumetai Hikari',
                'title_jp' => 'ツメタイヒカリ',
                'release_date' => '1999-12-10',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Tsumetai Hikari', 'title_jp' => 'ツメタイヒカリ'],
                    ['title' => 'Buranko kara', 'title_jp' => 'ブランコから'],
                ]
            ],
            [
                'title' => 'Slide',
                'title_jp' => 'スライド',
                'release_date' => '2000-04-19',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'SLIDE', 'title_jp' => 'スライド'],
                    ['title' => 'ORGAN.', 'title_jp' => 'オルガン.'],
                    ['title' => 'VERANDA', 'title_jp' => 'ベランダ'],
                ]
            ],
            [
                'title' => 'Rocket',
                'title_jp' => 'ロケット',
                'release_date' => '2000-07-12',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'ROCKET', 'title_jp' => 'ロケット'],
                    ['title' => 'Sonzai Riyuu', 'title_jp' => '存在理由'],
                    ['title' => 'Trance Orange (Live Version)', 'title_jp' => 'トランスオレンジ'],
                ]
            ],
            [
                'title' => 'Planetarium',
                'title_jp' => 'プラネタリウム',
                'release_date' => '2001-02-07',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Planetarium', 'title_jp' => 'プラネタリウム'],
                    ['title' => 'AngelDust', 'title_jp' => 'エンジェルダスト'],
                    ['title' => 'Ekitai', 'title_jp' => '液体'],
                ]
            ],
            [
                'title' => 'Chiriyuku Bokura',
                'title_jp' => '散リユク僕ラ',
                'release_date' => '2001-11-14',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Chiriyuku Bokura', 'title_jp' => '散リユク僕ラ'],
                    ['title' => 'Gichi Gichi', 'title_jp' => 'ギチギチ'],
                    ['title' => 'Platform', 'title_jp' => 'プラットホーム'],
                ]
            ],
            [
                'title' => 'Aoi Tori',
                'title_jp' => '青い鳥',
                'release_date' => '2002-06-26',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Aoi Tori', 'title_jp' => '蒼い鳥'],
                    ['title' => 'GERBERA', 'title_jp' => 'ガーベラ'],
                ]
            ],
            [
                'title' => 'Baka ni Natta Noni',
                'title_jp' => 'バカになったのに',
                'release_date' => '2003-05-21',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Baka ni Natta no ni', 'title_jp' => 'バカになったのに'],
                    ['title' => 'Moshimo Piano ga Hiketa Nara', 'title_jp' => 'もしもピアノが弾けたなら'],
                ]
            ],
            [
                'title' => 'Moshimo Piano ga Hiketanara',
                'title_jp' => 'もしもピアノが弾けたなら',
                'release_date' => '2003-07-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Moshimo Piano ga Hiketa Nara', 'title_jp' => 'もしもピアノが弾けたなら'],
                    ['title' => 'Baka ni Natta no ni', 'title_jp' => 'バカになったのに'],
                ]
            ],
            [
                'title' => 'Mizuiro Girlfriend',
                'title_jp' => '水色ガールフレンド',
                'release_date' => '2003-10-01',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Mizuiro no Girlfriend', 'title_jp' => '水色ガールフレンド'],
                    ['title' => 'lilac', 'title_jp' => 'lilac'],
                ]
            ],
            [
                'title' => 'Yuki Hotaru',
                'title_jp' => '雪蛍',
                'release_date' => '2004-01-21',
                'type' => 'single',
                'tracks' => [
                    ['title' => '「Yuki Hotaru」', 'title_jp' => '「雪蛍」'],
                    ['title' => 'Shiroi Ashiato', 'title_jp' => '白い足跡'],
                    ['title' => 'Fuyu no Umi wa Yuuei Kinshi de', 'title_jp' => '冬の海は遊泳禁止で'],
                ]
            ],
            [
                'title' => 'Harusaki Sentimental',
                'title_jp' => '春咲センチメンタル',
                'release_date' => '2004-03-10',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Harusaki SENTIMENTAL', 'title_jp' => '春咲センチメンタル'],
                    ['title' => 'Honjitsu wa Seiten Nari', 'title_jp' => '本日は晴天なり'],
                ]
            ],
            [
                'title' => 'Melancholic',
                'title_jp' => 'メランコリック',
                'release_date' => '2004-07-28',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'MELANCHOLIC', 'title_jp' => 'メランコリック'],
                    ['title' => 'Synthese', 'title_jp' => 'ジンテーゼ'],
                ]
            ],
            [
                'title' => 'Sanbika',
                'title_jp' => '讃美歌',
                'release_date' => '2005-05-11',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Sanbika', 'title_jp' => '讃美歌'],
                    ['title' => 'Kougousei', 'title_jp' => '光合成'],
                ]
            ],
            [
                'title' => 'Namae no Nai Hana',
                'title_jp' => '名前のない花',
                'release_date' => '2005-10-12',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Namae no nai Hana', 'title_jp' => '名前のない花'],
                    ['title' => 'Paranoia', 'title_jp' => 'パラノイア'],
                ]
            ],
            [
                'title' => 'Ghost',
                'title_jp' => 'Ghost',
                'release_date' => '2005-11-16',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Ghost', 'title_jp' => 'Ghost'],
                    ['title' => 'Suisai', 'title_jp' => '水彩'],
                ]
            ],
            [
                'title' => 'Kuuchuu Buranko',
                'title_jp' => '空中ブランコ',
                'release_date' => '2005-12-14',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Kuuchuu BURANKO', 'title_jp' => '空中ブランコ'],
                    ['title' => 'Tsuki no Hikari wo Tayori ni', 'title_jp' => '月の光をたよりに'],
                ]
            ],
            [
                'title' => 'Namida Drop',
                'title_jp' => 'ナミダドロップ',
                'release_date' => '2006-05-10',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'NAMIDA DROP', 'title_jp' => 'ナミダドロップ'],
                    ['title' => 'Rokugetsu no Ame', 'title_jp' => '六月の雨'],
                ]
            ],
            [
                'title' => 'Spica',
                'title_jp' => 'スピカ',
                'release_date' => '2007-01-23',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'SPICA', 'title_jp' => 'スピカ'],
                    ['title' => 'Romu', 'title_jp' => 'ロム'],
                ]
            ],
            [
                'title' => 'Makka na Ito',
                'title_jp' => '真っ赤な糸',
                'release_date' => '2007-05-16',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Makka na Ito', 'title_jp' => '真っ赤な糸'],
                    ['title' => 'Ai yori Aoku', 'title_jp' => '藍より青く'],
                ]
            ],
            [
                'title' => 'Alone Again, Wonderful World',
                'title_jp' => 'アローンアゲイン、ワンダフルワールド',
                'release_date' => '2008-04-09',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Alone Again Wonderful World', 'title_jp' => 'アローンアゲイン、ワンダフルワールド'],
                    ['title' => 'Psychedelizm', 'title_jp' => 'サイケデリズム'],
                    ['title' => 'Hajimete no XXX (Bonus Track)', 'title_jp' => '初めてのXXX'],
                ]
            ],
            [
                'title' => 'Replay / Dolly',
                'title_jp' => 'リプレイ/ドリー',
                'release_date' => '2008-08-13',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Replay', 'title_jp' => 'リプレイ'],
                    ['title' => 'Dolly', 'title_jp' => 'Dolly'],
                    ['title' => 'Kaisou, Koe wa Naku。', 'title_jp' => '回想、声はなく。'],
                ]
            ],
            [
                'title' => 'Fukurou',
                'title_jp' => '梟',
                'release_date' => '2009-06-10',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Fukuro', 'title_jp' => '梟'],
                    ['title' => 'CONSENT。', 'title_jp' => 'コンセント。'],
                    ['title' => 'Bermuda Triangle', 'title_jp' => 'バミューダトライアングル'],
                ]
            ],
            [
                'title' => 'Sanatorium',
                'title_jp' => 'サナトリウム',
                'release_date' => '2009-10-28',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'SANATORIUM', 'title_jp' => 'サナトリウム'],
                    ['title' => 'PIED PIPER', 'title_jp' => 'パイドパイパー'],
                ]
            ],
            [
                'title' => 'Moonlight----.',
                'title_jp' => 'ムーンライト――――。',
                'release_date' => '2010-07-28',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Moonlight —————。', 'title_jp' => 'ムーンライト────。'],
                    ['title' => 'Bambi', 'title_jp' => 'バンビ'],
                    ['title' => 'Rusty', 'title_jp' => 'Rusty'],
                ]
            ],
            [
                'title' => 'Mirai Iro',
                'title_jp' => 'みらいいろ',
                'release_date' => '2010-12-15',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Mirai iro', 'title_jp' => 'みらいいろ'],
                    ['title' => 'Paper plane', 'title_jp' => 'Paper plane'],
                    ['title' => 'Moonlight —————。 (Shinjuku Live 2010-08-12)', 'title_jp' => 'ムーンライト────。'],
                ]
            ],
            [
                'title' => 'Joumyaku',
                'title_jp' => '静脈',
                'release_date' => '2012-02-29',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Jomyaku', 'title_jp' => '静脈'],
                    ['title' => 'Narihibiku, Kane (Rebuild)', 'title_jp' => '鳴り響く、鐘'],
                ]
            ],
            [
                'title' => 'Kuchizuke',
                'title_jp' => 'くちづけ',
                'release_date' => '2012-06-20',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Kuchizuke', 'title_jp' => 'くちづけ'],
                    ['title' => 'Trance Orange (Rebuild)', 'title_jp' => 'トランスオレンジ'],
                ]
            ],
            [
                'title' => 'Shion',
                'title_jp' => 'シオン',
                'release_date' => '2012-09-05',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'SHION', 'title_jp' => 'シオン'],
                    ['title' => 'Mahiru No Tsuki (Rebuild)', 'title_jp' => 'まひるの月'],
                ]
            ],
            [
                'title' => 'Doukou',
                'title_jp' => '瞳孔',
                'release_date' => '2013-09-04',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Doko', 'title_jp' => '瞳孔'],
                    ['title' => 'Jikanzaka', 'title_jp' => '時間坂'],
                    ['title' => 'AIREN', 'title_jp' => 'アイレン'],
                ]
            ],
            [
                'title' => 'Mime',
                'title_jp' => 'マイム',
                'release_date' => '2014-09-03',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Mime', 'title_jp' => 'マイム'],
                    ['title' => 'TWINKLE', 'title_jp' => 'トゥインクル'],
                    ['title' => 'RECALL', 'title_jp' => 'リコール'],
                ]
            ],
            [
                'title' => 'Slow',
                'title_jp' => 'スロウ',
                'release_date' => '2015-03-04',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Slow', 'title_jp' => 'スロウ'],
                    ['title' => 'CHAOS RIRON', 'title_jp' => 'カオスリロン'],
                    ['title' => 'Mime (Voodoo Carnival Remix)', 'title_jp' => 'マイム'],
                ]
            ],
            [
                'title' => 'Rakka',
                'title_jp' => '落花',
                'release_date' => '2015-09-02',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Rakka', 'title_jp' => '落花'],
                    ['title' => 'Kanshou Diary', 'title_jp' => '感傷ダイアリー'],
                    ['title' => 'GEKKO OVERHEAD (2015-05-23・Danshi gentei kouen-ban)', 'title_jp' => 'GEKKO OVERHEAD'],
                ]
            ],
            [
                'title' => 'Silent Noise',
                'title_jp' => 'サイレントノイズ',
                'release_date' => '2016-08-17',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Silent Noise', 'title_jp' => 'サイレントノイズ'],
                    ['title' => 'Shizuka no Umi', 'title_jp' => '静かの海'],
                    ['title' => 'Shinkuro', 'title_jp' => 'シンクロ'],
                ]
            ],
            [
                'title' => 'Nenriki',
                'title_jp' => '念力',
                'release_date' => '2017-01-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Nenriki', 'title_jp' => '念力'],
                    ['title' => 'creep', 'title_jp' => 'creep'],
                    ['title' => 'Circus (Live Arrange Version)', 'title_jp' => 'サーカス'],
                ]
            ],
            [
                'title' => 'Uchuu Yuuei',
                'title_jp' => '雨中遊泳',
                'release_date' => '2017-06-21',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Uchuu yuuei', 'title_jp' => '雨中遊泳'],
                    ['title' => 'UTOPIA BERRY BLUE', 'title_jp' => 'ユートピアベリーブルー'],
                ]
            ],
            [
                'title' => 'Inside Out',
                'title_jp' => 'インサイドアウト',
                'release_date' => '2018-07-25',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Inside Out', 'title_jp' => 'インサイドアウト'],
                    ['title' => 'Tomoshibi', 'title_jp' => '灯火'],
                ]
            ],
            [
                'title' => 'Senzou',
                'title_jp' => '潜像',
                'release_date' => '2019-09-04',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Senzo', 'title_jp' => '潜像'],
                    ['title' => 'IC', 'title_jp' => 'IC'],
                ]
            ],
            [
                'title' => 'Azabana',
                'title_jp' => '痣花',
                'release_date' => '2023-07-19',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Azabana', 'title_jp' => '痣花'],
                ]
            ],
            [
                'title' => 'Zawameki',
                'title_jp' => 'ざわめき',
                'release_date' => '2023-12-13',
                'type' => 'single',
                'tracks' => [
                    ['title' => 'Zawameki', 'title_jp' => 'ざわめき'],
                ]
            ],
            [
                'title' => 'Cut -Early Songs Best Selection-',
                'title_jp' => 'Cut',
                'release_date' => '2001-03-27',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Psycho Garden', 'title_jp' => 'サイコガーデン'],
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Angelfish', 'title_jp' => 'エンゼルフィッシュ'],
                    ['title' => 'Mahiru No Tsuki', 'title_jp' => 'まひるの月'],
                    ['title' => 'May Day', 'title_jp' => 'May Day'],
                    ['title' => 'Tsumetai Hikari', 'title_jp' => 'ツメタイヒカリ'],
                    ['title' => 'Planetarium', 'title_jp' => 'プラネタリウム'],
                    ['title' => 'Ether Note', 'title_jp' => 'エーテルノート'],
                    ['title' => 'Itai Ao', 'title_jp' => '痛い青'],
                    ['title' => 'Twice', 'title_jp' => 'Twice'],
                    ['title' => 'Nukegara', 'title_jp' => '「ぬけがら」'],
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'CREAM', 'title_jp' => 'クリーム'],
                ]
            ],
            [
                'title' => 'Single Collection',
                'title_jp' => 'Single Collection',
                'release_date' => '2001-11-14',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'Hontou no Uso', 'title_jp' => '本当の嘘'],
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Tremolo', 'title_jp' => 'トレモロ'],
                    ['title' => 'Sink', 'title_jp' => 'Sink'],
                    ['title' => 'Tsumetai Hikari', 'title_jp' => 'ツメタイヒカリ'],
                    ['title' => 'SLIDE', 'title_jp' => 'スライド'],
                    ['title' => 'ROCKET', 'title_jp' => 'ロケット'],
                    ['title' => 'Planetarium', 'title_jp' => 'プラネタリウム'],
                    ['title' => 'Narihibiku, Kane', 'title_jp' => '鳴り響く、鐘'],
                    ['title' => 'Abstruct my life', 'title_jp' => 'アブストラクトマイライフ'],
                    ['title' => 'Panorama', 'title_jp' => 'パノラマ'],
                    ['title' => 'Gessekai', 'title_jp' => '月世界'],
                    ['title' => 'Buranko kara', 'title_jp' => 'ブランコから'],
                    ['title' => 'ORGAN.', 'title_jp' => 'オルガン.'],
                    ['title' => 'Planetarium (98 Version)', 'title_jp' => 'プラネタリウム'],
                    ['title' => 'Ekitai (98 Version)', 'title_jp' => '液体'],
                ]
            ],
            [
                'title' => 'Premium Best',
                'title_jp' => 'Premium Best',
                'release_date' => '2002-11-07',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'May Day', 'title_jp' => 'May Day'],
                    ['title' => 'Tremolo (Ver. 2.0)', 'title_jp' => 'トレモロ'],
                    ['title' => 'Slide', 'title_jp' => 'スライド'],
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Sink (Ver. 2.0)', 'title_jp' => 'Sink'],
                    ['title' => 'Tsumetai Hikari', 'title_jp' => 'ツメタイヒカリ'],
                    ['title' => 'Itai Ao', 'title_jp' => '痛い青'],
                    ['title' => 'Ether Note', 'title_jp' => 'エーテルノート'],
                    ['title' => 'Cream', 'title_jp' => 'クリーム'],
                    ['title' => 'Reset', 'title_jp' => 'リセット'],
                    ['title' => 'Psycho Garden', 'title_jp' => 'サイコガーデン'],
                    ['title' => 'Organ (Alternative Guitar Solo Version)', 'title_jp' => 'オルガン'],
                    ['title' => 'Trance Orange (Live Version)', 'title_jp' => 'トランスオレンジ'],
                ]
            ],
            [
                'title' => 'Best Album Shiro Ban',
                'title_jp' => '白盤',
                'release_date' => '2005-10-26',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Mizuiro no Girlfriend', 'title_jp' => '水色ガールフレンド'],
                    ['title' => 'VERANDA', 'title_jp' => 'ベランダ'],
                    ['title' => '「Yuki Hotaru」', 'title_jp' => '「雪蛍」'],
                    ['title' => 'Nukegara', 'title_jp' => '「ぬけがら」'],
                    ['title' => 'Moshimo Piano ga Hiketa Nara', 'title_jp' => 'もしもピアノが弾けたなら'],
                    ['title' => 'Irogoto', 'title_jp' => 'イロゴト'],
                    ['title' => 'Saishuu Densha', 'title_jp' => '最終電車'],
                    ['title' => 'Aoi Tori', 'title_jp' => '蒼い鳥'],
                    ['title' => 'Gichi Gichi', 'title_jp' => 'ギチギチ'],
                    ['title' => 'Harusaki SENTIMENTAL', 'title_jp' => '春咲センチメンタル'],
                    ['title' => 'Kuuhaku no hi', 'title_jp' => '空白の日'],
                    ['title' => 'Inori', 'title_jp' => '祈り'],
                    ['title' => 'Tsumetai Hikari', 'title_jp' => 'ツメタイヒカリ'],
                ]
            ],
            [
                'title' => 'Best Album Kuro Ban',
                'title_jp' => '黒盤',
                'release_date' => '2005-10-26',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Chiriyuku Bokura', 'title_jp' => '散リユク僕ラ'],
                    ['title' => 'Seiza Zukuri', 'title_jp' => '星座づくり'],
                    ['title' => 'Baka ni Natta no ni', 'title_jp' => 'バカになったのに'],
                    ['title' => 'Chibashi, Wakabaku, Roku jihan', 'title_jp' => '千葉市、若葉区、6時30分'],
                    ['title' => 'MELANCHOLIC', 'title_jp' => 'メランコリック'],
                    ['title' => 'Zange wa Yokushitsu de', 'title_jp' => '懺悔は浴室で'],
                    ['title' => 'Angelfish', 'title_jp' => 'エンゼルフィッシュ'],
                    ['title' => 'cell.', 'title_jp' => 'cell.'],
                    ['title' => 'Glider', 'title_jp' => 'グライダー'],
                    ['title' => 'GERBERA', 'title_jp' => 'ガーベラ'],
                    ['title' => 'Sunday', 'title_jp' => 'サンデー'],
                    ['title' => 'lilac', 'title_jp' => 'lilac'],
                    ['title' => 'National Kid', 'title_jp' => 'ナショナルキッド'],
                    ['title' => 'Kaibutsu-kun', 'title_jp' => '怪物くん'],
                    ['title' => 'Baria', 'title_jp' => 'バリア'],
                    ['title' => 'SLIDE', 'title_jp' => 'スライド'],
                    ['title' => 'Sink', 'title_jp' => 'Sink'],
                ]
            ],
            [
                'title' => 'What is "Plastic Tree"?',
                'title_jp' => '',
                'release_date' => '2007-07-13',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Sanbika', 'title_jp' => '讃美歌'],
                    ['title' => 'Kougousei', 'title_jp' => '光合成'],
                    ['title' => 'Namae no Nai Hana', 'title_jp' => '名前のない花'],
                    ['title' => 'Paranoia', 'title_jp' => 'パラノイア'],
                    ['title' => 'Ghost', 'title_jp' => 'Ghost'],
                    ['title' => 'Suisai', 'title_jp' => '水彩'],
                    ['title' => 'Kuuchuu Buranko', 'title_jp' => '空中ブランコ'],
                    ['title' => 'Tsuki no Hikari wo Tayori ni', 'title_jp' => '月の光を便りに'],
                ]
            ],
            [
                'title' => 'B-Men Gahou',
                'title_jp' => 'B面画報',
                'release_date' => '2007-09-05',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'VERANDA', 'title_jp' => 'ベランダ'],
                    ['title' => 'lilac', 'title_jp' => 'lilac'],
                    ['title' => 'Kougousei', 'title_jp' => '光合成'],
                    ['title' => 'Suisai', 'title_jp' => '水彩'],
                    ['title' => 'Sonzai Riyuu', 'title_jp' => '存在理由'],
                    ['title' => 'Paranoia', 'title_jp' => 'パラノイア'],
                    ['title' => 'Rokugetsu no Ame', 'title_jp' => '六月の雨'],
                    ['title' => 'Honjitsu wa Seiten Nari', 'title_jp' => '本日は晴天なり'],
                    ['title' => 'Synthese', 'title_jp' => 'ジンテーゼ'],
                    ['title' => 'Fuyu no Umi wa Yuuei Kinshi de', 'title_jp' => '冬の海は遊泳禁止で'],
                    ['title' => 'Tsuki no Hikari wo Tayori ni', 'title_jp' => '月の光をたよりに'],
                    ['title' => 'AngelDust', 'title_jp' => 'エンジェルダスト'],
                    ['title' => 'Shiroi Ashiato', 'title_jp' => '白い足跡'],
                    ['title' => 'Romu', 'title_jp' => 'ロム'],
                    ['title' => 'Ai yori Aoku', 'title_jp' => '藍より青く'],
                ]
            ],
            [
                'title' => 'Gestalt Houkai',
                'title_jp' => 'ゲシュタルト崩壊',
                'release_date' => '2009-08-26',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Replay', 'title_jp' => 'リプレイ'],
                    ['title' => 'Zaza Furi, Zaza Nari', 'title_jp' => 'ザザ降り、ザザ鳴り'],
                    ['title' => 'Utsusemi', 'title_jp' => 'うつせみ'],
                    ['title' => 'Dummy Box', 'title_jp' => 'Dummy Box'],
                    ['title' => 'Makka na Ito', 'title_jp' => '真っ赤な糸'],
                    ['title' => 'Sanbika', 'title_jp' => '讃美歌'],
                    ['title' => 'Tetris', 'title_jp' => 'テトリス'],
                    ['title' => 'SPICA', 'title_jp' => 'スピカ'],
                    ['title' => 'Alone Again Wonderful World', 'title_jp' => 'アローンアゲイン、ワンダフルワールド'],
                    ['title' => 'Ghost', 'title_jp' => 'Ghost'],
                    ['title' => 'Melt', 'title_jp' => 'メルト'],
                    ['title' => 'HATE・RED, DIP・IT', 'title_jp' => 'ヘイト・レッド、ディップ・イット'],
                    ['title' => 'Kiokuyuki', 'title_jp' => '記憶行き'],
                    ['title' => 'ANDROMETAMORPHOSE', 'title_jp' => 'アンドロメタモルフォーゼ'],
                    ['title' => 'Kuuchuu BURANKO', 'title_jp' => '空中ブランコ'],
                ]
            ],
            [
                'title' => 'ALL TIME THE BEST',
                'title_jp' => 'ALL TIME THE BEST',
                'release_date' => '2010-07-07',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Monophobia', 'title_jp' => 'Monophobia'],
                    ['title' => 'Shoujokyou Sou', 'title_jp' => '少女狂想'],
                    ['title' => '37℃', 'title_jp' => '37℃'],
                    ['title' => 'Tremolo', 'title_jp' => 'トレモロ'],
                    ['title' => 'Fuyu no Umi wa Yuuei Kinshi de', 'title_jp' => '冬の海は遊泳禁止で'],
                    ['title' => 'NAMIDA DROP', 'title_jp' => 'ナミダドロップ'],
                    ['title' => 'Ruisen Kairo', 'title_jp' => '涙腺回路'],
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'Honjitsu wa Seiten Nari', 'title_jp' => '本日は晴天なり'],
                    ['title' => 'Balloon', 'title_jp' => 'バルーン'],
                    ['title' => 'egg', 'title_jp' => 'egg'],
                    ['title' => 'Mizuiro no Girlfriend', 'title_jp' => '水色ガールフレンド'],
                    ['title' => 'Sanbika', 'title_jp' => '讃美歌'],
                    ['title' => 'ROCKET', 'title_jp' => 'ロケット'],
                    ['title' => 'Nejimaki Neurose', 'title_jp' => 'ねじまきノイローゼ'],
                    ['title' => 'Kuuchuu BURANKO', 'title_jp' => '空中ブランコ'],
                    ['title' => 'MELANCHOLIC', 'title_jp' => 'メランコリック'],
                    ['title' => 'SANATORIUM', 'title_jp' => 'サナトリウム'],
                    ['title' => 'Nemureru Mori', 'title_jp' => '眠れる森'],
                    ['title' => 'Hontou no Uso', 'title_jp' => '本当の嘘'],
                    ['title' => 'National Kid', 'title_jp' => 'ナショナルキッド'],
                    ['title' => 'lilac', 'title_jp' => 'lilac'],
                    ['title' => 'Trance Orange', 'title_jp' => 'トランスオレンジ'],
                    ['title' => 'Q', 'title_jp' => 'Q'],
                    ['title' => 'Harusaki SENTIMENTAL', 'title_jp' => '春咲センチメンタル'],
                    ['title' => 'Mahiru No Tsuki', 'title_jp' => 'まひるの月'],
                    ['title' => '1999', 'title_jp' => '1999'],
                    ['title' => 'Uwa no Sora', 'title_jp' => 'うわのそら'],
                    ['title' => 'Sink', 'title_jp' => 'Sink'],
                    ['title' => 'Gentou Kikai', 'title_jp' => '幻燈機械'],
                    ['title' => 'Closet Child', 'title_jp' => 'クローゼットチャイルド'],
                    ['title' => 'Ghost', 'title_jp' => 'Ghost'],
                    ['title' => 'Replay', 'title_jp' => 'リプレイ'],
                    ['title' => 'Moonlight —————。 (Trailer)', 'title_jp' => 'ムーンライト────。'],
                ]
            ],
            [
                'title' => 'Zoku B-Men Gahou',
                'title_jp' => '続・B面画報',
                'release_date' => '2019-02-13',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Dolly', 'title_jp' => 'Dolly'],
                    ['title' => 'Paper plane', 'title_jp' => 'Paper plane'],
                    ['title' => 'CHAOS RIRON', 'title_jp' => 'カオスリロン'],
                    ['title' => 'Tomoshibi', 'title_jp' => '灯火'],
                    ['title' => 'Shinkuro', 'title_jp' => 'シンクロ'],
                    ['title' => 'Rusty', 'title_jp' => 'Rusty'],
                    ['title' => 'Jikanzaka', 'title_jp' => '時間坂'],
                    ['title' => 'Kaisou, Koe wa Naku。', 'title_jp' => '回想、声はなく。'],
                    ['title' => 'CONSENT。', 'title_jp' => 'コンセント。'],
                    ['title' => 'Bambi', 'title_jp' => 'バンビ'],
                    ['title' => 'Hajimete no ×××', 'title_jp' => 'はじめての×××'],
                    ['title' => 'Shizuka no Umi', 'title_jp' => '静かの海'],
                    ['title' => 'Kanshou Diary', 'title_jp' => '感傷ダイアリー'],
                    ['title' => 'TWINKLE', 'title_jp' => 'トゥインクル'],
                    ['title' => 'PIED PIPER', 'title_jp' => 'パイドパイパー'],
                    ['title' => 'creep', 'title_jp' => 'creep'],
                    ['title' => 'AIREN', 'title_jp' => 'アイレン'],
                    ['title' => 'Psychedelizm', 'title_jp' => 'サイケデリズム'],
                    ['title' => 'Bermuda Triangle', 'title_jp' => 'バミューダトライアングル'],
                    ['title' => 'UTOPIA BERRY BLUE', 'title_jp' => 'ユートピアベリーブルー'],
                    ['title' => 'RECALL', 'title_jp' => 'リコール'],
                ]
            ],
            [
                'title' => '(Re)quest',
                'title_jp' => '(Re)quest',
                'release_date' => '2022-04-28',
                'type' => 'best',
                'tracks' => [
                    ['title' => 'Wareta Mado', 'title_jp' => '割れた窓'],
                    ['title' => 'Zetsubou no Oka', 'title_jp' => '絶望の丘'],
                    ['title' => 'Tremolo', 'title_jp' => 'トレモロ'],
                    ['title' => 'Sink', 'title_jp' => 'Sink'],
                    ['title' => 'ROCKET', 'title_jp' => 'ロケット'],
                    ['title' => 'Suimin yaku', 'title_jp' => '睡眠薬'],
                    ['title' => 'Planetarium', 'title_jp' => 'プラネタリウム'],
                    ['title' => 'Chiriyuku Bokura', 'title_jp' => '散リユク僕ラ'],
                    ['title' => 'Aoi Tori', 'title_jp' => '蒼い鳥'],
                    ['title' => 'Ame ni Uteba', 'title_jp' => '雨ニ唄エバ'],
                    ['title' => '「Yuki Hotaru」', 'title_jp' => '「雪蛍」'],
                    ['title' => 'Fuyu no Umi wa Yuuei Kinshi de', 'title_jp' => '冬の海は遊泳禁止で'],
                    ['title' => 'Harusaki SENTIMENTAL', 'title_jp' => '春咲センチメンタル'],
                    ['title' => 'Kuuchuu BURANKO', 'title_jp' => '空中ブランコ'],
                    ['title' => 'MELANCHOLIC (Rebuild)', 'title_jp' => 'メランコリック'],
                    ['title' => 'SPICA', 'title_jp' => 'スピカ'],
                    ['title' => 'Makka na Ito', 'title_jp' => '真っ赤な糸'],
                    ['title' => 'ZERO', 'title_jp' => 'ゼロ'],
                    ['title' => 'Replay', 'title_jp' => 'リプレイ'],
                    ['title' => 'Fukuro', 'title_jp' => '梟'],
                    ['title' => 'PIED PIPER', 'title_jp' => 'パイドパイパー'],
                    ['title' => 'Mirai iro', 'title_jp' => 'みらいいろ'],
                    ['title' => 'Kuchizuke', 'title_jp' => 'くちづけ'],
                    ['title' => 'LIFE・IS・BEAUTIFUL', 'title_jp' => 'ライフ・イズ・ビューティフル'],
                    ['title' => 'Doko', 'title_jp' => '瞳孔'],
                    ['title' => 'Mime', 'title_jp' => 'マイム'],
                    ['title' => 'Slow', 'title_jp' => 'スロウ'],
                    ['title' => 'Hakusei', 'title_jp' => '剥製'],
                    ['title' => 'Shizuka no Umi', 'title_jp' => '静かの海'],
                    ['title' => 'ENDROLL。', 'title_jp' => 'エンドロール。'],
                ]
            ],
        ];

        $db->beginTransaction();
        try {
            $stmtAlbum = $db->prepare("
                INSERT INTO albums (title, title_jp, release_date, album_type, cover_url, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtSong = $db->prepare("
                INSERT INTO songs (album_id, track_num, title, title_jp, sort_order)
                VALUES (?, ?, ?, ?, ?)
            ");

            $sortOrder = 1;
            foreach ($discography as $a) {
                $stmtAlbum->execute([
                    $a['title'],
                    $a['title_jp'] ?? '',
                    $a['release_date'] ?? '',
                    $a['type'],
                    '', // cover_url se actualizará desde admin o con imágenes por defecto
                    $sortOrder++
                ]);
                $albumId = (int)$db->lastInsertId();

                $trackNum = 1;
                foreach ($a['tracks'] as $t) {
                    $stmtSong->execute([
                        $albumId,
                        $trackNum,
                        $t['title'],
                        $t['title_jp'] ?? '',
                        $trackNum
                    ]);
                    $trackNum++;
                }
            }

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Error sembrando discografía: ' . $e->getMessage());
        }
    }

    // ==========================================
    // MÉTODOS DE CONSULTA Y GESTIÓN
    // ==========================================

    public static function getAlbumsWithSongs(string $search = '', string $type = ''): array {
        $db = self::get();

        $params = [];
        $sql = "SELECT * FROM albums WHERE 1=1";

        if ($type !== '' && $type !== 'all') {
            $sql .= " AND album_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY sort_order ASC, release_date ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $albums = $stmt->fetchAll();

        // Obtener canciones con posible audio preview asignado
        $hasAudioTable = (bool)$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audio_tracks'")->fetch();
        if ($hasAudioTable) {
            $songsSql = "
                SELECT s.*, COALESCE(NULLIF(s.preview_url, ''), at.file_url) AS preview_audio_url 
                FROM songs s 
                LEFT JOIN audio_tracks at ON at.song_id = s.id AND at.is_active = 1
                ORDER BY s.album_id ASC, s.track_num ASC, s.sort_order ASC
            ";
        } else {
            $songsSql = "SELECT s.*, NULLIF(s.preview_url, '') AS preview_audio_url FROM songs s ORDER BY album_id ASC, track_num ASC, sort_order ASC";
        }
        $allSongs = $db->query($songsSql)->fetchAll();

        $songsByAlbum = [];
        foreach ($allSongs as $s) {
            $songsByAlbum[$s['album_id']][] = $s;
        }

        $result = [];
        $searchLower = mb_strtolower(trim($search), 'UTF-8');

        foreach ($albums as $alb) {
            $albSongs = $songsByAlbum[$alb['id']] ?? [];

            if ($searchLower !== '') {
                $albMatches = (mb_strpos(mb_strtolower($alb['title'], 'UTF-8'), $searchLower) !== false) ||
                              (mb_strpos(mb_strtolower($alb['title_jp'] ?? '', 'UTF-8'), $searchLower) !== false);

                $filteredSongs = [];
                foreach ($albSongs as $s) {
                    $songMatches = (mb_strpos(mb_strtolower($s['title'], 'UTF-8'), $searchLower) !== false) ||
                                  (mb_strpos(mb_strtolower($s['title_jp'] ?? '', 'UTF-8'), $searchLower) !== false);
                    if ($songMatches || $albMatches) {
                        $filteredSongs[] = $s;
                    }
                }

                if (empty($filteredSongs) && !$albMatches) {
                    continue;
                }
                $alb['songs'] = $filteredSongs;
            } else {
                $alb['songs'] = $albSongs;
            }

            $result[] = $alb;
        }

        return $result;
    }

    public static function hasVoted(string $email, string $ip, string $token): array {
        $db = self::get();
        $email = trim(mb_strtolower($email, 'UTF-8'));

        // 1. Verificar por email
        $stmt = $db->prepare("SELECT id, voter_email, created_at FROM votes WHERE LOWER(voter_email) = ? LIMIT 1");
        $stmt->execute([$email]);
        $byEmail = $stmt->fetch();
        if ($byEmail) {
            return ['voted' => true, 'reason' => 'email', 'vote' => $byEmail];
        }

        // 2. Verificar por token de dispositivo/navegador si existe
        if (!empty($token)) {
            $stmt = $db->prepare("SELECT id, voter_email, created_at FROM votes WHERE vote_token = ? LIMIT 1");
            $stmt->execute([$token]);
            $byToken = $stmt->fetch();
            if ($byToken) {
                return ['voted' => true, 'reason' => 'device', 'vote' => $byToken];
            }
        }

        // 3. Verificar límite por IP si está activado en settings
        $ipLimitActive = self::getSetting('allow_ip_limit', '1') === '1';
        if ($ipLimitActive && !empty($ip)) {
            $maxIp = (int)self::getSetting('max_votes_per_ip', '3');
            $stmt = $db->prepare("SELECT COUNT(*) FROM votes WHERE voter_ip = ?");
            $stmt->execute([$ip]);
            $ipCount = (int)$stmt->fetchColumn();
            if ($ipCount >= $maxIp) {
                return ['voted' => true, 'reason' => 'ip_limit', 'count' => $ipCount];
            }
        }

        return ['voted' => false];
    }

    public static function submitVote(string $email, string $name, string $nickname, array $songIds, string $ip, string $userAgent, string $token, string $concertLocation = 'Chile'): array {
        $db = self::get();
        $email = trim(mb_strtolower($email, 'UTF-8'));
        $name = trim($name);
        $nickname = trim($nickname);
        $concertLocation = trim($concertLocation) ?: 'Chile';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Por favor ingresa un correo electrónico válido.'];
        }

        // Validar cantidad de canciones (máximo configurado, por defecto 10)
        $maxVotes = (int)self::getSetting('max_votes_per_user', '10');
        $uniqueSongIds = array_unique(array_filter(array_map('intval', $songIds)));

        if (empty($uniqueSongIds)) {
            return ['success' => false, 'error' => 'Debes elegir al menos una canción.'];
        }

        if (count($uniqueSongIds) > $maxVotes) {
            return ['success' => false, 'error' => "Puedes elegir un máximo de {$maxVotes} canciones."];
        }

        // Verificar si ya votó
        $check = self::hasVoted($email, $ip, $token);
        if ($check['voted']) {
            if ($check['reason'] === 'email') {
                return ['success' => false, 'error' => 'Ya existe un voto registrado con este correo electrónico.'];
            } elseif ($check['reason'] === 'device') {
                return ['success' => false, 'error' => 'Ya has emitido tu voto desde este dispositivo.'];
            } elseif ($check['reason'] === 'ip_limit') {
                return ['success' => false, 'error' => 'Se ha alcanzado el límite de votos permitidos para esta red/conexión.'];
            }
        }

        // Obtener info de las canciones
        $placeholders = implode(',', array_fill(0, count($uniqueSongIds), '?'));
        $stmt = $db->prepare("SELECT id, album_id, title FROM songs WHERE id IN ($placeholders)");
        $stmt->execute($uniqueSongIds);
        $validSongs = $stmt->fetchAll();

        if (count($validSongs) !== count($uniqueSongIds)) {
            return ['success' => false, 'error' => 'Algunas canciones seleccionadas no son válidas.'];
        }

        $db->beginTransaction();
        try {
            $stmtVote = $db->prepare("
                INSERT INTO votes (voter_email, voter_name, voter_nickname, concert_location, voter_ip, user_agent, vote_token, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
            ");
            $stmtVote->execute([$email, $name, $nickname, $concertLocation, $ip, $userAgent, $token]);
            $voteId = (int)$db->lastInsertId();

            $stmtItem = $db->prepare("
                INSERT INTO vote_items (vote_id, song_id, album_id, created_at)
                VALUES (?, ?, ?, datetime('now', 'localtime'))
            ");

            foreach ($validSongs as $song) {
                $stmtItem->execute([$voteId, $song['id'], $song['album_id']]);
            }

            $db->commit();
            return ['success' => true, 'vote_id' => $voteId, 'count' => count($validSongs)];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Error al guardar el voto: ' . $e->getMessage()];
        }
    }

    public static function getRanking(string $location = ''): array {
        $db = self::get();

        $loc = trim($location);
        if ($loc === 'Mexico') $loc = 'México';
        $filterByLoc = in_array($loc, ['Chile', 'México']);

        if ($filterByLoc) {
            $stmtVoters = $db->prepare("SELECT COUNT(*) FROM votes WHERE concert_location = ?");
            $stmtVoters->execute([$loc]);
            $totalVoters = (int)$stmtVoters->fetchColumn();

            $stmtVotes = $db->prepare("
                SELECT COUNT(vi.id) 
                FROM vote_items vi 
                JOIN votes v ON vi.vote_id = v.id 
                WHERE v.concert_location = ?
            ");
            $stmtVotes->execute([$loc]);
            $totalVotes = (int)$stmtVotes->fetchColumn();

            $sql = "
                SELECT 
                    s.id AS song_id,
                    s.title AS song_title,
                    s.title_jp AS song_title_jp,
                    COALESCE(NULLIF(s.preview_url, ''), at.file_url) AS preview_audio_url,
                    a.id AS album_id,
                    a.title AS album_title,
                    a.album_type,
                    a.cover_url,
                    COUNT(vi.id) AS votes_count
                FROM songs s
                JOIN albums a ON s.album_id = a.id
                LEFT JOIN audio_tracks at ON at.song_id = s.id AND at.is_active = 1
                JOIN vote_items vi ON s.id = vi.song_id
                JOIN votes v ON vi.vote_id = v.id AND v.concert_location = ?
                GROUP BY s.id
                HAVING votes_count > 0
                ORDER BY votes_count DESC, s.title ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$loc]);
            $ranking = $stmt->fetchAll();
        } else {
            $totalVoters = (int)$db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
            $totalVotes = (int)$db->query("SELECT COUNT(*) FROM vote_items")->fetchColumn();

            $sql = "
                SELECT 
                    s.id AS song_id,
                    s.title AS song_title,
                    s.title_jp AS song_title_jp,
                    COALESCE(NULLIF(s.preview_url, ''), at.file_url) AS preview_audio_url,
                    a.id AS album_id,
                    a.title AS album_title,
                    a.album_type,
                    a.cover_url,
                    COUNT(vi.id) AS votes_count
                FROM songs s
                JOIN albums a ON s.album_id = a.id
                LEFT JOIN audio_tracks at ON at.song_id = s.id AND at.is_active = 1
                JOIN vote_items vi ON s.id = vi.song_id
                GROUP BY s.id
                HAVING votes_count > 0
                ORDER BY votes_count DESC, s.title ASC
            ";
            $ranking = $db->query($sql)->fetchAll();
        }

        // Calcular porcentaje sobre votantes únicos
        foreach ($ranking as &$row) {
            $row['percentage'] = $totalVoters > 0 ? round(($row['votes_count'] / $totalVoters) * 100, 1) : 0;
        }

        return [
            'total_voters' => $totalVoters,
            'total_votes' => $totalVotes,
            'location' => $loc,
            'ranking' => $ranking
        ];
    }

    public static function getVotesList(int $limit = 50, int $offset = 0): array {
        $db = self::get();

        $total = (int)$db->query("SELECT COUNT(*) FROM votes")->fetchColumn();

        $stmt = $db->prepare("
            SELECT v.*, 
                   (SELECT COUNT(*) FROM vote_items vi WHERE vi.vote_id = v.id) AS songs_count
            FROM votes v
            ORDER BY v.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $votes = $stmt->fetchAll();

        // Cargar nombres de canciones para cada voto
        foreach ($votes as &$v) {
            $stmtSongs = $db->prepare("
                SELECT s.title, s.title_jp, a.title AS album_title
                FROM vote_items vi
                JOIN songs s ON vi.song_id = s.id
                JOIN albums a ON vi.album_id = a.id
                WHERE vi.vote_id = ?
                ORDER BY a.title ASC, s.title ASC
            ");
            $stmtSongs->execute([$v['id']]);
            $v['songs'] = $stmtSongs->fetchAll();
        }

        return [
            'total' => $total,
            'votes' => $votes
        ];
    }

    public static function deleteVote(int $id): bool {
        $db = self::get();
        $stmt = $db->prepare("DELETE FROM votes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function clearAllVotes(): bool {
        $db = self::get();
        $db->exec("DELETE FROM vote_items;");
        $db->exec("DELETE FROM votes;");
        return true;
    }

    public static function updateAlbumCover(int $albumId, string $coverUrl): bool {
        $db = self::get();
        $stmt = $db->prepare("UPDATE albums SET cover_url = ? WHERE id = ?");
        return $stmt->execute([$coverUrl, $albumId]);
    }

    public static function addSong(int $albumId, string $title, string $titleJp = '', int $trackNum = 0): int {
        $db = self::get();
        if ($trackNum <= 0) {
            $maxTrack = (int)$db->query("SELECT MAX(track_num) FROM songs WHERE album_id = {$albumId}")->fetchColumn();
            $trackNum = $maxTrack + 1;
        }
        $stmt = $db->prepare("
            INSERT INTO songs (album_id, track_num, title, title_jp, sort_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$albumId, $trackNum, trim($title), trim($titleJp), $trackNum]);
        return (int)$db->lastInsertId();
    }

    public static function deleteSong(int $songId): bool {
        $db = self::get();
        $stmt = $db->prepare("DELETE FROM songs WHERE id = ?");
        return $stmt->execute([$songId]);
    }

    public static function updateSong(int $songId, string $title, string $titleJp): bool {
        $db = self::get();
        $stmt = $db->prepare("UPDATE songs SET title = ?, title_jp = ? WHERE id = ?");
        return $stmt->execute([trim($title), trim($titleJp), $songId]);
    }

    public static function addAlbum(string $title, string $titleJp, string $releaseDate, string $type, string $coverUrl = ''): int {
        $db = self::get();
        $maxSort = (int)$db->query("SELECT MAX(sort_order) FROM albums")->fetchColumn();
        $stmt = $db->prepare("
            INSERT INTO albums (title, title_jp, release_date, album_type, cover_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([trim($title), trim($titleJp), trim($releaseDate), $type, trim($coverUrl), $maxSort + 1]);
        return (int)$db->lastInsertId();
    }

    public static function getSetting(string $key, string $default = ''): string {
        $db = self::get();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (string)$row['value'] : $default;
    }

    public static function setSetting(string $key, string $value): bool {
        $db = self::get();
        $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
        return $stmt->execute([$key, $value]);
    }

    public static function getAudioTracks(bool $activeOnly = false): array {
        $db = self::get();
        $hasAudioTable = (bool)$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audio_tracks'")->fetch();
        if (!$hasAudioTable) {
            return [];
        }

        $sql = "
            SELECT at.*, s.title AS song_title, s.title_jp AS song_title_jp, alb.title AS album_title, alb.cover_url 
            FROM audio_tracks at
            LEFT JOIN songs s ON at.song_id = s.id
            LEFT JOIN albums alb ON s.album_id = alb.id
        ";
        if ($activeOnly) {
            $sql .= " WHERE at.is_active = 1";
        }
        $sql .= " ORDER BY at.id DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function addAudioTrack(string $title, string $artist, string $fileUrl, ?int $songId = null): int {
        $db = self::get();
        self::initSchema();
        $stmt = $db->prepare("
            INSERT INTO audio_tracks (title, artist, file_url, song_id, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([trim($title), trim($artist) ?: 'Plastic Tree', trim($fileUrl), $songId ?: null]);
        return (int)$db->lastInsertId();
    }

    public static function deleteAudioTrack(int $id): bool {
        $db = self::get();
        $stmt = $db->prepare("SELECT file_url FROM audio_tracks WHERE id = ?");
        $stmt->execute([$id]);
        $url = $stmt->fetchColumn();
        if ($url && strpos($url, 'assets/audio/') !== false) {
            $localPath = __DIR__ . '/' . ltrim($url, '/');
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }
        $del = $db->prepare("DELETE FROM audio_tracks WHERE id = ?");
        return $del->execute([$id]);
    }

    public static function updateSongPreview(int $songId, string $previewUrl): bool {
        $db = self::get();
        self::initSchema();
        $stmt = $db->prepare("UPDATE songs SET preview_url = ? WHERE id = ?");
        return $stmt->execute([trim($previewUrl), $songId]);
    }

    public static function getAllPlaylistTracks(): array {
        $db = self::get();
        self::initSchema();
        $tracks = [];
        $seenUrls = [];

        // 1. Pistas de audio_tracks
        $audioTracks = self::getAudioTracks(true);
        foreach ($audioTracks as $t) {
            $tracks[] = [
                'id' => (int)$t['id'],
                'title' => $t['title'],
                'artist' => $t['artist'] ?: 'Plastic Tree',
                'file_url' => $t['file_url'],
                'song_id' => $t['song_id'] ? (int)$t['song_id'] : null,
                'album_title' => $t['album_title'] ?? '',
                'cover_url' => $t['cover_url'] ?? ''
            ];
            $seenUrls[$t['file_url']] = true;
        }

        // 2. Canciones con preview_url configurado
        $songsWithPreview = $db->query("
            SELECT s.id AS song_id, s.title, s.title_jp, s.preview_url, a.title AS album_title, a.cover_url
            FROM songs s
            JOIN albums a ON s.album_id = a.id
            WHERE s.preview_url IS NOT NULL AND s.preview_url != ''
        ")->fetchAll();

        foreach ($songsWithPreview as $sp) {
            if (!isset($seenUrls[$sp['preview_url']])) {
                $tracks[] = [
                    'id' => 'song_' . $sp['song_id'],
                    'title' => $sp['title'],
                    'artist' => 'Plastic Tree',
                    'file_url' => $sp['preview_url'],
                    'song_id' => (int)$sp['song_id'],
                    'album_title' => $sp['album_title'],
                    'cover_url' => $sp['cover_url']
                ];
                $seenUrls[$sp['preview_url']] = true;
            }
        }

        return $tracks;
    }
}
