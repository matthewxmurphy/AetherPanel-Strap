#!/usr/bin/env bash

aetherpanel_step_panel_files() {
  ensure_user_group
  ensure_operator_user
  append_authorized_keys
  ensure_dirs
  write_node_env
  write_join_seed
  write_branding_seed
  write_control_db_seed
  write_control_db_env_seed
  write_onboarding_seed
  write_panel_model_seed
  deploy_controller_runtime
  write_lighttpd_config
}
