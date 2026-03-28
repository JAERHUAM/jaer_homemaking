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
        throw new Exception('관리자만 삭제할 수 있습니다.');
    }
    
    // POST 데이터 확인
    if (!isset($_POST['filename']) || !isset($_POST['bo_table'])) {
        throw new Exception('필수 데이터가 누락되었습니다.');
    }
    
    $bo_table = clean_xss_tags($_POST['bo_table']);
    $filename = basename($_POST['filename']); // 경로 조작 방지
    
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
    
    // 파일 경로 설정
    $images_dir = $board_skin_path . '/images';
    $filepath = $images_dir . '/' . $filename;
    
    // 파일 존재 확인
    if (!file_exists($filepath)) {
        throw new Exception('파일을 찾을 수 없습니다.');
    }
    
    // 파일이 images 디렉토리 내에 있는지 확인 (보안)
    $real_filepath = realpath($filepath);
    $real_images_dir = realpath($images_dir);
    
    if (!$real_filepath || !$real_images_dir || strpos($real_filepath, $real_images_dir) !== 0) {
        throw new Exception('잘못된 파일 경로입니다.');
    }
    
    // 파일 삭제
    if (!@unlink($filepath)) {
        throw new Exception('파일 삭제에 실패했습니다.');
    }
    
    // 성공 응답
    $response = array(
        'success' => true,
        'message' => '파일이 성공적으로 삭제되었습니다.'
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

