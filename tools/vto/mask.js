let landmarkerPromise = null;

function mediapipeUrls() {
    const version = "0.10.21";
    return {
        module: "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@" + version + "/vision_bundle.mjs",
        wasm: "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@" + version + "/wasm",
        model: "https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task",
    };
}

async function getLandmarker() {
    if (!landmarkerPromise) {
        landmarkerPromise = (async function () {
            const urls = mediapipeUrls();
            const vision = await import(urls.module);
            const fileset = await vision.FilesetResolver.forVisionTasks(urls.wasm);
            return vision.FaceLandmarker.createFromOptions(fileset, {
                baseOptions: {
                    modelAssetPath: urls.model,
                    delegate: "CPU",
                },
                runningMode: "IMAGE",
                numFaces: 1,
            });
        })();
    }
    return landmarkerPromise;
}

function loadImage(file) {
    return new Promise(function (resolve, reject) {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = function () {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = function () {
            URL.revokeObjectURL(url);
            reject(new Error("Could not read the user image for masking."));
        };
        img.src = url;
    });
}

function faceStats(landmarks, width, height) {
    let minX = width;
    let minY = height;
    let maxX = 0;
    let maxY = 0;
    for (let i = 0; i < landmarks.length; i++) {
        const x = landmarks[i].x * width;
        const y = landmarks[i].y * height;
        if (x < minX) minX = x;
        if (y < minY) minY = y;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    }
    const browIdx = [70, 63, 105, 66, 107, 55, 336, 296, 334, 293, 300, 285];
    let browY = 0;
    for (let i = 0; i < browIdx.length; i++) {
        browY += landmarks[browIdx[i]].y * height;
    }
    browY /= browIdx.length;
    return {
        minX: minX,
        minY: minY,
        maxX: maxX,
        maxY: maxY,
        cx: (minX + maxX) / 2,
        cy: (minY + maxY) / 2,
        w: maxX - minX,
        h: maxY - minY,
        browY: browY,
        chin: { x: landmarks[152].x * width, y: landmarks[152].y * height },
        leftEar: { x: landmarks[234].x * width, y: landmarks[234].y * height },
        rightEar: { x: landmarks[454].x * width, y: landmarks[454].y * height },
        leftEye: { x: landmarks[33].x * width, y: landmarks[33].y * height },
        rightEye: { x: landmarks[263].x * width, y: landmarks[263].y * height },
        nose: { x: landmarks[1].x * width, y: landmarks[1].y * height },
        forehead: { x: landmarks[10].x * width, y: landmarks[10].y * height },
    };
}

function punch(ctx, draw) {
    ctx.save();
    ctx.filter = "blur(10px)";
    ctx.globalCompositeOperation = "destination-out";
    ctx.fillStyle = "#000";
    draw(ctx);
    ctx.restore();
}

function drawCapMask(ctx, f, width, height) {
    punch(ctx, function (c) {
        const top = Math.max(0, f.browY - f.h * 1.05);
        const bottom = f.browY + f.h * 0.04;
        const rx = f.w * 0.92;
        c.beginPath();
        c.ellipse(f.cx, (top + bottom) / 2, rx, Math.max(f.h * 0.55, (bottom - top) / 2), 0, 0, Math.PI * 2);
        c.rect(f.cx - rx, top, rx * 2, Math.max(8, bottom - top));
        c.fill();
    });
}

function drawSpectaclesMask(ctx, f) {
    punch(ctx, function (c) {
        const top = f.browY - f.h * 0.06;
        const bottom = f.nose.y + f.h * 0.02;
        const left = f.leftEar.x - f.w * 0.08;
        const right = f.rightEar.x + f.w * 0.08;
        const h = Math.max(16, bottom - top);
        const w = Math.max(16, right - left);
        const r = h * 0.45;
        c.beginPath();
        if (c.roundRect) {
            c.roundRect(left, top, w, h, r);
        } else {
            c.rect(left, top, w, h);
        }
        c.fill();
    });
}

function drawJewelleryMask(ctx, f) {
    punch(ctx, function (c) {
        const earR = f.w * 0.16;
        c.beginPath();
        c.arc(f.leftEar.x, f.leftEar.y, earR, 0, Math.PI * 2);
        c.arc(f.rightEar.x, f.rightEar.y, earR, 0, Math.PI * 2);
        c.fill();
        c.beginPath();
        c.ellipse(f.chin.x, f.chin.y + f.h * 0.22, f.w * 0.62, f.h * 0.38, 0, 0, Math.PI * 2);
        c.fill();
        c.beginPath();
        c.ellipse(f.forehead.x, f.forehead.y, f.w * 0.18, f.h * 0.12, 0, 0, Math.PI * 2);
        c.fill();
    });
}

function drawApparelMask(ctx, f, width, height) {
    punch(ctx, function (c) {
        const top = f.chin.y - f.h * 0.12;
        c.fillRect(0, top, width, Math.max(8, height - top));
    });
}

export async function createCategoryMask(userFile, category) {
    const img = await loadImage(userFile);
    const landmarker = await getLandmarker();
    const result = landmarker.detect(img);
    if (!result.faceLandmarks || !result.faceLandmarks[0] || result.faceLandmarks[0].length < 400) {
        throw new Error("No face found. Use a clear photo of the person facing the camera.");
    }

    const width = img.naturalWidth || img.width;
    const height = img.naturalHeight || img.height;
    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext("2d");
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width, height);

    const face = faceStats(result.faceLandmarks[0], width, height);
    if (category === "cap") {
        drawCapMask(ctx, face, width, height);
    } else if (category === "spectacles") {
        drawSpectaclesMask(ctx, face);
    } else if (category === "jewellery") {
        drawJewelleryMask(ctx, face);
    } else {
        drawApparelMask(ctx, face, width, height);
    }

    const blob = await new Promise(function (resolve) {
        canvas.toBlob(resolve, "image/png");
    });
    if (!blob) {
        throw new Error("Could not build the face mask.");
    }
    return {
        blob: blob,
        previewUrl: canvas.toDataURL("image/png"),
    };
}
