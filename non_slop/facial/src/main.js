import { FaceLandmarker, FilesetResolver } from '@mediapipe/tasks-vision';

let stream = null;
let faceLandmarker = null;
const cameraOnOffBtn = document.getElementById('camera-on-off-btn');
let detectedFaceCount = null;
let currentTimeDetectionData = []; // [{ landmarks, boundingBox, blendshapes }]

const MIN_BRIGHTNESS = 60;   // 0-255 grayscale average
const MAX_BRIGHTNESS = 200;
const BLUR_VARIANCE_THRESHOLD = 100; // Laplacian variance; lower = blurrier

// ---- blink-based liveness ----
const BLINK_SCORE_THRESHOLD = 0.5;  // blendshape score above this = eye considered closed
const BLINK_GRACE_PERIOD_MS = 8000; // how long we wait for at least one blink before failing
let blinkEvents = [];
let eyeCurrentlyClosed = false;
let livenessTrackingStartTime = null;

// 1. initialize mediapipe — Face Landmarker (not FaceDetector) so we get
// blendshape scores like eyeBlinkLeft/eyeBlinkRight for real blink detection.
// Model: https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task
async function initializeMediaPipe() {
    const vision = await FilesetResolver.forVisionTasks(
        "node_modules/@mediapipe/tasks-vision/wasm"
    );

    faceLandmarker = await FaceLandmarker.createFromOptions(vision, {
        baseOptions: {
            modelAssetPath: "app/shared/models/face_landmarker.task",
            delegate: 'CPU'
        },
        outputFaceBlendshapes: true,
        runningMode: 'VIDEO',
        numFaces: 1
    });
}

// 2. check whether camera exists and turn on the camera
async function checkCameraExists() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    return devices.some(device => device.kind === 'videoinput');
}

async function turnOnCamera() {
    const videoElement = document.getElementById('webcam_footage');

    if (!(await checkCameraExists())) {
        console.log('camera does not exist');
        return;
    }

    if (stream === null) {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoElement.srcObject = stream;
            videoElement.onloadedmetadata = () => {
                videoElement.play();
                livenessTrackingStartTime = performance.now();
                startDetection();
            };
        }
        catch (error) {
            console.error('error with camera', error);
        }
        return;
    }

    const videoTrack = stream.getVideoTracks()[0];
    if (videoTrack.enabled) {
        stream.getTracks().forEach(track => track.stop());
        videoElement.srcObject = null;
        stream = null;
        blinkEvents = [];
        eyeCurrentlyClosed = false;
        livenessTrackingStartTime = null;
    } else {
        videoTrack.enabled = true;
        livenessTrackingStartTime = performance.now();
    }
}

function drawBoundingBox(boundingBox, ctx) {
    ctx.strokeStyle = "red";
    ctx.lineWidth = 3;
    ctx.strokeRect(boundingBox.originX, boundingBox.originY, boundingBox.width, boundingBox.height);
}

// FaceLandmarker doesn't return a boundingBox directly (unlike FaceDetector),
// so derive one from the min/max of the 478 normalized landmark points.
function computeBoundingBox(landmarks, videoWidth, videoHeight) {
    let minX = 1, minY = 1, maxX = 0, maxY = 0;
    for (const lm of landmarks) {
        if (lm.x < minX) minX = lm.x;
        if (lm.y < minY) minY = lm.y;
        if (lm.x > maxX) maxX = lm.x;
        if (lm.y > maxY) maxY = lm.y;
    }
    return {
        originX: minX * videoWidth,
        originY: minY * videoHeight,
        width: (maxX - minX) * videoWidth,
        height: (maxY - minY) * videoHeight
    };
}

function startDetection() {
    const videoElement = document.getElementById('webcam_footage');
    const canvasElement = document.getElementById('webcam_canvas');
    const ctx = canvasElement.getContext('2d');

    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;

    const results = faceLandmarker.detectForVideo(videoElement, performance.now());

    ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
    ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

    const faces = results.faceLandmarks || [];
    detectedFaceCount = faces.length;

    currentTimeDetectionData = faces.map((landmarks, i) => ({
        landmarks,
        boundingBox: computeBoundingBox(landmarks, canvasElement.width, canvasElement.height),
        blendshapes: results.faceBlendshapes?.[i]
    }));

    for (const face of currentTimeDetectionData) {
        drawBoundingBox(face.boundingBox, ctx);
    }

    if (detectedFaceCount === 1 && currentTimeDetectionData[0].blendshapes) {
        recordBlinkSample(currentTimeDetectionData[0].blendshapes);
    }

    requestAnimationFrame(startDetection);
}

function cropByBBox(canvas, bbox, padding = 0.5) {
    const ctx = canvas.getContext('2d');

    const padX = bbox.width * padding;
    const padY = bbox.height * padding;

    const x = Math.max(0, bbox.originX - padX);
    const y = Math.max(0, bbox.originY - padY);
    const right = Math.min(canvas.width, bbox.originX + bbox.width + padX);
    const bottom = Math.min(canvas.height, bbox.originY + bbox.height + padY);

    const width = right - x;
    const height = bottom - y;

    const croppedCanvas = document.createElement('canvas');
    croppedCanvas.width = width;
    croppedCanvas.height = height;

    const croppedCtx = croppedCanvas.getContext('2d');
    croppedCtx.putImageData(ctx.getImageData(x, y, width, height), 0, 0);

    return croppedCanvas;
}

// records a completed blink (closed -> open transition) using the
// eyeBlinkLeft / eyeBlinkRight blendshape scores from Face Landmarker
function recordBlinkSample(blendshapes) {
    const categories = blendshapes.categories;
    const leftBlink = categories.find(c => c.categoryName === 'eyeBlinkLeft')?.score ?? 0;
    const rightBlink = categories.find(c => c.categoryName === 'eyeBlinkRight')?.score ?? 0;
    const blinkScore = Math.max(leftBlink, rightBlink);

    const now = performance.now();

    if (blinkScore > BLINK_SCORE_THRESHOLD) {
        eyeCurrentlyClosed = true;
    } else if (eyeCurrentlyClosed) {
        eyeCurrentlyClosed = false;
        blinkEvents.push(now); // eyes just reopened -> one completed blink
    }

    while (blinkEvents.length && now - blinkEvents[0] > BLINK_GRACE_PERIOD_MS) {
        blinkEvents.shift();
    }
}

// ---- lighting + blur ----
function errorHandling() {
    const webcam_canvas = document.getElementById('webcam_canvas');

    if (!currentTimeDetectionData || currentTimeDetectionData.length !== 1) {
        return { pass: false, reason: 'Make sure exactly one face is visible.' };
    }

    const bbox = currentTimeDetectionData[0].boundingBox;
    const faceCanvas = cropByBBox(webcam_canvas, bbox, 0.2);
    const ctx = faceCanvas.getContext('2d');
    const { data, width, height } = ctx.getImageData(0, 0, faceCanvas.width, faceCanvas.height);

    let total = 0;
    for (let i = 0; i < data.length; i += 4) {
        total += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
    }
    const avgBrightness = total / (data.length / 4);

    if (avgBrightness < MIN_BRIGHTNESS) {
        return { pass: false, reason: 'Too dark — move to a brighter area.' };
    }
    if (avgBrightness > MAX_BRIGHTNESS) {
        return { pass: false, reason: 'Too bright — avoid strong backlight.' };
    }

    const gray = new Float32Array(width * height);
    for (let i = 0; i < width * height; i++) {
        gray[i] = 0.299 * data[i * 4] + 0.587 * data[i * 4 + 1] + 0.114 * data[i * 4 + 2];
    }

    let sum = 0, sumSq = 0, count = 0;
    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            const idx = y * width + x;
            const v = gray[idx - width] + gray[idx + width] + gray[idx - 1] + gray[idx + 1] - 4 * gray[idx];
            sum += v;
            sumSq += v * v;
            count++;
        }
    }
    const mean = sum / count;
    const variance = (sumSq / count) - (mean * mean);

    if (variance < BLUR_VARIANCE_THRESHOLD) {
        return { pass: false, reason: 'Image is too blurry — hold the camera steady.' };
    }

    return { pass: true };
}

// ---- blink-based liveness ----
// A real blink is a physiological signal a static photo can never produce,
// so requiring at least one within a grace window is far more reliable than
// tracking raw eye-position drift. IMPORTANT LIMITATION: this does not stop
// someone holding a phone/tablet playing a video of a real (blinking) face —
// that kind of screen-replay spoof needs texture/moiré analysis, depth
// sensing, or a dedicated anti-spoofing model, which is beyond this heuristic.
function livelynessDetection() {
    if (livenessTrackingStartTime === null) {
        return { pass: true };
    }

    if (blinkEvents.length > 0) {
        return { pass: true };
    }

    const elapsed = performance.now() - livenessTrackingStartTime;
    if (elapsed < BLINK_GRACE_PERIOD_MS) {
        return { pass: true, reason: 'Analyzing...' };
    }

    return { pass: false, reason: 'No blink detected — please blink naturally.' };
}

function takePhoto() {
    const webcam_canvas = document.getElementById('webcam_canvas');

    if (detectedFaceCount !== 1 || currentTimeDetectionData.length !== 1) {
        return;
    }

    const lightingBlurResult = errorHandling();
    if (!lightingBlurResult.pass) {
        console.warn('Photo rejected:', lightingBlurResult.reason);
        return;
    }

    const livenessResult = livelynessDetection();
    if (!livenessResult.pass) {
        console.warn('Photo rejected:', livenessResult.reason);
        return;
    }

    const boundingBox = currentTimeDetectionData[0].boundingBox;
    const landmarks = currentTimeDetectionData[0].landmarks;
    const croppedImg = cropByBBox(webcam_canvas, boundingBox);

    document.body.appendChild(croppedImg);

    return { landmarks, boundingBox, croppedImg };
}

async function init() {
    await initializeMediaPipe();
    const photoButton = document.getElementById("clickPhoto");

    cameraOnOffBtn.addEventListener('click', async () => {
        await turnOnCamera();
    });

    photoButton.addEventListener('click', () => {
        takePhoto();
    });
}

init();