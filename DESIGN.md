# Design Document: PDF Activity Grader (PDF 활동지 채점기)

## 1. Overview
스캔된 이미지나 PDF 형식의 활동지를 OCR(광학 문자 인식)로 읽어 들여, 정답지와 비교하고 오타를 점검하여 자동으로 채점하는 Python 기반 웹 애플리케이션입니다.

## 2. Core Features
- **이중 파일 업로드**: 교사용 정답지(Key)와 학생용 제출본(Submission) PDF/이미지 업로드.
- **한국어 OCR**: `EasyOCR` 또는 `PaddleOCR`을 활용하여 이미지 내 텍스트 추출 (세로쓰기/가로쓰기 대응).
- **텍스트 비교 및 채점**: 
    - 정답지와 학생 제출본의 텍스트를 정렬(Alignment).
    - 오타(Typo) 및 오답 감지.
    - 유사도 기반 채점 또는 감점 방식 적용.
- **시각적 결과 리포트**:
    - 어느 부분에서 오타가 발생했는지 강조 표시.
    - 최종 점수 및 정답/오답 상세 리스트 제공.

## 3. Technology Stack
- **Language**: Python 3.10+
- **Frontend/Backend**: `Streamlit` (빠른 프로토타이핑 및 인터랙티브 UI)
- **OCR**: `EasyOCR` (한국어 지원 및 설정 간편)
- **PDF Processing**: `pdf2image`, `poppler`
- **Text Comparison**: `difflib` (SequenceMatcher) or Fuzzy string matching.

## 4. User Flow
1. **정답지 업로드**: 교사가 기준이 되는 정답 활동지를 업로드합니다.
2. **학생 제출물 업로드**: 채점할 학생들의 활동지 파일들을 업로드합니다.
3. **분석 시작**: '채점 시작' 버튼 클릭 시 서버에서 OCR 및 비교 로직 수행.
4. **결과 확인**: 화면에 각 학생별 오타 위치, 교정 제안, 최종 점수 출력.
5. **내보내기**: 채점 결과를 CSV 또는 PDF로 저장.

## 5. Visual Strategy (UI/UX)
- **Modern Clean Dashboard**: Streamlit의 사이드바와 메인 레이아웃을 활용하여 깔끔한 인터페이스 구성.
- **Interactive Highlighting**: 정답과 다른 부분을 빨간색으로 표시하여 시인성 확보.
- **Progress Indicators**: 대용량 PDF 처리 시 진행 상태 바(Progress bar) 표시.

---
**이 설계대로 진행할까요? 추가하거나 수정하고 싶은 기능이 있다면 말씀해 주세요.**
