#!/usr/bin/env bash

aetherpanel_step_preflight() {
  require_root
  detect_os
  stage_support_tree
}
