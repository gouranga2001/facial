import threading

import insightface
import cv2 as cv
import numpy as np
import logging

logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger(__name__)

# onnxruntime sessions are not guaranteed safe for concurrent .run() calls
# from multiple threads. Since routes now run in FastAPI's threadpool,
# serialize access to the shared FaceAnalysis instance with this lock.
inference_lock = threading.Lock()


def decode_image_from_upload(file_bytes):
    arr = np.frombuffer(file_bytes, dtype=np.uint8)
    img = cv.imdecode(arr, cv.IMREAD_COLOR)
    if img is None:
        raise ValueError("Could not decode uploaded image")
    return img


def load_model(model_name="buffalo_l", det_size=(320, 320)):
    app = insightface.app.FaceAnalysis(
        name=model_name,
        providers=["CPUExecutionProvider"],
    )
    app.prepare(ctx_id=-1, det_size=det_size)  # ctx_id=-1 -> CPU
    return app


def load_image(path):
    img = cv.imread(path)
    if img is None:
        raise FileNotFoundError(f"Could not read image at: {path}")
    return img


def _face_area(face):
    x1, y1, x2, y2 = face.bbox
    return (x2 - x1) * (y2 - y1)


# generates 5 keypoints, different from what mediapipe generates
def get_face(app, img):
    with inference_lock:
        faces = app.get(img)
    if not faces:
        return None
    # pick the largest face by bbox area rather than detection order,
    # so a background face/poster doesn't get embedded by accident
    face = max(faces, key=_face_area)
    return face


def get_embedding(app, img):
    face = get_face(app, img)
    if face is None:
        raise ValueError("No usable face detected in image")
    return face.embedding


def cosine_similarity(a, b):
    a = a / np.linalg.norm(a)
    b = b / np.linalg.norm(b)
    return float(np.dot(a, b))


def classify(similarity, accept_thresh=0.40, reject_thresh=0.30):
    if similarity >= accept_thresh:
        return "ACCEPT"
    elif similarity >= reject_thresh:
        return "REVIEW"
    else:
        return "REJECT"


def compare_faces(app, img1, img2, accept_thresh=0.40, reject_thresh=0.30):
    emb1 = get_embedding(app, img1)
    emb2 = get_embedding(app, img2)

    similarity = cosine_similarity(emb1, emb2)
    decision = classify(similarity, accept_thresh, reject_thresh)

    return similarity, decision


def main():
    app = load_model()

    img1 = load_image("../photo_2.jpeg")
    img2 = load_image("../photo1.png")

    similarity, decision = compare_faces(app, img1, img2)

    logger.info(f"Similarity: {similarity:.4f}")
    logger.info(f"Decision:   {decision}")


if __name__ == "__main__":
    main()