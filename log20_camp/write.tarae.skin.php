<?php
// TR_LOG 메모용 tarae 서브게시판 전용 쓰기 스킨
if (!defined('_GNUBOARD_')) {
    // 독립 실행 방지
    exit;
}

// 필수 전역 변수
global $g5, $member, $board, $bo_table, $board_skin_url, $editor_html, $editor_js, $is_admin;

// 게시판 쓰기 권한 체크
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

// 스타일 통일 (pclist와 동일한 스킨 CSS 사용)
echo '<link rel="stylesheet" href="'.$board_skin_url.'/css/style.css">';

// tr_log 게시판 테이블 사용
$tarae_table = $g5['write_prefix'] . $bo_table;

// 부모 글 ID (수정 모드에서는 기존 wr_parent 사용, 작성 모드에서는 GET 파라미터 사용)
$parent_wr_id = 0;
if ($w == 'u' && isset($write['wr_parent'])) {
    $parent_wr_id = (int)$write['wr_parent'];
} else {
    $parent_wr_id = isset($_GET['wr_parent']) ? (int)$_GET['wr_parent'] : 0;
}

// 부모 글 정보 가져오기
$parent_wr_1 = '';
$parent_wr_2 = '';
if ($parent_wr_id > 0) {
    $parent_write = sql_fetch("SELECT wr_1, wr_2 FROM {$tarae_table} WHERE wr_id = '{$parent_wr_id}'");
    if ($parent_write) {
        $parent_wr_1 = isset($parent_write['wr_1']) ? $parent_write['wr_1'] : '';
        $parent_wr_2 = isset($parent_write['wr_2']) ? $parent_write['wr_2'] : '';
    }
}

// 수정 모드일 때 기존 데이터 불러오기
$tarae_subject_value = '';
$tarae_summary_value = '';
$tarae_image_urls = array('', '', '', '');
if ($w == 'u' && isset($write)) {
    $tarae_subject_value = isset($write['wr_subject']) ? $write['wr_subject'] : '';
    $tarae_summary_value = isset($write['wr_3']) ? $write['wr_3'] : '';
    $tarae_image_urls[0] = isset($write['wr_39']) ? $write['wr_39'] : '';
    $tarae_image_urls[1] = isset($write['wr_40']) ? $write['wr_40'] : '';
    $tarae_image_urls[2] = isset($write['wr_41']) ? $write['wr_41'] : '';
    $tarae_image_urls[3] = isset($write['wr_42']) ? $write['wr_42'] : '';
}

// 메모 저장 처리는 write_update.php에서 처리하므로 여기서는 제거
// (Gracenote 표준 흐름 사용)

?>

<div class="tarae_write_wrap" id="tarae_write_wrap">
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
        <input type="hidden" name="w" value="<?php echo $w ?>">
        <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
        <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
        <input type="hidden" name="wr_4" value="tarae">
        <?php if ($parent_wr_id > 0) { ?>
        <input type="hidden" name="wr_parent" value="<?php echo (int)$parent_wr_id; ?>">
        <?php } ?>
        <?php if ($w == 'u' && isset($write['wr_subject'])) { ?>
        <input type="hidden" name="wr_subject" id="wr_subject" value="<?php echo htmlspecialchars($write['wr_subject']); ?>">
        <?php } else { ?>
        <input type="hidden" name="wr_subject" id="wr_subject" value="">
        <?php } ?>

        <div class="log20_write_form">
            <dl>
                <dt><label for="tarae_subject">제목 / 부제</label></dt>
                <dd>
                    <div class="log20_field_row">
                        <div class="log20_field_block flex-1">
                            <input type="text" name="tarae_subject" id="tarae_subject" class="frm_input full_input" maxlength="255" placeholder="제목" value="<?php echo htmlspecialchars($tarae_subject_value); ?>" required>
                        </div>
                        <div class="log20_field_block flex-1">
                            <input type="text" name="tarae_summary" id="tarae_summary" class="frm_input full_input" maxlength="255" placeholder="부제 (선택)" value="<?php echo htmlspecialchars($tarae_summary_value); ?>">
                        </div>
                    </div>
                </dd>
            </dl>

            <dl>
                <dt><label for="wr_content">본문</label></dt>
                <dd>
                    <div class="bo_w_msg write_div">
                        <?php echo $editor_html; ?>
                    </div>
                </dd>
            </dl>

            <dl>
                <dt><label>메인 이미지 (최대 4개)</label></dt>
                <dd>
                    <?php for ($i = 1; $i <= 4; $i++) { 
                        $wr_field = 'wr_' . (38 + $i); // wr_39, wr_40, wr_41, wr_42
                        $image_url_value = $tarae_image_urls[$i - 1];
                    ?>
                    <div class="log20_field_row" style="margin-bottom:6px;">
                        <?php if ($w == 'u' && !empty($image_url_value)) { ?>
                        <div class="log20_existing_image_wrapper" style="margin-bottom:6px;">
                            <img src="<?php echo htmlspecialchars($image_url_value); ?>" alt="기존 이미지 <?php echo $i; ?>" style="max-width:200px; max-height:200px; border:1px solid #ddd;">
                            <div style="margin-top:4px;">
                                <label><input type="checkbox" name="<?php echo $wr_field; ?>_del" value="1"> 기존 이미지 삭제</label>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="log20_field_block">
                            <label for="tarae_image_file_<?php echo $i; ?>" class="sound_only">메인 이미지 <?php echo $i; ?> 파일</label>
                            <input type="file" name="tarae_image_file_<?php echo $i; ?>" id="tarae_image_file_<?php echo $i; ?>" class="frm_input full_input" accept="image/*">
                        </div>
                        <div class="log20_field_block">
                            <label for="<?php echo $wr_field; ?>" class="sound_only">메인 이미지 <?php echo $i; ?> URL</label>
                            <input type="text" name="<?php echo $wr_field; ?>" id="<?php echo $wr_field; ?>" class="frm_input full_input" maxlength="255" placeholder="이미지 <?php echo $i; ?> URL (https://...)" value="<?php echo htmlspecialchars($image_url_value); ?>">
                        </div>
                    </div>
                    <?php } ?>
                    <p class="log20_field_note">※ 파일을 업로드하면 해당 파일 경로가 우선 사용됩니다. URL은 직접 입력 시에만 사용됩니다.</p>
                </dd>
            </dl>
        </div>

    <div class="btn_confirm write_div">
        <a href="<?php echo get_pretty_url($bo_table); ?>" class="btn_cancel btn">취소</a>
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">작성완료</button>
    </div>
    </form>
</div>

<script>
function fwrite_submit(f) {
    // 에디터 내용 처리
    <?php echo $editor_js; ?>

    // 제목을 wr_subject에 복사 (tarae_subject는 write_update에서 처리)
    if (f.tarae_subject && f.tarae_subject.value.trim()) {
        if (!f.wr_subject) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'wr_subject';
            f.appendChild(hidden);
        }
        f.wr_subject.value = f.tarae_subject.value.trim();
    }

    if (!f.tarae_subject || !f.tarae_subject.value.trim()) {
        alert('제목을 입력해주세요.');
        if (f.tarae_subject) f.tarae_subject.focus();
        return false;
    }

    if (!f.wr_content || !f.wr_content.value.trim()) {
        alert('본문을 입력해주세요.');
        return false;
    }

    // 제출 버튼 비활성화 (중복 제출 방지)
    if (document.getElementById("btn_submit")) {
        document.getElementById("btn_submit").disabled = "disabled";
    }

    return true;
}
</script>
