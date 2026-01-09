#!/bin/bash
#
# IPplan DNS Zone Sync - Linux Setup Script
# Run this script as root to install the cron job
#
# Usage:
#   ./dns-sync-setup.sh [options]
#
# Options:
#   --install      Install the cron job (default)
#   --uninstall    Remove the cron job
#   --status       Show current status
#   --run-now      Run the sync immediately
#   --interval N   Set interval in minutes (default: 15)
#   --php PATH     Path to PHP executable
#   --ipplan PATH  Path to IPplan installation
#   --help         Show this help message
#

set -e

# Default values
INTERVAL=15
PHP_PATH=""
IPPLAN_PATH=""
CRON_FILE="/etc/cron.d/ipplan-dns-sync"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

print_header() {
    echo ""
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN} IPplan DNS Zone Sync Setup${NC}"
    echo -e "${CYAN}========================================${NC}"
    echo ""
}

print_help() {
    cat << EOF
IPplan DNS Zone Sync - Linux Setup Script

Usage:
  $0 [options]

Options:
  --install      Install the cron job (default action)
  --uninstall    Remove the cron job
  --status       Show current status
  --run-now      Run the sync immediately
  --interval N   Set interval in minutes (default: 15)
  --php PATH     Path to PHP executable
  --ipplan PATH  Path to IPplan installation
  --help         Show this help message

Examples:
  # Install with defaults (auto-detect PHP and IPplan paths)
  sudo $0

  # Install with custom interval
  sudo $0 --interval 30

  # Install with explicit paths
  sudo $0 --php /usr/bin/php --ipplan /var/www/ipplan

  # Check status
  $0 --status

  # Remove installation
  sudo $0 --uninstall
EOF
}

find_php() {
    # Check common locations
    local php_paths=(
        "/usr/bin/php"
        "/usr/local/bin/php"
        "/opt/php/bin/php"
        "/opt/rh/php*/root/usr/bin/php"
    )

    # Try 'which' first
    if command -v php &> /dev/null; then
        echo "$(which php)"
        return 0
    fi

    # Search common paths
    for path in "${php_paths[@]}"; do
        if [[ -x "$path" ]]; then
            echo "$path"
            return 0
        fi
    done

    return 1
}

find_ipplan() {
    # Try to determine from script location
    local parent_dir="$(dirname "$SCRIPT_DIR")"

    if [[ -f "$parent_dir/config.php" ]]; then
        echo "$parent_dir"
        return 0
    fi

    # Check common web paths
    local web_paths=(
        "/var/www/html/ipplan"
        "/var/www/ipplan"
        "/srv/www/htdocs/ipplan"
        "/usr/share/ipplan"
    )

    for path in "${web_paths[@]}"; do
        if [[ -f "$path/config.php" ]]; then
            echo "$path"
            return 0
        fi
    done

    return 1
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}Error: This operation requires root privileges.${NC}"
        echo "Please run with: sudo $0 $@"
        exit 1
    fi
}

show_status() {
    echo "Checking DNS Zone Sync status..."
    echo ""

    # Check if cron file exists
    if [[ -f "$CRON_FILE" ]]; then
        echo -e "${GREEN}Cron job is installed:${NC} $CRON_FILE"
        echo ""
        echo "Current configuration:"
        cat "$CRON_FILE" | grep -v "^#" | grep -v "^$" | while read line; do
            echo "  $line"
        done
    else
        echo -e "${YELLOW}Cron job is not installed.${NC}"
    fi

    echo ""

    # Check for config file
    if [[ -n "$IPPLAN_PATH" ]] || IPPLAN_PATH=$(find_ipplan); then
        local config_file="$IPPLAN_PATH/data/dns-sync-config.json"
        if [[ -f "$config_file" ]]; then
            echo -e "${GREEN}Config file exists:${NC} $config_file"

            # Show basic stats from config
            if command -v jq &> /dev/null; then
                local enabled=$(jq -r '.enabled // false' "$config_file" 2>/dev/null)
                local zone_count=$(jq -r '.zones | length' "$config_file" 2>/dev/null || echo "0")
                local last_run=$(jq -r '.last_run // "never"' "$config_file" 2>/dev/null)

                echo "  Enabled: $enabled"
                echo "  Zones configured: $zone_count"
                echo "  Last run: $last_run"
            fi
        else
            echo -e "${YELLOW}Config file not found:${NC} $config_file"
            echo "  Run the sync script or configure via web interface to create it."
        fi
    else
        echo -e "${YELLOW}Could not locate IPplan installation.${NC}"
    fi

    echo ""
}

uninstall() {
    check_root

    echo "Uninstalling DNS Zone Sync cron job..."

    if [[ -f "$CRON_FILE" ]]; then
        rm -f "$CRON_FILE"
        echo -e "${GREEN}Cron job removed:${NC} $CRON_FILE"
    else
        echo -e "${YELLOW}Cron job was not installed.${NC}"
    fi

    echo ""
    echo "Note: Configuration file and logs have been preserved."
}

run_now() {
    echo "Running DNS Zone Sync..."
    echo ""

    # Find PHP
    if [[ -z "$PHP_PATH" ]]; then
        PHP_PATH=$(find_php) || {
            echo -e "${RED}Error: Could not find PHP executable.${NC}"
            exit 1
        }
    fi

    # Find IPplan
    if [[ -z "$IPPLAN_PATH" ]]; then
        IPPLAN_PATH=$(find_ipplan) || {
            echo -e "${RED}Error: Could not find IPplan installation.${NC}"
            exit 1
        }
    fi

    local script="$IPPLAN_PATH/contrib/dns-zone-sync.php"

    if [[ ! -f "$script" ]]; then
        echo -e "${RED}Error: Sync script not found: $script${NC}"
        exit 1
    fi

    "$PHP_PATH" "$script" -v
}

install() {
    check_root

    echo "Installing DNS Zone Sync cron job..."
    echo ""

    # Find PHP
    if [[ -z "$PHP_PATH" ]]; then
        echo "Searching for PHP executable..."
        PHP_PATH=$(find_php) || {
            echo -e "${RED}Error: Could not find PHP executable.${NC}"
            echo "Please specify using --php parameter"
            exit 1
        }
    fi

    if [[ ! -x "$PHP_PATH" ]]; then
        echo -e "${RED}Error: PHP not found at: $PHP_PATH${NC}"
        exit 1
    fi
    echo -e "${GREEN}Found PHP:${NC} $PHP_PATH"

    # Find IPplan
    if [[ -z "$IPPLAN_PATH" ]]; then
        echo "Searching for IPplan installation..."
        IPPLAN_PATH=$(find_ipplan) || {
            echo -e "${RED}Error: Could not find IPplan installation.${NC}"
            echo "Please specify using --ipplan parameter"
            exit 1
        }
    fi

    if [[ ! -f "$IPPLAN_PATH/config.php" ]]; then
        echo -e "${RED}Error: IPplan not found at: $IPPLAN_PATH${NC}"
        exit 1
    fi
    echo -e "${GREEN}Found IPplan:${NC} $IPPLAN_PATH"

    local script="$IPPLAN_PATH/contrib/dns-zone-sync.php"

    if [[ ! -f "$script" ]]; then
        echo -e "${RED}Error: Sync script not found: $script${NC}"
        exit 1
    fi

    # Ensure data directory exists
    local data_dir="$IPPLAN_PATH/data"
    if [[ ! -d "$data_dir" ]]; then
        echo "Creating data directory: $data_dir"
        mkdir -p "$data_dir"
        # Try to set ownership to web server user
        if id www-data &>/dev/null; then
            chown www-data:www-data "$data_dir"
        elif id apache &>/dev/null; then
            chown apache:apache "$data_dir"
        elif id nginx &>/dev/null; then
            chown nginx:nginx "$data_dir"
        fi
        chmod 755 "$data_dir"
    fi

    # Calculate cron schedule
    # For intervals that divide 60 evenly, use */N syntax
    # Otherwise, use specific minutes
    local cron_schedule
    if (( 60 % INTERVAL == 0 )); then
        cron_schedule="*/$INTERVAL * * * *"
    else
        # Generate list of minutes
        local minutes=""
        for (( m=0; m<60; m+=INTERVAL )); do
            if [[ -n "$minutes" ]]; then
                minutes="$minutes,"
            fi
            minutes="$minutes$m"
        done
        cron_schedule="$minutes * * * *"
    fi

    # Create cron file
    echo "Creating cron job..."
    cat > "$CRON_FILE" << EOF
# IPplan DNS Zone Sync
# Automatically synchronizes DNS zones from configured DNS servers
# Created: $(date)
#
# This file is managed by dns-sync-setup.sh
# To modify, run: $0 --uninstall && $0 --install --interval N
#
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

$cron_schedule root $PHP_PATH $script -q 2>&1 | logger -t ipplan-dns-sync
EOF

    chmod 644 "$CRON_FILE"

    echo ""
    echo -e "${GREEN}Cron job installed successfully!${NC}"
    echo ""
    echo "Configuration:"
    echo "  Cron file: $CRON_FILE"
    echo "  Schedule: Every $INTERVAL minutes"
    echo "  PHP: $PHP_PATH"
    echo "  Script: $script"
    echo ""
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "  1. Configure zones in IPplan web interface or"
    echo "     edit: $data_dir/dns-sync-config.json"
    echo "  2. View status: $0 --status"
    echo "  3. Run immediately: $0 --run-now"
    echo ""
    echo "Logs are written to syslog (typically /var/log/syslog or /var/log/messages)"
    echo "View with: grep 'ipplan-dns-sync' /var/log/syslog"
    echo ""
}

# Parse arguments
ACTION="install"

while [[ $# -gt 0 ]]; do
    case $1 in
        --install)
            ACTION="install"
            shift
            ;;
        --uninstall)
            ACTION="uninstall"
            shift
            ;;
        --status)
            ACTION="status"
            shift
            ;;
        --run-now)
            ACTION="run-now"
            shift
            ;;
        --interval)
            INTERVAL="$2"
            shift 2
            ;;
        --php)
            PHP_PATH="$2"
            shift 2
            ;;
        --ipplan)
            IPPLAN_PATH="$2"
            shift 2
            ;;
        --help|-h)
            print_help
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Execute action
print_header

case $ACTION in
    install)
        install
        ;;
    uninstall)
        uninstall
        ;;
    status)
        show_status
        ;;
    run-now)
        run_now
        ;;
esac
