# WebRTC Screen and Camera Sharing in PalMA

PalMA supports in-browser screen sharing, window sharing, and camera/webcam sharing
via the **WebRTC** sub-tab in the *Add* section — no third-party software installation
is required.

---

## How It Works

1. The user opens the **WebRTC** sub-tab in the PalMA interface and clicks one of:
   - **Share Screen / Window** — captures the whole display or a single application
     window / browser tab using the browser's `getDisplayMedia()` API.
   - **Share Camera** — captures the device camera / webcam using `getUserMedia()`.

2. A live preview is shown in the browser.

3. JavaScript (in `webrtc.js`) captures frames from the stream on a hidden `<canvas>`
   element and POSTs them as JPEG images to `webrtc_receiver.php` at approximately
   10 frames per second.

4. `webrtc_receiver.php` validates the session, checks that the data is a valid JPEG,
   and writes it atomically to a temporary file:
   `/tmp/palma_webrtc_<session_id>.jpg`

5. PalMA opens `webrtc_display.php?sid=<session_id>` on the monitor display via
   `palma-browser`. This PHP script streams the most recent JPEG frame in a
   `multipart/x-mixed-replace` (MJPEG) HTTP response, providing a live display at
   up to 10 fps.

6. When the user clicks **Stop Sharing** (or closes the browser's built-in sharing
   dialog), the stream is terminated on both ends.

---

## Requirements

### HTTPS (mandatory)

The `getDisplayMedia()` and `getUserMedia()` APIs are **only available in secure
contexts** — that is, pages served over `https://` or from `localhost`.

> **If PalMA is served over plain HTTP, the WebRTC buttons will show an error
> message and sharing will not be possible.**

To enable HTTPS, you can use:

- **Let's Encrypt** (recommended for internet-accessible servers):
  ```
  sudo apt install certbot python3-certbot-apache   # or nginx
  sudo certbot --apache -d your.palma.hostname
  ```

- **Self-signed certificate** (for internal/LAN use):
  ```bash
  sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout /etc/ssl/private/palma-selfsigned.key \
      -out /etc/ssl/certs/palma-selfsigned.crt \
      -subj "/CN=your.palma.hostname"
  ```
  Then configure Apache / nginx to use the certificate and update
  `start_url` in `palma.ini` to `https://your.palma.hostname/`.

  Note: Users will see a browser warning about the self-signed certificate.
  They must accept it before WebRTC features will work.

### Browser Support

| Browser | Screen/Window sharing | Camera sharing |
|---|---|---|
| Chrome / Chromium 72+ | ✅ | ✅ |
| Firefox 66+ | ✅ | ✅ |
| Edge 79+ (Chromium-based) | ✅ | ✅ |
| Safari 13+ (macOS / iOS) | ✅ (screen: macOS 13+) | ✅ |
| Android (Chrome) | ❌ (screen share not available) | ✅ |
| iOS (Safari) | ❌ | ✅ |

> Screen sharing on Android and iOS is generally not supported by the browser
> APIs — camera sharing works fine.

---

## Configuration

No additional configuration is needed beyond enabling HTTPS. PalMA automatically
constructs `webrtc_receiver.php` and `webrtc_display.php` URLs from the
`start_url` value in `palma.ini`.

---

## Files

| File | Purpose |
|---|---|
| `webrtc.js` | Client-side capture: `getDisplayMedia()` / `getUserMedia()`, canvas frame capture, JPEG POST |
| `webrtc_receiver.php` | Server-side: validates session, stores latest JPEG frame in `/tmp` |
| `webrtc_display.php` | Server-side: streams latest JPEG frames as MJPEG to the monitor browser |
| `index.php` | Modified to include the *WebRTC* sub-tab in the *Add* section |
| `palma.css` | Modified to style the WebRTC UI elements |

---

## Frame Streaming Mechanism

The implementation uses a **canvas-based JPEG polling** approach rather than a direct
WebRTC peer connection:

```
User browser                 PalMA server             PalMA monitor (palma-browser)
────────────                 ────────────             ─────────────────────────────
getDisplayMedia()
  │
  ▼
<video> element ──► <canvas>.drawImage() ──► toBlob('image/jpeg')
                                                │
                                          fetch POST ──► webrtc_receiver.php
                                                             │
                                                         writes JPEG to /tmp
                                                             │
                                              webrtc_display.php ◄── HTTP GET
                                                   │
                                            multipart/x-mixed-replace
                                                   │
                                            (MJPEG stream) ──────────────► <img>
```

This approach:
- Requires **no WebRTC signalling server**
- Works entirely with standard HTTP(S) requests
- Introduces some latency (100–300 ms typical) compared to native VNC

---

## Limitations vs. VNC

| Feature | VNC | WebRTC (this implementation) |
|---|---|---|
| Software install | Required | None (browser only) |
| Protocol | TCP/VNC | HTTP(S) frame upload |
| Frame rate | Up to 30 fps | ~10 fps (configurable) |
| Latency | Low (50–100 ms) | Medium (100–300 ms) |
| Audio | Depends on client | Not supported |
| Mobile | Limited | Camera sharing works |
| HTTPS requirement | No | Yes |
| Individual window share | No | Yes (Chrome/Edge) |

---

## Troubleshooting

**"WebRTC requires HTTPS" error**
: The PalMA server must be accessed via `https://`. See the HTTPS setup section above.

**"Permission denied" error**
: The user must grant the browser permission to capture the screen or camera.
  Check that the browser has the necessary OS-level permissions (especially on macOS/iOS).

**Blank or frozen display on the monitor**
: Ensure the PalMA server is reachable from the monitor. Verify that `/tmp` is writable
  by the web server user (`www-data`).

**Screen sharing button is missing**
: Some browsers (e.g., Firefox on Linux with Wayland and no `xdg-desktop-portal`)
  may not support `getDisplayMedia()`. Use Chrome/Chromium in this case.

**High CPU usage**
: Frame capture at 10 fps with JPEG compression uses moderate CPU. Reduce the frame
  rate by modifying `FRAME_RATE` in `webrtc.js` if needed.

---

## Cleanup

Temporary JPEG files are stored in `/tmp/palma_webrtc_<session_id>.jpg`.
The operating system typically clears `/tmp` on reboot. For long-running servers,
add a cron job to remove stale files:

```bash
# Remove WebRTC temp files older than 2 hours
0 * * * * find /tmp -name 'palma_webrtc_*.jpg' -mmin +120 -delete
```

---

## Security Notes

- `webrtc_receiver.php` includes `auth.php`, so only authenticated PalMA users can
  upload frames.
- Each session's frames are stored in a file named with the PHP session ID, preventing
  one user from overwriting another user's stream.
- The JPEG data is validated (magic bytes `FF D8`) before being written to disk.
- `webrtc_display.php` sanitises the `sid` URL parameter (allows only alphanumeric
  characters) to prevent path traversal.
