import json
import os
import uuid
import logging
import numpy as np

from contextlib import asynccontextmanager
from fastapi import FastAPI
from fastapi.responses import JSONResponse
from pydantic import BaseModel

from app.insightface_service import (
    load_model,
    decode_image_from_upload,
    get_embedding,
    cosine_similarity,
    classify,
)

logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")
logger = logging.getLogger(__name__)

face_model = None

# Base directory that Laravel-provided file_paths are resolved against.
# Set via env var in real deployments; keeps this service from being able
# to read arbitrary files on disk if a path is ever malformed or malicious.
UPLOAD_BASE_DIR = os.environ.get("UPLOAD_BASE_DIR", "/var/app/storage/photos")


class EmbedRequest(BaseModel):
    file_path: str


class VerifyRequest(BaseModel):
    file_path: str
    stored_embedding: str


def resolve_safe_path(file_path: str) -> str:
    """
    Resolve file_path relative to UPLOAD_BASE_DIR and make sure the
    result doesn't escape that directory (blocks ../.. traversal and
    absolute-path overrides).
    """
    base = os.path.realpath(UPLOAD_BASE_DIR)
    full_path = os.path.realpath(os.path.join(base, file_path))
    if not (full_path == base or full_path.startswith(base + os.sep)):
        raise ValueError("file_path resolves outside the allowed directory")
    return full_path


@asynccontextmanager
async def lifespan(app: FastAPI):
    global face_model
    face_model = load_model()
    logger.info("InsightFace model loaded")
    yield
    logger.info("Shutting down")


app = FastAPI(
    title="Face Recognition API",
    lifespan=lifespan,
)


# Plain `def` (not `async def`): FastAPI runs sync routes in a threadpool
# automatically, so the blocking file I/O + inference here no longer stalls
# the event loop for other in-flight requests.
@app.post("/api/v1/face/embed")
def embed_face(request: EmbedRequest):
    correlation_id = str(uuid.uuid4())
    logger.info(f"[{correlation_id}] embed request")

    try:
        safe_path = resolve_safe_path(request.file_path)

        with open(safe_path, "rb") as photo:
            photo_bytes = photo.read()

        img = decode_image_from_upload(photo_bytes)
        embedding = get_embedding(face_model, img)

        return {
            "correlation_id": correlation_id,
            "success": True,
            "data": {
                "embedding": embedding.tolist()
            },
        }

    except FileNotFoundError:
        logger.warning(
            f"[{correlation_id}] file not found: {request.file_path}"
        )

        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "FILE_NOT_FOUND",
                    "message": "The specified file was not found",
                },
            },
        )

    except ValueError as e:
        logger.warning(f"[{correlation_id}] {e}")

        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "FACE_NOT_DETECTED",
                    "message": str(e),
                },
            },
        )

    except Exception:
        logger.exception(
            f"[{correlation_id}] unexpected error"
        )

        return JSONResponse(
            status_code=500,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "INTERNAL_ERROR",
                    "message": "Something went wrong",
                },
            },
        )


@app.post("/api/v1/face/verify")
def verify_face(request: VerifyRequest):
    correlation_id = str(uuid.uuid4())
    logger.info(f"[{correlation_id}] verify request")

    try:
        stored_emb_array = np.array(
            json.loads(request.stored_embedding),
            dtype=np.float32,
        )

        safe_path = resolve_safe_path(request.file_path)

        with open(safe_path, "rb") as photo:
            photo_bytes = photo.read()

        uploaded_img = decode_image_from_upload(photo_bytes)
        uploaded_embedding = get_embedding(
            face_model,
            uploaded_img,
        )

        similarity = cosine_similarity(
            uploaded_embedding,
            stored_emb_array,
        )

        decision = classify(similarity)
        # Collapse the three-way decision to a boolean for Laravel.
        # REVIEW currently defaults to False (treated as "not a match")
        # since it's an uncertain case, not a confident accept.
        is_match = decision == "ACCEPT"

        return {
            "correlation_id": correlation_id,
            "success": True,
            "data": {
                "match": is_match,
                "similarity": round(similarity, 4),
                "decision": decision,
            },
        }

    except FileNotFoundError:
        logger.warning(
            f"[{correlation_id}] file not found: {request.file_path}"
        )

        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "FILE_NOT_FOUND",
                    "message": "The specified file was not found",
                },
            },
        )

    except (json.JSONDecodeError, TypeError, ValueError) as e:
        logger.warning(
            f"[{correlation_id}] invalid stored_embedding or file_path: {e}"
        )

        return JSONResponse(
            status_code=422,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "INVALID_EMBEDDING",
                    "message": "stored_embedding was malformed",
                },
            },
        )

    except Exception:
        logger.exception(
            f"[{correlation_id}] unexpected error"
        )

        return JSONResponse(
            status_code=500,
            content={
                "correlation_id": correlation_id,
                "success": False,
                "error": {
                    "code": "INTERNAL_ERROR",
                    "message": "Something went wrong",
                },
            },
        )