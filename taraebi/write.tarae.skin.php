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

// 스타일 통일 (메모 스킨 CSS 사용)
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';

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
$parent_subject = '';
$parent_ca_name = '';
if ($parent_wr_id > 0) {
    $parent_write = sql_fetch("SELECT wr_subject, wr_1, wr_2, ca_name FROM {$tarae_table} WHERE wr_id = '{$parent_wr_id}'");
    if ($parent_write) {
        $parent_subject = isset($parent_write['wr_subject']) ? $parent_write['wr_subject'] : '';
        $parent_wr_1 = isset($parent_write['wr_1']) ? $parent_write['wr_1'] : '';
        $parent_wr_2 = isset($parent_write['wr_2']) ? $parent_write['wr_2'] : '';
        $parent_ca_name = isset($parent_write['ca_name']) ? $parent_write['ca_name'] : '';
    }
}

// 게시판 설정에 맞춘 최대 이미지 수
$max_image_count = isset($board['bo_upload_count']) ? (int)$board['bo_upload_count'] : 0;
if ($max_image_count > 16) {
    $max_image_count = 16;
}
if ($max_image_count < 0) {
    $max_image_count = 0;
}
$tarae_image_start_index = 11;

// 수정 모드일 때 기존 데이터 불러오기
$tarae_subject_value = '';
$tarae_image_urls = $max_image_count > 0 ? array_fill(0, $max_image_count, '') : array();
if ($w == 'u' && isset($write)) {
    $tarae_subject_value = isset($write['wr_subject']) ? $write['wr_subject'] : '';
    for ($i = 1; $i <= $max_image_count; $i++) {
        $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
        $tarae_image_urls[$i - 1] = isset($write[$field]) ? $write[$field] : '';
    }
}

// 새 글 작성 시 제목 자동 생성 (부모글 제목 + 순번)
if ($w == '' && $parent_wr_id > 0 && $tarae_subject_value === '') {
    $count_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$tarae_table} WHERE wr_parent = '{$parent_wr_id}' AND wr_id != wr_parent AND wr_4 = 'tarae'");
    $seq = isset($count_row['cnt']) ? ((int)$count_row['cnt'] + 1) : 1;
    if ($seq < 1) {
        $seq = 1;
    }
    $base_subject = clean_xss_tags($parent_subject, 1, 1);
    $tarae_subject_value = substr($base_subject . sprintf('%04d', $seq), 0, 255);
}

// 기본 표시 개수 (기존 이미지가 있으면 그 수를 우선)
$existing_image_count = 0;
foreach ($tarae_image_urls as $url) {
    if (!empty($url)) {
        $existing_image_count++;
    }
}
$default_image_count = $max_image_count > 0 ? max(1, $existing_image_count) : 0;
if ($default_image_count > $max_image_count) {
    $default_image_count = $max_image_count;
}
// 기존 이미지 목록 (미리보기용)
$tarae_existing_files = array();
for ($i = 1; $i <= $max_image_count; $i++) {
    $field = 'wr_' . ($tarae_image_start_index - 1 + $i);
    $url = isset($tarae_image_urls[$i - 1]) ? $tarae_image_urls[$i - 1] : '';
    if (!empty($url)) {
        $tarae_existing_files[] = array(
            'field' => $field,
            'url' => $url
        );
    }
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
        <input type="hidden" name="write_type" value="tarae">
        <?php if ($parent_wr_id > 0) { ?>
        <input type="hidden" name="wr_parent" value="<?php echo (int)$parent_wr_id; ?>">
        <?php } ?>
        <?php if (!empty($board['bo_use_category'])) { ?>
        <input type="hidden" name="ca_name" value="<?php echo htmlspecialchars($parent_ca_name); ?>">
        <?php } ?>
        <?php if ($w == 'u' && isset($write['wr_subject'])) { ?>
        <input type="hidden" name="wr_subject" id="wr_subject" value="<?php echo htmlspecialchars($write['wr_subject']); ?>">
        <?php } else { ?>
        <input type="hidden" name="wr_subject" id="wr_subject" value="">
        <?php } ?>

        <div class="log20_write_form">
            <input type="hidden" name="tarae_subject" id="tarae_subject" value="<?php echo htmlspecialchars($tarae_subject_value); ?>">

            <dl>
                <dt><label for="wr_content">본문</label></dt>
                <dd>
                    <div class="bo_w_msg write_div">
                        <?php echo $editor_html; ?>
                    </div>
                </dd>
            </dl>

            <dl>
                <dt><label>이미지</label></dt>
                <dd>
                    <div class="tarae_attach_count" id="tarae_attach_count">0 / <?php echo $max_image_count; ?></div>
                    <div class="tarae_attach_controls">
                        <button type="button" class="tarae_attach_btn" id="tarae_attach_btn">이미지 첨부</button>
                        <input type="file" id="tarae_attach_files" name="tarae_attach_files[]" class="tarae_attach_input" accept="image/*,image/gif" multiple>
                    </div>
                    <div class="tarae_url_controls">
                        <button type="button" class="tarae_url_add_btn" id="tarae_url_add_btn">이미지 URL</button>
                        <div class="tarae_url_inputs tarae_url_inputs--inline" id="tarae_url_inputs"></div>
                    </div>
                    <div class="tarae_paste_controls">
                        <button type="button" class="tarae_paste_add_btn" id="tarae_paste_add_btn">이미지 붙여넣기</button>
                        <div class="tarae_paste_inputs tarae_paste_inputs--inline" id="tarae_paste_inputs"></div>
                    </div>
                    <div class="log20_tarae_reply_preview" id="tarae_attach_preview"></div>
                    <?php for ($i = 1; $i <= $max_image_count; $i++) { 
                        $wr_field = 'wr_' . ($tarae_image_start_index - 1 + $i); // wr_11 ~ wr_26
                        $image_url_value = isset($tarae_image_urls[$i - 1]) ? $tarae_image_urls[$i - 1] : '';
                    ?>
                    <div class="log20_field_row tarae_image_row" data-image-index="<?php echo $i; ?>" data-field="<?php echo $wr_field; ?>" style="margin-bottom:6px; display:none;">
                        <input type="hidden" name="<?php echo $wr_field; ?>_del" value="0">
                        <?php if ($w == 'u' && !empty($image_url_value)) { ?>
                        <div class="log20_existing_image_wrapper" style="margin-bottom:6px;">
                            <img src="<?php echo htmlspecialchars($image_url_value); ?>" alt="기존 이미지 <?php echo $i; ?>" style="max-width:200px; max-height:200px; border:1px solid #ddd;">
                            <div style="margin-top:4px;">
                                <button type="button" class="tarae_image_delete_btn" data-target-field="<?php echo $wr_field; ?>">기존 이미지 삭제</button>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="log20_field_block">
                            <label for="tarae_image_file_<?php echo $i; ?>" class="sound_only">메인 이미지 <?php echo $i; ?> 파일</label>
                            <input type="file" name="tarae_image_file_<?php echo $i; ?>" id="tarae_image_file_<?php echo $i; ?>" class="frm_input full_input tarae_image_file_slot" accept="image/*">
                        </div>
                        <div class="log20_field_block">
                            <label for="<?php echo $wr_field; ?>" class="sound_only">메인 이미지 <?php echo $i; ?> URL</label>
                            <input type="text" name="<?php echo $wr_field; ?>" id="<?php echo $wr_field; ?>" class="frm_input full_input tarae_image_url_slot" maxlength="255" placeholder="이미지 <?php echo $i; ?> URL (https://...)" value="<?php echo htmlspecialchars($image_url_value); ?>">
                        </div>
                    </div>
                    <?php } ?>
                </dd>
            </dl>

        <dl>
            <dt><label for="wr_6">Youtube</label></dt>
            <dd>
                <input type="url" name="wr_6" id="wr_6" class="frm_input full_input" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo isset($write['wr_6']) ? htmlspecialchars($write['wr_6']) : ''; ?>">
            </dd>
        </dl>
        </div>

    <div class="btn_confirm write_div">
        <?php
        $cancel_url = G5_BBS_URL . '/board.php?bo_table=' . urlencode($bo_table);
        if ($parent_wr_id > 0) {
            $cancel_url .= '&wr_id=' . (int)$parent_wr_id;
        }
        ?>
        <a href="<?php echo $cancel_url; ?>" class="btn_cancel btn">취소</a>
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">작성완료</button>
        <span class="tarae_uploading tarae_uploading--submit" id="tarae_uploading_submit">이미지에 풀을 바르는 중</span>
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

    if (!f.wr_content || !f.wr_content.value.trim()) {
        alert('본문을 입력해주세요.');
        return false;
    }

    // 제출 버튼 비활성화 (중복 제출 방지)
    if (document.getElementById("btn_submit")) {
        document.getElementById("btn_submit").disabled = "disabled";
    }
    var uploading = document.getElementById("tarae_uploading_submit");
    if (uploading) {
        uploading.classList.add("is-visible");
    }

    return true;
}

(function() {
    var maxCount = <?php echo (int)$max_image_count; ?>;
    var attachBtn = document.getElementById('tarae_attach_btn');
    var attachInput = document.getElementById('tarae_attach_files');
    var countEl = document.getElementById('tarae_attach_count');
    var preview = document.getElementById('tarae_attach_preview');
    var urlAddBtn = document.getElementById('tarae_url_add_btn');
    var urlInputsWrap = document.getElementById('tarae_url_inputs');
    var pasteAddBtn = document.getElementById('tarae_paste_add_btn');
    var pasteInputsWrap = document.getElementById('tarae_paste_inputs');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.tarae_image_row'));
    var slots = rows.map(function(row) {
        var field = row.getAttribute('data-field');
        return {
            field: field,
            fileInput: row.querySelector('.tarae_image_file_slot'),
            urlInput: row.querySelector('.tarae_image_url_slot'),
            delInput: row.querySelector('input[name="' + field + '_del"]')
        };
    });
    var existingFiles = <?php echo json_encode($tarae_existing_files); ?>;


    function availableSlots() {
        return slots.filter(function(slot) {
            if (!slot) return false;
            var urlVal = slot.urlInput ? slot.urlInput.value.trim() : '';
            var delVal = slot.delInput ? slot.delInput.value : '0';
            return !urlVal || delVal === '1';
        });
    }

    function addUrlPreview(url, field) {
        if (!preview) return;
        var item = document.createElement('div');
        item.className = 'log20_tarae_reply_preview_item';
        item.setAttribute('data-existing', '1');
        item.setAttribute('data-field', field);
        item.setAttribute('data-url', url);
        item.setAttribute('draggable', 'true');
        item.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-field="' + field + '" data-url="' + url + '" aria-label="첨부 취소">×</button>';
        preview.appendChild(item);
    }

    function renderExisting() {
        if (!preview) return;
        preview.innerHTML = '';
        existingFiles.forEach(function(file) {
            if (!file || !file.url || !file.field) return;
            var item = document.createElement('div');
            item.className = 'log20_tarae_reply_preview_item';
            item.setAttribute('data-existing', '1');
            item.setAttribute('data-field', file.field);
            item.setAttribute('data-url', file.url);
            item.setAttribute('draggable', 'true');
            item.innerHTML = '<img src="' + file.url + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-field="' + file.field + '" data-url="' + file.url + '" aria-label="첨부 취소">×</button>';
            preview.appendChild(item);
        });
    }

    function updateCount() {
        if (!countEl) return;
        var existingCount = preview ? preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length : 0;
        var newCount = attachInput && attachInput.files ? attachInput.files.length : 0;
        countEl.textContent = (existingCount + newCount) + ' / ' + maxCount;
    }

    function syncFilesToSlots() {
        if (!attachInput) return;
        var existingCount = preview ? preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length : 0;
        var allowed = Math.max(0, maxCount - existingCount);
        var files = attachInput.files ? Array.prototype.slice.call(attachInput.files) : [];
        if (files.length > allowed) {
            alert('첨부파일은 최대 ' + maxCount + '개까지 업로드 가능합니다.');
            files = files.slice(0, allowed);
            var dtTrim = new DataTransfer();
            files.forEach(function(file) { dtTrim.items.add(file); });
            attachInput.files = dtTrim.files;
        }

        if (!preview) return;

        var allItems = Array.prototype.slice.call(preview.querySelectorAll('.log20_tarae_reply_preview_item'));
        var fileIdx = 0;

        // 기존 파일 미리보기 자리 유지
        allItems.forEach(function(item) {
            if (item.getAttribute('data-existing') !== '0') return;
            if (fileIdx >= files.length) {
                var oldUrl = item.getAttribute('data-url');
                if (oldUrl) URL.revokeObjectURL(oldUrl);
                item.remove();
                return;
            }
            var file = files[fileIdx];
            var url = URL.createObjectURL(file);
            var img = item.querySelector('img');
            var oldUrl = item.getAttribute('data-url');
            if (img) img.src = url;
            item.setAttribute('data-index', String(fileIdx));
            item.setAttribute('data-url', url);
            var btn = item.querySelector('.log20_tarae_reply_preview_remove[data-index]');
            if (btn) {
                btn.setAttribute('data-index', String(fileIdx));
                btn.setAttribute('data-url', url);
            }
            if (oldUrl && oldUrl !== url) {
                URL.revokeObjectURL(oldUrl);
            }
            fileIdx += 1;
        });

        // 남은 파일은 끝에 추가
        while (fileIdx < files.length) {
            var nextFile = files[fileIdx];
            var nextUrl = URL.createObjectURL(nextFile);
            var newItem = document.createElement('div');
            newItem.className = 'log20_tarae_reply_preview_item';
            newItem.setAttribute('data-existing', '0');
            newItem.setAttribute('data-field', '');
            newItem.setAttribute('data-index', String(fileIdx));
            newItem.setAttribute('draggable', 'true');
            newItem.setAttribute('data-url', nextUrl);
            newItem.innerHTML = '<img src="' + nextUrl + '" alt=""><button type="button" class="log20_tarae_reply_preview_remove" data-index="' + fileIdx + '" data-url="' + nextUrl + '" aria-label="첨부 취소">×</button>';
            preview.appendChild(newItem);
            fileIdx += 1;
        }

        // 미리보기 순서를 기준으로 슬롯 재정렬
        syncSlotsFromPreview();
    }

    function syncSlotsFromPreview() {
        if (!preview) return;
        var items = Array.prototype.slice.call(preview.querySelectorAll('.log20_tarae_reply_preview_item'));
        var currentFiles = attachInput && attachInput.files ? Array.prototype.slice.call(attachInput.files) : [];
        var filesByIndex = {};
        currentFiles.forEach(function(file, idx) { filesByIndex[idx] = file; });

        slots.forEach(function(slot) {
            if (slot && slot.fileInput) slot.fileInput.value = '';
            if (slot && slot.urlInput) slot.urlInput.value = '';
            if (slot && slot.delInput) slot.delInput.value = '1';
        });

        var available = slots.slice();
        var newFilesOrdered = [];
        items.forEach(function(item) {
            var slot = available.shift();
            if (!slot) return;
            var isExisting = item.getAttribute('data-existing') === '1';
            if (isExisting) {
                var url = item.getAttribute('data-url') || '';
                if (slot.urlInput) slot.urlInput.value = url;
                if (slot.delInput) slot.delInput.value = '0';
                item.setAttribute('data-field', slot.field);
            } else {
                var idx = parseInt(item.getAttribute('data-index'), 10);
                var file = filesByIndex[idx];
                if (file) {
                    newFilesOrdered.push(file);
                    if (slot.fileInput) {
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        slot.fileInput.files = dt.files;
                    }
                    if (slot.urlInput) slot.urlInput.value = 'file:' + idx;
                    if (slot.delInput) slot.delInput.value = '0';
                    item.setAttribute('data-field', slot.field);
                }
            }
        });

        if (attachInput) {
            var dtAll = new DataTransfer();
            newFilesOrdered.forEach(function(file) { dtAll.items.add(file); });
            attachInput.files = dtAll.files;
        }

        // 재인덱스
        var newItems = preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="0"]');
        newItems.forEach(function(item, idx) {
            item.setAttribute('data-index', idx);
            var btn = item.querySelector('.log20_tarae_reply_preview_remove[data-index]');
            if (btn) btn.setAttribute('data-index', idx);
        });
        updateCount();
    }

    if (attachBtn && attachInput) {
        attachBtn.addEventListener('click', function() {
            attachInput._taraePrevFiles = attachInput.files ? Array.prototype.slice.call(attachInput.files) : [];
            attachInput.click();
        });
        attachInput.addEventListener('change', function() {
            var prevFiles = attachInput._taraePrevFiles || [];
            var newFiles = attachInput.files ? Array.prototype.slice.call(attachInput.files) : [];
            if (prevFiles.length && newFiles.length) {
                var existingCount = preview ? preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length : 0;
                var allowed = Math.max(0, maxCount - existingCount);
                var merged = prevFiles.concat(newFiles);
                if (merged.length > allowed) {
                    merged = merged.slice(0, allowed);
                }
                var dtMerge = new DataTransfer();
                merged.forEach(function(file) { dtMerge.items.add(file); });
                attachInput.files = dtMerge.files;
            }
            attachInput._taraePrevFiles = null;
            syncFilesToSlots();
        });
    }

    if (urlAddBtn && urlInputsWrap) {
        urlAddBtn.addEventListener('click', function() {
            urlInputsWrap.innerHTML = '';
            var row = document.createElement('div');
            row.className = 'tarae_url_row';
            row.innerHTML = '<input type="text" class="tarae_url_input" placeholder="이미지 URL (https://...)"><button type="button" class="tarae_url_done_btn">입력 완료</button><button type="button" class="tarae_url_cancel_btn">취소</button>';
            urlInputsWrap.appendChild(row);
        });

        urlInputsWrap.addEventListener('click', function(e) {
            var cancelBtn = e.target.closest('.tarae_url_cancel_btn');
            if (cancelBtn) {
                var cancelRow = cancelBtn.closest('.tarae_url_row');
                if (cancelRow) cancelRow.remove();
                return;
            }
            var doneBtn = e.target.closest('.tarae_url_done_btn');
            if (!doneBtn) return;
            var row = doneBtn.closest('.tarae_url_row');
            if (!row) return;
            var input = row.querySelector('.tarae_url_input');
            var url = input ? input.value.trim() : '';
            if (!url) {
                alert('이미지 URL을 입력해주세요.');
                return;
            }
            if (preview && preview.querySelectorAll('.log20_tarae_reply_preview_item').length >= maxCount) {
                alert('첨부파일은 최대 ' + maxCount + '개까지 업로드 가능합니다.');
                return;
            }
            addUrlPreview(url, '');
            syncSlotsFromPreview();
            updateCount();
            if (input) {
                input.value = '';
                input.focus();
            }
        });
    }

    if (pasteAddBtn && pasteInputsWrap) {
        pasteAddBtn.addEventListener('click', function() {
            pasteInputsWrap.innerHTML = '';
            var row = document.createElement('div');
            row.className = 'tarae_paste_row';
            row.innerHTML = '<textarea class="tarae_paste_input" rows="2" placeholder="여기에 이미지를 붙여넣기 (Ctrl+V)"></textarea><button type="button" class="tarae_paste_cancel_btn">취소</button>';
            pasteInputsWrap.appendChild(row);
        });

        pasteInputsWrap.addEventListener('click', function(e) {
            var cancelBtn = e.target.closest('.tarae_paste_cancel_btn');
            if (!cancelBtn) return;
            var row = cancelBtn.closest('.tarae_paste_row');
            if (row) row.remove();
        });

        pasteInputsWrap.addEventListener('paste', function(e) {
            var input = e.target.closest('.tarae_paste_input');
            if (!input) return;
            var items = (e.clipboardData || window.clipboardData).items || [];
            var imageFile = null;
            for (var i = 0; i < items.length; i++) {
                if (items[i].type && items[i].type.indexOf('image') === 0) {
                    imageFile = items[i].getAsFile();
                    break;
                }
            }
            if (!imageFile) return;

            var existingCount = preview ? preview.querySelectorAll('.log20_tarae_reply_preview_item[data-existing="1"]').length : 0;
            var newCount = attachInput && attachInput.files ? attachInput.files.length : 0;
            if (existingCount + newCount >= maxCount) {
                alert('첨부파일은 최대 ' + maxCount + '개까지 업로드 가능합니다.');
                return;
            }

            var dt = new DataTransfer();
            if (attachInput && attachInput.files) {
                Array.prototype.forEach.call(attachInput.files, function(file) {
                    dt.items.add(file);
                });
            }
            dt.items.add(imageFile);
            attachInput.files = dt.files;
            syncFilesToSlots();
            input.value = '';
        });
    }

    if (preview) {
        preview.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.log20_tarae_reply_preview_remove');
            if (!removeBtn) return;
            var url = removeBtn.getAttribute('data-url');
            if (removeBtn.hasAttribute('data-field')) {
                var field = removeBtn.getAttribute('data-field');
                var slot = slots.find(function(s) { return s.field === field; });
                if (slot && slot.delInput) slot.delInput.value = '1';
                if (slot && slot.urlInput) slot.urlInput.value = '';
            }
            if (removeBtn.hasAttribute('data-index')) {
                var removeIndex = parseInt(removeBtn.getAttribute('data-index'), 10);
                if (!isNaN(removeIndex) && attachInput && attachInput.files) {
                    var dt = new DataTransfer();
                    Array.prototype.forEach.call(attachInput.files, function(file, idx) {
                        if (idx !== removeIndex) dt.items.add(file);
                    });
                    attachInput.files = dt.files;
                }
            }
            if (url) URL.revokeObjectURL(url);
            var item = removeBtn.closest('.log20_tarae_reply_preview_item');
            if (item) item.remove();
            syncFilesToSlots();
        });

        var draggedItem = null;
        preview.addEventListener('dragstart', function(e) {
            var item = e.target.closest('.log20_tarae_reply_preview_item');
            if (!item) return;
            draggedItem = item;
            item.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        preview.addEventListener('dragend', function() {
            if (draggedItem) draggedItem.classList.remove('is-dragging');
            draggedItem = null;
        });
        preview.addEventListener('dragover', function(e) {
            if (!draggedItem) return;
            e.preventDefault();
            var target = e.target.closest('.log20_tarae_reply_preview_item');
            preview.querySelectorAll('.log20_tarae_reply_preview_item.is-drop-target').forEach(function(el) {
                el.classList.remove('is-drop-target');
            });
            if (target && target !== draggedItem) {
                target.classList.add('is-drop-target');
            }
        });
        preview.addEventListener('dragleave', function(e) {
            var target = e.target.closest('.log20_tarae_reply_preview_item');
            if (!target) return;
            target.classList.remove('is-drop-target');
        });
        preview.addEventListener('drop', function(e) {
            if (!draggedItem) return;
            e.preventDefault();
            var target = e.target.closest('.log20_tarae_reply_preview_item');
            if (target && target !== draggedItem) {
                preview.insertBefore(draggedItem, target);
            } else {
                preview.appendChild(draggedItem);
            }
            preview.querySelectorAll('.log20_tarae_reply_preview_item.is-drop-target').forEach(function(el) {
                el.classList.remove('is-drop-target');
            });
            draggedItem.classList.remove('is-dragging');
            draggedItem = null;
            syncSlotsFromPreview();
        });
    }

    renderExisting();
    updateCount();
})();
</script>
