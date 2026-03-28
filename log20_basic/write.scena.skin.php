<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// design.lib.php include
include_once(G5_LIB_PATH.'/design.lib.php');
global $g5;

// 게시판 쓰기 권한 체크
global $board, $member, $is_admin;
if ($w == '') {
    // 글쓰기 권한 체크
    if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 쓸 권한이 없습니다.');
        } else {
            alert("글을 쓸 권한이 없습니다.\\n회원이시라면 로그인 후 이용해 보십시오.", G5_BBS_URL.'/login.php?'.(isset($qstr) ? $qstr : '').'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    }
} else if ($w == 'u') {
    // 글수정 권한 체크
    global $write;
    if ($member['mb_id'] && $write['mb_id'] === $member['mb_id']) {
        // 자신의 글은 통과
    } else if ($is_admin) {
        // 관리자는 통과
    } else if ($member['mb_level'] < $board['bo_write_level']) {
        if ($member['mb_id']) {
            alert('글을 수정할 권한이 없습니다.');
        } else {
            alert('글을 수정할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.', G5_BBS_URL.'/login.php?'.(isset($qstr) ? $qstr : '').'&amp;url='.urlencode($_SERVER['SCRIPT_NAME'].'?bo_table='.$bo_table));
        }
    } else {
        // 권한은 있지만 자신의 글이 아니면 차단
        alert('자신이 작성한 글만 수정할 수 있습니다.');
    }
}

// 활성화된 폰트 목록 가져오기
$font_result = get_active_fonts();
$font_options = [];
while($font_row = sql_fetch_array($font_result)) {
    $font_options[] = $font_row;
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';

// rulecss 폴더의 모든 CSS 파일을 수집 (작성 화면 전체에는 적용하지 않고,
// 아래 JS 에서 에디터 iframe 에만 주입하기 위해 URL 목록만 만든다)
$log20_rulecss_files = array();
$log20_rulecss_urls  = array();
$log20_rulecss_path  = $board_skin_path . '/rulecss';
$log20_rulecss_url   = $board_skin_url . '/rulecss';

if (is_dir($log20_rulecss_path)) {
    if ($dh = opendir($log20_rulecss_path)) {
        while (($entry = readdir($dh)) !== false) {
            if (preg_match('/\.css$/i', $entry)) {
                $log20_rulecss_files[] = $entry;
                $log20_rulecss_urls[]  = $log20_rulecss_url . '/' . $entry;
            }
        }
        closedir($dh);
    }
}
?>

<section id="bo_w">
    <h2 class="sound_only"><?php echo $g5['title'] ?></h2>

    <!-- 게시물 작성/수정 시작 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off" style="width:90%; margin:0 auto;">
    <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="wr_4" value="scena">
    <input type="hidden" name="write_type" value="scena">
    <?php
    // wr_parent 설정 (부모 게시물 ID)
    $wr_parent = isset($_GET['wr_parent']) ? (int)$_GET['wr_parent'] : 0;
    if ($wr_parent > 0) {
        echo '<input type="hidden" name="wr_parent" value="'.$wr_parent.'">';
    }
    
    $option = '';
    $option_hidden = '';
    if ($is_notice || $is_html || $is_secret) { 
        $option = '';
        if ($is_notice) {
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="notice" name="notice"  class="selec_chk" value="1" '.$notice_checked.'>'.PHP_EOL.'<label for="notice"><span></span>공지</label></li>';
        }
        if ($is_html) {
            if ($is_dhtml_editor) {
                $option_hidden .= '<input type="hidden" value="html1" name="html">';
            } else {
                $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" class="selec_chk" value="'.$html_value.'" '.$html_checked.'>'.PHP_EOL.'<label for="html"><span></span>html</label></li>';
            }
        }
        if ($is_secret) {
            $secret_selected = '';
            $member_selected = '';

            if (isset($write['wr_option'])) {
                if (strpos($write['wr_option'], 'secret') !== false) {
                    $secret_selected = 'selected';
                }
                if (strpos($write['wr_option'], 'member') !== false) {
                    $member_selected = 'selected';
                }
            }

            if ($is_admin || $is_secret==1) {
                $option .= PHP_EOL.'<li class="chk_box">'.PHP_EOL.'<label for="set_secret">공개 설정</label>'.PHP_EOL.'<select id="set_secret" name="secret">'.PHP_EOL.'<option value="">전체공개</option>'.PHP_EOL.'<option value="secret" '.$secret_selected.'>비밀글</option>';
                if ($is_member) {
                    $option .= PHP_EOL.'<option value="member" '.$member_selected.'>멤버공개</option>';
                }
                $option .= PHP_EOL.'</select>'.PHP_EOL.'</li>';
            } else {
                $option_hidden .= '<input type="hidden" name="secret" value="secret">';
            }
        }
    }
    echo $option_hidden;

    $needs_guest_name = $is_name;
    $is_name = false;

$write_table_name = $g5['write_prefix'] . $bo_table;

$wr19_column = sql_fetch("SHOW COLUMNS FROM {$write_table_name} LIKE 'wr_19'");
if (!$wr19_column) {
    sql_query("ALTER TABLE {$write_table_name} ADD COLUMN wr_19 VARCHAR(255) NOT NULL DEFAULT ''", false);
}

$wr20_column = sql_fetch("SHOW COLUMNS FROM {$write_table_name} LIKE 'wr_20'");
if (!$wr20_column) {
    sql_query("ALTER TABLE {$write_table_name} ADD COLUMN wr_20 VARCHAR(255) NOT NULL DEFAULT ''", false);
}
    $pc_image_table = $write_table_name . '_pc_images';
    $pc_existing_images = array();
    if ($w == 'u' && $wr_id) {
        $table_check = sql_fetch("SHOW TABLES LIKE '{$pc_image_table}'");
        if ($table_check) {
            $pc_img_result = sql_query("SELECT pc_index, image_url FROM `{$pc_image_table}` WHERE wr_id = '{$wr_id}' ORDER BY pc_index ASC");
            while ($pc_img_row = sql_fetch_array($pc_img_result)) {
                $idx = (int)$pc_img_row['pc_index'];
                $pc_existing_images[$idx] = $pc_img_row['image_url'];
            }
        }
    }

    // 핸드아웃 데이터 로드 (수정 모드)
    $handout_table = $write_table_name . '_handouts';
    $handout_existing_data = array();
    $handout_count_value = 0;
    if ($w == 'u' && $wr_id) {
        $ht_check = sql_fetch("SHOW TABLES LIKE '{$handout_table}'");
        if ($ht_check) {
            $ht_result = sql_query("SELECT handout_index, title, content, content_front, content_back, image_url FROM `{$handout_table}` WHERE wr_id = '{$wr_id}' ORDER BY handout_index ASC", false);
            if ($ht_result) {
                while ($ht_row = sql_fetch_array($ht_result)) {
                    $idx = (int)$ht_row['handout_index'];
                    if ($idx >= 1 && $idx <= 20) {
                        $handout_existing_data[$idx] = array(
                            'title' => isset($ht_row['title']) ? stripslashes($ht_row['title']) : '',
                            'content' => isset($ht_row['content']) ? stripslashes($ht_row['content']) : '',
                            'content_front' => isset($ht_row['content_front']) ? stripslashes($ht_row['content_front']) : '',
                            'content_back' => isset($ht_row['content_back']) ? stripslashes($ht_row['content_back']) : '',
                            'image_url' => isset($ht_row['image_url']) ? $ht_row['image_url'] : ''
                        );
                        if ($idx > $handout_count_value) {
                            $handout_count_value = $idx;
                        }
                    }
                }
            }
        }
    }
    ?>

    <?php if ($is_category) { ?>
    <div class="bo_w_select write_div">
        <label for="ca_name" class="sound_only">분류<strong>필수</strong></label>
        <select name="ca_name" id="ca_name" required>
            <option value="">분류를 선택하세요</option>
            <?php echo $category_option ?>
        </select>
    </div>
    <?php } ?>

    <div class="bo_w_info write_div">
        <?php if ($is_name) { ?>
            <label for="wr_name" class="sound_only">이름<strong>필수</strong></label>
            <input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input half_input required" placeholder="이름">
        <?php } ?>
    
        <?php if ($is_password) { ?>
            <label for="wr_password" class="sound_only">비밀번호<strong>필수</strong></label>
            <input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input half_input <?php echo $password_required ?>" placeholder="비밀번호">
        <?php } ?>

        <?php if ($is_email) { ?>
            <label for="wr_email" class="sound_only">이메일</label>
            <input type="text" name="wr_email" value="<?php echo $email ?>" id="wr_email" class="frm_input half_input email " placeholder="이메일">
        <?php } ?>
    </div>
    
    <?php if ($option) { ?>
    <div class="write_div">
        <span class="sound_only">옵션</span>
        <ul class="bo_v_option" style="display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
        <?php echo $option ?>
        <?php if ($is_secret && ($is_admin || $is_secret==1)) { 
            $existing_secret_password = '';
            if ($w == 'u' && isset($write['wr_secret']) && !empty($write['wr_secret'])) {
                $existing_secret_password = $write['wr_secret'];
            }
            $secret_placeholder = $existing_secret_password ? '기존 비밀번호가 설정되어 있습니다. 변경하려면 새로 입력하세요.' : '비밀글 조회 시 필요한 비밀번호';
        ?>
        <li id="secret_password_area" style="display:none; list-style:none;">
            <label for="wr_secret" style="margin-right:8px;">비밀글 조회 비밀번호</label>
            <input type="password" name="wr_secret" id="wr_secret" class="frm_input half_input" placeholder="<?php echo htmlspecialchars($secret_placeholder); ?>" maxlength="20">
        </li>
        <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <!-- 시나리오 전용 필드 시작 { -->
    <div class="log20_write_form">
        <dl>
            <dt><label for="wr_subject">제목 / 작가<strong>필수</strong></label></dt>
            <dd>
                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:0 0 60%; min-width:240px;">
                        <label for="wr_subject" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">시나리오 제목</label>
                        <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input full_input required" maxlength="255" placeholder="제목을 입력하세요">
                    </div>
                    <div style="flex:0 0 40%; min-width:160px;">
                        <label for="wr_writer"><?php echo $needs_guest_name ? '작가<strong>필수</strong>' : '작가'; ?></label>
                        <?php
                        $writer_value = '';
                        if ($w == 'u' && isset($write['wr_20'])) {
                            $writer_value = $write['wr_20'];
                        } elseif (isset($_POST['wr_writer'])) {
                            $writer_value = $_POST['wr_writer'];
                        } elseif (isset($write['wr_20'])) {
                            $writer_value = $write['wr_20'];
                        }
                        ?>
                        <input type="text" name="wr_writer" value="<?php echo htmlspecialchars($writer_value); ?>" id="wr_writer" class="frm_input full_input<?php echo $needs_guest_name ? ' required' : ''; ?>" maxlength="100" placeholder="작가명을 입력하세요" <?php echo $needs_guest_name ? 'required' : ''; ?>>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label for="wr_3">부제</label></dt>
            <dd>
                <textarea name="wr_3" id="wr_3" class="frm_input full_input" rows="6" placeholder="부제를 입력하세요" style="resize: vertical; min-height: 120px;"><?php echo isset($write['wr_3']) ? htmlspecialchars($write['wr_3']) : '' ?></textarea>
            </dd>
        </dl>

        <dl>
            <dt><label>날짜</label></dt>
            <dd>
                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label for="wr_datetime_start" style="white-space:nowrap;">시작날짜:</label>
                        <?php
                        $date_start_value = '';
                        if ($w == 'u' && isset($write['wr_datetime'])) {
                            $date_start_value = date('Y-m-d', strtotime($write['wr_datetime']));
                        } else {
                            $date_start_value = date('Y-m-d');
                        }
                        ?>
                        <input type="date" name="wr_datetime" value="<?php echo $date_start_value; ?>" id="wr_datetime_start" class="frm_input">
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label for="wr_datetime_end" style="white-space:nowrap;">종료날짜:</label>
                        <?php
                        $date_end_value = '';
                        if ($w == 'u' && isset($write['wr_5']) && !empty($write['wr_5'])) {
                            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $write['wr_5'])) {
                                $date_end_value = $write['wr_5'];
                            } elseif (strtotime($write['wr_5']) !== false) {
                                $date_end_value = date('Y-m-d', strtotime($write['wr_5']));
                            }
                        }
                        if (empty($date_end_value)) {
                            $date_end_value = date('Y-m-d', strtotime('+1 day'));
                        }
                        ?>
                        <input type="date" name="wr_5" value="<?php echo $date_end_value; ?>" id="wr_datetime_end" class="frm_input">
                    </div>
                </div>
                <small style="display:block; margin-top:5px; color:#666;">연 · 월 · 일까지만 입력되어 저장됩니다.</small>
            </dd>
        </dl>

        <dl>
            <dt><label>룰 / GM</label></dt>
            <dd>
                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label for="wr_19_rule" class="sound_only">룰</label>
                        <input type="text" name="wr_19" value="<?php echo isset($write['wr_19']) ? $write['wr_19'] : '' ?>" id="wr_19_rule" class="frm_input full_input" maxlength="255" placeholder="룰을 입력하세요">
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label for="wr_4_gm" class="sound_only">GM</label>
                        <?php
                        $gm_value = '';
                        if ($w == 'u' && isset($write['wr_6'])) {
                            $gm_value = $write['wr_6'];
                        }
                        ?>
                        <input type="text" name="wr_6" value="<?php echo htmlspecialchars($gm_value) ?>" id="wr_4_gm" class="frm_input full_input" placeholder="GM을 입력하세요">
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>PC 정보</label></dt>
            <dd>
                <?php
                $pc_count_value = 2;
                if ($w == 'u' && isset($write['wr_11'])) {
                    $pc_count_value = (int)$write['wr_11'];
                }
                ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label for="pc_count_input" style="white-space:nowrap;">PC 수</label>
                            <input type="number" name="wr_11" value="<?php echo (int)$pc_count_value; ?>" id="pc_count_input" min="0" max="6" class="frm_input" placeholder="0~6">
                        </div>
                        <small style="color:#666;">PC 수를 입력하면 해당 개수만큼의 PC 입력칸이 표시됩니다 (최대 6명).</small>
                    </div>
                    <div class="log20_pc_rows" style="display:flex; flex-direction:column; gap:12px;">
                        <?php
                        for ($i = 1; $i <= 6; $i++) {
                            $field = 'wr_' . (11 + $i); // wr_12 ~ wr_17
                            $pc_value = ($w == 'u' && isset($write[$field])) ? $write[$field] : '';
                            if (isset($_POST[$field])) {
                                $pc_value = $_POST[$field];
                            }
                            $pc_url_field = 'pc_' . $i . '_image_url';
                            $pc_url_value = '';
                            if (isset($_POST[$pc_url_field])) {
                                $pc_url_value = $_POST[$pc_url_field];
                            } elseif ($w == 'u' && isset($pc_existing_images[$i])) {
                                $pc_url_value = $pc_existing_images[$i];
                            }
                        ?>
                        <div class="log20_pc_row" data-pc-index="<?php echo $i; ?>" style="display:none; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                            <label for="pc_<?php echo $i; ?>" style="min-width:40px; padding-top:8px;">PC<?php echo $i; ?></label>
                            <div style="flex:1; min-width:180px;">
                                <label for="pc_<?php echo $i; ?>" style="display:block; font-size:12px; color:#666; margin-bottom:4px;">PC이름</label>
                                <input type="text" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($pc_value); ?>" id="pc_<?php echo $i; ?>" class="frm_input full_input" placeholder="PC<?php echo $i; ?> 이름을 입력하세요">
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label for="pc_<?php echo $i; ?>_file" style="display:block; font-size:12px; color:#666; margin-bottom:4px;">이미지 업로드</label>
                                <input type="file" name="pc_<?php echo $i; ?>_image_file" id="pc_<?php echo $i; ?>_file" class="frm_input full_input" accept="image/*">
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <label for="pc_<?php echo $i; ?>_url" style="display:block; font-size:12px; color:#666; margin-bottom:4px;">이미지 URL</label>
                                <input type="url" name="pc_<?php echo $i; ?>_image_url" value="<?php echo htmlspecialchars($pc_url_value); ?>" id="pc_<?php echo $i; ?>_url" class="frm_input full_input" placeholder="https://example.com/pc<?php echo $i; ?>.jpg">
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>썸네일 이미지</label></dt>
            <dd>
                <?php
                $parent_thumb_url = '';
                $parent_wr_id = 0;
                if (isset($wr_parent) && (int)$wr_parent > 0) {
                    $parent_wr_id = (int)$wr_parent;
                } elseif (isset($write['wr_parent']) && (int)$write['wr_parent'] > 0) {
                    $parent_wr_id = (int)$write['wr_parent'];
                }
                if ($parent_wr_id > 0) {
                    $parent_row = sql_fetch("SELECT wr_7 FROM {$write_table} WHERE wr_id = '{$parent_wr_id}'");
                    if ($parent_row && !empty($parent_row['wr_7'])) {
                        $parent_thumb_url = $parent_row['wr_7'];
                    }
                }
                $use_parent_checked = false;
                if ($parent_thumb_url !== '') {
                    if (!empty($_POST['use_parent_thumb'])) {
                        $use_parent_checked = true;
                    } elseif ($w == 'u' && isset($write['wr_7']) && $write['wr_7'] === $parent_thumb_url) {
                        $use_parent_checked = true;
                    }
                }
                ?>
                <input type="hidden" name="wr_7_del" id="wr_7_del" value="0">
                <input type="hidden" name="use_parent_thumb" id="use_parent_thumb" value="<?php echo $use_parent_checked ? '1' : '0'; ?>">
                <?php if ($parent_thumb_url !== '') { ?>
                <div class="log20_existing_image" style="margin-bottom: 15px;">
                    <button type="button" id="use_parent_thumb_btn" class="btn_b01 btn" style="padding: 4px 8px;">
                        기본 이미지 사용
                    </button>
                    <small style="display:block; margin-top:5px; color:#666;">부모글 썸네일을 그대로 사용합니다.</small>
                </div>
                <?php } ?>
                <?php if ($w == 'u' && isset($write['wr_7']) && !empty($write['wr_7'])) { ?>
                <div class="log20_existing_image" id="log20_existing_image_wrap" style="margin-bottom: 15px;">
                    <label>기존 이미지</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                        <img src="<?php echo htmlspecialchars($write['wr_7']) ?>" alt="기존 이미지" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                        <div>
                            <a href="<?php echo htmlspecialchars($write['wr_7']) ?>" target="_blank" style="display: block; margin-bottom: 5px;">이미지 크게 보기</a>
                            <button type="button" id="wr_7_delete_btn" class="btn_b01 btn" style="padding: 4px 8px;">기존 이미지 삭제</button>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="wr_7_file">이미지 파일 업로드</label>
                        <input type="file" name="wr_7_file" id="wr_7_file" class="frm_input full_input" accept="image/*">
                        <small style="display: block; margin-top: 5px; color: #666;">새 이미지를 업로드하면 기존 이미지가 교체됩니다.</small>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_7">이미지 URL</label>
                        <input type="url" name="wr_7" value="<?php echo isset($write['wr_7']) ? htmlspecialchars($write['wr_7']) : '' ?>" id="wr_7" class="frm_input full_input" size="50" placeholder="https://example.com/image.jpg">
                        <small style="display: block; margin-top: 5px; color: #666;">또는 이미지 URL을 입력하세요.</small>
                    </div>
                </div>
                <?php if ($parent_thumb_url !== '') { ?>
                <script>
                (function() {
                    var toggleBtn = document.getElementById('use_parent_thumb_btn');
                    var hiddenInput = document.getElementById('use_parent_thumb');
                    var urlInput = document.getElementById('wr_7');
                    if (!toggleBtn || !hiddenInput || !urlInput) return;
                    var parentUrl = <?php echo json_encode($parent_thumb_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                    var originalValue = urlInput.value || '';
                    var isActive = hiddenInput.value === '1';
                    function syncUi() {
                        toggleBtn.classList.toggle('is-active', isActive);
                        if (isActive) {
                            urlInput.value = parentUrl;
                            hiddenInput.value = '1';
                        } else {
                            urlInput.value = originalValue;
                            hiddenInput.value = '0';
                        }
                    }
                    toggleBtn.addEventListener('click', function() {
                        isActive = !isActive;
                        syncUi();
                    });
                    syncUi();
                })();
                </script>
                <?php } ?>
                <script>
                (function() {
                    var deleteBtn = document.getElementById('wr_7_delete_btn');
                    if (!deleteBtn) return;
                    var deleteInput = document.getElementById('wr_7_del');
                    var urlInput = document.getElementById('wr_7');
                    var fileInput = document.getElementById('wr_7_file');
                    var existingWrap = document.getElementById('log20_existing_image_wrap');
                    var parentCheck = document.querySelector('input[name="use_parent_thumb"]');
                    deleteBtn.addEventListener('click', function() {
                        if (deleteInput) {
                            deleteInput.value = '1';
                        }
                        if (urlInput) {
                            urlInput.value = '';
                        }
                        if (fileInput) {
                            fileInput.value = '';
                        }
                        if (parentCheck) {
                            parentCheck.value = '0';
                        }
                        var tbBtn = document.getElementById('use_parent_thumb_btn');
                        if (tbBtn) {
                            tbBtn.classList.remove('is-active');
                        }
                        if (existingWrap) {
                            existingWrap.style.display = 'none';
                        }
                    });
                })();
                </script>
            </dd>
        </dl>
    </div>
    <!-- } 시나리오 전용 필드 끝 -->

    <!-- 본문 첨부파일 영역 시작 { -->
    <div class="log20_write_form">
        <dl>
            <dt><label for="scena_body_file">본문 첨부파일 (.txt / .html)</label></dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <input type="file" name="scena_body_file" id="scena_body_file" class="frm_input full_input" accept=".txt,.html,.htm,text/plain,text/html">
                        <small style="display:block; margin-top:5px; color:#666;">
                            대용량 HTML 텍스트를 포함한 <strong>.txt 또는 .html 파일</strong>을 업로드하면,
                            파일 내용을 읽어 시나리오 본문으로 사용합니다. (rulecss가 적용된 상태로 표시됩니다)
                        </small>
                    </div>
                </div>
                <?php
                // 수정 모드에서 기존 첨부파일 경로 안내 (wr_21 사용 예정)
                $existing_body_file = '';
                if ($w == 'u' && isset($write['wr_21']) && $write['wr_21']) {
                    $existing_body_file = $write['wr_21'];
                }
                if ($existing_body_file) {
                    $display_path = htmlspecialchars($existing_body_file, ENT_QUOTES);
                ?>
                <div style="margin-top:8px; color:#444; font-size:12px;">
                    현재 등록된 본문 파일: <span style="font-weight:bold;"><?php echo $display_path; ?></span><br>
                    새 파일을 업로드하지 않으면 기존 파일이 그대로 사용됩니다.
                </div>
                <?php } ?>
            </dd>
        </dl>
    </div>
    <!-- } 본문 첨부파일 영역 끝 -->

    <!-- 핸드아웃 영역 시작 { -->
    <div class="log20_write_form">
        <dl>
            <dt><label for="handout_count_input">핸드아웃 개수</label></dt>
            <dd>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="number" name="handout_count" value="<?php echo (int)$handout_count_value; ?>" id="handout_count_input" min="0" max="20" class="frm_input" placeholder="0~20">
                        <small style="color:#666;">핸드아웃 개수를 입력하면 해당 개수만큼의 입력칸이 표시됩니다 (최대 20개).</small>
                    </div>
                </div>
            </dd>
        </dl>
        <div class="log20_handout_rows" style="display:flex; flex-direction:column; gap:20px; margin-top:20px;">
            <?php
            for ($i = 1; $i <= 20; $i++) {
                $handout_title = '';
                $handout_content_front = '';
                $handout_content_back = '';
                $handout_image_url = '';
                if (isset($handout_existing_data[$i])) {
                    $handout_title = $handout_existing_data[$i]['title'];
                    $handout_content_front = isset($handout_existing_data[$i]['content_front']) ? $handout_existing_data[$i]['content_front'] : '';
                    $handout_content_back = isset($handout_existing_data[$i]['content_back']) ? $handout_existing_data[$i]['content_back'] : '';
                    // 기존 content가 있고 front/back이 없으면 content를 front로 사용 (호환성)
                    if (empty($handout_content_front) && empty($handout_content_back) && isset($handout_existing_data[$i]['content'])) {
                        $handout_content_front = $handout_existing_data[$i]['content'];
                    }
                    $handout_image_url = $handout_existing_data[$i]['image_url'];
                }
                if (isset($_POST['handout_' . $i . '_title'])) {
                    $handout_title = stripslashes($_POST['handout_' . $i . '_title']);
                }
                if (isset($_POST['handout_' . $i . '_content_front'])) {
                    $handout_content_front = stripslashes($_POST['handout_' . $i . '_content_front']);
                }
                if (isset($_POST['handout_' . $i . '_content_back'])) {
                    $handout_content_back = stripslashes($_POST['handout_' . $i . '_content_back']);
                }
                if (isset($_POST['handout_' . $i . '_image_url'])) {
                    $handout_image_url = $_POST['handout_' . $i . '_image_url'];
                }
            ?>
            <div class="log20_handout_row" data-handout-index="<?php echo $i; ?>" style="display:none; border:1px solid #ddd; border-radius:8px; padding:15px; background:#fafafa;">
                <div style="margin-bottom:12px; font-weight:bold; color:#333; border-bottom:1px solid #ccc; padding-bottom:8px;">
                    핸드아웃 <?php echo $i; ?>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <!-- 제목 (1줄) -->
                    <div>
                        <label for="handout_<?php echo $i; ?>_title" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">제목</label>
                        <input type="text" name="handout_<?php echo $i; ?>_title" value="<?php echo htmlspecialchars($handout_title); ?>" id="handout_<?php echo $i; ?>_title" class="frm_input full_input" maxlength="255" placeholder="핸드아웃 제목을 입력하세요">
                    </div>
                    <!-- 본문 - 앞면과 뒷면을 한 줄에 배치 -->
                    <div style="display:flex; gap:4%; align-items:flex-start;">
                        <!-- 본문 - 앞면 -->
                        <div style="width:48%;">
                            <label for="handout_<?php echo $i; ?>_content_front" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">앞면</label>
                            <textarea name="handout_<?php echo $i; ?>_content_front" id="handout_<?php echo $i; ?>_content_front" class="frm_input full_input" rows="8" placeholder="핸드아웃 앞면이나 조사 조건을 입력하세요" style="resize: vertical; min-height: 200px; max-height: 200px; height: 200px; overflow-y: auto; width:100%;"><?php echo htmlspecialchars($handout_content_front); ?></textarea>
                        </div>
                        <!-- 본문 - 뒷면 -->
                        <div style="width:48%;">
                            <label for="handout_<?php echo $i; ?>_content_back" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">뒷면</label>
                            <textarea name="handout_<?php echo $i; ?>_content_back" id="handout_<?php echo $i; ?>_content_back" class="frm_input full_input" rows="8" placeholder="핸드아웃 뒷면을 입력하세요 " style="resize: vertical; min-height: 200px; max-height: 200px; height: 200px; overflow-y: auto; width:100%;"><?php echo htmlspecialchars($handout_content_back); ?></textarea>
                        </div>
                    </div>
                    <!-- 이미지 (파일 업로드 + URL) -->
                    <div style="display:flex; gap:15px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:200px;">
                            <label for="handout_<?php echo $i; ?>_file" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">이미지 파일 업로드</label>
                            <input type="file" name="handout_<?php echo $i; ?>_image_file" id="handout_<?php echo $i; ?>_file" class="frm_input full_input" accept="image/*">
                        </div>
                        <div style="flex:1; min-width:200px;">
                            <label for="handout_<?php echo $i; ?>_image_url" style="display:block; margin-bottom:5px; font-size:13px; color:#666;">이미지 URL</label>
                            <input type="url" name="handout_<?php echo $i; ?>_image_url" value="<?php echo htmlspecialchars($handout_image_url); ?>" id="handout_<?php echo $i; ?>_image_url" class="frm_input full_input" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <!-- } 핸드아웃 영역 끝 -->

    <?php if ($is_use_captcha) { //자동등록방지  ?>
    <div class="write_div">
        <?php echo $captcha_html ?>
    </div>
    <?php } ?>

    <div class="btn_confirm write_div">
        <a href="<?php echo get_pretty_url($bo_table); ?>" class="btn_cancel btn">취소</a>
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">작성완료</button>
    </div>
    </form>

    <script>
    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("자동 줄바꿈을 하시겠습니까?\n\n자동 줄바꿈은 게시물 내용중 줄바뀐 곳을<br>태그로 변환하는 기능입니다.");
            if (result)
                obj.value = "html2";
            else
                obj.value = "html1";
        }
        else
            obj.value = "";
    }

    // RA0 비밀글 선택 시 비밀번호 입력창 표시
    document.addEventListener("DOMContentLoaded", function() {
        var setSecretSelect = document.getElementById("set_secret");
        var secretPasswordDiv = document.getElementById("secret_password_area");
        var secretPasswordInput = document.getElementById("wr_secret");
        var pcCountInput = document.getElementById("pc_count_input");
        var pcRows = document.querySelectorAll(".log20_pc_row");

        if (setSecretSelect && secretPasswordDiv && secretPasswordInput) {
            setSecretSelect.addEventListener("change", function() {
                if (this.value === "secret") {
                    secretPasswordDiv.style.display = "list-item";
                    secretPasswordInput.required = true;
                } else {
                    secretPasswordDiv.style.display = "none";
                    secretPasswordInput.required = false;
                    secretPasswordInput.value = "";
                }
            });

            // 수정 시 비밀글이면 비밀번호 입력창 표시
            if (setSecretSelect && setSecretSelect.value === "secret") {
                secretPasswordDiv.style.display = "list-item";
                secretPasswordInput.required = true;
            }
        }

        function updatePcRows() {
            if (!pcCountInput || !pcRows.length) return;
            var count = parseInt(pcCountInput.value, 10);
            if (isNaN(count)) count = 0;
            if (count < 0) count = 0;
            if (count > 6) count = 6;
            pcCountInput.value = count;
            pcRows.forEach(function(row) {
                var idx = parseInt(row.getAttribute("data-pc-index"), 10);
                row.style.display = idx <= count && count > 0 ? "flex" : "none";
            });
        }

        updatePcRows();

        if (pcCountInput) {
            pcCountInput.addEventListener("input", updatePcRows);
        }

        // 핸드아웃 개수에 따른 입력칸 표시/숨김
        var handoutCountInput = document.getElementById("handout_count_input");
        var handoutRows = document.querySelectorAll(".log20_handout_row");

        function updateHandoutRows() {
            if (!handoutCountInput || !handoutRows.length) return;
            var count = parseInt(handoutCountInput.value, 10);
            if (isNaN(count)) count = 0;
            if (count < 0) count = 0;
            if (count > 20) count = 20;
            handoutCountInput.value = count;
            handoutRows.forEach(function(row) {
                var idx = parseInt(row.getAttribute("data-handout-index"), 10);
                row.style.display = idx <= count && count > 0 ? "block" : "none";
            });
        }

        updateHandoutRows();

        if (handoutCountInput) {
            handoutCountInput.addEventListener("input", updateHandoutRows);
        }

        // -----------------------------
        // rulecss 스타일을 에디터 미리보기(iframe)에도 자동 적용
        // -----------------------------
        var log20RuleCssUrls = <?php echo json_encode($log20_rulecss_urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?> || [];

        // 에디터 iframe에 주입할 기본 CSS (view.scena.skin.php와 동일하게)
        var log20EditorBaseCss = '* { box-sizing: border-box; }' +
            'body { margin: 0; padding: 0; font-size: 13px; line-height: 1.6; ' +
            'font-family: "Malgun Gothic", "맑은 고딕", "Apple SD Gothic Neo", "돋움", Dotum, Arial, sans-serif; ' +
            'color: #333; background: transparent; }' +
            'p { margin: 0; padding: 0; }' +
            'table { border-collapse: collapse; border-spacing: 0; }' +
            'td, th { padding: 0; }' +
            'a { color: inherit; text-decoration: none; background-color: transparent; margin: 0; padding: 0; border: 0; outline: 0; display: inline; box-shadow: none; -webkit-box-shadow: none; -moz-box-shadow: none; }' +
            'a[style*="display: block"], a[style*="display:block"] { display: block !important; }' +
            'img { max-width: 100%; height: auto; }';

        function log20ApplyRuleCssToIframes() {
            var iframes = document.querySelectorAll("iframe");
            if (!iframes.length) return;

            iframes.forEach(function(iframe) {
                try {
                    var doc = iframe.contentDocument || iframe.contentWindow.document;
                    if (!doc || !doc.head) return;

                    // 기본 CSS 스타일 주입 (한 번만)
                    if (!doc.querySelector('style[data-log20-editor-base]')) {
                        var styleEl = doc.createElement("style");
                        styleEl.type = "text/css";
                        styleEl.setAttribute("data-log20-editor-base", "1");
                        styleEl.textContent = log20EditorBaseCss;
                        doc.head.insertBefore(styleEl, doc.head.firstChild);
                    }

                    // rulecss 파일들 주입
                    if (log20RuleCssUrls.length) {
                        log20RuleCssUrls.forEach(function(href) {
                            if (!href) return;
                            if (doc.querySelector('link[data-log20-rulecss][href="'+href+'"]')) return;

                            var linkEl = doc.createElement("link");
                            linkEl.rel = "stylesheet";
                            linkEl.href = href;
                            linkEl.setAttribute("data-log20-rulecss", "1");
                            doc.head.appendChild(linkEl);
                        });
                    }
                } catch (e) {
                    // cross-domain 등 접근 불가한 iframe 은 무시
                }
            });
        }

        // 에디터가 완전히 로드된 뒤에도 주기적으로 iframe 에 CSS 를 주입
        // (</> 버튼으로 HTML / 미리보기 전환 시에도 적용되도록 약간 여유를 둔 polling)
        log20ApplyRuleCssToIframes(); // 즉시 한 번 실행
        setInterval(log20ApplyRuleCssToIframes, 2000);
    });

    function fwrite_submit(f)
    {
        // 비밀글 선택 시 비밀번호 필수 체크
        var setSecretSelect = document.getElementById("set_secret");
        var secretPasswordInput = document.getElementById("wr_secret");
        if (setSecretSelect && setSecretSelect.value === "secret") {
            if (secretPasswordInput && secretPasswordInput.value === "") {
                alert("비밀글 조회 비밀번호를 입력해주세요.");
                secretPasswordInput.focus();
                return false;
            }
        }

        // 금지단어 체크 (본문은 사용하지 않고 제목만 검사)
        var subject = "";
        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.wr_subject.value,
                "content": ""
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
            }
        });

        if (subject) {
            alert("제목에 금지단어('"+subject+"')가 포함되어있습니다");
            f.wr_subject.focus();
            return false;
        }

        var pcCountInput = document.getElementById("pc_count_input");
        var pcRows = document.querySelectorAll(".log20_pc_row");
        if (pcCountInput && pcRows.length) {
            var count = parseInt(pcCountInput.value, 10);
            if (isNaN(count) || count < 0) count = 0;
            if (count > 6) count = 6;
            pcCountInput.value = count;
            pcRows.forEach(function(row) {
                var idx = parseInt(row.getAttribute("data-pc-index"), 10);
                if (idx > count) {
                    var input = row.querySelector("input");
                    if (input) {
                        input.value = "";
                    }
                }
            });
        }

        // 핸드아웃 개수 검증 및 초기화
        var handoutCountInput = document.getElementById("handout_count_input");
        var handoutRows = document.querySelectorAll(".log20_handout_row");
        if (handoutCountInput && handoutRows.length) {
            var count = parseInt(handoutCountInput.value, 10);
            if (isNaN(count) || count < 0) count = 0;
            if (count > 20) count = 20;
            handoutCountInput.value = count;
            handoutRows.forEach(function(row) {
                var idx = parseInt(row.getAttribute("data-handout-index"), 10);
                if (idx > count) {
                    var titleInput = row.querySelector("input[type='text']");
                    var contentTextareas = row.querySelectorAll("textarea");
                    var urlInput = row.querySelector("input[type='url']");
                    var fileInput = row.querySelector("input[type='file']");
                    if (titleInput) titleInput.value = "";
                    if (contentTextareas.length > 0) {
                        contentTextareas.forEach(function(ta) {
                            ta.value = "";
                        });
                    }
                    if (urlInput) urlInput.value = "";
                    if (fileInput) fileInput.value = "";
                }
            });
        }

        <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>

        // 본문 첨부파일(.txt / .html) 유효성 검사
        var bodyFileInput = document.getElementById("scena_body_file");
        var hasNewFile = false;
        if (bodyFileInput && bodyFileInput.files && bodyFileInput.files.length > 0) {
            var file = bodyFileInput.files[0];
            var name = file.name || "";
            var ext = name.split(".").pop().toLowerCase();
            if (ext !== "txt" && ext !== "html" && ext !== "htm") {
                alert("본문 첨부파일은 .txt 또는 .html 형식만 업로드할 수 있습니다.");
                bodyFileInput.focus();
                return false;
            }
            hasNewFile = true;
        }

        // 수정 모드에서 기존 파일이 있다면 새 파일 없이도 통과 허용
        var hasExistingFile = <?php echo ($w == 'u' && isset($write['wr_21']) && $write['wr_21']) ? 'true' : 'false'; ?>;
        if (!hasNewFile && !hasExistingFile) {
            alert("본문 첨부파일(.txt 또는 .html)을 업로드해주세요.");
            if (bodyFileInput) bodyFileInput.focus();
            return false;
        }

        // 제출 버튼 비활성화
        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</section>
<!-- } 게시물 작성/수정 끝 -->

