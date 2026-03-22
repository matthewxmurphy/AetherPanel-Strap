#!/usr/bin/env bash

aetherpanel_step_tailscale() {
  ensure_tailscale_connected
  detect_tailscale_ip
}
