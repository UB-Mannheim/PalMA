// Copyright (C) 2014-2024 Universitätsbibliothek Mannheim
// See file LICENSE for license details.
//
// WebRTC screen, window and camera sharing for PalMA.
// Captures frames from a MediaStream and POSTs them as JPEG to webrtc_receiver.php.

(function () {
    'use strict';

    var captureInterval = null;
    var currentStream = null;
    var FRAME_RATE = 10; // frames per second

    function getElements() {
        return {
            preview:    document.getElementById('webrtc-preview'),
            status:     document.getElementById('webrtc-status'),
            stopBtn:    document.getElementById('webrtc-stop'),
            screenBtn:  document.getElementById('webrtc-screen-btn'),
            cameraBtn:  document.getElementById('webrtc-camera-btn')
        };
    }

    function setStatus(msg) {
        var el = document.getElementById('webrtc-status');
        if (el) {
            el.textContent = msg;
        }
    }

    function showStop(show) {
        var el = document.getElementById('webrtc-stop');
        if (el) {
            el.style.display = show ? 'inline-block' : 'none';
        }
    }

    function showPreview(show) {
        var el = document.getElementById('webrtc-preview');
        if (el) {
            el.style.display = show ? 'block' : 'none';
        }
    }

    function sendFrame(canvas, receiverUrl) {
        canvas.toBlob(function (blob) {
            if (!blob) {
                return;
            }
            fetch(receiverUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'image/jpeg' },
                body: blob
            }).catch(function (err) {
                console.error('WebRTC frame send error:', err);
            });
        }, 'image/jpeg', 0.85);
    }

    function startCapture(stream, receiverUrl, displayUrl) {
        currentStream = stream;
        var el = getElements();

        // Show live preview.
        if (el.preview) {
            el.preview.srcObject = stream;
            el.preview.play();
        }
        showPreview(true);
        showStop(true);
        setStatus(window.webrtcStrings && window.webrtcStrings.sharing
            ? window.webrtcStrings.sharing
            : 'Sharing…');

        // Create an off-screen canvas for frame capture.
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var video = document.createElement('video');
        video.srcObject = stream;
        video.muted = true;
        video.play();

        video.addEventListener('loadedmetadata', function () {
            canvas.width  = video.videoWidth  || 1280;
            canvas.height = video.videoHeight || 720;
        });

        captureInterval = setInterval(function () {
            if (!currentStream) {
                return;
            }
            if (video.readyState >= video.HAVE_CURRENT_DATA) {
                if (canvas.width === 0 || canvas.height === 0) {
                    canvas.width  = video.videoWidth  || 1280;
                    canvas.height = video.videoHeight || 720;
                }
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                sendFrame(canvas, receiverUrl);
            }
        }, 1000 / FRAME_RATE);

        // Listen for the user stopping the stream via the browser's built-in
        // stop button (e.g. the "Stop sharing" button in Chrome).
        stream.getTracks().forEach(function (track) {
            track.addEventListener('ended', function () {
                stopSharing();
            });
        });

        // Tell the PalMA monitor to open the MJPEG display URL.
        if (typeof sendToNuc === 'function' && displayUrl) {
            sendToNuc('openURL=' + encodeURIComponent(displayUrl));
        }
    }

    function stopSharing() {
        clearInterval(captureInterval);
        captureInterval = null;

        if (currentStream) {
            currentStream.getTracks().forEach(function (track) { track.stop(); });
            currentStream = null;
        }

        var el = getElements();
        if (el.preview) {
            el.preview.srcObject = null;
        }
        showPreview(false);
        showStop(false);
        setStatus(window.webrtcStrings && window.webrtcStrings.stopped
            ? window.webrtcStrings.stopped
            : 'Sharing stopped.');

        // Tell the PalMA monitor to close the WebRTC window.
        if (typeof sendToNuc === 'function' && window.webrtcCloseUrl) {
            sendToNuc('openURL=' + encodeURIComponent(window.webrtcCloseUrl));
        }
    }

    function checkSecureContext() {
        if (typeof window !== 'undefined' && window.isSecureContext === false) {
            setStatus(window.webrtcStrings && window.webrtcStrings.httpsRequired
                ? window.webrtcStrings.httpsRequired
                : 'WebRTC requires HTTPS. Please use a secure connection.');
            return false;
        }
        return true;
    }

    function checkSupport() {
        if (!navigator.mediaDevices) {
            setStatus(window.webrtcStrings && window.webrtcStrings.notSupported
                ? window.webrtcStrings.notSupported
                : 'WebRTC is not supported by your browser. Please try Chrome 72+, Firefox 66+, or Safari 13+.');
            return false;
        }
        return true;
    }

    window.startScreenShare = function (receiverUrl, displayUrl) {
        if (!checkSecureContext() || !checkSupport()) {
            return;
        }
        if (!navigator.mediaDevices.getDisplayMedia) {
            setStatus(window.webrtcStrings && window.webrtcStrings.screenNotSupported
                ? window.webrtcStrings.screenNotSupported
                : 'Screen sharing is not supported by your browser.');
            return;
        }
        navigator.mediaDevices.getDisplayMedia({ video: true, audio: false })
            .then(function (stream) {
                startCapture(stream, receiverUrl, displayUrl);
            })
            .catch(function (err) {
                if (err.name === 'NotAllowedError') {
                    setStatus(window.webrtcStrings && window.webrtcStrings.denied
                        ? window.webrtcStrings.denied
                        : 'Permission denied.');
                } else {
                    setStatus((window.webrtcStrings && window.webrtcStrings.error
                        ? window.webrtcStrings.error
                        : 'Error:') + ' ' + err.message);
                }
            });
    };

    window.startCameraShare = function (receiverUrl, displayUrl) {
        if (!checkSecureContext() || !checkSupport()) {
            return;
        }
        if (!navigator.mediaDevices.getUserMedia) {
            setStatus(window.webrtcStrings && window.webrtcStrings.cameraNotSupported
                ? window.webrtcStrings.cameraNotSupported
                : 'Camera sharing is not supported by your browser.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(function (stream) {
                startCapture(stream, receiverUrl, displayUrl);
            })
            .catch(function (err) {
                if (err.name === 'NotAllowedError') {
                    setStatus(window.webrtcStrings && window.webrtcStrings.denied
                        ? window.webrtcStrings.denied
                        : 'Permission denied.');
                } else {
                    setStatus((window.webrtcStrings && window.webrtcStrings.error
                        ? window.webrtcStrings.error
                        : 'Error:') + ' ' + err.message);
                }
            });
    };

    window.stopWebRTCSharing = function () {
        stopSharing();
    };
}());
