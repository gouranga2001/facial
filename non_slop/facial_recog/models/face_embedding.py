from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class f_embedding(BaseModel):
    user_id: int
    embedding: list[float]
