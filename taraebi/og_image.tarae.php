<?php
// taraebi 공유용 OG 이미지 생성 
if (!defined('_GNUBOARD_')) {
    $skin_path = dirname(__FILE__);
    $common_path = realpath($skin_path . '/../../../_common.php');
    if ($common_path && file_exists($common_path)) {
        include_once($common_path);
    } else {
        exit;
    }
}

// OG 메타 생성 유틸 (list.tarae.skin.php 등에서 재사용)
if (!function_exists('tarae_apply_share_meta')) {
    function tarae_apply_share_meta($write_table, $current_bo_table, $parent_wr_id, $board_skin_url)
    {
        global $config, $tarae_share_meta_enabled, $tarae_share_meta;
        $tarae_share_term = isset($_GET['tarae_search']) ? trim($_GET['tarae_search']) : '';
        $tarae_share_parent = isset($_GET['tarae_parent']) ? (int)$_GET['tarae_parent'] : 0;
        $tarae_share_id = isset($_GET['tarae_id']) ? (int)$_GET['tarae_id'] : 0;
        $tarae_share_meta_enabled = false;
        $tarae_share_meta = '';
        if ($tarae_share_term !== '' || $tarae_share_id > 0) {
            $tarae_share_meta_enabled = true;
            $share_subject = $tarae_share_term ?: ($config['cf_title'] ?? '');
            $share_desc = $config['cf_description'] ?? '';
            $share_image = '';
            $share_row = null;
            $is_comment_share = false;
            if ($tarae_share_id > 0) {
                $share_row = sql_fetch("SELECT wr_id, wr_parent, wr_subject, wr_content, mb_id, wr_11, wr_12, wr_13, wr_14, wr_15, wr_16, wr_17, wr_18, wr_19, wr_20, wr_21, wr_22, wr_23, wr_24, wr_25, wr_26 FROM {$write_table} WHERE wr_id = '{$tarae_share_id}' LIMIT 1");
                if ($share_row && isset($share_row['wr_parent'], $share_row['wr_id'])) {
                    $is_comment_share = ((int)$share_row['wr_parent'] !== (int)$share_row['wr_id']);
                }
            }
            $parent_wr_id_safe = 0;
            if ($share_row) {
                $parent_wr_id_safe = 0;
            } elseif ($tarae_share_parent > 0) {
                $parent_wr_id_safe = $tarae_share_parent;
            } elseif (isset($parent_wr_id) && (int)$parent_wr_id > 0) {
                $parent_wr_id_safe = (int)$parent_wr_id;
            }
            if (!$share_row && $parent_wr_id_safe > 0 && $tarae_share_term !== '') {
                $safe_term = sql_real_escape_string($tarae_share_term);
                $share_row = sql_fetch("SELECT wr_subject, wr_content, mb_id, wr_11, wr_12, wr_13, wr_14, wr_15, wr_16, wr_17, wr_18, wr_19, wr_20, wr_21, wr_22, wr_23, wr_24, wr_25, wr_26 FROM {$write_table} WHERE wr_parent = '{$parent_wr_id_safe}' AND wr_4 = 'tarae' AND wr_subject = '{$safe_term}' LIMIT 1");
            }
            if (!$share_row && $tarae_share_term !== '') {
                $safe_term = sql_real_escape_string($tarae_share_term);
                $share_row = sql_fetch("SELECT wr_subject, wr_content, mb_id, wr_11, wr_12, wr_13, wr_14, wr_15, wr_16, wr_17, wr_18, wr_19, wr_20, wr_21, wr_22, wr_23, wr_24, wr_25, wr_26 FROM {$write_table} WHERE wr_4 = 'tarae' AND wr_subject = '{$safe_term}' ORDER BY wr_datetime DESC LIMIT 1");
            }
            if ($share_row) {
                $share_subject = $share_row['wr_subject'] ? $share_row['wr_subject'] : $share_subject;
                $share_desc = $share_row['wr_content'] ? $share_row['wr_content'] : '';
                for ($i = 11; $i <= 26; $i++) {
                    $field = 'wr_' . $i;
                    if (!empty($share_row[$field])) {
                        $share_image = trim($share_row[$field]);
                        break;
                    }
                }
                if (!$share_image && !empty($share_row['mb_id'])) {
                    $share_mem = get_member($share_row['mb_id'], 'mb_signature');
                    if ($share_mem && !empty($share_mem['mb_signature'])) {
                        $share_image = trim($share_mem['mb_signature']);
                    }
                }
            }
            $share_subject = strip_tags($share_subject);
            $share_desc = html_entity_decode(strip_tags($share_desc), ENT_QUOTES, 'UTF-8');
            $share_desc = str_replace(array("\r", "\n", "\t", '&nbsp;'), ' ', $share_desc);
            $share_desc = preg_replace('/\s+/', ' ', $share_desc);
            $share_desc = trim($share_desc);
            if ($share_desc !== '') {
                $share_desc = cut_str($share_desc, 200, '...');
            } else {
                $share_desc = $share_subject;
            }
            $share_og_image = $board_skin_url . '/og_image.tarae.php?bo_table=' . urlencode($current_bo_table);
            if (isset($parent_wr_id) && (int)$parent_wr_id > 0) {
                $share_og_image .= '&wr_id=' . (int)$parent_wr_id;
            }
            if ($tarae_share_parent > 0) {
                $share_og_image .= '&tarae_parent=' . (int)$tarae_share_parent;
            }
            if ($tarae_share_term !== '') {
                $share_og_image .= '&tarae_search=' . urlencode($tarae_share_term);
            }
            if ($tarae_share_id > 0) {
                $share_og_image .= '&tarae_id=' . (int)$tarae_share_id;
            }
            if ($tarae_share_term !== '' || $tarae_share_id > 0) {
                $ogv_seed = $share_subject . '|' . $share_desc;
                $share_og_image .= '&ogv=' . substr(sha1($ogv_seed), 0, 12);
            }
            if (strpos($share_og_image, '//') === 0) {
                $share_og_image = 'https:' . $share_og_image;
            } elseif (!preg_match('~^https?://~i', $share_og_image)) {
                $share_og_image = rtrim(G5_URL, '/') . '/' . ltrim($share_og_image, '/');
            }
            $share_url = G5_URL . $_SERVER['REQUEST_URI'];
            $tarae_share_meta = '<meta property="og:type" content="article">' . PHP_EOL
                . '<meta property="og:site_name" content="' . htmlspecialchars($config['cf_title']) . '">' . PHP_EOL
                . '<meta property="og:title" content="' . htmlspecialchars($share_subject) . '">' . PHP_EOL
                . '<meta property="og:description" content="' . htmlspecialchars($share_desc) . '">' . PHP_EOL
                . '<meta property="og:url" content="' . htmlspecialchars($share_url) . '">' . PHP_EOL
                . '<meta property="og:image" content="' . htmlspecialchars($share_og_image) . '">' . PHP_EOL
                . '<meta property="og:image:width" content="1200">' . PHP_EOL
                . '<meta property="og:image:height" content="630">' . PHP_EOL
                . '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL
                . '<meta name="twitter:title" content="' . htmlspecialchars($share_subject) . '">' . PHP_EOL
                . '<meta name="twitter:description" content="' . htmlspecialchars($share_desc) . '">' . PHP_EOL
                . '<meta name="twitter:image" content="' . htmlspecialchars($share_og_image) . '">' . PHP_EOL;
        }

        if ($tarae_share_meta_enabled && function_exists('add_replace')) {
            if (!function_exists('tarae_share_add_meta')) {
                function tarae_share_add_meta($meta = '') {
                    return '';
                }
            }
            if (!function_exists('tarae_share_filter_buffer')) {
                function tarae_share_filter_buffer($buffer = '') {
                    global $tarae_share_meta_enabled, $tarae_share_meta;
                    if (!$tarae_share_meta_enabled || !$buffer) {
                        return $buffer;
                    }
                    $buffer = preg_replace('/<meta[^>]+property=["\']og:(title|description|url|image|image:width|image:height|site_name|type)["\'][^>]*>\s*/i', '', $buffer);
                    $buffer = preg_replace('/<meta[^>]+name=["\']twitter:(card|title|description|image)["\'][^>]*>\s*/i', '', $buffer);
                    if ($tarae_share_meta) {
                        $buffer = preg_replace('#</head>#i', $tarae_share_meta . "\n</head>", $buffer, 1);
                    }
                    return $buffer;
                }
            }
            add_replace('html_process_add_meta', 'tarae_share_add_meta', 0, 1);
            add_replace('html_process_buffer', 'tarae_share_filter_buffer', 0, 1);
        }
    }
}

// 리스트에서 include될 때는 OG 이미지 출력 로직을 건너뜀
if (defined('TARAE_OG_META_ONLY')) {
    return;
}

// 파라미터 정리
$bo_table = isset($_GET['bo_table']) ? preg_replace('/[^a-z0-9_]/i', '', $_GET['bo_table']) : '';
if (!$bo_table) {
    exit;
}
$write_table = $g5['write_prefix'] . $bo_table;
$_tarae_board = sql_fetch("SELECT bo_1_subj FROM {$g5['board_table']} WHERE bo_table = '{$bo_table}' LIMIT 1");
$parent_wr_id = isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : 0;
$search_term = isset($_GET['tarae_search']) ? trim($_GET['tarae_search']) : '';
$tarae_parent = isset($_GET['tarae_parent']) ? (int)$_GET['tarae_parent'] : 0;
$tarae_id = isset($_GET['tarae_id']) ? (int)$_GET['tarae_id'] : 0;
$tarae_debug = isset($_GET['tarae_debug']) ? (int)$_GET['tarae_debug'] : 0;
$debug_target_parent = ($search_term !== '') ? ($tarae_parent > 0 ? $tarae_parent : $parent_wr_id) : 0;
$target_parents = array();
if ($parent_wr_id > 0) {
    $target_parents[] = (int)$parent_wr_id;
}
if ($tarae_parent > 0) {
    $target_parents[] = (int)$tarae_parent;
}
$target_parents = array_values(array_unique($target_parents));

// 텍스트/이미지 준비
$share_subject = $search_term ?: ($config['cf_title'] ?? '');
$share_desc = $config['cf_description'] ?? '';
$share_image = '';
$force_text_only = $search_term !== '';
$share_row = null;
$share_row_source = '';

if ($tarae_id > 0) {
    $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$tarae_id}' LIMIT 1");
    if ($share_row) {
        $share_row_source = 'share_id';
    }
} elseif ($search_term !== '') {
    $safe_term = sql_real_escape_string($search_term);
    foreach ($target_parents as $target_parent) {
        $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_parent = '{$target_parent}' AND wr_4 = 'tarae' AND wr_subject = '{$safe_term}' LIMIT 1");
        if ($share_row) {
            $share_row_source = 'parent_subject_match';
            break;
        }
    }
    if (!$share_row) {
        foreach ($target_parents as $target_parent) {
            $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_parent = '{$target_parent}' AND wr_subject = '{$safe_term}' LIMIT 1");
            if ($share_row) {
                $share_row_source = 'parent_subject_match_any';
                break;
            }
        }
    }
    if (!$share_row && $tarae_parent > 0) {
        $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$tarae_parent}' AND wr_parent != wr_id AND wr_4 = 'tarae' LIMIT 1");
        if ($share_row) {
            $share_row_source = 'tarae_parent_as_comment_id';
        }
    }
    if (!$share_row && $tarae_parent > 0) {
        $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$tarae_parent}' AND wr_parent != wr_id LIMIT 1");
        if ($share_row) {
            $share_row_source = 'tarae_parent_as_comment_id_any';
        }
    }
    if (!$share_row) {
        $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_4 = 'tarae' AND wr_subject = '{$safe_term}' ORDER BY wr_datetime DESC LIMIT 1");
        if ($share_row) {
            $share_row_source = 'subject_global_latest';
        }
    }
    if (!$share_row) {
        $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_subject = '{$safe_term}' ORDER BY wr_datetime DESC LIMIT 1");
        if ($share_row) {
            $share_row_source = 'subject_global_latest_any';
        }
    }
} elseif ($parent_wr_id > 0) {
    $share_row = sql_fetch("SELECT * FROM {$write_table} WHERE wr_id = '{$parent_wr_id}' LIMIT 1");
    if ($share_row) {
        $share_row_source = 'parent_by_id';
    }
}

if ($share_row) {
    if (!empty($share_row['wr_subject'])) {
        $share_subject = $share_row['wr_subject'];
    }
    if (!empty($share_row['wr_content'])) {
        $share_desc = $share_row['wr_content'];
    }
    if (!$force_text_only) {
        for ($i = 11; $i <= 26; $i++) {
            $field = 'wr_' . $i;
            if (!empty($share_row[$field])) {
                $share_image = trim($share_row[$field]);
                break;
            }
        }
        if (!$share_image && !empty($share_row['mb_id'])) {
            $share_mem = get_member($share_row['mb_id'], 'mb_signature');
            if ($share_mem && !empty($share_mem['mb_signature'])) {
                $share_image = trim($share_mem['mb_signature']);
            }
        }
    }
}

$share_subject = strip_tags($share_subject);
$share_desc = str_ireplace(array('<br>', '<br/>', '<br />'), "\n", $share_desc);
$share_desc = html_entity_decode(strip_tags($share_desc), ENT_QUOTES, 'UTF-8');
$share_desc = str_replace(array("\r\n", "\r"), "\n", $share_desc);
$share_desc = str_replace(array("\t", '&nbsp;'), ' ', $share_desc);
$lines_raw = preg_split("/\n+/", $share_desc);
$clean_lines = array();
foreach ($lines_raw as $line) {
    $line = preg_replace('/\s+/', ' ', $line);
    $line = trim($line);
    if ($line !== '') {
        $clean_lines[] = $line;
    }
}
$share_desc = implode("\n", $clean_lines);

// 디버그 출력 (JSON)
if ($tarae_debug === 1) {
    $debug_parent_match_count = null;
    $debug_parent_match_any_count = null;
    $debug_parent_match_counts = array();
    $debug_parent_match_any_counts = array();
    $debug_parent_latest = array();
    if (!empty($target_parents)) {
        $safe_term = sql_real_escape_string($search_term);
        foreach ($target_parents as $candidate_parent) {
            $count_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_parent = '{$candidate_parent}' AND wr_4 = 'tarae' AND wr_subject = '{$safe_term}'");
            $debug_parent_match_counts[(string)$candidate_parent] = $count_row && isset($count_row['cnt']) ? (int)$count_row['cnt'] : 0;
            $count_row_any = sql_fetch("SELECT COUNT(*) AS cnt FROM {$write_table} WHERE wr_parent = '{$candidate_parent}' AND wr_subject = '{$safe_term}'");
            $debug_parent_match_any_counts[(string)$candidate_parent] = $count_row_any && isset($count_row_any['cnt']) ? (int)$count_row_any['cnt'] : 0;
        }
        $first_parent = $target_parents[0];
        $debug_parent_latest = sql_query("SELECT wr_id, wr_parent, wr_subject, wr_4, wr_datetime FROM {$write_table} WHERE wr_parent = '{$first_parent}' ORDER BY wr_datetime DESC LIMIT 5");
        $debug_parent_match_count = $debug_parent_match_counts[(string)$first_parent] ?? 0;
        $debug_parent_match_any_count = $debug_parent_match_any_counts[(string)$first_parent] ?? 0;
    }
    $debug_payload = array(
        'bo_table' => $bo_table,
        'write_table' => $write_table,
        'parent_wr_id' => $parent_wr_id,
        'tarae_parent' => $tarae_parent,
        'tarae_search' => $search_term,
        'tarae_search_hex' => bin2hex($search_term),
        'debug_target_parent' => $debug_target_parent,
        'target_parents' => $target_parents,
        'force_text_only' => $force_text_only,
        'share_row_found' => $share_row ? true : false,
        'share_row_source' => $share_row_source,
        'share_row_id' => $share_row && isset($share_row['wr_id']) ? (int)$share_row['wr_id'] : 0,
        'share_subject' => $share_subject,
        'share_desc_preview' => mb_substr($share_desc, 0, 200, 'UTF-8'),
        'share_desc_length' => mb_strlen($share_desc, 'UTF-8'),
        'share_image' => $share_image,
        'parent_subject_match_count' => $debug_parent_match_count,
        'parent_subject_match_any_count' => $debug_parent_match_any_count,
        'parent_subject_match_counts' => $debug_parent_match_counts,
        'parent_subject_match_any_counts' => $debug_parent_match_any_counts,
    );
    if ($debug_parent_latest && is_resource($debug_parent_latest)) {
        $debug_payload['parent_latest_samples'] = array();
        while ($row = sql_fetch_array($debug_parent_latest)) {
            $debug_payload['parent_latest_samples'][] = array(
                'wr_id' => (int)$row['wr_id'],
                'wr_parent' => (int)$row['wr_parent'],
                'wr_subject' => $row['wr_subject'],
                'wr_4' => $row['wr_4'],
                'wr_datetime' => $row['wr_datetime'],
            );
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($debug_payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// 이미지 URL 정규화
if ($share_image !== '') {
    if (strpos($share_image, '//') === 0) {
        $share_image = 'https:' . $share_image;
    } elseif (strpos($share_image, 'http://') !== 0 && strpos($share_image, 'https://') !== 0) {
        $share_image = rtrim(G5_URL, '/') . '/' . ltrim($share_image, '/');
    }
}

function tarae_fetch_image_bytes($url) {
    if (!$url) return '';
    // 로컬 경로 매핑
    if (strpos($url, G5_URL) === 0) {
        $local = G5_PATH . substr($url, strlen(G5_URL));
        if (file_exists($local)) {
            return @file_get_contents($local);
        }
    }
    if (defined('G5_DATA_URL') && strpos($url, G5_DATA_URL) === 0) {
        $local = G5_DATA_PATH . substr($url, strlen(G5_DATA_URL));
        if (file_exists($local)) {
            return @file_get_contents($local);
        }
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data ?: '';
    }
    return @file_get_contents($url);
}

function tarae_pick_font() {
    $fonts_dir = __DIR__ . '/fonts';
    $latest_font = '';
    $latest_time = 0;

    if (is_dir($fonts_dir)) {
        $entries = glob($fonts_dir . '/*.ttf');
        if (is_array($entries)) {
            foreach ($entries as $path) {
                if ($path && file_exists($path)) {
                    $mtime = @filemtime($path);
                    if ($mtime !== false && $mtime >= $latest_time) {
                        $latest_time = $mtime;
                        $latest_font = $path;
                    }
                }
            }
        }
    }

    if ($latest_font) {
        return $latest_font;
    }

    $fallbacks = array(
        '/usr/share/fonts/truetype/nanum/NanumGothic.ttf',
        '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    );
    foreach ($fallbacks as $path) {
        if ($path && file_exists($path)) {
            return $path;
        }
    }
    return '';
}

function tarae_text_width($text, $size, $font) {
    if ($font && function_exists('imagettfbbox')) {
        $box = imagettfbbox($size, 0, $font, $text);
        return abs($box[2] - $box[0]);
    }
    return imagefontwidth(5) * strlen($text);
}

function tarae_wrap_text($text, $size, $font, $max_width, $max_lines) {
    $lines = array();
    if ($text === '') return $lines;
    $is_cjk = preg_match('/[^\x00-\x7F]/', $text) === 1;
    $chunks = $is_cjk ? preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) : preg_split('/\s+/', $text);
    $current = '';
    foreach ($chunks as $chunk) {
        $next = $current === '' ? $chunk : ($is_cjk ? $current . $chunk : $current . ' ' . $chunk);
        if (tarae_text_width($next, $size, $font) <= $max_width) {
            $current = $next;
            continue;
        }
        if ($current !== '') {
            $lines[] = $current;
            if (count($lines) >= $max_lines) {
                return $lines;
            }
        }
        $current = $chunk;
    }
    if ($current !== '' && count($lines) < $max_lines) {
        $lines[] = $current;
    }
    return $lines;
}

// 캔버스 생성
$width = 1200;
$height = 630;
$top_pad = (int)round($height * 0.1);
$bottom_pad = (int)round($height * 0.1);
$im = imagecreatetruecolor($width, $height);
imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));

// 상단 10% 여백 영역(흰 배경 유지)
$top_bg = imagecolorallocate($im, 255, 255, 255);
imagefilledrectangle($im, 0, 0, $width, $top_pad, $top_bg);

// 하단 텍스트
$font = tarae_pick_font();
$desc_color = imagecolorallocate($im, 60, 60, 60);
$margin = 50;
$text_top = $top_pad + 30;
$text_bottom = $height - $bottom_pad - 20;
$y = $text_top;

if ($font && function_exists('imagettftext')) {
    $desc_size_custom = isset($_tarae_board['bo_1_subj']) ? (int)$_tarae_board['bo_1_subj'] : 0;
    $desc_size = ($desc_size_custom >= 8 && $desc_size_custom <= 120) ? $desc_size_custom : 21;
    $line_height = 29;
    $para_gap = 8;
    $paragraphs = $share_desc !== '' ? preg_split("/\n+/", $share_desc) : array();
    $max_lines = (int)floor(max(0, ($text_bottom - $text_top + $para_gap) / $line_height));
    $rendered = 0;
    foreach ($paragraphs as $para) {
        if ($rendered >= $max_lines) break;
        $wrapped = tarae_wrap_text($para, $desc_size, $font, $width - $margin * 2, $max_lines - $rendered);
        foreach ($wrapped as $line) {
            imagettftext($im, $desc_size, 0, $margin, $y, $desc_color, $font, $line);
            $y += $line_height;
            $rendered++;
            if ($rendered >= $max_lines) break;
        }
        if ($rendered < $max_lines) {
            $y += $para_gap;
        }
    }
} else {
    // 폰트가 없으면 기본 폰트 사용 (영문/숫자 위주)
    if ($share_desc) {
        imagestring($im, 3, $margin, $y, $share_desc, $desc_color);
    }
}

// 출력
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($im);
imagedestroy($im);
exit;
