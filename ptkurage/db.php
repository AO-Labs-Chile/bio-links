<?php
/**
 * Capa de almacenamiento y datos para PT:Kurage
 * Soporta SQLite nativo con fallback automático a JSON
 */

defined('PT_KURAGE_APP') or define('PT_KURAGE_APP', true);

class KurageDB {
    private static $instance = null;
    private $pdo = null;
    private $use_json = false;
    private $data_dir;
    private $db_file;
    private $json_file;

    private function __construct() {
        $this->data_dir = __DIR__ . '/data';
        if (!is_dir($this->data_dir)) {
            @mkdir($this->data_dir, 0755, true);
        }

        $this->db_file = $this->data_dir . '/ptkurage.sqlite';
        $this->json_file = $this->data_dir . '/records.json';

        if (extension_loaded('pdo_sqlite')) {
            try {
                $this->pdo = new PDO('sqlite:' . $this->db_file);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->init_sqlite_tables();
            } catch (Exception $e) {
                $this->use_json = true;
            }
        } else {
            $this->use_json = true;
        }

        if ($this->use_json && !file_exists($this->json_file)) {
            file_put_contents($this->json_file, json_encode(['records' => [], 'settings' => []], JSON_PRETTY_PRINT));
        }
    }

    public static function get() {
        if (self::$instance === null) {
            self::$instance = new KurageDB();
        }
        return self::$instance;
    }

    private function init_sqlite_tables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE COLLATE NOCASE,
                code TEXT,
                country TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_checked_at DATETIME,
                last_sent_at DATETIME,
                sent_count INTEGER DEFAULT 0
            );
            CREATE INDEX IF NOT EXISTS idx_email ON records(email);

            CREATE TABLE IF NOT EXISTS settings (
                s_key TEXT PRIMARY KEY,
                s_val TEXT
            );

            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT,
                action_name TEXT,
                attempts INTEGER DEFAULT 1,
                first_attempt INTEGER,
                last_attempt INTEGER
            );
            CREATE INDEX IF NOT EXISTS idx_rate ON rate_limits(ip, action_name);
        ");

        $columns_to_add = [
            'country' => 'TEXT',
            'delivery_status' => "TEXT DEFAULT 'pending'",
            'last_status_at' => 'DATETIME',
            'bounce_reason' => 'TEXT',
            'resend_email_id' => 'TEXT'
        ];
        foreach ($columns_to_add as $col => $type) {
            try {
                $this->pdo->exec("ALTER TABLE records ADD COLUMN $col $type");
            } catch (Exception $e) {
                // Columna ya existe
            }
        }

        try {
            $this->pdo->exec("UPDATE records SET delivery_status = 'sent' WHERE sent_count > 0 AND (delivery_status = 'pending' OR delivery_status IS NULL OR delivery_status = '')");
        } catch (Exception $e) {
            // Ignorar
        }
    }

    public function check_ip_limit($ip, $action, $max_attempts = 15, $window_seconds = 60) {
        $now = time();
        if (!$this->use_json) {
            // Limpiar registros antiguos
            $this->pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < :cutoff")->execute([':cutoff' => $now - 3600]);

            $stmt = $this->pdo->prepare("SELECT * FROM rate_limits WHERE ip = :ip AND action_name = :action LIMIT 1");
            $stmt->execute([':ip' => $ip, ':action' => $action]);
            $row = $stmt->fetch();

            if (!$row) {
                $ins = $this->pdo->prepare("INSERT INTO rate_limits (ip, action_name, attempts, first_attempt, last_attempt) VALUES (:ip, :action, 1, :now, :now)");
                $ins->execute([':ip' => $ip, ':action' => $action, ':now' => $now]);
                return true;
            }

            if (($now - $row['first_attempt']) < $window_seconds) {
                if ($row['attempts'] >= $max_attempts) {
                    return false; // Bloqueado
                }
                $up = $this->pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = :now WHERE id = :id");
                $up->execute([':now' => $now, ':id' => $row['id']]);
                return true;
            } else {
                // Ventana expiró, reiniciar contador
                $up = $this->pdo->prepare("UPDATE rate_limits SET attempts = 1, first_attempt = :now, last_attempt = :now WHERE id = :id");
                $up->execute([':now' => $now, ':id' => $row['id']]);
                return true;
            }
        } else {
            // Fallback en sesión o JSON
            $key = 'rate_' . $action . '_' . md5($ip);
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = ['count' => 1, 'start' => $now];
                return true;
            }
            if (($now - $_SESSION[$key]['start']) < $window_seconds) {
                if ($_SESSION[$key]['count'] >= $max_attempts) {
                    return false;
                }
                $_SESSION[$key]['count']++;
                return true;
            } else {
                $_SESSION[$key] = ['count' => 1, 'start' => $now];
                return true;
            }
        }
    }

    // --- JSON Helpers ---
    private function load_json() {
        if (!file_exists($this->json_file)) {
            return ['records' => [], 'settings' => []];
        }
        $data = json_decode(file_get_contents($this->json_file), true);
        if (!is_array($data)) {
            $data = ['records' => [], 'settings' => []];
        }
        if (!isset($data['records'])) $data['records'] = [];
        if (!isset($data['settings'])) $data['settings'] = [];
        return $data;
    }

    private function save_json($data) {
        file_put_contents($this->json_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    // --- Public Methods ---

    public function find_by_email($email) {
        $email = trim(strtolower($email));
        if (empty($email)) return null;

        if (!$this->use_json) {
            $stmt = $this->pdo->prepare("SELECT * FROM records WHERE LOWER(email) = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch();
            if ($row) {
                // Update last_checked_at
                $up = $this->pdo->prepare("UPDATE records SET last_checked_at = datetime('now') WHERE id = :id");
                $up->execute([':id' => $row['id']]);
                return $row;
            }
            return null;
        } else {
            $data = $this->load_json();
            foreach ($data['records'] as &$r) {
                if (strtolower($r['email']) === $email) {
                    $r['last_checked_at'] = date('Y-m-d H:i:s');
                    $this->save_json($data);
                    return $r;
                }
            }
            return null;
        }
    }

    public function find_by_id($id) {
        $id = (int)$id;
        if ($id <= 0) return null;

        if (!$this->use_json) {
            $stmt = $this->pdo->prepare("SELECT * FROM records WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } else {
            $data = $this->load_json();
            foreach ($data['records'] as $r) {
                if ((int)$r['id'] === $id) {
                    return $r;
                }
            }
            return null;
        }
    }

    public function get_records_by_ids(array $ids) {
        $ids = array_filter(array_map('intval', $ids), function($id) { return $id > 0; });
        if (empty($ids)) return [];

        if (!$this->use_json) {
            $in_clause = implode(',', $ids);
            $stmt = $this->pdo->query("SELECT * FROM records WHERE id IN ($in_clause) ORDER BY id ASC");
            return $stmt->fetchAll() ?: [];
        } else {
            $data = $this->load_json();
            $id_map = array_flip($ids);
            $res = [];
            foreach ($data['records'] as $r) {
                if (isset($id_map[(int)$r['id']])) {
                    $res[] = $r;
                }
            }
            return $res;
        }
    }

    public function record_sent($email, $resend_id = null) {
        $email = trim(strtolower($email));
        if (empty($email)) return false;

        if (!$this->use_json) {
            $sql = "UPDATE records SET sent_count = sent_count + 1, last_sent_at = datetime('now'), delivery_status = 'sent'";
            $params = [':email' => $email];
            if (!empty($resend_id)) {
                $sql .= ", resend_email_id = :resend_id";
                $params[':resend_id'] = $resend_id;
            }
            $sql .= " WHERE LOWER(email) = :email";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } else {
            $data = $this->load_json();
            foreach ($data['records'] as &$r) {
                if (strtolower($r['email']) === $email) {
                    $r['sent_count'] = ($r['sent_count'] ?? 0) + 1;
                    $r['last_sent_at'] = date('Y-m-d H:i:s');
                    $r['delivery_status'] = 'sent';
                    if (!empty($resend_id)) $r['resend_email_id'] = $resend_id;
                    $this->save_json($data);
                    return true;
                }
            }
            return false;
        }
    }

    public function update_delivery_status($email, $status, $resend_id = null, $reason = null) {
        $email = trim(strtolower($email));
        if (empty($email)) return false;

        $status = strtolower(trim($status));
        $valid_statuses = ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'sent';
        }

        if (!$this->use_json) {
            // Si el estado actual ya es 'clicked', no degradar si llega un 'opened' duplicado
            $stmt = $this->pdo->prepare("SELECT delivery_status FROM records WHERE LOWER(email) = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $current_status = $stmt->fetchColumn();

            if ($current_status === 'clicked' && $status === 'opened') {
                $status = 'clicked';
            }

            $sql = "UPDATE records SET delivery_status = :status, last_status_at = datetime('now')";
            $params = [':status' => $status, ':email' => $email];

            if ($resend_id !== null && $resend_id !== '') {
                $sql .= ", resend_email_id = :resend_id";
                $params[':resend_id'] = $resend_id;
            }
            if ($reason !== null && $reason !== '') {
                $sql .= ", bounce_reason = :reason";
                $params[':reason'] = $reason;
            }

            $sql .= " WHERE LOWER(email) = :email";
            $up = $this->pdo->prepare($sql);
            return $up->execute($params);
        } else {
            $data = $this->load_json();
            foreach ($data['records'] as &$r) {
                if (strtolower($r['email']) === $email) {
                    if (($r['delivery_status'] ?? '') === 'clicked' && $status === 'opened') {
                        $status = 'clicked';
                    }
                    $r['delivery_status'] = $status;
                    $r['last_status_at'] = date('Y-m-d H:i:s');
                    if ($resend_id) $r['resend_email_id'] = $resend_id;
                    if ($reason) $r['bounce_reason'] = $reason;
                    $this->save_json($data);
                    return true;
                }
            }
            return false;
        }
    }

    public function import_batch(array $rows, $replace = false) {
        $imported = 0;
        $updated = 0;

        if (!$this->use_json) {
            $this->pdo->beginTransaction();
            try {
                if ($replace) {
                    $this->pdo->exec("DELETE FROM records");
                    $existing_map = [];
                } else {
                    $existing_rows = $this->pdo->query("SELECT LOWER(email) AS em FROM records")->fetchAll();
                    $existing_map = [];
                    foreach ($existing_rows as $er) {
                        $existing_map[$er['em']] = true;
                    }
                }

                $stmt_insert = $this->pdo->prepare("
                    INSERT INTO records (email, code, country, created_at) 
                    VALUES (:email, :code, :country, datetime('now'))
                    ON CONFLICT(email) DO UPDATE SET 
                        code = CASE WHEN excluded.code != '' THEN excluded.code ELSE records.code END,
                        country = CASE WHEN excluded.country != '' THEN excluded.country ELSE records.country END
                ");

                foreach ($rows as $row) {
                    $email = trim(strtolower($row['email'] ?? ''));
                    $code = trim($row['code'] ?? '');
                    $country = trim($row['country'] ?? $row['pais'] ?? '');
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    $stmt_insert->execute([':email' => $email, ':code' => $code, ':country' => $country]);
                    if (isset($existing_map[$email])) {
                        $updated++;
                    } else {
                        $imported++;
                        $existing_map[$email] = true;
                    }
                }

                $this->pdo->commit();
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } else {
            $data = $this->load_json();
            if ($replace) {
                $data['records'] = [];
            }

            $map = [];
            foreach ($data['records'] as $idx => $r) {
                $map[strtolower($r['email'])] = $idx;
            }

            foreach ($rows as $row) {
                $email = trim(strtolower($row['email'] ?? ''));
                $code = trim($row['code'] ?? '');
                $country = trim($row['country'] ?? $row['pais'] ?? '');
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                if (isset($map[$email])) {
                    if (!empty($code)) $data['records'][$map[$email]]['code'] = $code;
                    if (!empty($country)) $data['records'][$map[$email]]['country'] = $country;
                    $updated++;
                } else {
                    $new_id = count($data['records']) + 1;
                    $data['records'][] = [
                        'id' => $new_id,
                        'email' => $email,
                        'code' => $code,
                        'country' => $country,
                        'created_at' => date('Y-m-d H:i:s'),
                        'last_checked_at' => null,
                        'last_sent_at' => null,
                        'sent_count' => 0
                    ];
                    $map[$email] = count($data['records']) - 1;
                    $imported++;
                }
            }
            $this->save_json($data);
        }

        return ['imported' => $imported, 'updated' => $updated];
    }

    public function get_records($search = '', $page = 1, $per_page = 50, $sort_by = 'id', $sort_dir = 'DESC', $country = '', $status = '') {
        $search = trim($search);
        $country = trim($country);
        $status = strtolower(trim($status));
        $offset = ($page - 1) * $per_page;

        $allowed_sorts = ['id', 'email', 'code', 'country', 'sent_count', 'last_sent_at', 'created_at', 'delivery_status'];
        if (!in_array($sort_by, $allowed_sorts)) {
            $sort_by = 'id';
        }
        $sort_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';

        if (!$this->use_json) {
            $where_clauses = [];
            $params = [];

            if (!empty($search)) {
                $where_clauses[] = "(email LIKE :s OR code LIKE :s OR country LIKE :s)";
                $params[':s'] = "%$search%";
            }

            if (!empty($country)) {
                if ($country === '__empty__') {
                    $where_clauses[] = "(country IS NULL OR trim(country) = '')";
                } else {
                    $where_clauses[] = "LOWER(country) = LOWER(:country)";
                    $params[':country'] = $country;
                }
            }

            if (!empty($status)) {
                if ($status === 'pending') {
                    $where_clauses[] = "(sent_count = 0 OR delivery_status = 'pending' OR delivery_status IS NULL OR delivery_status = '')";
                } elseif ($status === 'sent') {
                    $where_clauses[] = "delivery_status = 'sent'";
                } elseif ($status === 'delivered') {
                    $where_clauses[] = "delivery_status = 'delivered'";
                } elseif ($status === 'opened') {
                    $where_clauses[] = "delivery_status IN ('opened', 'clicked')";
                } elseif ($status === 'clicked') {
                    $where_clauses[] = "delivery_status = 'clicked'";
                } elseif ($status === 'bounced') {
                    $where_clauses[] = "delivery_status = 'bounced'";
                } elseif ($status === 'complained') {
                    $where_clauses[] = "delivery_status = 'complained'";
                }
            }

            $where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

            $count_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM records $where");
            $count_stmt->execute($params);
            $total = (int)$count_stmt->fetchColumn();

            // Construcción segura del ORDER BY
            if ($sort_by === 'last_sent_at') {
                $order_clause = "ORDER BY (last_sent_at IS NULL OR last_sent_at = '') ASC, last_sent_at $sort_dir, id DESC";
            } elseif ($sort_by === 'country') {
                $order_clause = "ORDER BY (country IS NULL OR country = '') ASC, country COLLATE NOCASE $sort_dir, email ASC";
            } elseif ($sort_by === 'email' || $sort_by === 'code') {
                $order_clause = "ORDER BY $sort_by COLLATE NOCASE $sort_dir";
            } elseif ($sort_by === 'sent_count') {
                $order_clause = "ORDER BY sent_count $sort_dir, id DESC";
            } elseif ($sort_by === 'delivery_status') {
                $order_clause = "ORDER BY (delivery_status IS NULL OR delivery_status = '') ASC, delivery_status $sort_dir, id DESC";
            } else {
                $order_clause = "ORDER BY $sort_by $sort_dir";
            }

            $list_stmt = $this->pdo->prepare("SELECT * FROM records $where $order_clause LIMIT :limit OFFSET :offset");
            foreach ($params as $k => $v) {
                $list_stmt->bindValue($k, $v);
            }
            $list_stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
            $list_stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $list_stmt->execute();
            $items = $list_stmt->fetchAll();

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $per_page];
        } else {
            $data = $this->load_json();
            $filtered = [];
            foreach ($data['records'] as $r) {
                if (!empty($search)) {
                    $matches = (stripos($r['email'] ?? '', $search) !== false || 
                                stripos($r['code'] ?? '', $search) !== false || 
                                stripos($r['country'] ?? '', $search) !== false);
                    if (!$matches) continue;
                }
                if (!empty($country)) {
                    $rc = trim($r['country'] ?? '');
                    if ($country === '__empty__') {
                        if ($rc !== '') continue;
                    } else {
                        if (strtolower($rc) !== strtolower($country)) continue;
                    }
                }
                if (!empty($status)) {
                    $ds = $r['delivery_status'] ?? 'pending';
                    $sc = (int)($r['sent_count'] ?? 0);
                    if ($status === 'pending' && !($sc === 0 || $ds === 'pending' || empty($ds))) continue;
                    if ($status === 'sent' && $ds !== 'sent') continue;
                    if ($status === 'delivered' && $ds !== 'delivered') continue;
                    if ($status === 'opened' && !($ds === 'opened' || $ds === 'clicked')) continue;
                    if ($status === 'clicked' && $ds !== 'clicked') continue;
                    if ($status === 'bounced' && $ds !== 'bounced') continue;
                    if ($status === 'complained' && $ds !== 'complained') continue;
                }
                $filtered[] = $r;
            }
            usort($filtered, function($a, $b) use ($sort_by, $sort_dir) {
                $valA = $a[$sort_by] ?? '';
                $valB = $b[$sort_by] ?? '';
                if ($valA == $valB) return 0;
                $cmp = ($valA < $valB) ? -1 : 1;
                return $sort_dir === 'ASC' ? $cmp : -$cmp;
            });
            $total = count($filtered);
            $items = array_slice($filtered, $offset, $per_page);
            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $per_page];
        }
    }

    public function get_countries() {
        if (!$this->use_json) {
            $stmt = $this->pdo->query("
                SELECT 
                    CASE WHEN country IS NULL OR trim(country) = '' THEN '__empty__' ELSE trim(country) END AS country_name,
                    COUNT(*) AS count 
                FROM records 
                GROUP BY country_name 
                ORDER BY count DESC, country_name ASC
            ");
            return $stmt->fetchAll() ?: [];
        } else {
            $data = $this->load_json();
            $counts = [];
            foreach ($data['records'] as $r) {
                $c = trim($r['country'] ?? '');
                $name = ($c === '') ? '__empty__' : $c;
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
            $res = [];
            foreach ($counts as $name => $count) {
                $res[] = ['country_name' => $name, 'count' => $count];
            }
            usort($res, function($a, $b) {
                if ($a['count'] == $b['count']) return strcmp($a['country_name'], $b['country_name']);
                return $b['count'] - $a['count'];
            });
            return $res;
        }
    }

    public function get_all_records() {
        if (!$this->use_json) {
            return $this->pdo->query("SELECT email, code, country, sent_count, last_sent_at, created_at FROM records ORDER BY id ASC")->fetchAll();
        } else {
            $data = $this->load_json();
            return $data['records'];
        }
    }

    public function upsert_single($email, $code, $id = null, $country = '') {
        $email = trim(strtolower($email));
        $code = trim($code);
        $country = trim($country);

        if (!$this->use_json) {
            if ($id) {
                $stmt = $this->pdo->prepare("UPDATE records SET email = :email, code = :code, country = :country WHERE id = :id");
                return $stmt->execute([':email' => $email, ':code' => $code, ':country' => $country, ':id' => $id]);
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO records (email, code, country, created_at) VALUES (:email, :code, :country, datetime('now'))
                    ON CONFLICT(email) DO UPDATE SET 
                        code = excluded.code,
                        country = excluded.country
                ");
                return $stmt->execute([':email' => $email, ':code' => $code, ':country' => $country]);
            }
        } else {
            $data = $this->load_json();
            if ($id) {
                foreach ($data['records'] as &$r) {
                    if ($r['id'] == $id) {
                        $r['email'] = $email;
                        $r['code'] = $code;
                        if (!empty($country)) $r['country'] = $country;
                        $this->save_json($data);
                        return true;
                    }
                }
            } else {
                foreach ($data['records'] as &$r) {
                    if (strtolower($r['email']) === $email) {
                        $r['code'] = $code;
                        if (!empty($country)) $r['country'] = $country;
                        $this->save_json($data);
                        return true;
                    }
                }
                $data['records'][] = [
                    'id' => count($data['records']) + 1,
                    'email' => $email,
                    'code' => $code,
                    'country' => $country,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_checked_at' => null,
                    'last_sent_at' => null,
                    'sent_count' => 0
                ];
                $this->save_json($data);
                return true;
            }
            return false;
        }
    }

    public function delete_record($id) {
        if (!$this->use_json) {
            $stmt = $this->pdo->prepare("DELETE FROM records WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } else {
            $data = $this->load_json();
            $data['records'] = array_values(array_filter($data['records'], function($r) use ($id) {
                return $r['id'] != $id;
            }));
            $this->save_json($data);
            return true;
        }
    }

    public function delete_records_batch(array $ids) {
        $ids = array_filter(array_map('intval', $ids), function($id) { return $id > 0; });
        if (empty($ids)) return 0;

        if (!$this->use_json) {
            $in_clause = implode(',', $ids);
            return $this->pdo->exec("DELETE FROM records WHERE id IN ($in_clause)");
        } else {
            $data = $this->load_json();
            $id_map = array_flip($ids);
            $before = count($data['records']);
            $data['records'] = array_values(array_filter($data['records'], function($r) use ($id_map) {
                return !isset($id_map[$r['id']]);
            }));
            $this->save_json($data);
            return $before - count($data['records']);
        }
    }

    public function clear_all_records() {
        if (!$this->use_json) {
            $this->pdo->exec("DELETE FROM records");
            $this->pdo->exec("VACUUM");
            return true;
        } else {
            $data = $this->load_json();
            $data['records'] = [];
            $this->save_json($data);
            return true;
        }
    }

    public function get_stats() {
        if (!$this->use_json) {
            $total = (int)$this->pdo->query("SELECT COUNT(*) FROM records")->fetchColumn();
            $sent = (int)$this->pdo->query("SELECT COUNT(*) FROM records WHERE sent_count > 0")->fetchColumn();
            $total_sent = (int)$this->pdo->query("SELECT COALESCE(SUM(sent_count), 0) FROM records")->fetchColumn();
            $checked = (int)$this->pdo->query("SELECT COUNT(*) FROM records WHERE last_checked_at IS NOT NULL")->fetchColumn();
            $delivered = (int)$this->pdo->query("SELECT COUNT(*) FROM records WHERE delivery_status = 'delivered'")->fetchColumn();
            $opened = (int)$this->pdo->query("SELECT COUNT(*) FROM records WHERE delivery_status IN ('opened', 'clicked')")->fetchColumn();
            $bounced = (int)$this->pdo->query("SELECT COUNT(*) FROM records WHERE delivery_status = 'bounced'")->fetchColumn();
            return [
                'total' => $total,
                'sent_users' => $sent,
                'total_sends' => $total_sent,
                'checked' => $checked,
                'delivered' => $delivered,
                'opened' => $opened,
                'bounced' => $bounced
            ];
        } else {
            $data = $this->load_json();
            $total = count($data['records']);
            $sent = 0;
            $total_sent = 0;
            $checked = 0;
            $delivered = 0;
            $opened = 0;
            $bounced = 0;
            foreach ($data['records'] as $r) {
                if (!empty($r['sent_count'])) {
                    $sent++;
                    $total_sent += (int)$r['sent_count'];
                }
                if (!empty($r['last_checked_at'])) {
                    $checked++;
                }
                $ds = $r['delivery_status'] ?? 'pending';
                if ($ds === 'delivered') $delivered++;
                if ($ds === 'opened' || $ds === 'clicked') $opened++;
                if ($ds === 'bounced') $bounced++;
            }
            return [
                'total' => $total,
                'sent_users' => $sent,
                'total_sends' => $total_sent,
                'checked' => $checked,
                'delivered' => $delivered,
                'opened' => $opened,
                'bounced' => $bounced
            ];
        }
    }

    public function get_setting($key, $default = null) {
        if (!$this->use_json) {
            $stmt = $this->pdo->prepare("SELECT s_val FROM settings WHERE s_key = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();
            return ($val !== false) ? $val : $default;
        } else {
            $data = $this->load_json();
            return $data['settings'][$key] ?? $default;
        }
    }

    public function set_setting($key, $value) {
        if (!$this->use_json) {
            $stmt = $this->pdo->prepare("
                INSERT INTO settings (s_key, s_val) VALUES (:k, :v)
                ON CONFLICT(s_key) DO UPDATE SET s_val = excluded.s_val
            ");
            return $stmt->execute([':k' => $key, ':v' => $value]);
        } else {
            $data = $this->load_json();
            $data['settings'][$key] = $value;
            $this->save_json($data);
            return true;
        }
    }
}
