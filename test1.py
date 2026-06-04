import cv2 as cv
import insightface
import numpy as np
from insightface.app import FaceAnalysis


# Initialize face analysis model
app = FaceAnalysis(name='buffalo_l', providers=['CPUExecutionProvider'])  # Use 'CUDAExecutionProvider' for GPU
app.prepare(ctx_id=-1)  # ctx_id=-1 for CPU, 0 for GPU


# get face embeddings
def get_face_embeddings(image_path):
    """Extract face embeddings"""

    img = cv.imread(image_path)
    if img is None:
        raise FileNotFoundError(f"could not find image {image_path}")
    
    faces = app.get(img)

    if (len(faces) < 1):
        raise ValueError('no face detected')
    if (len(faces) > 1):
        raise ValueError('multiple faces detected')
    
    return faces[0].embedding


# compare face using cosine similarity
def compare_faces(emb1, emb2):
    """Compare two embeddings using cosine similarity"""

    similarity = np.dot(emb1, emb2) / (np.linalg.norm(emb1) * np.linalg.norm(emb2))
    print(similarity)
    return similarity

img_path_1 = 'img_1.jpg'
img_path_2 = 'img_2.jpeg'


try:
    emb_1 = get_face_embeddings(img_path_1)
    emb_2 = get_face_embeddings(img_path_2)

    similarity_score = compare_faces(emb_1,emb_2)

    print(f"comparing {img_path_1} with {img_path_2}")
    print(f"Similarity Score: {similarity_score:.4f}")

    if similarity_score > 0.65: #threshold = 0.65
        print("Same person: YES")
    else:
        print("Same person: NO")

    

except Exception as e:
    print(f"Error: {str(e)}")


"""
    missing elements for production
    - Enrollment with multiple images
    - generally if we see a phone's faceid it does not rely on one frontal image 
    we need users to provide one of each atleast
     - front face
     - slight left turn to few degrees
     - slight right turn to few degrees
     - looking slightly up
     - looking slightly down
"""