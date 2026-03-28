<?php
// 출력 버퍼 정리
if (ob_get_level()) {
    ob_end_clean();
}

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// 공통 파일 포함
include_once('./../../../_common.php');

try {
    // 관리자 권한 확인
    if (!$is_admin) {
        throw new Exception('관리자만 업로드할 수 있습니다.');
    }
    
    // POST 데이터 확인
    if (!isset($_FILES['image']) || !isset($_POST['bo_table'])) {
        throw new Exception('필수 데이터가 누락되었습니다.');
    }
    
    $bo_table = clean_xss_tags($_POST['bo_table']);
    
    // 게시판 정보 확인
    $board = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '" . sql_real_escape_string($bo_table) . "'");
    if (!$board) {
        throw new Exception('존재하지 않는 게시판입니다.');
    }
    
    // 현재 스킨 폴더 이름 가져오기
    $current_skin_name = basename(__DIR__);
    
    // 스킨이 현재 폴더의 스킨인지 확인
    if ($board['bo_skin'] !== $current_skin_name) {
        throw new Exception('이 스킨 게시판만 사용할 수 있습니다.');
    }
    
    // 스킨 경로 설정
    $board_skin_path = get_skin_path('board', $current_skin_name);
    $board_skin_url = get_skin_url('board', $current_skin_name);
    
    // 파일 업로드 처리
    $file = $_FILES['image'];
    
    // 파일 검증
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('파일 업로드 오류가 발생했습니다.');
    }
    
    // 파일 확장자 확인
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('허용되지 않는 파일 형식입니다. (jpg, jpeg, png, gif, webp만 허용)');
    }
    
    // MIME 타입 확인
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($mime_type, $allowed_mimes)) {
        throw new Exception('올바른 이미지 파일이 아닙니다.');
    }
    
    // 파일 크기 제한 (10MB)
    $max_file_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_file_size) {
        throw new Exception('파일 크기가 너무 큽니다. (최대 10MB)');
    }
    
    // 업로드 디렉토리 설정
    $images_dir = $board_skin_path . '/images';
    
    // images 폴더가 없으면 생성
    if (!is_dir($images_dir)) {
        if (!@mkdir($images_dir, 0755, true)) {
            throw new Exception('업로드 디렉토리를 생성할 수 없습니다.');
        }
    }
    
    // 파일명 생성 (중복 방지)
    $filename = date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
    $filepath = $images_dir . '/' . $filename;
    
    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('파일 저장에 실패했습니다.');
    }
    
    // 파일 권한 설정
    @chmod($filepath, 0644);
    
    // URL 생성
    $file_url = $board_skin_url . '/images/' . $filename;
    
    // 성공 응답
    $response = array(
        'success' => true,
        'url' => $file_url,
        'filename' => $filename,
        'message' => '파일이 성공적으로 업로드되었습니다.'
    );
    
    // 출력 버퍼 내용 제거
    ob_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 오류 응답
    $response = array(
        'success' => false,
        'message' => $e->getMessage()
    );
    
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

