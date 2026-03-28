<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// write_type 파라미터에 따라 다른 스킨 파일 로드
$write_type = isset($_GET['write_type']) ? $_GET['write_type'] : '';
if ($write_type === 'tarae') {
    $custom_skin_file = $board_skin_path . '/write.tarae.skin.php';
    if (file_exists($custom_skin_file)) {
        include_once($custom_skin_file);
        exit;
    }
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
echo '<link rel="stylesheet" href="'.$board_skin_url.'/style.css">';
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
    <?php
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
                // 비밀글 조회 비밀번호 입력칸을 공개설정 오른편에 추가
                $secret_password_value = '';
                if ($w == 'u' && isset($write['wr_secret'])) {
                    $secret_password_value = htmlspecialchars($write['wr_secret']);
                }
                $option .= PHP_EOL.'<li class="chk_box" id="secret_password_area" style="display:none;">'.PHP_EOL.'<label for="wr_secret">비밀글 조회 비밀번호</label>'.PHP_EOL.'<input type="password" name="wr_secret" id="wr_secret" value="'.$secret_password_value.'" class="frm_input" placeholder="비밀글 조회 시 필요한 비밀번호" maxlength="20">'.PHP_EOL.'</li>';
            } else {
                $option_hidden .= '<input type="hidden" name="secret" value="secret">';
            }
        }
    }
    echo $option_hidden;
    ?>

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
        <ul class="bo_v_option">
        <?php echo $option ?>
        </ul>
    </div>
    <?php } ?>

    <!-- TR_LOG 전용 필드 시작 { -->
    <div class="log20_write_form">
        <?php if ($is_category && isset($board['bo_category_list']) && trim($board['bo_category_list']) !== '') { ?>
        <dl>
            <dt><label for="ca_name">분류</label></dt>
            <dd>
                <select name="ca_name" id="ca_name" required>
                    <option value="">분류를 선택하세요</option>
                    <?php echo $category_option ?>
                </select>
            </dd>
        </dl>
        <?php } ?>
        <dl class="log20_double_row">
            <dt>제목 / 순서</dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="wr_subject">제목<strong>(필수)</strong></label>
                        <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input full_input required" size="50" maxlength="255" placeholder="제목을 입력하세요">
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_order">게시글 순서</label>
                        <input type="number" name="wr_order" value="<?php echo isset($write['wr_order']) ? (int)$write['wr_order'] : 0 ?>" id="wr_order" class="frm_input full_input" min="0" placeholder="숫자가 작을수록 먼저 표시됩니다">
                        <small style="display: block; margin-top: 5px; color: #666;">숫자가 작을수록 먼저 표시됩니다 (0이면 기본 순서)</small>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label for="wr_3">부제</label></dt>
            <dd>
                <input type="text" name="wr_3" value="<?php echo isset($write['wr_3']) ? htmlspecialchars($write['wr_3']) : '' ?>" id="wr_3" class="frm_input full_input" size="50" maxlength="255" placeholder="부제를 입력하세요">
            </dd>
        </dl>

        <dl class="log20_double_row">
            <dt>색상</dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block">
                        <label for="wr_1">제목 색</label>
                        <div class="log20_field_inline">
                            <input type="color" name="wr_1" value="<?php echo isset($write['wr_1']) && $write['wr_1'] ? $write['wr_1'] : '#000000' ?>" id="wr_1" class="frm_input">
                            <input type="text" name="wr_1_text" value="<?php echo isset($write['wr_1']) ? $write['wr_1'] : '' ?>" id="wr_1_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_2">제목 배경 색</label>
                        <div class="log20_field_inline">
                            <input type="color" name="wr_2" value="<?php echo isset($write['wr_2']) && $write['wr_2'] ? $write['wr_2'] : '#000000' ?>" id="wr_2" class="frm_input">
                            <input type="text" name="wr_2_text" value="<?php echo isset($write['wr_2']) ? $write['wr_2'] : '' ?>" id="wr_2_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                </div>
            </dd>
        </dl>

        <dl>
            <dt><label>썸네일 종류</label></dt>
            <dd>
                <div class="log20_field_inline">
                    <?php
                    $has_image = isset($write['wr_7']) && !empty($write['wr_7']);
                    $has_icon = isset($write['wr_8']) && !empty($write['wr_8']);
                    $default_thumbnail_type = ($has_icon && !$has_image) ? 'icon' : 'image';
                    ?>
                    <label for="thumbnail_type_image" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="thumbnail_type" id="thumbnail_type_image" value="image" <?php echo $default_thumbnail_type === 'image' ? 'checked' : ''; ?> style="margin: 0;">
                        <span>이미지 업로드</span>
                    </label>
                    <label for="thumbnail_type_icon" style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="radio" name="thumbnail_type" id="thumbnail_type_icon" value="icon" <?php echo $default_thumbnail_type === 'icon' ? 'checked' : ''; ?> style="margin: 0;">
                        <span>FA 아이콘 사용</span>
                    </label>
                </div>
            </dd>
        </dl>

        <dl class="log20_double_row" id="thumbnail_image_area" style="<?php echo $default_thumbnail_type === 'icon' ? 'display: none;' : ''; ?>">
            <dt>썸네일 이미지</dt>
            <dd>
                <?php if ($w == 'u' && isset($write['wr_7']) && !empty($write['wr_7'])) { ?>
                <div class="log20_existing_image" style="margin-bottom: 15px;">
                    <label>기존 이미지</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                        <img src="<?php echo htmlspecialchars($write['wr_7']) ?>" alt="기존 이미지" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                        <div>
                            <a href="<?php echo htmlspecialchars($write['wr_7']) ?>" target="_blank" style="display: block; margin-bottom: 5px;">이미지 크게 보기</a>
                            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" name="wr_7_del" value="1" style="margin: 0;">
                                <span>기존 이미지 삭제</span>
                            </label>
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
                        <input type="url" name="wr_7" value="<?php echo isset($write['wr_7']) ? $write['wr_7'] : '' ?>" id="wr_7" class="frm_input full_input" size="50" placeholder="https://example.com/image.jpg">
                        <small style="display: block; margin-top: 5px; color: #666;">또는 이미지 URL을 입력하세요.</small>
                    </div>
                </div>
            </dd>
        </dl>

        <dl class="log20_double_row" id="thumbnail_icon_area" style="<?php echo $default_thumbnail_type === 'icon' ? '' : 'display: none;'; ?>">
            <dt>FA 아이콘 설정</dt>
            <dd>
                <div class="log20_field_row">
                    <div class="log20_field_block" style="flex: 0 0 35%; min-width: 200px;">
                        <label for="wr_8">FA 무료 아이콘</label>
                        <input type="text" name="wr_8" value="<?php echo isset($write['wr_8']) ? $write['wr_8'] : '' ?>" id="wr_8" class="frm_input full_input" size="50" maxlength="100" placeholder="예: 이름만 입력">
                        <small>FontAwesome의 fa-solid fa- 뒤의 이름만 입력</small>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_9">아이콘 색</label>
                        <div class="log20_field_inline">
                            <input type="color" name="wr_9" value="<?php echo isset($write['wr_9']) && $write['wr_9'] ? $write['wr_9'] : '#000000' ?>" id="wr_9" class="frm_input">
                            <input type="text" name="wr_9_text" value="<?php echo isset($write['wr_9']) ? $write['wr_9'] : '' ?>" id="wr_9_text" class="frm_input" placeholder="#000000 형식" maxlength="7">
                        </div>
                    </div>
                    <div class="log20_field_block">
                        <label for="wr_10">배경 색</label>
                        <div class="log20_field_inline">
                            <input type="color" name="wr_10" value="<?php echo isset($write['wr_10']) && $write['wr_10'] ? $write['wr_10'] : '#ffffff' ?>" id="wr_10" class="frm_input">
                            <input type="text" name="wr_10_text" value="<?php echo isset($write['wr_10']) ? $write['wr_10'] : '' ?>" id="wr_10_text" class="frm_input" placeholder="#ffffff 형식" maxlength="7">
                        </div>
                    </div>
                </div>
            </dd>
        </dl>
    </div>
    <!-- } TR_LOG 전용 필드 끝 -->


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

    // 색상 선택기와 텍스트 입력 동기화
    $(function() {
        // 색1 동기화
        $('#wr_1').on('change', function() {
            $('#wr_1_text').val($(this).val());
        });
        $('#wr_1_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#wr_1').val(val);
            }
        });

        // 색2 동기화
        $('#wr_2').on('change', function() {
            $('#wr_2_text').val($(this).val());
        });
        $('#wr_2_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#wr_2').val(val);
            }
        });

        // FA 색(wr_9) 동기화
        $('#wr_9').on('change', function() {
            $('#wr_9_text').val($(this).val());
        });
        $('#wr_9_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#wr_9').val(val);
            }
        });

        // 배경 색(wr_10) 동기화
        $('#wr_10').on('change', function() {
            $('#wr_10_text').val($(this).val());
        });
        $('#wr_10_text').on('input', function() {
            var val = $(this).val();
            if (/^#[0-9A-F]{6}$/i.test(val)) {
                $('#wr_10').val(val);
            }
        });

        // 썸네일 종류 선택에 따른 필드 표시/숨김
        function toggleThumbnailFields() {
            var selectedType = $('input[name="thumbnail_type"]:checked').val();
            if (selectedType === 'image') {
                $('#thumbnail_image_area').show();
                $('#thumbnail_icon_area').hide();
            } else if (selectedType === 'icon') {
                $('#thumbnail_image_area').hide();
                $('#thumbnail_icon_area').show();
            }
        }

        $('input[name="thumbnail_type"]').on('change', function() {
            toggleThumbnailFields();
        });

        // 초기 상태 설정
        toggleThumbnailFields();

        // 공지 체크 시 게시글 순서를 최상단으로 설정
        function syncNoticeOrder() {
            var $notice = $('#notice');
            var $order = $('#wr_order');
            if (!$notice.length || !$order.length) {
                return;
            }

            if ($notice.is(':checked')) {
                if ($order.data('prev-order') === undefined) {
                    $order.data('prev-order', $order.val());
                }
                $order.val(-1);
            } else if ($order.data('prev-order') !== undefined) {
                $order.val($order.data('prev-order'));
                $order.removeData('prev-order');
            }
        }

        $('#notice').on('change', function() {
            syncNoticeOrder();
        });
        syncNoticeOrder();
    });

    // RA0 비밀글 선택 시 비밀번호 입력창 표시
    document.addEventListener("DOMContentLoaded", function() {
        var setSecretSelect = document.getElementById("set_secret");
        var secretPasswordDiv = document.getElementById("secret_password_area");
        var secretPasswordInput = document.getElementById("wr_secret");

        if (setSecretSelect && secretPasswordDiv && secretPasswordInput) {
            setSecretSelect.addEventListener("change", function() {
                if (this.value === "secret") {
                    secretPasswordDiv.style.display = "block";
                    secretPasswordInput.required = true;
                } else {
                    secretPasswordDiv.style.display = "none";
                    secretPasswordInput.required = false;
                    // 비밀번호 값은 유지 (수정 시 기존 값 보존)
                }
            });

            // 수정 시 비밀글이면 비밀번호 입력창 표시
            if (setSecretSelect && setSecretSelect.value === "secret") {
                secretPasswordDiv.style.display = "block";
                secretPasswordInput.required = true;
            }
        }
    });

    function fwrite_submit(f)
    {
        // 공지 체크 시 게시글 순서를 최상단으로 고정
        if (f.notice && f.notice.checked && f.wr_order) {
            f.wr_order.value = -1;
        }
        // 색상 값 처리 (텍스트 입력값이 있으면 그것을 사용, 없으면 color input 값 사용)
        if (f.wr_1 && f.wr_1_text) {
            if (f.wr_1_text.value && /^#[0-9A-F]{6}$/i.test(f.wr_1_text.value)) {
                f.wr_1.value = f.wr_1_text.value;
            } else if (!f.wr_1_text.value && f.wr_1.value) {
                f.wr_1_text.value = f.wr_1.value;
            }
        }
        if (f.wr_2 && f.wr_2_text) {
            if (f.wr_2_text.value && /^#[0-9A-F]{6}$/i.test(f.wr_2_text.value)) {
                f.wr_2.value = f.wr_2_text.value;
            } else if (!f.wr_2_text.value && f.wr_2.value) {
                f.wr_2_text.value = f.wr_2.value;
            }
        }
        if (f.wr_9 && f.wr_9_text) {
            if (f.wr_9_text.value && /^#[0-9A-F]{6}$/i.test(f.wr_9_text.value)) {
                f.wr_9.value = f.wr_9_text.value;
            } else if (!f.wr_9_text.value && f.wr_9.value) {
                f.wr_9_text.value = f.wr_9.value;
            }
        }
        if (f.wr_10 && f.wr_10_text) {
            if (f.wr_10_text.value && /^#[0-9A-F]{6}$/i.test(f.wr_10_text.value)) {
                f.wr_10.value = f.wr_10_text.value;
            } else if (!f.wr_10_text.value && f.wr_10.value) {
                f.wr_10_text.value = f.wr_10.value;
            }
        }

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

        <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</section>
<!-- } 게시물 작성/수정 끝 -->

