#!/usr/bin/env bash

aetherpanel_step_services() {
  configure_fail2ban
  configure_crowdsec_local
  enable_services
}
