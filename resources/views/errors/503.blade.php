<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance</title>
    <style>
        :root{
            --bg0:#070a17;
            --bg1:#0b1430;
            --card:rgba(255,255,255,.06);
            --border:rgba(255,255,255,.12);
            --text:#e5e7eb;
            --muted:#94a3b8;
            --primary:#3b82f6;
            --accent:#22c55e;
            --warn:#f59e0b;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
            color:var(--text);
            background:linear-gradient(120deg,var(--bg0),var(--bg1));
            overflow:hidden;
        }

        /* Animated background */
        .bg{
            position:fixed;
            inset:-40%;
            background:
                radial-gradient(900px 600px at 15% 15%, rgba(59,130,246,.35), transparent 60%),
                radial-gradient(900px 600px at 85% 35%, rgba(34,197,94,.22), transparent 60%),
                radial-gradient(900px 600px at 45% 85%, rgba(245,158,11,.18), transparent 60%);
            filter:blur(18px) saturate(110%);
            animation:bgMove 16s ease-in-out infinite alternate;
            transform:translate3d(0,0,0);
        }
        @keyframes bgMove{
            from{transform:translate(-2%,-2%) scale(1.02)}
            to{transform:translate(2%,1%) scale(1.06)}
        }

        /* Floating shapes */
        .shape{position:fixed; border-radius:999px; opacity:.55; filter:blur(.2px)}
        .s1{width:220px;height:220px; left:8%; top:18%;
            background:linear-gradient(135deg,rgba(59,130,246,.65),rgba(99,102,241,.05));
            animation:float1 10s ease-in-out infinite;
        }
        .s2{width:160px;height:160px; right:12%; top:22%;
            background:linear-gradient(135deg,rgba(34,197,94,.55),rgba(34,197,94,.05));
            animation:float2 12s ease-in-out infinite;
        }
        .s3{width:260px;height:260px; right:18%; bottom:10%;
            background:linear-gradient(135deg,rgba(245,158,11,.50),rgba(245,158,11,.05));
            animation:float3 14s ease-in-out infinite;
        }
        @keyframes float1{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(18px,22px) scale(1.06)}}
        @keyframes float2{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-22px,18px) scale(1.08)}}
        @keyframes float3{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-14px,-24px) scale(1.05)}}

        /* Layout */
        .wrap{
            position:relative;
            height:100%;
            display:grid;
            place-items:center;
            padding:24px;
        }
        .card{
            width:min(980px,100%);
            background:linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
            border:1px solid var(--border);
            border-radius:22px;
            backdrop-filter:blur(14px);
            box-shadow:0 30px 80px rgba(0,0,0,.45);
            overflow:hidden;
        }
        .card-inner{padding:34px; display:grid; grid-template-columns: 1.1fr .9fr; gap:26px; align-items:center;}
        @media(max-width:860px){.card-inner{grid-template-columns:1fr}}

        /* Mobile optimizations */
        @media (max-width: 520px){
            body{padding:0}
            .wrap{padding:16px}
            .card{border-radius:18px}
            .card-inner{padding:20px; gap:16px}
            .title{font-size:22px}
            .desc{font-size:14px}
            .badge{font-size:11px; padding:7px 10px}
            .btn{width:100%}
            .btns{gap:10px}
            .panel{min-height:200px; padding:16px}
            .gear{width:50px;height:50px}
            .spinner{width:24px;height:24px}
            .progress{height:9px}
        }

        /* Slightly larger phones / small tablets */
        @media (min-width: 521px) and (max-width: 860px){
            .card-inner{padding:26px}
            .panel{min-height:220px}
        }

        .badge{
            display:inline-flex; align-items:center; gap:10px;
            padding:8px 12px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,.14);
            background:rgba(255,255,255,.05);
            color:var(--muted);
            font-size:12px;
            width:fit-content;
        }
        .dot{width:10px;height:10px;border-radius:50%; background:var(--warn); box-shadow:0 0 0 6px rgba(245,158,11,.12)}

        .title{margin:14px 0 8px; font-size:28px; line-height:1.15; letter-spacing:-.3px}
        .desc{margin:0; color:var(--muted); line-height:1.65; max-width:70ch}

        .status{
            margin-top:18px;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            align-items:center;
        }

        .btns{margin-top:22px; display:flex; gap:12px; flex-wrap:wrap}
        .btn{
            display:inline-flex; align-items:center; justify-content:center;
            gap:10px;
            padding:11px 14px;
            border-radius:14px;
            border:1px solid rgba(255,255,255,.14);
            background:rgba(255,255,255,.05);
            text-decoration:none;
            color:var(--text);
            transition:.15s ease;
        }
        .btn:hover{transform:translateY(-1px); border-color:rgba(255,255,255,.22)}
        .btn-primary{background:rgba(59,130,246,.20); border-color:rgba(59,130,246,.45)}
        .btn-primary:hover{border-color:rgba(59,130,246,.70)}

        /* Right side illustration */
        .panel{
            position:relative;
            border-radius:18px;
            border:1px solid rgba(255,255,255,.10);
            background:rgba(0,0,0,.12);
            padding:22px;
            min-height:240px;
            overflow:hidden;
        }
        .panel::before{
            content:"";
            position:absolute; inset:-60px;
            background:conic-gradient(from 180deg, rgba(59,130,246,.25), rgba(34,197,94,.20), rgba(245,158,11,.18), rgba(59,130,246,.25));
            filter:blur(14px);
            animation:spinBg 10s linear infinite;
            opacity:.9;
        }
        @keyframes spinBg{to{transform:rotate(360deg)}}

        .panel-content{position:relative; z-index:1; display:flex; flex-direction:column; gap:12px}

        .gear-row{display:flex; gap:14px; align-items:center}
        .gear{
            width:56px; height:56px; border-radius:16px;
            border:1px solid rgba(255,255,255,.14);
            background:rgba(255,255,255,.06);
            display:grid; place-items:center;
            position:relative;
            overflow:hidden;
        }
        .spinner{
            width:28px;height:28px;border-radius:50%;
            border:3px solid rgba(255,255,255,.18);
            border-top-color: rgba(255,255,255,.85);
            animation:spin .9s linear infinite;
        }
        @keyframes spin{to{transform:rotate(360deg)}}

        .progress{
            height:10px;
            border-radius:999px;
            background:rgba(255,255,255,.10);
            overflow:hidden;
            border:1px solid rgba(255,255,255,.10);
        }
        .bar{
            height:100%;
            width:42%;
            background:linear-gradient(90deg, rgba(59,130,246,.9), rgba(34,197,94,.75));
            border-radius:999px;
            animation:load 2.8s ease-in-out infinite;
        }
        @keyframes load{0%{transform:translateX(-40%); width:35%}50%{transform:translateX(25%); width:55%}100%{transform:translateX(120%); width:35%}}

        .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:12px; color:rgba(148,163,184,.95)}

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce){
            .bg,.s1,.s2,.s3,.panel::before,.spinner,.bar{animation:none!important}
        }
    </style>
</head>
<body>
    @php
        // If the app is put into maintenance mode with `php artisan down --refresh=60`,
        // Laravel includes `refresh` (seconds) in the maintenance payload.
        // Depending on Laravel version, it may be available via the exception or the maintenance file.
        // IMPORTANT: prefer the maintenance file when available, so changes (e.g. 60 -> 70) reflect immediately.
        $refreshSeconds = null;

        try {
            // 1) Prefer: read maintenance file written by `artisan down`
            $paths = [
                storage_path('framework/down'), // JSON payload
                storage_path('framework/maintenance.php'), // PHP array payload (older/custom)
            ];
            foreach ($paths as $p) {
                if (!is_string($p) || !is_file($p)) continue;

                $raw = @file_get_contents($p);
                if ($raw === false) continue;

                $json = json_decode($raw, true);
                if (is_array($json)) {
                    if (isset($json['refresh']) && is_numeric($json['refresh'])) {
                        $refreshSeconds = (int) $json['refresh'];
                        break;
                    }
                    if ($refreshSeconds === null && isset($json['retry']) && is_numeric($json['retry'])) {
                        $refreshSeconds = (int) $json['retry'];
                        break;
                    }
                }

                if ($refreshSeconds === null && str_contains($raw, 'return')) {
                    try {
                        $arr = @include $p;
                        if (is_array($arr)) {
                            if (isset($arr['refresh']) && is_numeric($arr['refresh'])) {
                                $refreshSeconds = (int) $arr['refresh'];
                                break;
                            }
                            if ($refreshSeconds === null && isset($arr['retry']) && is_numeric($arr['retry'])) {
                                $refreshSeconds = (int) $arr['retry'];
                                break;
                            }
                        }
                    } catch (Throwable $e) {
                        // ignore
                    }
                }
            }

            // 2) Fallback: try exception->data()['refresh'] if file is not readable
            if ($refreshSeconds === null && isset($exception) && is_object($exception)) {
                $data = method_exists($exception, 'data') ? $exception->data() : null;
                if (is_array($data) && isset($data['refresh']) && is_numeric($data['refresh'])) {
                    $refreshSeconds = (int) $data['refresh'];
                }

                if ($refreshSeconds === null && isset($exception->refresh) && is_numeric($exception->refresh)) {
                    $refreshSeconds = (int) $exception->refresh;
                }
            }
        } catch (Throwable $e) {
            $refreshSeconds = null;
        }

        if ($refreshSeconds !== null && $refreshSeconds < 0) {
            $refreshSeconds = null;
        }
    @endphp

    <div class="bg"></div>
    <div class="shape s1"></div>
    <div class="shape s2"></div>
    <div class="shape s3"></div>

    <div class="wrap">
        <div class="card" role="status" aria-live="polite">
            <div class="card-inner">
                <div>
                    <div class="badge"><span class="dot"></span> Maintenance Mode</div>
                    <h1 class="title">We’ll be back shortly.</h1>
                    <p class="desc">
                        We’re performing a quick upgrade to improve performance and reliability.
                        Please refresh in a few minutes.
                        @if(!empty($exception) && method_exists($exception, 'getMessage') && $exception->getMessage())
                            <br><span class="mono">{{ $exception->getMessage() }}</span>
                        @endif
                    </p>

                    <div class="status">
                        <span class="badge" title="HTTP status">503 Service Unavailable</span>
                        <span class="badge" title="Requested path"><span class="mono">/{{ request()->path() }}</span></span>
                        <span class="badge" title="Server time"><span class="mono">{{ now()->format('Y-m-d H:i:s') }}</span></span>
                        @if($refreshSeconds)
                            <span class="badge" title="Auto refresh interval"><span class="mono">Auto refresh: {{ $refreshSeconds }}s</span></span>
                        @endif
                    </div>

                    @if($refreshSeconds)
                        <div class="status" style="margin-top:12px;">
                            <span class="badge" title="Next retry" id="retryTimer"><span class="mono">Retrying in {{ $refreshSeconds }}s…</span></span>
                        </div>
                    @endif

                    <div class="btns">
                        <a class="btn btn-primary" href="{{ url('/') }}">Go to Home</a>
                        <a class="btn" href="javascript:location.reload()">Reload</a>
                    </div>
                </div>

                <div class="panel" aria-hidden="true">
                    <div class="panel-content">
                        <div class="gear-row">
                            <div class="gear"><div class="spinner"></div></div>
                            <div>
                                <div style="font-weight:700;">Applying updates</div>
                                <div class="mono">Optimizing services • Migrating configs • Warming cache</div>
                            </div>
                        </div>
                        <div class="progress" aria-hidden="true"><div class="bar"></div></div>
                        <div class="mono">
                            Tip: if you’re an admin, you can check logs for details.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($refreshSeconds)
    <script>
        (function(){
            var seconds = {{ (int) $refreshSeconds }};
            var el = document.getElementById('retryTimer');
            if(!el) return;

            function tick(){
                if(seconds < 0) return;
                el.innerHTML = '<span class="mono">Retrying in ' + seconds + 's…</span>';
                seconds -= 1;
                if(seconds >= 0){
                    setTimeout(tick, 1000);
                }
            }
            tick();
        })();
    </script>
    @endif
</body>
</html>
