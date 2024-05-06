function createQRCode(data, size, callback) {
    var tempContainer = $('<div></div>');
    new QRCode(tempContainer[0], {
        text: data,
        width: size,
        height: size,
        colorDark: "#000000",
        colorLight: "rgba(0,0,0,0)",  // Transparent background
        correctLevel: QRCode.CorrectLevel.H
    });

    // Wait for QR code to be drawn then return the canvas
    setTimeout(() => {
        var canvas = tempContainer.find('canvas')[0];
        callback(canvas);
    }, 100);  // Ensure enough time for QR code to render
}

function createQRCodeCanvas(data, size) {
    var qrCanvas = document.createElement('canvas');
    qrCanvas.width = size;
    qrCanvas.height = size;
    new QRCode(qrCanvas, {
        text: data,
        width: size,
        height: size,
        colorDark: "#000000",
        colorLight: "rgba(0,0,0,0)",  // Transparent background
        correctLevel: QRCode.CorrectLevel.H
    });
    return qrCanvas;
}

function clearCircle(canvas, x, y, radius) {
    console.log(x, y, radius);
    var context = canvas.getContext('2d');
    context.globalCompositeOperation = 'destination-out';
    context.beginPath();
    context.arc(x, y, radius, 0, 2 * Math.PI);
    context.fill();
}

function debugCircle(canvas, x, y, radius) {
    var context = canvas.getContext('2d');
    context.save();
    context.globalCompositeOperation = 'source-over';
    context.fillStyle = 'black'; // Use a visible color
    context.beginPath();
    context.arc(x, y, radius, 0, 2 * Math.PI);
    context.fill();
    context.restore();
}

function manualClearCircle(canvas, x, y, radius) {
    var context = canvas.getContext('2d');
    var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
    var data = imageData.data;
    for (var i = 0; i < data.length; i += 4) {
        var ix = (i / 4) % canvas.width;
        var iy = Math.floor((i / 4) / canvas.width);
        var dist = Math.sqrt((ix - x) ** 2 + (iy - y) ** 2);
        if (dist < radius) {
            data[i + 3] = 0; // Set alpha to 0 (transparent)
        }
    }
    context.putImageData(imageData, 0, 0);
}


function drawLogo(canvas, logoSrc, x, y, size, callback) {
    var context = canvas.getContext('2d');
    var logo = new Image();
    logo.onload = function() {
        context.globalCompositeOperation = 'source-over';  // Draw over the existing content
        context.drawImage(logo, x - size / 2, y - size / 2, size, size);
        callback(canvas);
    };
    logo.src = logoSrc;
}

function compositeImages(baseCanvas, overlayCanvas, callback) {
    var context = baseCanvas.getContext('2d');
    context.drawImage(overlayCanvas, 0, 0, baseCanvas.width, baseCanvas.height);
    callback(baseCanvas);
}
function GenerateQRCodeImageData(url, callback) {
    var canvasWidth = 256;  // Width of the parchment canvas
    var canvasHeight = 256;  // Height of the parchment canvas
    var qrCodeScale = 0.8;  // Scale of QR code relative to canvas size; adjust this for different sizes
    var qrCodeSize = Math.min(canvasWidth, canvasHeight) * qrCodeScale;  // Calculated QR code size
    var center = canvasWidth / 2;
    var logoSize = 50;  // Diameter of the logo
    console.log("Starting QR code generation");
    // Create QR code
    createQRCode(url, qrCodeSize, function(qrCanvas) {
        console.log("QR code generated");
        // Clear a circle for the logo
        console.log(qrCanvas.width, qrCanvas.height);
        //debugCircle(qrCanvas, qrCodeSize / 2, qrCodeSize / 2, logoSize / 2);
        //clearCircle(qrCanvas, qrCodeSize / 2, qrCodeSize / 2, logoSize / 2);
        manualClearCircle(qrCanvas, qrCodeSize / 2, qrCodeSize / 2, logoSize / 2);
        console.log("Circle cleared");


        // Draw the logo
        drawLogo(qrCanvas, "/assets/media/logo3.png", qrCodeSize / 2, qrCodeSize / 2, logoSize, function(logoCanvas) {
            console.log("Logo drawn");
            // Create a parchment background canvas
            var parchmentCanvas = document.createElement('canvas');
            parchmentCanvas.width = canvasWidth;
            parchmentCanvas.height = canvasHeight;
            var parchmentCtx = parchmentCanvas.getContext('2d');

            // Load the parchment background
            var parchmentImg = new Image();
            parchmentImg.onload = function() {
                parchmentCtx.drawImage(parchmentImg, 0, 0, canvasWidth, canvasHeight);
                
                // Composite the QR code with logo over the parchment
                // Position the QR code in the center of the parchment
                var offset = (canvasWidth - qrCodeSize) / 2;  // Calculate the offset to center the QR code
                parchmentCtx.drawImage(logoCanvas, offset, offset, qrCodeSize, qrCodeSize);
                
                // Convert canvas to data URL and pass to callback
                var imageData = parchmentCanvas.toDataURL("image/png");
                callback(imageData);
            };
            parchmentImg.src = "/assets/media/parchment.png";
            parchmentImg.onerror = function() {
                console.error("Failed to load parchment image.");
                callback(null);
            };
        });
    });
}
