import { FaceDetector,FilesetResolver } from '@mediapipe/tasks-vision';

// Stores the active camera stream globally
let stream = null;
let faceDetector = null; 
const cameraOnOffBtn = document.getElementById('camera-on-off-btn');
let detectedFaceCount = null;
let currentTimeDetectionData = [];

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
        minDetectionConfidence: 0.8,
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
                startDetection();
                console.log('num of faces detected',detectedFaceCount);
                
                
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

function startDetection() {

    const videoElement = document.getElementById('webcam_footage');
    const canvasElement = document.getElementById('webcam_canvas');
    const ctx = canvasElement.getContext('2d');

    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;
    //cuurent time
    const startTimeMs = performance.now();

    const results = faceDetector.detectForVideo(
        videoElement,
        startTimeMs// telling media pipe to start detecting the img of the current time frame
    );

    ctx.clearRect(0,0,canvasElement.width,canvasElement.height);

    ctx.drawImage(videoElement,0,0,canvasElement.width,canvasElement.height);

    if (results.detections) {
        for (const detection of results.detections) {
            drawBoundingBox(
                detection.boundingBox,
                ctx
            );
            console.log(detection);
            
        }
    }

    currentTimeDetectionData = results.detections;

    
    requestAnimationFrame(startDetection);

    detectedFaceCount = results.detections.length;
    console.log('numner of faces',detectedFaceCount);
}

function takePhoto() {
    const photoButton = document.getElementById("clickPhoto");
    if (detectedFaceCount == 1) {
        console.log('eligible for clicking the btn'); 
        if(currentTimeDetectionData.length === 1){
            console.log('detected value when taken a photo is ',currentTimeDetectionData[0]);
            console.log(currentTimeDetectionData[0].data.keypoints);
        }  
    }
}
async function init() {
    await initializeMediaPipe();

    cameraOnOffBtn.addEventListener('click', async () => {
        await turnOnCamera();
        takePhoto();
    });
}

init();
