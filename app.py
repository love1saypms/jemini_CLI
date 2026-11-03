import streamlit as st
import easyocr
import numpy as np
from PIL import Image
import fitz  # PyMuPDF
import difflib
import re
import io

# Set page config
st.set_page_config(page_title="PDF 활동지 채점기", layout="wide")

# Initialize EasyOCR reader
@st.cache_resource
def load_reader():
    return easyocr.Reader(['ko', 'en'])

reader = load_reader()

def extract_text_from_pdf(pdf_bytes):
    # Open PDF from bytes using PyMuPDF
    doc = fitz.open(stream=pdf_bytes, filetype="pdf")
    full_text = []
    
    for page_num in range(len(doc)):
        page = doc.load_page(page_num)
        # Render page to an image (pixmap)
        # Using a higher matrix for better OCR accuracy (zoom factor)
        zoom = 2.0 
        mat = fitz.Matrix(zoom, zoom)
        pix = page.get_pixmap(matrix=mat)
        
        # Convert pixmap to PIL Image
        img_data = pix.tobytes("png")
        image = Image.open(io.BytesIO(img_data))
        
        # Convert PIL image to numpy array for EasyOCR
        img_np = np.array(image)
        result = reader.readtext(img_np, detail=0)
        full_text.append("\n".join(result))
        
    doc.close()
    return "\n---\n".join(full_text)

def compare_texts(answer_key, student_submission):
    # Normalize texts (remove extra whitespaces, etc.)
    def normalize(text):
        return re.sub(r'\s+', ' ', text).strip()

    normalized_key = normalize(answer_key)
    normalized_sub = normalize(student_submission)

    # Use difflib to find differences
    matcher = difflib.SequenceMatcher(None, normalized_key, normalized_sub)
    ratio = matcher.ratio()
    
    diff = list(difflib.ndiff(normalized_key.split(), normalized_sub.split()))
    
    return ratio, diff

st.title("📝 PDF 활동지 채점기")
st.markdown("정답지 PDF와 학생 제출용 PDF를 업로드하여 오타를 점검하고 채점하세요.")

col1, col2 = st.columns(2)

with col1:
    st.header("1. 정답지 (Key)")
    key_file = st.file_uploader("교사용 정답지 PDF 업로드", type=["pdf"], key="key")

with col2:
    st.header("2. 학생 제출본 (Submission)")
    student_file = st.file_uploader("학생 제출 활동지 PDF 업로드", type=["pdf"], key="student")

if key_file and student_file:
    if st.button("채점 시작"):
        with st.spinner("OCR 분석 중... (시간이 다소 소요될 수 있습니다)"):
            try:
                # Extract text
                key_text = extract_text_from_pdf(key_file.read())
                student_text = extract_text_from_pdf(student_file.read())

                # Compare
                score_ratio, diff_list = compare_texts(key_text, student_text)

                st.success("분석 완료!")
                
                # Display Results
                st.subheader(f"📊 최종 유사도 점수: {score_ratio * 100:.2f}%")
                
                st.divider()
                
                st.subheader("🔍 오타 및 차이점 상세 분석")
                
                # Custom visualization for diff
                result_html = []
                for word in diff_list:
                    if word.startswith('  '): # Match
                        result_html.append(f"<span>{word[2:]} </span>")
                    elif word.startswith('- '): # Missing in student/Correct in key
                        result_html.append(f"<span style='color:red; text-decoration:line-through;'>{word[2:]}</span> ")
                    elif word.startswith('+ '): # Added by student/Typo
                        result_html.append(f"<span style='color:blue; font-weight:bold;'>{word[2:]}</span> ")
                
                st.markdown("".join(result_html), unsafe_allow_html=True)
                
                st.info("💡 **범례**: 빨간색(정답 누락), 파란색(학생 추가/오타)")

                with st.expander("원문 텍스트 보기"):
                    c1, c2 = st.columns(2)
                    c1.text_area("정답지 원문", key_text, height=300)
                    c2.text_area("학생 제출본 원문", student_text, height=300)

            except Exception as e:
                st.error(f"오류가 발생했습니다: {e}")
                st.info("PDF 파일이 올바른지 확인해주세요.")
else:
    st.warning("두 파일 모두 업로드해야 채점을 진행할 수 있습니다.")
