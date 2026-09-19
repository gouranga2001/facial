import json
import uuid
import logging
import numpy as np
from contextlib import asynccontextmanager
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.responses import JSONResponse

from app.insightface_service import load_model, decode_image_from_upload, get_embedding, cosine_similarity, classify

logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger(__name__)

face_model = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    global face_model
    face_model = load_model()
    logger.info("InsightFace model loaded")
    yield
    logger.info("Shutting down")


app = FastAPI(title="Face Recognition API", lifespan=lifespan)


@app.post("/api/v1/face/embed")
async def embed_face(photo: UploadFile = File(...)):
    correlation_id = str(uuid.uuid4())
    logger.info(f"[{correlation_id}] embed request")

    try:
        photo_bytes = await photo.read()
        img = decode_image_from_upload(photo_bytes)
        embedding = get_embedding(face_model, img)

        return {
            "correlation_id": correlation_id,
            "success": True,
            "data": {"embedding": embedding.tolist()},
        }

    except ValueError as e:
        logger.warning(f"[{correlation_id}] {e}")
        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {"code": "FACE_NOT_DETECTED", "message": str(e)},
            },
        )
    except Exception:
        logger.exception(f"[{correlation_id}] unexpected error")
        return JSONResponse(
            status_code=500,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {"code": "INTERNAL_ERROR", "message": "Something went wrong"},
            },
        )


@app.post("/api/v1/face/verify")
async def verify_face(
    stored_embedding: str = Form(...),
    photo: UploadFile = File(...),
):
    correlation_id = str(uuid.uuid4())
    logger.info(f"[{correlation_id}] verify request")

    try:
        stored_emb_array = np.array(json.loads(stored_embedding), dtype=np.float32)

        photo_bytes = await photo.read()
        uploaded_img = decode_image_from_upload(photo_bytes)
        uploaded_embedding = get_embedding(face_model, uploaded_img)

        similarity = cosine_similarity(uploaded_embedding, stored_emb_array)
        decision = classify(similarity)

        return {
            "correlation_id": correlation_id,
            "success": True,
            "data": {"similarity": round(similarity, 4), "decision": decision},
        }

    except ValueError as e:
        logger.warning(f"[{correlation_id}] {e}")
        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {"code": "FACE_NOT_DETECTED", "message": str(e)},
            },
        )
    except (json.JSONDecodeError, TypeError) as e:
        logger.warning(f"[{correlation_id}] bad stored_embedding: {e}")
        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {"code": "INVALID_EMBEDDING", "message": "stored_embedding was malformed"},
            },
        )
    except Exception:
        logger.exception(f"[{correlation_id}] unexpected error")
        return JSONResponse(
            status_code=500,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {"code": "INTERNAL_ERROR", "message": "Something went wrong"},
            },
        )