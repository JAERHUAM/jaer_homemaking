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
        throw new Exception('관리자만 메모를 저장할 수 있습니다.');
    }
    
    // POST 데이터 확인
    if (!isset($_POST['filename']) || !isset($_POST['bo_table'])) {
        throw new Exception('필수 데이터가 누락되었습니다.');
    }
    
    $bo_table = clean_xss_tags($_POST['bo_table']);
    $filename = basename($_POST['filename']); // 경로 조작 방지
    $memo = isset($_POST['memo']) ? trim($_POST['memo']) : '';
    
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
    
    // 이미지 디렉토리 경로
    $images_dir = $board_skin_path . '/images';
    $memos_file = $images_dir . '/image_memos.json';
    
    // 메모 데이터 로드
    $image_memos = array();
    if (file_exists($memos_file)) {
        $memos_json = file_get_contents($memos_file);
        $image_memos = json_decode($memos_json, true);
        if (!is_array($image_memos)) {
            $image_memos = array();
        }
    }
    
    // 메모 저장 또는 삭제
    if (empty($memo)) {
        // 빈 메모는 삭제
        if (isset($image_memos[$filename])) {
            unset($image_memos[$filename]);
        }
    } else {
        // 메모 저장 (XSS 방지)
        $image_memos[$filename] = htmlspecialchars($memo, ENT_QUOTES, 'UTF-8');
    }
    
    // JSON 파일로 저장
    $memos_json = json_encode($image_memos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($memos_file, $memos_json) === false) {
        throw new Exception('메모 저장에 실패했습니다.');
    }
    
    // 파일 권한 설정
    @chmod($memos_file, 0644);
    
    // 성공 응답
    $response = array(
        'success' => true,
        'message' => '메모가 성공적으로 저장되었습니다.'
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

