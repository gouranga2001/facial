import { FaceDetector,FilesetResolver } from '@mediapipe/tasks-vision';

// Stores the active camera stream globally
let stream = null;
let faceDetector = null; 
let lastVideo = -1;
const cameraOnOffBtn = document.getElementById('camera-on-off-btn');

// 1. initialize mediapipe
async function initializeMediaPipe() {
    const vison = await FilesetResolver.forVisionTasks(
        "node_modules/@mediapipe/tasks-vision/wasm"
    );

    faceDetector = await FaceDetector.createFromOptions(vison,{
        baseOptions: {
            modelAssetPath: "app/shared/models/blaze_face_short_range.tflite",
            delegate: 'CPU'
        },
        minDetectionConfidence: 0.5,
        minSuppressionThreshold: 0.3,
        runningMode: 'VIDEO'
    });

}

// 2. check weather camera exist and turn on the camera
async function checkCameraExists () {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const hasCamera = devices.some(device => device.kind === 'videoinput');
    return hasCamera
}


async function turnOnCamera () {
    //html video tag
     const videoElement = document.getElementById('webcam_footage');

    

    const isCameraExist = await checkCameraExists();
    if (!isCameraExist){
        console.log('camera does not exist');
        return;
    }

    
    // Scenario 1: Camera has never been turned on (First initialization)
    if(stream === null) {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video:true });
            videoElement.srcObject = stream;
            videoElement.onloadedmetadata = () => {
                videoElement.play();
                startDetection()
            }
            console.log("this is the stream",stream);
        }
        catch (error) {
            console.error('error with camera',error);
        }
        return;
    }

    // Scenario 2: Camera is already active, so toggle the video track state
    const videoTrack = stream.getVideoTracks()[0];

    if (videoTrack.enabled) {
        stream.getTracks().forEach(track => track.stop());
        videoElement.srcObject = null;
        stream = null;

    }
    else {
        videoTrack.enabled = true;
    }
    
   

}

function drawBoundingBox(boundingBox, ctx) {

    ctx.strokeStyle = "red";
    ctx.lineWidth = 3;

    ctx.strokeRect(
        boundingBox.originX,
        boundingBox.originY,
        boundingBox.width,
        boundingBox.height
    );
}

async function startDetection() {
    const videoElement = document.getElementById('webcam_footage');
    const htmlCanvasElement = document.getElementById('webcam_canvas');
    const canvasElement = document.getElementById('webcam_canvas');
    const ctx = canvasElement.getContext('2d');
    htmlCanvasElement.width = videoElement.videoWidth;
    htmlCanvasElement.height = videoElement.videoHeight;
    
    if (videoElement.currentTime !== lastVideo) {
        lastVideo = videoElement.currentTime;

        const startTimeMs = performance.now();
        const results = faceDetector.detectForVideo(videoElement,startTimeMs);

        // Clear canvas, draw current frame, then draw detections
        ctx.clearRect(0, 0, htmlCanvasElement.width, htmlCanvasElement.height);
        ctx.drawImage(videoElement, 0, 0, htmlCanvasElement.width, htmlCanvasElement.height);

        if(results.detections) {
            for(const detections  of results.detections) {
                drawBoundingBox(detections.boundingBox,ctx);
            }
        }
    }
}



async function init() {
    await initializeMediaPipe();

    cameraOnOffBtn.addEventListener('click', async () => {
        await turnOnCamera();

    });
}

init();
