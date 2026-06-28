<div wire:key="webcam-{{ md5($statePath) }}">

    <div style="border:2px dashed #C9A24B;border-radius:10px;padding:14px;background:#FBF8F0;margin-bottom:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <strong style="color:#0B3D3C;font-size:13px;">📷 Webcam Photos</strong>
            <button type="button"
                onclick="document.getElementById('jt-modal-{{ md5($statePath) }}').style.display = 'flex'; window.jtStartCamera_{{ md5($statePath) }}();"
                style="background:#0B3D3C;color:#fff;border:1px solid #C9A24B;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;">
                Open Camera
            </button>
        </div>
        <div style="display:flex;flex-wrap:wrap;">
            @forelse ($photos as $index => $path)
                <div style="position:relative;display:inline-block;margin:4px;">
                    <img src="{{ route('tenant.storage', ['path' => ltrim($path, '/')]) }}"
                         style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:2px solid #0B3D3C;">
                    <button type="button" wire:click="removePhoto({{ $index }})" wire:loading.attr="disabled"
                        style="position:absolute;top:-6px;right:-6px;background:#B8463F;color:white;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:1;">
                        &times;
                    </button>
                </div>
            @empty
                <div style="color:#9ca3af;font-size:12px;font-style:italic;">No webcam photos captured yet.</div>
            @endforelse
        </div>
    </div>

    <div id="jt-modal-{{ md5($statePath) }}" style="position:fixed;inset:0;background:rgba(11,61,60,0.85);z-index:9999;align-items:center;justify-content:center;display:none;">
        <div style="background:#fff;border-radius:14px;padding:18px;max-width:640px;width:95%;box-shadow:0 20px 60px rgba(0,0,0,0.4);max-height:92vh;overflow-y:auto;">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="color:#0B3D3C;font-family:'Fraunces',serif;" id="jt-modal-title-{{ md5($statePath) }}">Capture Item Photo</strong>
                <button type="button" onclick="window.jtCloseModal_{{ md5($statePath) }}();" style="border:none;background:none;font-size:24px;cursor:pointer;color:#0B3D3C;">&times;</button>
            </div>

            <!-- ── STEP 1: LIVE CAMERA ── -->
            <div id="jt-step-live-{{ md5($statePath) }}">
                <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center;">
                    <label style="font-size:11px;font-weight:700;color:#0B3D3C;">Aspect:</label>
                    <select id="jt-aspect-{{ md5($statePath) }}"
                        onchange="window.jtSetAspect_{{ md5($statePath) }}(this.value)"
                        style="font-size:12px;padding:4px 8px;border-radius:6px;border:1px solid #C9A24B;background:#fff;color:#0B3D3C;font-weight:600;">
                        <option value="free">Free</option>
                        <option value="1:1">1:1 Square</option>
                        <option value="4:5" selected>4:5 Portrait</option>
                        <option value="4:3">4:3 Standard</option>
                        <option value="16:9">16:9 Wide</option>
                    </select>

                    <button type="button" id="jt-switch-btn-{{ md5($statePath) }}"
                        onclick="window.jtSwitchCamera_{{ md5($statePath) }}()"
                        style="font-size:12px;padding:5px 10px;border-radius:6px;border:1px solid #0B3D3C;background:#fff;color:#0B3D3C;font-weight:600;cursor:pointer;display:none;">
                        🔄 Switch Camera
                    </button>

                    <button type="button" id="jt-torch-btn-{{ md5($statePath) }}"
                        onclick="window.jtToggleTorch_{{ md5($statePath) }}()"
                        style="font-size:12px;padding:5px 10px;border-radius:6px;border:1px solid #0B3D3C;background:#fff;color:#0B3D3C;font-weight:600;cursor:pointer;display:none;">
                        🔦 Flash: Off
                    </button>
                </div>

                <div id="jt-frame-{{ md5($statePath) }}" style="position:relative;background:#000;border-radius:8px;overflow:hidden;width:100%;aspect-ratio:4/5;display:flex;align-items:center;justify-content:center;">
                    <div id="jt-loading-{{ md5($statePath) }}" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:white;font-size:14px;z-index:2;">
                        Starting camera...
                    </div>
                    <video id="jt-video-{{ md5($statePath) }}" autoplay playsinline muted
                        style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);height:100%;width:auto;min-width:100%;object-fit:cover;display:block;"></video>
                </div>

                <div id="jt-error-{{ md5($statePath) }}" style="display:none;background:#FEE2E2;border:1px solid #F87171;color:#B91C1C;padding:10px;border-radius:6px;font-size:12px;margin-top:12px;font-weight:600;">
                    <span id="jt-error-text-{{ md5($statePath) }}"></span>
                    <div style="margin-top:4px;font-weight:normal;">If you are testing locally, ensure you are using 'localhost' or an HTTPS connection.</div>
                </div>

                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="button" id="jt-capture-btn-{{ md5($statePath) }}"
                        onclick="window.jtCapturePhoto_{{ md5($statePath) }}()"
                        style="flex:1;background:#C9A24B;color:#0B3D3C;border:none;border-radius:8px;padding:10px;font-weight:800;cursor:pointer;opacity:0.5;"
                        disabled>
                        📸 Capture Photo
                    </button>
                    <button type="button" onclick="window.jtCloseModal_{{ md5($statePath) }}();"
                        style="background:#f1f5f9;color:#374151;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:600;">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- ── STEP 2: PREVIEW + CHOOSE SCAN OR PHOTO ── -->
            <div id="jt-step-preview-{{ md5($statePath) }}" style="display:none;text-align:center;">
                <img id="jt-preview-img-{{ md5($statePath) }}" style="max-width:100%;max-height:340px;border-radius:8px;border:2px solid #C9A24B;margin:0 auto;display:block;">

                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="button" id="jt-save-btn-{{ md5($statePath) }}"
                        onclick="window.jtSaveCapturedPhoto_{{ md5($statePath) }}()"
                        style="flex:1;background:#0F7A5C;color:#fff;border:none;border-radius:8px;padding:10px;font-weight:800;cursor:pointer;">
                        ✅ Use This Photo
                    </button>
                    <button type="button"
                        onclick="window.jtOpenScanMode_{{ md5($statePath) }}()"
                        style="flex:1;background:#0B3D3C;color:#fff;border:1px solid #C9A24B;border-radius:8px;padding:10px;font-weight:800;cursor:pointer;">
                        📄 Scan as Document
                    </button>
                </div>
                <div style="margin-top:8px;">
                    <button type="button" onclick="window.jtRetakePhoto_{{ md5($statePath) }}()"
                        style="background:none;border:none;color:#6b7280;font-size:12px;font-weight:600;cursor:pointer;text-decoration:underline;">
                        ↺ Retake Photo
                    </button>
                </div>
            </div>

            <!-- ── STEP 3: SCAN MODE — drag corners ── -->
            <div id="jt-step-scan-{{ md5($statePath) }}" style="display:none;">
                <div style="font-size:11px;color:#6b7280;margin-bottom:8px;">Drag the four corner dots to outline the document edges.</div>
                <div id="jt-scan-frame-{{ md5($statePath) }}" style="position:relative;width:100%;background:#111;border-radius:8px;overflow:hidden;touch-action:none;">
                    <img id="jt-scan-img-{{ md5($statePath) }}" style="width:100%;display:block;user-select:none;-webkit-user-drag:none;">
                    <svg id="jt-scan-overlay-{{ md5($statePath) }}" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;">
                        <polygon id="jt-scan-poly-{{ md5($statePath) }}" points="" fill="rgba(201,162,75,0.25)" stroke="#C9A24B" stroke-width="3"></polygon>
                    </svg>
                    <div class="jt-handle" data-corner="tl" style="position:absolute;width:26px;height:26px;background:#C9A24B;border:3px solid #fff;border-radius:50%;cursor:grab;transform:translate(-50%,-50%);box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>
                    <div class="jt-handle" data-corner="tr" style="position:absolute;width:26px;height:26px;background:#C9A24B;border:3px solid #fff;border-radius:50%;cursor:grab;transform:translate(-50%,-50%);box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>
                    <div class="jt-handle" data-corner="br" style="position:absolute;width:26px;height:26px;background:#C9A24B;border:3px solid #fff;border-radius:50%;cursor:grab;transform:translate(-50%,-50%);box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>
                    <div class="jt-handle" data-corner="bl" style="position:absolute;width:26px;height:26px;background:#C9A24B;border:3px solid #fff;border-radius:50%;cursor:grab;transform:translate(-50%,-50%);box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>
                </div>

                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="button" onclick="window.jtApplyScanWarp_{{ md5($statePath) }}()"
                        style="flex:1;background:#C9A24B;color:#0B3D3C;border:none;border-radius:8px;padding:10px;font-weight:800;cursor:pointer;">
                        ➡ Flatten Document
                    </button>
                    <button type="button" onclick="window.jtCancelScanMode_{{ md5($statePath) }}()"
                        style="background:#f1f5f9;color:#374151;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:600;">
                        Back
                    </button>
                </div>
            </div>

            <!-- ── STEP 4: SCAN RESULT + ENHANCEMENT FILTERS ── -->
            <div id="jt-step-result-{{ md5($statePath) }}" style="display:none;text-align:center;">
                <img id="jt-result-img-{{ md5($statePath) }}" style="max-width:100%;max-height:340px;border-radius:8px;border:2px solid #0F7A5C;margin:0 auto;display:block;background:#fff;">

                <div style="display:flex;gap:6px;margin-top:10px;justify-content:center;flex-wrap:wrap;">
                    <button type="button" onclick="window.jtSetFilter_{{ md5($statePath) }}('original')" class="jt-filter-btn" data-filter="original"
                        style="font-size:12px;padding:6px 12px;border-radius:6px;border:1.5px solid #0B3D3C;background:#0B3D3C;color:#fff;font-weight:700;cursor:pointer;">
                        Original
                    </button>
                    <button type="button" onclick="window.jtSetFilter_{{ md5($statePath) }}('enhanced')" class="jt-filter-btn" data-filter="enhanced"
                        style="font-size:12px;padding:6px 12px;border-radius:6px;border:1.5px solid #0B3D3C;background:#fff;color:#0B3D3C;font-weight:700;cursor:pointer;">
                        Enhanced
                    </button>
                    <button type="button" onclick="window.jtSetFilter_{{ md5($statePath) }}('bw')" class="jt-filter-btn" data-filter="bw"
                        style="font-size:12px;padding:6px 12px;border-radius:6px;border:1.5px solid #0B3D3C;background:#fff;color:#0B3D3C;font-weight:700;cursor:pointer;">
                        Black &amp; White
                    </button>
                </div>

                <div style="display:flex;gap:8px;margin-top:14px;">
                    <button type="button" id="jt-save-scan-btn-{{ md5($statePath) }}"
                        onclick="window.jtSaveScannedDocument_{{ md5($statePath) }}()"
                        style="flex:1;background:#0F7A5C;color:#fff;border:none;border-radius:8px;padding:10px;font-weight:800;cursor:pointer;">
                        ✅ Save Scanned Document
                    </button>
                    <button type="button" onclick="window.jtBackToScanMode_{{ md5($statePath) }}()"
                        style="background:#f1f5f9;color:#374151;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:600;">
                        ↺ Adjust Corners
                    </button>
                </div>
            </div>

            <canvas id="jt-canvas-{{ md5($statePath) }}" style="display:none;"></canvas>
            <canvas id="jt-warp-canvas-{{ md5($statePath) }}" style="display:none;"></canvas>
        </div>
    </div>

    <script>
        (function() {
            const hash = '{{ md5($statePath) }}';
            let stream = null;
            let currentFacingMode = 'environment';
            let currentAspect = '4:5';
            let torchOn = false;
            let lastCapturedDataUrl = null;
            let scanResultDataUrl = null;
            let currentFilter = 'original';
            let corners = { tl: {x:0.1,y:0.1}, tr: {x:0.9,y:0.1}, br: {x:0.9,y:0.9}, bl: {x:0.1,y:0.9} };
            let draggingCorner = null;

            const aspectRatioMap = { 'free': null, '1:1': 1, '4:5': 4/5, '4:3': 4/3, '16:9': 16/9 };

            const els = (suffix) => document.getElementById('jt-' + suffix + '-' + hash);

            function showStep(stepName) {
                ['live', 'preview', 'scan', 'result'].forEach(s => {
                    els('step-' + s).style.display = (s === stepName) ? 'block' : 'none';
                });
                els('modal-title').textContent = stepName === 'scan' || stepName === 'result' ? 'Scan Document' : 'Capture Item Photo';
            }

            function applyAspectToFrame() {
                const frame = els('frame');
                const ratio = aspectRatioMap[currentAspect];
                frame.style.aspectRatio = ratio ? (currentAspect === '1:1' ? '1/1' : currentAspect.replace(':', '/')) : 'auto';
            }

            window['jtSetAspect_' + hash] = function(value) { currentAspect = value; applyAspectToFrame(); };

            async function detectCameraCount() {
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const count = devices.filter(d => d.kind === 'videoinput').length;
                    els('switch-btn').style.display = count > 1 ? 'inline-block' : 'none';
                } catch (e) {}
            }

            window['jtStartCamera_' + hash] = function() {
                showStep('live');
                const video = els('video');
                els('error').style.display = 'none';
                els('loading').style.display = 'flex';
                els('capture-btn').disabled = true;
                els('capture-btn').style.opacity = '0.5';
                applyAspectToFrame();

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    document.getElementById('jt-error-text-' + hash).textContent = 'Webcam API not supported in this browser (HTTPS required).';
                    els('error').style.display = 'block';
                    els('loading').style.display = 'none';
                    return;
                }

                navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacingMode }, audio: false })
                    .then(s => {
                        stream = s;
                        video.srcObject = s;
                        els('loading').style.display = 'none';
                        els('capture-btn').disabled = false;
                        els('capture-btn').style.opacity = '1';
                        detectCameraCount();
                        setupTorchSupport();
                    })
                    .catch(e => {
                        document.getElementById('jt-error-text-' + hash).textContent = 'Camera Access Denied: ' + e.message;
                        els('error').style.display = 'block';
                        els('loading').style.display = 'none';
                    });
            };

            window['jtSwitchCamera_' + hash] = function() {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                window['jtStopCamera_' + hash]();
                window['jtStartCamera_' + hash]();
            };

            function setupTorchSupport() {
                if (!stream) { els('torch-btn').style.display = 'none'; return; }
                const track = stream.getVideoTracks()[0];
                const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                if (capabilities.torch) {
                    els('torch-btn').style.display = 'inline-block';
                    torchOn = false;
                    els('torch-btn').textContent = '🔦 Flash: Off';
                } else {
                    els('torch-btn').style.display = 'none';
                }
            }

            window['jtToggleTorch_' + hash] = function() {
                if (!stream) return;
                const track = stream.getVideoTracks()[0];
                torchOn = !torchOn;
                track.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(() => { torchOn = !torchOn; });
                els('torch-btn').textContent = torchOn ? '🔦 Flash: On' : '🔦 Flash: Off';
            };

            window['jtStopCamera_' + hash] = function() {
                if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
                torchOn = false;
            };

            window['jtCloseModal_' + hash] = function() {
                window['jtStopCamera_' + hash]();
                document.getElementById('jt-modal-' + hash).style.display = 'none';
                showStep('live');
            };

            window['jtCapturePhoto_' + hash] = function() {
                if (!stream) return;
                const video = els('video');
                const canvas = els('canvas');
                const ratio = aspectRatioMap[currentAspect];
                const vw = video.videoWidth, vh = video.videoHeight;
                let cropW = vw, cropH = vh, cropX = 0, cropY = 0;

                if (ratio) {
                    const currentRatio = vw / vh;
                    if (currentRatio > ratio) { cropW = vh * ratio; cropX = (vw - cropW) / 2; }
                    else { cropH = vw / ratio; cropY = (vh - cropH) / 2; }
                }

                canvas.width = cropW; canvas.height = cropH;
                canvas.getContext('2d').drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);
                lastCapturedDataUrl = canvas.toDataURL('image/jpeg', 0.92);

                els('preview-img').src = lastCapturedDataUrl;
                window['jtStopCamera_' + hash]();
                showStep('preview');
            };

            window['jtRetakePhoto_' + hash] = function() {
                lastCapturedDataUrl = null;
                window['jtStartCamera_' + hash]();
            };

            window['jtSaveCapturedPhoto_' + hash] = function() {
                if (!lastCapturedDataUrl) return;
                const btn = els('save-btn');
                btn.textContent = 'Saving...'; btn.disabled = true;
                @this.savePhoto(lastCapturedDataUrl).then(() => {
                    document.getElementById('jt-modal-' + hash).style.display = 'none';
                    btn.textContent = '✅ Use This Photo'; btn.disabled = false;
                    lastCapturedDataUrl = null;
                    showStep('live');
                });
            };

            // ── SCAN MODE ──────────────────────────────────────────────
            window['jtOpenScanMode_' + hash] = function() {
                if (!lastCapturedDataUrl) return;
                els('scan-img').src = lastCapturedDataUrl;
                corners = { tl: {x:0.08,y:0.08}, tr: {x:0.92,y:0.08}, br: {x:0.92,y:0.92}, bl: {x:0.08,y:0.92} };
                showStep('scan');
                // Wait for image to render before positioning handles
                els('scan-img').onload = renderHandles;
                if (els('scan-img').complete) renderHandles();
            };

            window['jtCancelScanMode_' + hash] = function() { showStep('preview'); };

            function renderHandles() {
                const frame = els('scan-frame');
                const rect = frame.getBoundingClientRect();
                const handles = frame.querySelectorAll('.jt-handle');
                handles.forEach(h => {
                    const c = corners[h.dataset.corner];
                    h.style.left = (c.x * rect.width) + 'px';
                    h.style.top = (c.y * rect.height) + 'px';
                });
                updatePolygon();
            }

            function updatePolygon() {
                const frame = els('scan-frame');
                const rect = frame.getBoundingClientRect();
                const pts = ['tl','tr','br','bl'].map(k => `${corners[k].x*rect.width},${corners[k].y*rect.height}`).join(' ');
                document.getElementById('jt-scan-poly-' + hash).setAttribute('points', pts);
            }

            function bindHandleDragging() {
                const frame = els('scan-frame');
                frame.querySelectorAll('.jt-handle').forEach(handle => {
                    const start = (e) => {
                        e.preventDefault();
                        draggingCorner = handle.dataset.corner;
                    };
                    handle.addEventListener('mousedown', start);
                    handle.addEventListener('touchstart', start, { passive: false });
                });

                const move = (e) => {
                    if (!draggingCorner) return;
                    const rect = frame.getBoundingClientRect();
                    const point = e.touches ? e.touches[0] : e;
                    let x = (point.clientX - rect.left) / rect.width;
                    let y = (point.clientY - rect.top) / rect.height;
                    x = Math.max(0, Math.min(1, x));
                    y = Math.max(0, Math.min(1, y));
                    corners[draggingCorner] = { x, y };
                    renderHandles();
                };
                const end = () => { draggingCorner = null; };

                document.addEventListener('mousemove', move);
                document.addEventListener('touchmove', move, { passive: false });
                document.addEventListener('mouseup', end);
                document.addEventListener('touchend', end);
            }
            bindHandleDragging();

            // Perspective warp via canvas using a simple bilinear-ish sampling
            // (projective transform solved per-pixel using the 4 source corners).
            function perspectiveWarp(img, srcCorners, outW, outH) {
                const canvas = els('warp-canvas');
                canvas.width = outW;
                canvas.height = outH;
                const ctx = canvas.getContext('2d');

                // Solve the 3x3 homography mapping unit square -> srcCorners (in pixel space)
                const s = srcCorners; // {tl,tr,br,bl} in source pixel coords
                function computeHomography(src, dst) {
                    // dst is the unit square corners (0,0)(1,0)(1,1)(0,1) scaled to outW/outH
                    const A = [];
                    const b = [];
                    const pairs = [['tl',0,0],['tr',1,0],['br',1,1],['bl',0,1]];
                    pairs.forEach(([key, ux, uy]) => {
                        const X = ux * outW, Y = uy * outH;
                        const x = src[key].x, y = src[key].y;
                        A.push([X, Y, 1, 0, 0, 0, -x*X, -x*Y]);
                        b.push(x);
                        A.push([0, 0, 0, X, Y, 1, -y*X, -y*Y]);
                        b.push(y);
                    });
                    const h = solveLinearSystem(A, b);
                    return [h[0],h[1],h[2],h[3],h[4],h[5],h[6],h[7],1];
                }

                function solveLinearSystem(A, b) {
                    const n = A.length;
                    for (let i = 0; i < n; i++) A[i].push(b[i]);
                    for (let col = 0; col < n; col++) {
                        let pivot = col;
                        for (let row = col+1; row < n; row++) if (Math.abs(A[row][col]) > Math.abs(A[pivot][col])) pivot = row;
                        [A[col], A[pivot]] = [A[pivot], A[col]];
                        for (let row = col+1; row < n; row++) {
                            const factor = A[row][col] / A[col][col];
                            for (let c = col; c <= n; c++) A[row][c] -= factor * A[col][c];
                        }
                    }
                    const x = new Array(n).fill(0);
                    for (let row = n-1; row >= 0; row--) {
                        let sum = A[row][n];
                        for (let c = row+1; c < n; c++) sum -= A[row][c] * x[c];
                        x[row] = sum / A[row][row];
                    }
                    return x;
                }

                const H = computeHomography(s);

                // Render source image to an offscreen canvas to read pixel data
                const srcCanvas = document.createElement('canvas');
                srcCanvas.width = img.naturalWidth;
                srcCanvas.height = img.naturalHeight;
                const srcCtx = srcCanvas.getContext('2d');
                srcCtx.drawImage(img, 0, 0);
                const srcData = srcCtx.getImageData(0, 0, srcCanvas.width, srcCanvas.height);
                const outImageData = ctx.createImageData(outW, outH);

                for (let Y = 0; Y < outH; Y++) {
                    for (let X = 0; X < outW; X++) {
                        const denom = H[6]*X + H[7]*Y + H[8];
                        const srcX = (H[0]*X + H[1]*Y + H[2]) / denom;
                        const srcY = (H[3]*X + H[4]*Y + H[5]) / denom;
                        const sx = Math.round(srcX), sy = Math.round(srcY);
                        const outIdx = (Y * outW + X) * 4;
                        if (sx >= 0 && sx < srcCanvas.width && sy >= 0 && sy < srcCanvas.height) {
                            const srcIdx = (sy * srcCanvas.width + sx) * 4;
                            outImageData.data[outIdx]   = srcData.data[srcIdx];
                            outImageData.data[outIdx+1] = srcData.data[srcIdx+1];
                            outImageData.data[outIdx+2] = srcData.data[srcIdx+2];
                            outImageData.data[outIdx+3] = 255;
                        } else {
                            outImageData.data[outIdx]   = 255;
                            outImageData.data[outIdx+1] = 255;
                            outImageData.data[outIdx+2] = 255;
                            outImageData.data[outIdx+3] = 255;
                        }
                    }
                }
                ctx.putImageData(outImageData, 0, 0);
                return canvas;
            }

            window['jtApplyScanWarp_' + hash] = function() {
                const frame = els('scan-frame');
                const rect = frame.getBoundingClientRect();
                const img = els('scan-img');

                // Convert corner ratios (relative to displayed frame) into source-image pixel coords
                const scaleX = img.naturalWidth / rect.width;
                const scaleY = img.naturalHeight / rect.height;
                const srcCorners = {};
                ['tl','tr','br','bl'].forEach(k => {
                    srcCorners[k] = { x: corners[k].x * rect.width * scaleX, y: corners[k].y * rect.height * scaleY };
                });

                // Output size based on average measured width/height of the quad
                const widthTop    = dist(srcCorners.tl, srcCorners.tr);
                const widthBottom = dist(srcCorners.bl, srcCorners.br);
                const heightLeft  = dist(srcCorners.tl, srcCorners.bl);
                const heightRight = dist(srcCorners.tr, srcCorners.br);
                const outW = Math.round(Math.max(widthTop, widthBottom));
                const outH = Math.round(Math.max(heightLeft, heightRight));

                const warpedCanvas = perspectiveWarp(img, srcCorners, Math.max(outW, 50), Math.max(outH, 50));
                scanResultDataUrl = warpedCanvas.toDataURL('image/jpeg', 0.95);
                currentFilter = 'original';
                applyFilterAndShow();
                showStep('result');
            };

            function dist(a, b) { return Math.sqrt((a.x-b.x)**2 + (a.y-b.y)**2); }

            window['jtBackToScanMode_' + hash] = function() { showStep('scan'); };

            // ── ENHANCEMENT FILTERS ─────────────────────────────────────
            window['jtSetFilter_' + hash] = function(filterName) {
                currentFilter = filterName;
                document.querySelectorAll('#jt-step-result-' + hash + ' .jt-filter-btn').forEach(btn => {
                    const active = btn.dataset.filter === filterName;
                    btn.style.background = active ? '#0B3D3C' : '#fff';
                    btn.style.color = active ? '#fff' : '#0B3D3C';
                });
                applyFilterAndShow();
            };

            function applyFilterAndShow() {
                if (!scanResultDataUrl) return;
                const tmpImg = new Image();
                tmpImg.onload = function() {
                    const canvas = els('warp-canvas');
                    canvas.width = tmpImg.naturalWidth;
                    canvas.height = tmpImg.naturalHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(tmpImg, 0, 0);

                    if (currentFilter !== 'original') {
                        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const d = imgData.data;
                        for (let i = 0; i < d.length; i += 4) {
                            let r = d[i], g = d[i+1], b = d[i+2];
                            if (currentFilter === 'bw') {
                                let gray = 0.299*r + 0.587*g + 0.114*b;
                                gray = (gray - 128) * 1.6 + 128 + 18; // contrast + slight brighten
                                gray = Math.max(0, Math.min(255, gray));
                                d[i] = d[i+1] = d[i+2] = gray;
                            } else if (currentFilter === 'enhanced') {
                                r = (r - 128) * 1.25 + 128 + 12;
                                g = (g - 128) * 1.25 + 128 + 12;
                                b = (b - 128) * 1.25 + 128 + 12;
                                d[i]   = Math.max(0, Math.min(255, r));
                                d[i+1] = Math.max(0, Math.min(255, g));
                                d[i+2] = Math.max(0, Math.min(255, b));
                            }
                        }
                        ctx.putImageData(imgData, 0, 0);
                    }
                    els('result-img').src = canvas.toDataURL('image/jpeg', 0.95);
                };
                tmpImg.src = scanResultDataUrl;
            }

            window['jtSaveScannedDocument_' + hash] = function() {
                const finalDataUrl = els('result-img').src;
                if (!finalDataUrl) return;
                const btn = els('save-scan-btn');
                btn.textContent = 'Saving...'; btn.disabled = true;
                @this.savePhoto(finalDataUrl).then(() => {
                    document.getElementById('jt-modal-' + hash).style.display = 'none';
                    btn.textContent = '✅ Save Scanned Document'; btn.disabled = false;
                    scanResultDataUrl = null;
                    lastCapturedDataUrl = null;
                    showStep('live');
                });
            };
        })();
    </script>
</div>