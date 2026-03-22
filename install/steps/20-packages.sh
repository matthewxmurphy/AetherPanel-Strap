#!/usr/bin/env bash

aetherpanel_step_packages() {
  install_packages
  ensure_php_fpm_running
  detect_php_fpm_socket
}
