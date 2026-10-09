<?php
/**
 * PT:Kurage - Encuesta Discografía Plastic Tree
 * API REST para Votación Pública y Administración
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

use KurageEncuesta\DB;

// Iniciar sesión segura para administración
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Headers JSON y CORS
header('Content-Type: application/json; charset=utf-8');

function sendJson($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getClientIp(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = explode(',', $_SERVER[$k])[0];
            return trim($ip);
        }
    }
    return '127.0.0.1';
}

function isAdmin(): bool {
    // Si ya está logueado en el panel principal o en este subpanel
    return !empty($_SESSION['encuesta_admin_logged']) || !empty($_SESSION['admin_logged']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        sendJson(['success' => false, 'error' => 'No autorizado. Inicia sesión en el panel.'], 401);
    }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {

        // ==========================================
        // ENDPOINTS PÚBLICOS
        // ==========================================

        case 'get_discography':
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? 'all';
            $albums = DB::getAlbumsWithSongs($search, $type);
            $surveyActive = DB::getSetting('survey_active', '1') === '1';
            $maxVotes = (int)DB::getSetting('max_votes_per_user', '10');
            $googleClientId = DB::getSetting('google_client_id', '');
            $requireGoogleAuth = DB::getSetting('require_google_auth', '0') === '1';
            $playlist = DB::getAllPlaylistTracks();

            sendJson([
                'success' => true,
                'albums' => $albums,
                'survey_active' => $surveyActive,
                'max_votes' => $maxVotes,
                'google_client_id' => $googleClientId,
                'require_google_auth' => $requireGoogleAuth,
                'playlist' => $playlist
            ]);
            break;

        case 'check_status':
            $email = $_GET['email'] ?? '';
            $token = $_COOKIE['kurage_vote_token'] ?? ($_GET['token'] ?? '');
            $ip = getClientIp();

            if (empty($email) && empty($token)) {
                sendJson(['success' => true, 'has_voted' => false]);
            }

            $check = DB::hasVoted($email, $ip, $token);
            sendJson([
                'success' => true,
                'has_voted' => $check['voted'],
                'reason' => $check['reason'] ?? ''
            ]);
            break;

        case 'submit_vote':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendJson(['success' => false, 'error' => 'Método no permitido.'], 405);
            }

            $surveyActive = DB::getSetting('survey_active', '1') === '1';
            if (!$surveyActive) {
                sendJson(['success' => false, 'error' => 'La votación se encuentra pausada o ha finalizado. ¡Gracias por participar!']);
            }

            // Datos de la votación
            $rawInput = file_get_contents('php://input');
            $payload = json_decode($rawInput, true);

            $email = trim($payload['email'] ?? ($_POST['email'] ?? ''));
            $name = trim($payload['name'] ?? ($_POST['name'] ?? ''));
            $nickname = trim($payload['nickname'] ?? ($_POST['nickname'] ?? ''));
            $concertLocation = trim($payload['concert_location'] ?? ($_POST['concert_location'] ?? 'Chile'));
            if (!in_array($concertLocation, ['Chile', 'México', 'Mexico'])) {
                $concertLocation = 'Chile';
            }
            if ($concertLocation === 'Mexico') {
                $concertLocation = 'México';
            }
            $songs = $payload['songs'] ?? ($_POST['songs'] ?? []);
            $clientToken = $payload['token'] ?? ($_COOKIE['kurage_vote_token'] ?? '');

            if (is_string($songs)) {
                $songs = json_decode($songs, true) ?: explode(',', $songs);
            }

            if (empty($email)) {
                sendJson(['success' => false, 'error' => 'Debes ingresar tu correo electrónico.']);
            }

            $requireGoogleAuth = DB::getSetting('require_google_auth', '0') === '1';
            $googleVerified = !empty($payload['google_verified']) || !empty($_POST['google_verified']);
            if ($requireGoogleAuth && !$googleVerified) {
                sendJson(['success' => false, 'error' => 'Esta encuesta requiere autenticarte con tu cuenta de Google para validar tu voto.']);
            }

            if (empty($clientToken)) {
                $clientToken = bin2hex(random_bytes(16));
            }

            $ip = getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $result = DB::submitVote($email, $name, $nickname, (array)$songs, $ip, $userAgent, $clientToken, $concertLocation);

            if ($result['success']) {
                // Guardar cookie por 1 año
                setcookie('kurage_vote_token', $clientToken, [
                    'expires' => time() + (365 * 24 * 3600),
                    'path' => '/ptkurage/encuesta/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                $result['token'] = $clientToken;
            }

            sendJson($result);
            break;

        case 'get_ranking_preview':
            $location = trim($_GET['location'] ?? '');
            $rankingData = DB::getRanking($location);
            // Para vista pública, mostramos el top 20
            $top20 = array_slice($rankingData['ranking'], 0, 20);
            sendJson([
                'success' => true,
                'location' => $rankingData['location'] ?? '',
                'total_voters' => $rankingData['total_voters'],
                'total_votes' => $rankingData['total_votes'],
                'top_songs' => $top20
            ]);
            break;

        // ==========================================
        // ENDPOINTS ADMINISTRATIVOS (LOGIN / AUTH)
        // ==========================================

        case 'admin_login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendJson(['success' => false, 'error' => 'Método no permitido.'], 405);
            }

            $password = $_POST['password'] ?? '';
            // Verificamos con el hash de config.php o la contraseña por defecto kurage2026
            $valid = password_verify($password, ADMIN_PASSWORD_HASH) || ($password === 'kurage2026');

            if ($valid) {
                $_SESSION['encuesta_admin_logged'] = true;
                sendJson(['success' => true, 'message' => 'Sesión iniciada con éxito.']);
            } else {
                sendJson(['success' => false, 'error' => 'Contraseña incorrecta.']);
            }
            break;

        case 'admin_logout':
            unset($_SESSION['encuesta_admin_logged']);
            sendJson(['success' => true]);
            break;

        case 'admin_check_auth':
            sendJson(['success' => true, 'logged' => isAdmin()]);
            break;

        // ==========================================
        // ENDPOINTS ADMINISTRATIVOS (DASHBOARD & RESULTADOS)
        // ==========================================

        case 'admin_get_dashboard':
            requireAdmin();
            $rankingData = DB::getRanking();
            $recentVotes = DB::getVotesList(20, 0);

            $db = DB::get();
            $totalAlbums = (int)$db->query("SELECT COUNT(*) FROM albums")->fetchColumn();
            $totalSongs = (int)$db->query("SELECT COUNT(*) FROM songs")->fetchColumn();

            // Canciones con vista previa / enlace a YouTube o audio
            $totalLinked = (int)$db->query("
                SELECT COUNT(DISTINCT s.id) 
                FROM songs s
                LEFT JOIN audio_tracks at ON at.song_id = s.id AND at.is_active = 1
                WHERE (s.preview_url IS NOT NULL AND s.preview_url != '')
                   OR (at.file_url IS NOT NULL AND at.file_url != '')
            ")->fetchColumn();

            sendJson([
                'success' => true,
                'total_voters' => $rankingData['total_voters'],
                'total_votes' => $rankingData['total_votes'],
                'total_albums' => $totalAlbums,
                'total_songs' => $totalSongs,
                'total_linked_songs' => $totalLinked,
                'ranking' => $rankingData['ranking'],
                'recent_votes' => $recentVotes['votes'],
                'settings' => [
                    'survey_active' => DB::getSetting('survey_active', '1'),
                    'max_votes_per_user' => DB::getSetting('max_votes_per_user', '10'),
                    'max_votes_per_ip' => DB::getSetting('max_votes_per_ip', '3'),
                    'allow_ip_limit' => DB::getSetting('allow_ip_limit', '1'),
                    'google_client_id' => DB::getSetting('google_client_id', '')
                ]
            ]);
            break;

        case 'admin_get_votes_page':
            requireAdmin();
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(10, min(200, (int)($_GET['per_page'] ?? 50)));
            $offset = ($page - 1) * $limit;

            $votesData = DB::getVotesList($limit, $offset);
            sendJson([
                'success' => true,
                'page' => $page,
                'per_page' => $limit,
                'total' => $votesData['total'],
                'votes' => $votesData['votes']
            ]);
            break;

        case 'admin_delete_vote':
            requireAdmin();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                sendJson(['success' => false, 'error' => 'ID inválido.']);
            }
            DB::deleteVote($id);
            sendJson(['success' => true]);
            break;

        case 'admin_clear_all_votes':
            requireAdmin();
            DB::clearAllVotes();
            sendJson(['success' => true, 'message' => 'Se han borrado todos los votos de prueba exitosamente.']);
            break;

        case 'admin_export_csv':
            requireAdmin();
            $rankingData = DB::getRanking();
            $votesList = DB::getVotesList(10000, 0);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="resultados_encuesta_plastictree_' . date('Ymd_His') . '.csv"');

            $out = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            // Sección 1: Ranking de Canciones
            fputcsv($out, ['--- RANKING GENERAL DE CANCIONES ---']);
            fputcsv($out, ['Posición', 'Canción', 'Título Japonés', 'Álbum / Single', 'Tipo', 'Cantidad de Votos', '% de Votantes']);

            $pos = 1;
            foreach ($rankingData['ranking'] as $r) {
                fputcsv($out, [
                    $pos++,
                    $r['song_title'],
                    $r['song_title_jp'] ?? '',
                    $r['album_title'],
                    strtoupper($r['album_type']),
                    $r['votes_count'],
                    $r['percentage'] . '%'
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['--- LISTADO INDIVIDUAL DE VOTANTES ---']);
            fputcsv($out, ['ID', 'Email', 'Nombre', 'Apodo', 'Concierto', 'IP', 'Fecha / Hora (Chile)', 'Canciones Elegidas']);

            foreach ($votesList['votes'] as $v) {
                $songNames = array_map(function($s) {
                    return $s['title'] . ' (' . $s['album_title'] . ')';
                }, $v['songs'] ?? []);

                fputcsv($out, [
                    $v['id'],
                    $v['voter_email'],
                    $v['voter_name'] ?: 'Anónimo',
                    $v['voter_nickname'] ?? '',
                    $v['concert_location'] ?? 'Chile',
                    $v['voter_ip'] ?? '',
                    $v['created_at'],
                    implode(' | ', $songNames)
                ]);
            }

            fclose($out);
            exit;

        // ==========================================
        // ENDPOINTS ADMINISTRATIVOS (DISCOGRAFÍA Y COVERS)
        // ==========================================

        case 'admin_upload_cover':
            requireAdmin();
            $albumId = (int)($_POST['album_id'] ?? 0);
            if ($albumId <= 0) {
                sendJson(['success' => false, 'error' => 'ID de álbum no proporcionado.']);
            }

            // Opción 1: Subida de archivo
            if (!empty($_FILES['cover_file']['name'])) {
                $file = $_FILES['cover_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    sendJson(['success' => false, 'error' => 'Error al subir el archivo.']);
                }

                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    sendJson(['success' => false, 'error' => 'Formato no permitido. Usa JPG, PNG, WEBP o GIF.']);
                }

                $coversDir = __DIR__ . '/assets/covers';
                if (!is_dir($coversDir)) {
                    mkdir($coversDir, 0755, true);
                }

                $filename = 'cover_' . $albumId . '_' . time() . '.' . $ext;
                $dest = $coversDir . '/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $coverUrl = 'assets/covers/' . $filename;
                    DB::updateAlbumCover($albumId, $coverUrl);
                    sendJson(['success' => true, 'cover_url' => $coverUrl]);
                } else {
                    sendJson(['success' => false, 'error' => 'No se pudo mover el archivo al servidor.']);
                }
            } 
            // Opción 2: URL de imagen remota
            elseif (!empty($_POST['cover_url'])) {
                $coverUrl = trim($_POST['cover_url']);
                DB::updateAlbumCover($albumId, $coverUrl);
                sendJson(['success' => true, 'cover_url' => $coverUrl]);
            } else {
                sendJson(['success' => false, 'error' => 'No se adjuntó archivo ni URL.']);
            }
            break;

        case 'admin_add_song':
            requireAdmin();
            $albumId = (int)($_POST['album_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $titleJp = trim($_POST['title_jp'] ?? '');
            $trackNum = (int)($_POST['track_num'] ?? 0);

            if ($albumId <= 0 || empty($title)) {
                sendJson(['success' => false, 'error' => 'El título de la canción y el álbum son obligatorios.']);
            }

            $newId = DB::addSong($albumId, $title, $titleJp, $trackNum);
            sendJson(['success' => true, 'song_id' => $newId]);
            break;

        case 'admin_update_song':
            requireAdmin();
            $songId = (int)($_POST['song_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $titleJp = trim($_POST['title_jp'] ?? '');

            if ($songId <= 0 || empty($title)) {
                sendJson(['success' => false, 'error' => 'Datos insuficientes.']);
            }

            DB::updateSong($songId, $title, $titleJp);
            sendJson(['success' => true]);
            break;

        case 'admin_update_song_preview':
            requireAdmin();
            $songId = (int)($_POST['song_id'] ?? 0);
            if ($songId <= 0) {
                sendJson(['success' => false, 'error' => 'ID de canción inválido.']);
            }

            $previewUrl = trim($_POST['preview_url'] ?? '');

            // Subida de MP3 opcional
            if (!empty($_FILES['audio_file']['name'])) {
                $file = $_FILES['audio_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    sendJson(['success' => false, 'error' => 'Error al subir el archivo de audio.']);
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['mp3', 'm4a', 'ogg', 'wav'];
                if (!in_array($ext, $allowed)) {
                    sendJson(['success' => false, 'error' => 'Formato no permitido. Solo se aceptan MP3, M4A, OGG o WAV.']);
                }
                $audioDir = __DIR__ . '/assets/audio';
                if (!is_dir($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }
                $filename = 'song_' . $songId . '_' . time() . '.' . $ext;
                $target = $audioDir . '/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    sendJson(['success' => false, 'error' => 'No se pudo guardar el archivo.']);
                }
                $previewUrl = 'assets/audio/' . $filename;
            }

            DB::updateSongPreview($songId, $previewUrl);
            sendJson([
                'success' => true,
                'song_id' => $songId,
                'preview_url' => $previewUrl,
                'message' => 'Audio / YouTube actualizado con éxito.'
            ]);
            break;

        case 'admin_delete_song':
            requireAdmin();
            $songId = (int)($_POST['song_id'] ?? 0);
            if ($songId <= 0) {
                sendJson(['success' => false, 'error' => 'ID inválido.']);
            }
            DB::deleteSong($songId);
            sendJson(['success' => true]);
            break;

        case 'admin_add_album':
            requireAdmin();
            $title = trim($_POST['title'] ?? '');
            $titleJp = trim($_POST['title_jp'] ?? '');
            $releaseDate = trim($_POST['release_date'] ?? '');
            $type = trim($_POST['album_type'] ?? 'studio');
            $coverUrl = trim($_POST['cover_url'] ?? '');

            if (empty($title)) {
                sendJson(['success' => false, 'error' => 'El título del álbum es obligatorio.']);
            }

            $newId = DB::addAlbum($title, $titleJp, $releaseDate, $type, $coverUrl);
            sendJson(['success' => true, 'album_id' => $newId]);
            break;

        case 'admin_update_settings':
            requireAdmin();
            if (isset($_POST['survey_active'])) {
                DB::setSetting('survey_active', $_POST['survey_active'] === '1' ? '1' : '0');
            }
            if (isset($_POST['max_votes_per_user'])) {
                $maxV = max(1, min(50, (int)$_POST['max_votes_per_user']));
                DB::setSetting('max_votes_per_user', (string)$maxV);
            }
            if (isset($_POST['allow_ip_limit'])) {
                DB::setSetting('allow_ip_limit', $_POST['allow_ip_limit'] === '1' ? '1' : '0');
            }
            if (isset($_POST['max_votes_per_ip'])) {
                $maxIp = max(1, min(20, (int)$_POST['max_votes_per_ip']));
                DB::setSetting('max_votes_per_ip', (string)$maxIp);
            }
            if (isset($_POST['google_client_id'])) {
                DB::setSetting('google_client_id', trim($_POST['google_client_id']));
            }
            if (isset($_POST['require_google_auth'])) {
                DB::setSetting('require_google_auth', $_POST['require_google_auth'] === '1' ? '1' : '0');
            }

            sendJson(['success' => true, 'message' => 'Configuraciones guardadas exitosamente.']);
            break;

        case 'admin_get_audio_tracks':
            requireAdmin();
            $tracks = DB::getAudioTracks(false);
            sendJson(['success' => true, 'tracks' => $tracks]);
            break;

        case 'admin_upload_audio':
            requireAdmin();
            $title = trim($_POST['title'] ?? '');
            $artist = trim($_POST['artist'] ?? '') ?: 'Plastic Tree';
            $songId = !empty($_POST['song_id']) ? (int)$_POST['song_id'] : null;
            $fileUrl = trim($_POST['audio_url'] ?? '');

            if (!empty($_FILES['audio_file']['name'])) {
                $file = $_FILES['audio_file'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    sendJson(['success' => false, 'error' => 'Error al cargar el archivo de audio.']);
                }
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['mp3', 'm4a', 'ogg', 'wav', 'aac'];
                if (!in_array($ext, $allowed)) {
                    sendJson(['success' => false, 'error' => 'Formato no permitido. Solo se aceptan MP3, M4A, OGG o WAV.']);
                }
                if ($file['size'] > 25 * 1024 * 1024) {
                    sendJson(['success' => false, 'error' => 'El archivo supera el tamaño máximo permitido (25MB).']);
                }

                $audioDir = __DIR__ . '/assets/audio';
                if (!is_dir($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }
                $filename = 'track_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $audioDir . '/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    sendJson(['success' => false, 'error' => 'No se pudo guardar el archivo en el servidor.']);
                }
                $fileUrl = 'assets/audio/' . $filename;
            }

            if (empty($fileUrl)) {
                sendJson(['success' => false, 'error' => 'Debes seleccionar un archivo de audio o escribir una URL.']);
            }

            if (empty($title)) {
                $title = !empty($_FILES['audio_file']['name']) 
                    ? pathinfo($_FILES['audio_file']['name'], PATHINFO_FILENAME) 
                    : 'Plastic Tree - Pista';
            }

            $newId = DB::addAudioTrack($title, $artist, $fileUrl, $songId);
            sendJson(['success' => true, 'id' => $newId, 'file_url' => $fileUrl]);
            break;

        case 'admin_delete_audio':
            requireAdmin();
            $trackId = (int)($_POST['id'] ?? 0);
            if ($trackId <= 0) {
                sendJson(['success' => false, 'error' => 'ID inválido.']);
            }
            DB::deleteAudioTrack($trackId);
            sendJson(['success' => true]);
            break;

        default:
            sendJson(['success' => false, 'error' => 'Acción no válida o no especificada.'], 400);
            break;
    }
} catch (\Throwable $e) {
    error_log('Error en API Encuesta: ' . $e->getMessage());
    sendJson(['success' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
}
