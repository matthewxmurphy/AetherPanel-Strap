#!/usr/bin/env bash

aetherpanel_step_panel_files() {
  ensure_user_group
  ensure_dirs
  write_node_env
  write_branding_seed
  write_onboarding_seed
  write_panel_model_seed
  write_basic_auth
  sync_ui_tree
  write_lighttpd_config
}
