#!/usr/bin/env bash
#
# One-time server setup: a virtual display for the scraper's headed browser.
#
# profixio's Cloudflare managed challenge (since 2026-09) is only passed by a
# *headed* Chromium, so the scraper needs an X display to open a window on.
# AlmaLinux/RHEL 10 ship no Xvfb any more; the replacement is Weston's headless
# backend with Xwayland, which provides a plain $DISPLAY for X11 clients.
#
# This installs Weston + Xwayland, registers a systemd service that keeps the
# virtual display running, and verifies it by obtaining a clearance with
# cf_clearance.py. Run as root from the project directory:
#
#     bash scripts/scraper/setup-display.sh
#
# Afterwards make sure .env has (defaults already match):
#     SCRAPER_DISPLAY=:0
#     SCRAPER_XDG_RUNTIME_DIR=/run/user/0
#
set -euo pipefail

SERVICE=scraper-display
RUNTIME_DIR=${SCRAPER_XDG_RUNTIME_DIR:-/run/user/$(id -u)}
LOG=/var/log/${SERVICE}.log
PROJECT_DIR=$(cd "$(dirname "$0")/../.." && pwd)
PYTHON="${PROJECT_DIR}/scripts/scraper/venv/bin/python3"
[ -x "$PYTHON" ] || PYTHON=python3

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this as root (it installs packages and a systemd service)." >&2
    exit 1
fi

say "Installing Weston (headless Wayland compositor) and Xwayland"
if command -v dnf >/dev/null; then
    dnf install -y epel-release >/dev/null 2>&1 || true
    dnf install -y weston xorg-x11-server-Xwayland
elif command -v apt-get >/dev/null; then
    apt-get install -y weston xwayland
else
    echo "Unsupported package manager — install weston + xwayland manually." >&2
    exit 1
fi

say "Writing /etc/systemd/system/${SERVICE}.service"
cat > /etc/systemd/system/${SERVICE}.service <<EOF
[Unit]
Description=Virtual display (Weston headless + Xwayland) for the iRacket scraper
After=network.target

[Service]
Type=simple
Environment=XDG_RUNTIME_DIR=${RUNTIME_DIR}
ExecStartPre=/bin/mkdir -p ${RUNTIME_DIR}
ExecStartPre=/bin/chmod 700 ${RUNTIME_DIR}
# Xwayland binds its socket in /tmp/.X11-unix, which never exists on a server
# without X (and /tmp may be wiped on reboot), so create it on every start.
ExecStartPre=/bin/mkdir -p /tmp/.X11-unix
ExecStartPre=/bin/chmod 1777 /tmp/.X11-unix
ExecStart=/usr/bin/weston --backend=headless --xwayland --socket=scraper --width=1366 --height=768 --idle-time=0 --log=${LOG}
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable ${SERVICE} >/dev/null 2>&1
systemctl restart ${SERVICE}     # (re)start — the script is safe to re-run
sleep 3

if ! systemctl is-active --quiet ${SERVICE}; then
    echo "Service failed to start. Log:" >&2
    tail -n 30 "$LOG" >&2 || journalctl -u ${SERVICE} -n 30 --no-pager >&2
    exit 1
fi
say "Service running. Weston log:"
tail -n 5 "$LOG" || true

# Xwayland starts lazily on the first X client; the display number shows up in
# the log afterwards. Assume :0 for the first client and read back what it got.
DISPLAY_NO=${SCRAPER_DISPLAY:-:0}

say "Verifying: obtaining a Cloudflare clearance on DISPLAY=${DISPLAY_NO}"
cd "$PROJECT_DIR"
if DISPLAY="$DISPLAY_NO" XDG_RUNTIME_DIR="$RUNTIME_DIR" "$PYTHON" scripts/scraper/cf_clearance.py --timeout 90 > /tmp/cf_clearance.json; then
    echo
    echo "Clearance obtained:"
    "$PYTHON" -c "import json; d=json.load(open('/tmp/cf_clearance.json')); print('  user-agent:', d['user_agent']); print('  cookies:   ', ', '.join(c['name'] for c in d['cookies'])); print('  cleared in:', d['cleared_in'], 's')"
    rm -f /tmp/cf_clearance.json
else
    echo
    echo "Clearance FAILED on DISPLAY=${DISPLAY_NO}." >&2
    echo "Check which display Xwayland is listening on:" >&2
    grep -i "xserver listening" "$LOG" >&2 || true
    echo "and set SCRAPER_DISPLAY in .env accordingly, then re-run:" >&2
    echo "    php artisan scraper:cf-clearance --refresh" >&2
    exit 1
fi

ACTUAL=$(grep -io "listening on display :[0-9]*" "$LOG" | tail -1 | grep -o ':[0-9]*' || true)
if [ -n "$ACTUAL" ] && [ "$ACTUAL" != "$DISPLAY_NO" ]; then
    echo "NOTE: Xwayland reports display ${ACTUAL} — set SCRAPER_DISPLAY=${ACTUAL} in .env"
fi

say "Done. The scraper can now run as usual, e.g.:"
echo "    php artisan scraper:cf-clearance --refresh    # check the clearance any time"
echo "    nohup php artisan scraper:start 2026-09 --no-backup --force --no-interaction > storage/logs/scrape-2026-09.log 2>&1 &"
