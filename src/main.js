import { FaceDetector, FilesetResolver }
from "@mediapipe/tasks-vision";
import { FaceLandmarker,FilesetResolver } from "@mediapipe/tasks-vision";

async function faceDetect() {

    // Load image
    const img = new Image();

    img.src = "/img_2.jpeg";

    await img.decode();

    // Canvas setup
    const canvas = document.getElementById("canvas");

    const ctx = canvas.getContext("2d");

    canvas.width = img.width;
    canvas.height = img.height;

    // Draw original image
    ctx.drawImage(img, 0, 0);

    // Load MediaPipe WASM
    const vision =
        await FilesetResolver.forVisionTasks(
            "/wasm"
        );

    // Create detector
    const faceDetector =
        await FaceDetector.createFromOptions(
            vision,
            {
                baseOptions: {
                    modelAssetPath:
                        "/models/blaze_face_short_range.tflite"
                },
                runningMode: "IMAGE"
            }
        );

    // Detect
    const result =
        faceDetector.detect(img);

    console.log(result);

    // Draw bounding boxes
    result.detections.forEach((detection) => {

        const box = detection.boundingBox;

        ctx.lineWidth = 4;
        ctx.strokeStyle = "red";

        ctx.strokeRect(
            box.originX,
            box.originY,
            box.width,
            box.height
        );
    });
}

faceDetect();