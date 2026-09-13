import os
import json
import uuid
import logging
import numpy as np
import mariadb
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.responses import JSONResponse

from app.insightface_service import load_model, decode_image_from_upload, get_embedding, cosine_similarity, classify
from app.schema import APIResponse, FaceMatchData, ErrorDetail, Decision

logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger(__name__)

app = FastAPI(title="Face Recognition API")
face_model = None

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "localhost"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "facial"),
}


@app.on_event("startup")
def startup_event():
    global face_model
    face_model = load_model()
    logger.info("InsightFace model loaded")


def get_db_conn():
    return mariadb.connect(**DB_CONFIG)


def fetch_embedding(user_id: int):
    conn = get_db_conn()
    try:
        cur = conn.cursor()
        cur.execute("SELECT embedding FROM face_embeddings WHERE user_id = ?", (user_id,))
        row = cur.fetchone()
        if row is None:
            return None
        return np.array(json.loads(row[0]), dtype=np.float32)
    finally:
        conn.close()


@app.post("/api/v1/face/verify", response_model=APIResponse)
async def verify_face(
    user_id: int = Form(...),
    photo: UploadFile = File(...),
):
    correlation_id = str(uuid.uuid4())
    logger.info(f"[{correlation_id}] verify request user_id={user_id}")

    try:
        stored_embedding = fetch_embedding(user_id)
        if stored_embedding is None:
            return JSONResponse(
                status_code=404,
                content=APIResponse(
                    correlation_id=correlation_id,
                    success=False,
                    error=ErrorDetail(code="USER_NOT_FOUND", message=f"No embedding for user_id {user_id}"),
                ).dict(),
            )

        photo_bytes = await photo.read()
        uploaded_img = decode_image_from_upload(photo_bytes)
        uploaded_embedding = get_embedding(face_model, uploaded_img)

        similarity = cosine_similarity(uploaded_embedding, stored_embedding)
        decision = classify(similarity)

        return APIResponse(
            correlation_id=correlation_id,
            success=True,
            data=FaceMatchData(user_id=str(user_id), similarity=round(similarity, 4), decision=Decision(decision)),
        )

    except ValueError as e:
        logger.warning(f"[{correlation_id}] {e}")
        return JSONResponse(
            status_code=422,
            content=APIResponse(
                correlation_id=correlation_id,
                success=False,
                error=ErrorDetail(code="FACE_NOT_DETECTED", message=str(e)),
            ).dict(),
        )
    except mariadb.Error as e:
        logger.error(f"[{correlation_id}] db error: {e}")
        return JSONResponse(
            status_code=500,
            content=APIResponse(
                correlation_id=correlation_id,
                success=False,
                error=ErrorDetail(code="DB_ERROR", message="Database error"),
            ).dict(),
        )
    except Exception:
        logger.exception(f"[{correlation_id}] unexpected error")
        return JSONResponse(
            status_code=500,
            content=APIResponse(
                correlation_id=correlation_id,
                success=False,
                error=ErrorDetail(code="INTERNAL_ERROR", message="Something went wrong"),
            ).dict(),
        )